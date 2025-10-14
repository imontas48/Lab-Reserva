<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas públicas (sin autenticación)
Route::prefix('v1')->group(function () {
    // Autenticación
    Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login'])->name('api.login');
    Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register'])->name('api.register');
});

// Rutas protegidas (requieren autenticación)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Autenticación
    Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout'])->name('api.logout');
    Route::get('/me', [App\Http\Controllers\Api\AuthController::class, 'me'])->name('api.me');

    // Laboratorios
    Route::apiResource('labs', App\Http\Controllers\Api\LabController::class);

    // Equipos de un laboratorio específico (ruta anidada debe ir ANTES del resource)
    Route::get('/labs/{lab}/equipment', [App\Http\Controllers\Api\EquipmentController::class, 'indexByLab'])
        ->name('api.labs.equipment.index');

    // Equipos (CRUD completo)
    Route::apiResource('equipment', App\Http\Controllers\Api\EquipmentController::class);

    // Software
    Route::apiResource('software', App\Http\Controllers\Api\SoftwareController::class);

    // Reservas - Rutas personalizadas (deben ir ANTES del resource)
    Route::get('/my-reservations', [App\Http\Controllers\Api\ReservationController::class, 'indexForUser'])
        ->name('api.reservations.my');
    Route::get('/equipment/{equipment}/reservations', [App\Http\Controllers\Api\ReservationController::class, 'indexForEquipment'])
        ->name('api.equipment.reservations');
    Route::patch('/reservations/{reservation}/cancel', [App\Http\Controllers\Api\ReservationController::class, 'cancel'])
        ->name('api.reservations.cancel');

    // Reservas (CRUD completo)
    Route::apiResource('reservations', App\Http\Controllers\Api\ReservationController::class);
});
