<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * bootstrap/app.php nunca llamaba a throttleApi(), y en Laravel 12 el grupo api
 * no aplica ningun limite si no se declara. /login quedaba abierto a fuerza
 * bruta ilimitada contra Hash::check con 12 rondas de bcrypt, que ademas lo
 * convertia en un vector de agotamiento de CPU trivial.
 */
class RateLimitTest extends TestCase
{
    public function test_el_login_se_bloquea_tras_cinco_intentos_fallidos(): void
    {
        User::factory()->create([
            'email' => 'victima@lab-reserva.test',
            'password' => Hash::make('la-buena'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', [
                'email' => 'victima@lab-reserva.test',
                'password' => "intento-{$i}",
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/login', [
            'email' => 'victima@lab-reserva.test',
            'password' => 'intento-6',
        ])->assertStatus(429);
    }

    public function test_el_bloqueo_alcanza_tambien_al_intento_con_la_contrasena_correcta(): void
    {
        User::factory()->create([
            'email' => 'victima@lab-reserva.test',
            'password' => Hash::make('la-buena'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', [
                'email' => 'victima@lab-reserva.test',
                'password' => "intento-{$i}",
            ]);
        }

        // Es el comportamiento correcto: el limitador cuenta peticiones, no
        // aciertos. Si dejase pasar la correcta, no frenaria la fuerza bruta.
        $this->postJson('/api/v1/login', [
            'email' => 'victima@lab-reserva.test',
            'password' => 'la-buena',
        ])->assertStatus(429);
    }

    public function test_el_registro_masivo_tambien_esta_limitado(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/register', [
                'name' => "Bot {$i}",
                'email' => "bot{$i}@lab-reserva.test",
                'password' => 'contrasena-larga',
                'password_confirmation' => 'contrasena-larga',
            ])->assertCreated();
        }

        $this->postJson('/api/v1/register', [
            'name' => 'Bot 6',
            'email' => 'bot6@lab-reserva.test',
            'password' => 'contrasena-larga',
            'password_confirmation' => 'contrasena-larga',
        ])->assertStatus(429);

        $this->assertDatabaseMissing('users', ['email' => 'bot6@lab-reserva.test']);
    }
}
