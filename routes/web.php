<?php

use Illuminate\Support\Facades\Route;

/**
 * Ruta principal de la aplicación
 * Todas las rutas de la SPA serán manejadas por Vue Router
 */
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
