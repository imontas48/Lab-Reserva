<?php

namespace App\Policies;

use App\Models\User;

/**
 * Gestion de usuarios. El permiso sale del RBAC; las reglas que protegen al
 * propio administrador (no degradarse, no darse de baja, no dejar el
 * sistema sin administradores) viven en UserService.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users', 'viewAny');
    }

    public function view(User $user, User $target): bool
    {
        return $user->hasPermission('users', 'view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users', 'create');
    }

    public function update(User $user, User $target): bool
    {
        return $user->hasPermission('users', 'update');
    }

    public function delete(User $user, User $target): bool
    {
        return $user->hasPermission('users', 'delete');
    }
}
