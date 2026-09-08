<?php

use Illuminate\Support\Facades\Route;

/**
 * Ruta principal de la aplicación.
 * Todas las rutas de la SPA serán manejadas por Vue Router.
 *
 * El comodín excluye el prefijo api/: sin esa exclusión, cualquier endpoint de
 * API inexistente caía aquí y devolvía el HTML de la SPA con un 200. El cliente
 * recibía una página en vez de un error, y el renderer de 404 de
 * bootstrap/app.php nunca llegaba a ejecutarse. Afectaba también a las rutas
 * con restricción de parámetro: /api/v1/reservations/by-role/loquesea
 * respondía 200 en lugar de 404.
 */
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api(?:/|$)).*$')->name('spa');
