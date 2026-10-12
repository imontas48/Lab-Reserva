<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthUserResource;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activity
    ) {}

    /**
     * Login user and create token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            // Se guarda el correo intentado, nunca la contrasena.
            $this->activity->record('login_failed', $user, 'Intento fallido de inicio de sesión', [
                'email' => $request->email,
            ]);

            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $this->activity->record('login', $user, 'Inició sesión', actor: $user);

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'user' => (new AuthUserResource($user))->resolve(),
            'token' => $token,
        ]);
    }

    /**
     * Register new user.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // El rol NO se acepta desde la peticion. Esta ruta es publica, asi que
        // permitir 'role' dejaba que cualquiera se registrase como admin y, con
        // ello, tomase el control: isAdmin() gobierna toda la autorizacion.
        // Elevar a teacher o admin es una accion deliberada de un administrador
        // (comando lab:make-admin) o del seeder de arranque.
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student',
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'message' => 'Usuario registrado exitosamente',
            'user' => (new AuthUserResource($user))->resolve(),
            'token' => $token,
        ], 201);
    }

    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        $this->activity->record('logout', $request->user(), 'Cerró sesión');

        return response()->json([
            'message' => 'Sesión cerrada exitosamente',
        ]);
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): AuthUserResource
    {
        return new AuthUserResource($request->user());
    }
}
