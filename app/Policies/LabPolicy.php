<?php

namespace App\Policies;

use App\Models\User;
use App\Models\labs;
use Illuminate\Auth\Access\Response;

class LabPolicy
{
    /**
     * Determine whether the user can view any models.
     * Cualquier usuario autenticado puede ver la lista de laboratorios.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * Cualquier usuario autenticado puede ver un laboratorio específico.
     */
    public function view(User $user, labs $labs): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     * Solo los administradores pueden crear laboratorios.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     * Solo los administradores pueden actualizar laboratorios.
     */
    public function update(User $user, labs $labs): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     * Solo los administradores pueden eliminar laboratorios.
     */
    public function delete(User $user, labs $labs): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, labs $labs): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, labs $labs): bool
    {
        return false;
    }
}
