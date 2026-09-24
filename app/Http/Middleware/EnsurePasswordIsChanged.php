<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea la API a quien todavia usa una contrasena temporal.
 *
 * El aviso en la interfaz no basta: el token Bearer es valido y cualquiera
 * podria llamar a la API directamente saltandose la pantalla de cambio. Por
 * eso la regla vive aqui. Solo quedan abiertas las rutas que hacen falta para
 * salir de ese estado: consultar quien soy, cambiar la contrasena y cerrar
 * sesion.
 */
class EnsurePasswordIsChanged
{
    public const ERROR_CODE = 'password_change_required';

    /**
     * @var list<string>
     */
    private const ALLOWED_ROUTES = [
        'api.me',
        'api.logout',
        'api.profile.password',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->mustChangePassword()) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Debes cambiar tu contraseña temporal antes de continuar.',
            'code' => self::ERROR_CODE,
        ], 403);
    }
}
