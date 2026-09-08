<?php

namespace App\Policies;

use App\Models\Software;
use App\Models\User;

class SoftwarePolicy
{
    /**
     * Determine whether the user can view any models.
     * Cualquier usuario autenticado puede ver la lista de software.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * Cualquier usuario autenticado puede ver un software específico.
     */
    public function view(User $user, Software $software): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     * Solo los administradores pueden crear software.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     * Solo los administradores pueden actualizar software.
     */
    public function update(User $user, Software $software): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     * Solo los administradores pueden eliminar software.
     */
    public function delete(User $user, Software $software): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Software $software): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Software $software): bool
    {
        return false;
    }
}
