<?php

namespace App\Providers;

use App\Models\equipment;
use App\Models\labs;
use App\Models\software;
use App\Policies\EquipmentPolicy;
use App\Policies\LabPolicy;
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
    }
}
