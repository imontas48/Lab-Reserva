<?php

use App\Http\Controllers\Api\AcademicPeriodController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\GroupRoleAssignmentController;
use App\Http\Controllers\Api\IncidentController;
use App\Http\Controllers\Api\LabClosureController;
use App\Http\Controllers\Api\LabController;
use App\Http\Controllers\Api\LabLayoutController;
use App\Http\Controllers\Api\LabScheduleController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\PermissionOverrideController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SoftwareController;
use App\Http\Controllers\Api\UserController;
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
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('api.password.email');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('api.password.reset');
});

// Rutas protegidas (requieren autenticación)
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // Autenticación
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('api.profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('api.profile.password');

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
    Route::get('/labs/{lab}/reservations', [ReservationController::class, 'indexForLab'])
        ->name('api.labs.reservations');

    // Incidencias de equipos y plano del laboratorio
    Route::get('/equipment/{equipment}/incidents', [IncidentController::class, 'indexForEquipment'])
        ->name('api.equipment.incidents.index');
    Route::post('/equipment/{equipment}/incidents', [IncidentController::class, 'store'])
        ->name('api.equipment.incidents.store');
    Route::get('/incidents', [IncidentController::class, 'index'])->name('api.incidents.index');
    Route::get('/incidents/{incident}', [IncidentController::class, 'show'])->name('api.incidents.show');
    Route::patch('/incidents/{incident}', [IncidentController::class, 'update'])->name('api.incidents.update');
    Route::get('/labs/{lab}/map', [LabLayoutController::class, 'show'])->name('api.labs.map');
    Route::put('/labs/{lab}/layout', [LabLayoutController::class, 'update'])->name('api.labs.layout');

    // Horario de apertura, cierres y periodos academicos
    Route::get('/labs/{lab}/schedule', [LabScheduleController::class, 'show'])
        ->name('api.labs.schedule');
    Route::put('/labs/{lab}/opening-hours', [LabScheduleController::class, 'syncOpeningHours'])
        ->name('api.labs.opening-hours.sync');
    Route::apiResource('closures', LabClosureController::class)
        ->parameters(['closures' => 'closure'])
        ->names([
            'index' => 'api.closures.index',
            'store' => 'api.closures.store',
            'show' => 'api.closures.show',
            'update' => 'api.closures.update',
            'destroy' => 'api.closures.destroy',
        ]);
    Route::apiResource('academic-periods', AcademicPeriodController::class)
        ->names([
            'index' => 'api.academic-periods.index',
            'store' => 'api.academic-periods.store',
            'show' => 'api.academic-periods.show',
            'update' => 'api.academic-periods.update',
            'destroy' => 'api.academic-periods.destroy',
        ]);

    // Gestion de usuarios (las rutas anidadas users/{user}/roles y
    // permission-overrides se declaran mas abajo; no chocan con el resource)
    Route::patch('/users/{user}/unblock', [UserController::class, 'unblock'])
        ->name('api.users.unblock');
    Route::apiResource('users', UserController::class)
        ->only(['index', 'show', 'update', 'destroy'])
        ->names([
            'index' => 'api.users.index',
            'show' => 'api.users.show',
            'update' => 'api.users.update',
            'destroy' => 'api.users.destroy',
        ]);

    // Reportes
    Route::get('/reports/summary', [ReportController::class, 'summary'])->name('api.reports.summary');
    Route::get('/reports/occupancy', [ReportController::class, 'occupancy'])->name('api.reports.occupancy');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('api.reports.export');

    // Centro de notificaciones del usuario autenticado
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('api.notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
        ->name('api.notifications.unread-count');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('api.notifications.read-all');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
        ->name('api.notifications.read');
    Route::patch('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])
        ->name('api.reservations.cancel');

    // Reserva de laboratorio completo y flujo de aprobacion. 'pending' debe
    // declararse antes del resource para que {reservation} no lo capture.
    Route::post('/lab-reservations', [ReservationController::class, 'storeLab'])
        ->name('api.reservations.store-lab');
    Route::post('/lab-reservations/recurring', [ReservationController::class, 'storeRecurringLab'])
        ->name('api.reservations.store-recurring-lab');
    Route::post('/reservations/{reservation}/check-in', [ReservationController::class, 'checkIn'])
        ->name('api.reservations.check-in');
    Route::post('/reservations/{reservation}/no-show', [ReservationController::class, 'markNoShow'])
        ->name('api.reservations.no-show');
    Route::patch('/reservations/{reservation}/cancel-series', [ReservationController::class, 'cancelSeries'])
        ->name('api.reservations.cancel-series');
    Route::get('/reservations/pending', [ReservationController::class, 'pending'])
        ->name('api.reservations.pending');
    Route::patch('/reservations/{reservation}/approve', [ReservationController::class, 'approve'])
        ->name('api.reservations.approve');
    Route::patch('/reservations/{reservation}/reject', [ReservationController::class, 'reject'])
        ->name('api.reservations.reject');

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
