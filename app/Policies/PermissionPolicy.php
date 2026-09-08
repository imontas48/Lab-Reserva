<?php

namespace App\Policies;

use App\Models\Permission;
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
