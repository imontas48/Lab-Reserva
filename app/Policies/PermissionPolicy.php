<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

class PermissionPolicy
{
    /**
     * Cualquier admin puede listar el catálogo de permisos.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Cualquier admin puede ver un permiso específico.
     */
    public function view(User $user, Permission $permission): bool
    {
        return $user->isAdmin();
    }

    /**
     * Solo admins pueden crear permisos.
     * En la práctica, los permisos se generan por seeder;
     * este método existe para cumplir el contrato de la Policy.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Solo admins pueden actualizar descripciones de permisos.
     */
    public function update(User $user, Permission $permission): bool
    {
        return $user->isAdmin();
    }

    /**
     * Solo admins pueden eliminar permisos.
     * Eliminar un permiso lo quita de todos los roles que lo tenían.
     */
    public function delete(User $user, Permission $permission): bool
    {
        return $user->isAdmin();
    }

    /**
     * Solo admins pueden consultar los permisos efectivos de un usuario.
     */
    public function viewEffective(User $user): bool
    {
        return $user->isAdmin();
    }
}
