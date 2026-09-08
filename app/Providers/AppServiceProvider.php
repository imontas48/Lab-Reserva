<?php

namespace App\Providers;

use App\Models\Equipment;
use App\Models\GroupRoleAssignment;
use App\Models\Lab;
use App\Models\Permission;
use App\Models\PermissionOverride;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\Software;
use App\Models\UserRole;
use App\Policies\EquipmentPolicy;
use App\Policies\LabPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\ReservationPolicy;
use App\Policies\RolePolicy;
use App\Policies\SoftwarePolicy;
use App\Services\PermissionService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar las políticas de autorización
        Gate::policy(Lab::class, LabPolicy::class);
        Gate::policy(Equipment::class, EquipmentPolicy::class);
        Gate::policy(Software::class, SoftwarePolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(Reservation::class, ReservationPolicy::class);

        $this->configureRateLimiting();
        $this->invalidatePermissionCacheOnRbacWrites();
    }

    /**
     * Invalida la caché de permisos ante cualquier escritura del RBAC.
     *
     * Se engancha a los eventos del modelo y no a llamadas repartidas por los
     * servicios: así cubre también las escrituras desde seeders, comandos,
     * tinker o código futuro, sin depender de que alguien recuerde invalidar.
     * En un sistema de control de acceso, una caché obsoleta es un fallo de
     * seguridad, no una molestia de rendimiento.
     */
    private function invalidatePermissionCacheOnRbacWrites(): void
    {
        $models = [
            Role::class,
            Permission::class,
            UserRole::class,
            GroupRoleAssignment::class,
            PermissionOverride::class,
        ];

        foreach ($models as $model) {
            $model::saved(static fn () => PermissionService::flushCache());
            $model::deleted(static fn () => PermissionService::flushCache());
        }

        // La tabla pivote role_permissions se escribe con sync(), que no
        // dispara eventos de modelo, así que RoleService::syncPermissions
        // invalida de forma explícita.
    }

    /**
     * Limitador del grupo api, que bootstrap/app.php activa con throttleApi().
     *
     * Laravel 11 dejo de registrarlo por defecto al eliminar el
     * RouteServiceProvider, de modo que el grupo api no aplicaba ningun
     * limite: la API quedaba abierta a fuerza bruta y a agotamiento de
     * recursos.
     *
     * Se cuenta por usuario autenticado y, si no lo hay, por IP: de lo
     * contrario todos los usuarios detras de una misma NAT compartirian
     * cupo.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
