<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    public function test_el_usuario_edita_su_nombre_y_correo(): void
    {
        $user = $this->actingAsStudent();
        User::factory()->create(['email' => 'ocupado@lab-reserva.test']);

        $this->patchJson('/api/v1/profile', ['name' => 'Nuevo Nombre', 'email' => 'nuevo@lab-reserva.test'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nuevo Nombre')
            ->assertJsonPath('data.email', 'nuevo@lab-reserva.test');

        $this->patchJson('/api/v1/profile', ['email' => 'ocupado@lab-reserva.test'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertSame('nuevo@lab-reserva.test', $user->fresh()->email);
    }

    public function test_el_perfil_no_permite_cambiar_el_rol(): void
    {
        $user = $this->actingAsStudent();

        $this->patchJson('/api/v1/profile', ['name' => 'Yo', 'role' => 'admin'])->assertOk();

        $this->assertSame('student', $user->fresh()->role);
    }

    public function test_cambiar_la_contraseña_exige_la_actual_y_cierra_las_otras_sesiones(): void
    {
        $user = User::factory()->create(['password' => 'antigua123']);
        $otraSesion = $user->createToken('otro-dispositivo')->plainTextToken;
        $actual = $user->createToken('este-dispositivo')->plainTextToken;

        $this->withToken($actual)->putJson('/api/v1/profile/password', [
            'current_password' => 'incorrecta',
            'password' => 'nueva-segura-123',
            'password_confirmation' => 'nueva-segura-123',
        ])->assertStatus(422)->assertJsonValidationErrors(['current_password']);

        $this->withToken($actual)->putJson('/api/v1/profile/password', [
            'current_password' => 'antigua123',
            'password' => 'nueva-segura-123',
            'password_confirmation' => 'nueva-segura-123',
        ])->assertOk();

        $this->assertTrue(Hash::check('nueva-segura-123', $user->fresh()->password));

        // La sesion que hizo el cambio sigue viva; la otra, no. El guard
        // conserva el usuario resuelto entre peticiones del mismo test, asi
        // que se olvida a mano antes de cada comprobacion.
        app('auth')->forgetGuards();
        $this->withToken($actual)->getJson('/api/v1/me')->assertOk();
        app('auth')->forgetGuards();
        $this->withToken($otraSesion)->getJson('/api/v1/me')->assertUnauthorized();
    }
}
