<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

/**
 * Administración del propio sistema de permisos.
 *
 * A diferencia de las policies de recursos, estas comprobaciones NO se
 * convierten a permisos del RBAC, y es deliberado: si 'roles.update' fuese un
 * permiso otorgable, concederlo equivaldría a conceder todo, porque quien lo
 * tuviese podría añadirse cualquier otro permiso. El RBAC pasaría a poder
 * modificarse a sí mismo y dejaría de ser una frontera de seguridad.
 *
 * Anclarlo al rol base del ENUM users.role, que solo se cambia desde consola
 * con lab:make-admin, mantiene una vía de recuperación aunque la configuración
 * del RBAC quede inservible.
 */
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
