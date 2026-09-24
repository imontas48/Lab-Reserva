<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Quien entra con una contrasena temporal solo puede cambiarla.
 */
class MustChangePasswordTest extends TestCase
{
    private function invitedUser(string $role = 'student'): User
    {
        $user = User::factory()->state(['role' => $role])->create([
            'password' => Hash::make('Temporal2026'),
        ]);
        $user->forceFill(['must_change_password' => true])->save();

        return $user;
    }

    public function test_el_login_informa_de_que_la_contrasena_es_temporal(): void
    {
        $user = $this->invitedUser();

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'Temporal2026'])
            ->assertOk()
            ->assertJsonPath('user.must_change_password', true);
    }

    public function test_la_api_queda_bloqueada_salvo_me_logout_y_cambio_de_contrasena(): void
    {
        $user = $this->invitedUser('admin');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/labs')
            ->assertForbidden()
            ->assertJsonPath('code', EnsurePasswordIsChanged::ERROR_CODE);

        $this->getJson('/api/v1/users')->assertForbidden();
        $this->patchJson('/api/v1/profile', ['name' => 'Otro'])->assertForbidden();

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.must_change_password', true);
    }

    public function test_cambiar_la_contrasena_levanta_el_bloqueo(): void
    {
        $user = $this->invitedUser();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile/password', [
            'current_password' => 'Temporal2026',
            'password' => 'Definitiva2026',
            'password_confirmation' => 'Definitiva2026',
        ])->assertOk();

        $this->assertFalse($user->fresh()->mustChangePassword());
        $this->getJson('/api/v1/labs')->assertOk();
    }

    public function test_la_nueva_contrasena_no_puede_ser_la_temporal(): void
    {
        $user = $this->invitedUser();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile/password', [
            'current_password' => 'Temporal2026',
            'password' => 'Temporal2026',
            'password_confirmation' => 'Temporal2026',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        $this->assertTrue($user->fresh()->mustChangePassword());
    }

    public function test_un_usuario_normal_no_se_ve_afectado(): void
    {
        $this->actingAsStudent();

        $this->getJson('/api/v1/labs')->assertOk();
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.must_change_password', false);
    }
}
