<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    /**
     * Cualquier admin puede listar roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Cualquier admin puede ver un rol específico.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->isAdmin();
    }

    /**
     * Solo admins pueden crear roles.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Solo admins pueden actualizar roles.
     * Los roles de sistema (is_system=true) no pueden modificarse.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->isAdmin() && ! $role->is_system;
    }

    /**
     * Solo admins pueden eliminar roles.
     * Los roles de sistema no pueden eliminarse.
     */
    public function delete(User $user, Role $role): bool
    {
        return $user->isAdmin() && ! $role->is_system;
    }

    /**
     * Solo admins pueden sincronizar los permisos de un rol.
     * Los roles de sistema no pueden modificarse.
     */
    public function syncPermissions(User $user, Role $role): bool
    {
        return $user->isAdmin() && ! $role->is_system;
    }
}
