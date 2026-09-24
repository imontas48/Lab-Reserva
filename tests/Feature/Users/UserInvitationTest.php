<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Alta de usuarios por un administrador con contrasena temporal.
 */
class UserInvitationTest extends TestCase
{
    public function test_el_administrador_da_de_alta_un_profesor_con_contrasena_generada(): void
    {
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Laura Docente',
            'email' => 'laura@lab-reserva.test',
            'role' => 'teacher',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'laura@lab-reserva.test')
            ->assertJsonPath('data.role', 'teacher')
            ->assertJsonPath('data.must_change_password', true)
            ->assertJsonMissingPath('data.temporary_password');

        $temporary = $response->json('temporary_password');
        $this->assertIsString($temporary);
        $this->assertGreaterThanOrEqual(8, strlen($temporary));

        $user = User::where('email', 'laura@lab-reserva.test')->firstOrFail();
        $this->assertTrue($user->mustChangePassword());
        $this->assertTrue(Hash::check($temporary, $user->password));
    }

    public function test_el_administrador_puede_fijar_la_contrasena_temporal(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/users', [
            'name' => 'Pedro Alumno',
            'email' => 'pedro@lab-reserva.test',
            'role' => 'student',
            'password' => 'Temporal2026',
        ])
            ->assertCreated()
            ->assertJsonPath('temporary_password', 'Temporal2026');

        $user = User::where('email', 'pedro@lab-reserva.test')->firstOrFail();
        $this->assertTrue(Hash::check('Temporal2026', $user->password));
    }

    public function test_el_administrador_puede_dar_de_alta_a_otro_administrador(): void
    {
        $this->actingAsAdmin();

        $this->postJson('/api/v1/users', [
            'name' => 'Segundo Admin',
            'email' => 'admin2@lab-reserva.test',
            'role' => 'admin',
        ])
            ->assertCreated()
            ->assertJsonPath('data.role', 'admin')
            ->assertJsonPath('data.must_change_password', true);
    }

    public function test_el_alta_valida_correo_unico_rol_y_longitud_de_contrasena(): void
    {
        User::factory()->create(['email' => 'ocupado@lab-reserva.test']);
        $this->actingAsAdmin();

        $this->postJson('/api/v1/users', [
            'name' => 'X',
            'email' => 'ocupado@lab-reserva.test',
            'role' => 'superuser',
            'password' => 'corta',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'role', 'password']);
    }

    public function test_solo_quien_tiene_users_create_puede_dar_de_alta(): void
    {
        $payload = ['name' => 'Nadie', 'email' => 'nadie@lab-reserva.test', 'role' => 'student'];

        $this->actingAsTeacher();
        $this->postJson('/api/v1/users', $payload)->assertForbidden();

        $this->actingAsStudent();
        $this->postJson('/api/v1/users', $payload)->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'nadie@lab-reserva.test']);
    }

    public function test_el_registro_publico_no_marca_contrasena_temporal(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Auto Registrado',
            'email' => 'auto@lab-reserva.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertCreated()
            ->assertJsonPath('user.must_change_password', false);
    }

    public function test_el_comando_de_consola_usa_el_mismo_camino(): void
    {
        $this->artisan('lab:invite-user', [
            'email' => 'consola@lab-reserva.test',
            '--name' => 'Desde Consola',
            '--role' => 'admin',
        ])
            ->expectsOutputToContain('Contrasena temporal:')
            ->assertSuccessful();

        $user = User::where('email', 'consola@lab-reserva.test')->firstOrFail();
        $this->assertSame('admin', $user->role);
        $this->assertTrue($user->mustChangePassword());
    }
}
