<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

/**
 * Recuperacion de contraseña con el broker estandar de Laravel. La URL del
 * enlace apunta a la SPA (ver AppServiceProvider).
 */
class PasswordResetController extends Controller
{
    /**
     * POST /api/v1/forgot-password
     *
     * Responde igual exista o no la cuenta: no revela que correos estan
     * registrados.
     */
    public function sendResetLink(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'Si el correo está registrado, recibirás un enlace para restablecer la contraseña.',
        ]);
    }

    /**
     * POST /api/v1/reset-password
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                // Sesiones anteriores fuera: quien pidio el enlace quiere
                // recuperar el control de la cuenta.
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => 'Contraseña restablecida. Ya puedes iniciar sesión.']);
    }
}
