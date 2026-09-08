<?php

namespace App\Policies;

use App\Models\Lab;
use App\Models\User;

class LabPolicy
{
    /**
     * Determine whether the user can view any models.
     * Cualquier usuario autenticado puede ver la lista de laboratorios.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('labs', 'viewAny');
    }

    /**
     * Determine whether the user can view the model.
     * Cualquier usuario autenticado puede ver un laboratorio específico.
     */
    public function view(User $user, Lab $labs): bool
    {
        return $user->hasPermission('labs', 'view');
    }

    /**
     * Determine whether the user can create models.
     * Solo los administradores pueden crear laboratorios.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('labs', 'create');
    }

    /**
     * Determine whether the user can update the model.
     * Solo los administradores pueden actualizar laboratorios.
     */
    public function update(User $user, Lab $labs): bool
    {
        return $user->hasPermission('labs', 'update');
    }

    /**
     * Determine whether the user can delete the model.
     * Solo los administradores pueden eliminar laboratorios.
     */
    public function delete(User $user, Lab $labs): bool
    {
        return $user->hasPermission('labs', 'delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Lab $labs): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Lab $labs): bool
    {
        return false;
    }
}
