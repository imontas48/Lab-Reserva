<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Tests\TestCase;

/**
 * POST /api/v1/register es publico y aceptaba el campo role, de modo que
 * cualquiera podia registrarse como admin. Como isAdmin() gobierna todas las
 * decisiones de autorizacion, eso equivalia a una toma de control remota sin
 * autenticar.
 */
class PrivilegeEscalationTest extends TestCase
{
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Alguien',
            'email' => 'alguien@lab-reserva.test',
            'password' => 'contrasena-larga',
            'password_confirmation' => 'contrasena-larga',
        ], $overrides);
    }

    public function test_el_registro_ignora_el_rol_enviado_por_el_cliente(): void
    {
        $this->postJson('/api/v1/register', $this->payload(['role' => 'admin']))
            ->assertCreated();

        $user = User::where('email', 'alguien@lab-reserva.test')->firstOrFail();

        $this->assertSame('student', $user->role);
        $this->assertFalse($user->isAdmin());
    }

    public function test_tampoco_permite_autoconcederse_el_rol_de_profesor(): void
    {
        $this->postJson('/api/v1/register', $this->payload(['role' => 'teacher']))
            ->assertCreated();

        $this->assertSame('student', User::where('email', 'alguien@lab-reserva.test')->value('role'));
    }

    public function test_el_registro_normal_sigue_funcionando(): void
    {
        $response = $this->postJson('/api/v1/register', $this->payload());

        $response->assertCreated()->assertJsonStructure(['token', 'user']);
        $this->assertDatabaseHas('users', [
            'email' => 'alguien@lab-reserva.test',
            'role' => 'student',
        ]);
    }

    public function test_el_comando_de_consola_promueve_a_administrador(): void
    {
        $user = User::factory()->create(['email' => 'profe@lab-reserva.test']);

        $this->artisan('lab:make-admin', ['email' => 'profe@lab-reserva.test'])
            ->assertSuccessful();

        $this->assertSame('admin', $user->fresh()->role);
    }

    public function test_el_comando_falla_si_el_usuario_no_existe_y_no_se_da_nombre(): void
    {
        $this->artisan('lab:make-admin', ['email' => 'nadie@lab-reserva.test'])
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'nadie@lab-reserva.test']);
    }
}
