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

    // Dashboard
    Route::get('/dashboard/stats', [App\Http\Controllers\Api\DashboardController::class, 'stats'])->name('api.dashboard.stats');

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
    Route::get('/reservations/by-role/{role}', [App\Http\Controllers\Api\ReservationController::class, 'indexByRole'])
        ->name('api.reservations.by-role');
    Route::get('/equipment/{equipment}/reservations', [App\Http\Controllers\Api\ReservationController::class, 'indexForEquipment'])
        ->name('api.equipment.reservations');
    Route::patch('/reservations/{reservation}/cancel', [App\Http\Controllers\Api\ReservationController::class, 'cancel'])
        ->name('api.reservations.cancel');

    // Reservas (CRUD completo)
    Route::apiResource('reservations', App\Http\Controllers\Api\ReservationController::class);

    // =========================================================================
    // MÓDULO: GESTIÓN DE ROLES Y PERMISOS
    // =========================================================================

    // Roles (CRUD + sync de permisos)
    // La ruta de sync debe declararse ANTES del apiResource para evitar conflicto con {role}
    Route::post('/roles/{role}/permissions', [App\Http\Controllers\Api\RoleController::class, 'syncPermissions'])
        ->name('api.roles.permissions.sync');
    Route::apiResource('roles', App\Http\Controllers\Api\RoleController::class)
        ->names([
            'index'   => 'api.roles.index',
            'store'   => 'api.roles.store',
            'show'    => 'api.roles.show',
            'update'  => 'api.roles.update',
            'destroy' => 'api.roles.destroy',
        ]);

    // Permisos (catálogo + permisos efectivos de un usuario)
    Route::get('/users/{user}/effective-permissions', [App\Http\Controllers\Api\PermissionController::class, 'effectivePermissions'])
        ->name('api.users.permissions.effective');
    Route::apiResource('permissions', App\Http\Controllers\Api\PermissionController::class)
        ->names([
            'index'   => 'api.permissions.index',
            'store'   => 'api.permissions.store',
            'show'    => 'api.permissions.show',
            'update'  => 'api.permissions.update',
            'destroy' => 'api.permissions.destroy',
        ]);

    // Roles individuales por usuario
    Route::prefix('users/{user}/roles')->name('api.users.roles.')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\UserRoleController::class, 'index'])
            ->name('index');
        Route::post('/', [App\Http\Controllers\Api\UserRoleController::class, 'store'])
            ->name('store');
        Route::patch('/{userRole}', [App\Http\Controllers\Api\UserRoleController::class, 'update'])
            ->name('update');
        Route::delete('/{userRole}', [App\Http\Controllers\Api\UserRoleController::class, 'destroy'])
            ->name('destroy');
    });

    // Reglas de asignación por grupo
    Route::patch('/group-role-assignments/{groupRoleAssignment}/toggle', [App\Http\Controllers\Api\GroupRoleAssignmentController::class, 'toggle'])
        ->name('api.group-role-assignments.toggle');
    Route::apiResource('group-role-assignments', App\Http\Controllers\Api\GroupRoleAssignmentController::class)
        ->except(['update'])
        ->names([
            'index'   => 'api.group-role-assignments.index',
            'store'   => 'api.group-role-assignments.store',
            'show'    => 'api.group-role-assignments.show',
            'destroy' => 'api.group-role-assignments.destroy',
        ]);

    // Sobreescrituras de permisos por usuario
    Route::prefix('users/{user}/permission-overrides')->name('api.users.permission-overrides.')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\PermissionOverrideController::class, 'index'])
            ->name('index');
        Route::post('/', [App\Http\Controllers\Api\PermissionOverrideController::class, 'store'])
            ->name('store');
        Route::get('/{permissionOverride}', [App\Http\Controllers\Api\PermissionOverrideController::class, 'show'])
            ->name('show');
        Route::patch('/{permissionOverride}', [App\Http\Controllers\Api\PermissionOverrideController::class, 'update'])
            ->name('update');
        Route::delete('/{permissionOverride}', [App\Http\Controllers\Api\PermissionOverrideController::class, 'destroy'])
            ->name('destroy');
    });
});
