<?php

namespace App\Providers;

use App\Models\equipment;
use App\Models\GroupRoleAssignment;
use App\Models\labs;
use App\Models\Permission;
use App\Models\Role;
use App\Models\software;
use App\Models\reservations;
use App\Models\UserRole;
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
        Gate::policy(labs::class, LabPolicy::class);
        Gate::policy(equipment::class, EquipmentPolicy::class);
        Gate::policy(software::class, SoftwarePolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(reservations::class, ReservationPolicy::class);
    }
}
