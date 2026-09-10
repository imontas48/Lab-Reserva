<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    public function test_solicitar_el_enlace_envia_la_notificacion_y_no_revela_si_el_correo_existe(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson('/api/v1/forgot-password', ['email' => $user->email])->assertOk();
        $this->postJson('/api/v1/forgot-password', ['email' => 'nadie@lab-reserva.test'])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use ($user) {
            $url = $n->toMail($user)->actionUrl;

            return str_contains($url, '/reset-password?') && str_contains($url, 'token=');
        });
        Notification::assertCount(1);
    }

    public function test_el_token_restablece_la_contraseña_y_cierra_las_sesiones(): void
    {
        $user = User::factory()->create(['password' => 'antigua123']);
        $user->createToken('sesion')->plainTextToken;
        $token = Password::broker()->createToken($user);

        $this->postJson('/api/v1/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nueva-segura-123',
            'password_confirmation' => 'nueva-segura-123',
        ])->assertOk();

        $this->assertTrue(Hash::check('nueva-segura-123', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());

        // El token es de un solo uso.
        $this->postJson('/api/v1/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'otra-segura-123',
            'password_confirmation' => 'otra-segura-123',
        ])->assertStatus(422);
    }

    public function test_un_token_invalido_se_rechaza(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/reset-password', [
            'token' => 'falso',
            'email' => $user->email,
            'password' => 'nueva-segura-123',
            'password_confirmation' => 'nueva-segura-123',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }
}
