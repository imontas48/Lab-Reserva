<?php

namespace App\Policies;

use App\Models\User;

/**
 * Horarios de apertura, cierres y periodos academicos comparten una misma
 * pareja de permisos: consultarlos (todos los roles) y gestionarlos (admin).
 * Se registra para LabClosure y AcademicPeriod; el horario de apertura se
 * autoriza con las mismas habilidades desde el controlador.
 */
class SchedulePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('schedule', 'view');
    }

    public function view(User $user, mixed $model = null): bool
    {
        return $user->hasPermission('schedule', 'view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('schedule', 'manage');
    }

    public function update(User $user, mixed $model = null): bool
    {
        return $user->hasPermission('schedule', 'manage');
    }

    public function delete(User $user, mixed $model = null): bool
    {
        return $user->hasPermission('schedule', 'manage');
    }
}
