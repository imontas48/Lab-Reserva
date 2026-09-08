<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\GroupRoleAssignmentController;
use App\Http\Controllers\Api\LabController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PermissionOverrideController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SoftwareController;
use App\Http\Controllers\Api\UserRoleController;
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
//
// Límite estricto y por separado del throttle general de la API: son los dos
// únicos endpoints sin autenticar, y son el objetivo natural de la fuerza bruta
// de credenciales y del alta masiva de cuentas.
Route::prefix('v1')->middleware('throttle:5,1')->group(function () {
    // Autenticación
    Route::post('/login', [AuthController::class, 'login'])->name('api.login');
    Route::post('/register', [AuthController::class, 'register'])->name('api.register');
});

// Rutas protegidas (requieren autenticación)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Autenticación
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('api.dashboard.stats');

    // Laboratorios
    Route::apiResource('labs', LabController::class);

    // Equipos de un laboratorio específico (ruta anidada debe ir ANTES del resource)
    Route::get('/labs/{lab}/equipment', [EquipmentController::class, 'indexByLab'])
        ->name('api.labs.equipment.index');

    // Equipos (CRUD completo)
    Route::apiResource('equipment', EquipmentController::class);

    // Software
    Route::apiResource('software', SoftwareController::class);

    // Reservas - Rutas personalizadas (deben ir ANTES del resource)
    Route::get('/my-reservations', [ReservationController::class, 'indexForUser'])
        ->name('api.reservations.my');
    // La validacion del rol vive en la restriccion de ruta y no en el
    // controlador: un rol inexistente es una ruta que no existe.
    Route::get('/reservations/by-role/{role}', [ReservationController::class, 'indexByRole'])
        ->whereIn('role', ['student', 'teacher'])
        ->name('api.reservations.by-role');
    Route::get('/equipment/{equipment}/reservations', [ReservationController::class, 'indexForEquipment'])
        ->name('api.equipment.reservations');
    Route::patch('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])
        ->name('api.reservations.cancel');

    // Reservas (CRUD completo)
    Route::apiResource('reservations', ReservationController::class);

    // =========================================================================
    // MÓDULO: GESTIÓN DE ROLES Y PERMISOS
    // =========================================================================

    // Roles (CRUD + sync de permisos)
    // La ruta de sync debe declararse ANTES del apiResource para evitar conflicto con {role}
    Route::post('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
        ->name('api.roles.permissions.sync');
    Route::apiResource('roles', RoleController::class)
        ->names([
            'index' => 'api.roles.index',
            'store' => 'api.roles.store',
            'show' => 'api.roles.show',
            'update' => 'api.roles.update',
            'destroy' => 'api.roles.destroy',
        ]);

    // Permisos (catálogo + permisos efectivos de un usuario)
    Route::get('/users/{user}/effective-permissions', [PermissionController::class, 'effectivePermissions'])
        ->name('api.users.permissions.effective');
    Route::apiResource('permissions', PermissionController::class)
        ->names([
            'index' => 'api.permissions.index',
            'store' => 'api.permissions.store',
            'show' => 'api.permissions.show',
            'update' => 'api.permissions.update',
            'destroy' => 'api.permissions.destroy',
        ]);

    // Roles individuales por usuario
    Route::prefix('users/{user}/roles')->name('api.users.roles.')->group(function () {
        Route::get('/', [UserRoleController::class, 'index'])
            ->name('index');
        Route::post('/', [UserRoleController::class, 'store'])
            ->name('store');
        Route::patch('/{userRole}', [UserRoleController::class, 'update'])
            ->name('update');
        Route::delete('/{userRole}', [UserRoleController::class, 'destroy'])
            ->name('destroy');
    });

    // Reglas de asignación por grupo
    Route::patch('/group-role-assignments/{groupRoleAssignment}/toggle', [GroupRoleAssignmentController::class, 'toggle'])
        ->name('api.group-role-assignments.toggle');
    Route::apiResource('group-role-assignments', GroupRoleAssignmentController::class)
        ->except(['update'])
        ->names([
            'index' => 'api.group-role-assignments.index',
            'store' => 'api.group-role-assignments.store',
            'show' => 'api.group-role-assignments.show',
            'destroy' => 'api.group-role-assignments.destroy',
        ]);

    // Sobreescrituras de permisos por usuario
    Route::prefix('users/{user}/permission-overrides')->name('api.users.permission-overrides.')->group(function () {
        Route::get('/', [PermissionOverrideController::class, 'index'])
            ->name('index');
        Route::post('/', [PermissionOverrideController::class, 'store'])
            ->name('store');
        Route::get('/{permissionOverride}', [PermissionOverrideController::class, 'show'])
            ->name('show');
        Route::patch('/{permissionOverride}', [PermissionOverrideController::class, 'update'])
            ->name('update');
        Route::delete('/{permissionOverride}', [PermissionOverrideController::class, 'destroy'])
            ->name('destroy');
    });
});
