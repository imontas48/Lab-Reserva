<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\User;

class EquipmentPolicy
{
    /**
     * Determine whether the user can view any models.
     * Cualquier usuario autenticado puede ver la lista de equipos.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('equipment', 'viewAny');
    }

    /**
     * Determine whether the user can view the model.
     * Cualquier usuario autenticado puede ver un equipo específico.
     */
    public function view(User $user, Equipment $equipment): bool
    {
        return $user->hasPermission('equipment', 'view');
    }

    /**
     * Determine whether the user can create models.
     * Solo los administradores pueden crear equipos.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('equipment', 'create');
    }

    /**
     * Determine whether the user can update the model.
     * Solo los administradores pueden actualizar equipos.
     */
    public function update(User $user, Equipment $equipment): bool
    {
        return $user->hasPermission('equipment', 'update');
    }

    /**
     * Determine whether the user can delete the model.
     * Solo los administradores pueden eliminar equipos.
     */
    public function delete(User $user, Equipment $equipment): bool
    {
        return $user->hasPermission('equipment', 'delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Equipment $equipment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Equipment $equipment): bool
    {
        return false;
    }
}
