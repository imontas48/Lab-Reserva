<?php

namespace App\Providers;

use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Permission;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\Software;
use App\Policies\EquipmentPolicy;
use App\Policies\LabPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\ReservationPolicy;
use App\Policies\RolePolicy;
use App\Policies\SoftwarePolicy;
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
