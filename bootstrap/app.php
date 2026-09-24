<?php

use App\Exceptions\BusinessRuleException;
use App\Http\Middleware\EnsurePasswordIsChanged;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Configurar middleware de API para aceptar JSON por defecto
        $middleware->api(prepend: [
            HandleCors::class,
        ]);

        // Sin esto el grupo api no aplica ningun throttle: en Laravel 12 el
        // limitador solo se activa si se declara explicitamente. La API estaba
        // abierta a fuerza bruta contra /login, que ademas ejecuta Hash::check
        // con 12 rondas de bcrypt y por tanto servia como vector de DoS.
        $middleware->throttleApi();

        // Se aplica en routes/api.php al grupo autenticado, no globalmente:
        // las rutas publicas no tienen usuario y no hay nada que comprobar.
        $middleware->alias([
            'password.changed' => EnsurePasswordIsChanged::class,
        ]);

        // Detras de un proxy inverso (nginx del host -> contenedor) la peticion
        // llega con la IP del proxy y en http plano. Sin confiar en el proxy,
        // el throttle agruparia a todos los usuarios bajo una sola IP y las URL
        // (reset de contrasena, assets) saldrian en http. Se lee de entorno para
        // que en local, sin proxy, nadie pueda falsear X-Forwarded-*.
        // Se confia ademas en X-Forwarded-Prefix: cuando el proxy monta la app
        // bajo un subdirectorio (/lab-reserva) y recorta ese prefijo antes de
        // reenviar, Laravel lo recupera de esa cabecera y url(), asset() y las
        // redirecciones vuelven a salir con el prefijo puesto.
        $trustedProxies = env('TRUSTED_PROXIES');
        if (is_string($trustedProxies) && $trustedProxies !== '') {
            $middleware->trustProxies(
                at: $trustedProxies === '*' ? '*' : array_map('trim', explode(',', $trustedProxies)),
                headers: Request::HEADER_X_FORWARDED_FOR
                    | Request::HEADER_X_FORWARDED_HOST
                    | Request::HEADER_X_FORWARDED_PORT
                    | Request::HEADER_X_FORWARDED_PROTO
                    | Request::HEADER_X_FORWARDED_PREFIX
            );
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // El bloque estaba vacio, de modo que toda excepcion de negocio salia
        // como 500. Se fija aqui el contrato HTTP de la API.

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (BusinessRuleException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return null;
        });

        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Esta accion no esta autorizada.',
                ], 403);
            }

            return null;
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'No autenticado.',
                ], 401);
            }

            return null;
        });

        // ModelNotFoundException llega como 404 por el binding de rutas, pero
        // findOrFail dentro de un servicio la propaga tal cual: sin esto seria
        // un 500. Nunca se revela el nombre del modelo.
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Recurso no encontrado.',
                ], 404);
            }

            return null;
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Recurso no encontrado.',
                ], 404);
            }

            return null;
        });
    })->create();
