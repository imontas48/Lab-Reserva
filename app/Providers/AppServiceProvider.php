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
use Illuminate\Support\Facades\Gate;
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
    }
}
