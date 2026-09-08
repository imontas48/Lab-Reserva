<?php

namespace App\Services;

use App\Models\GroupRoleAssignment;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Collection;

class UserRoleService
{
    /**
     * Lista todas las asignaciones de roles de un usuario (individuales).
     */
    public function getRolesForUser(User $user): Collection
    {
        return $user->userRoles()
            ->with(['role.permissions', 'grantedBy'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Asigna un rol individualmente a un usuario.
     *
     * @throws \RuntimeException Si el rol está inactivo.
     */
    public function assignRole(User $user, array $data, User $admin): UserRole
    {
        $role = Role::findOrFail($data['role_id']);

        if (! $role->is_active) {
            throw new \RuntimeException("El rol '{$role->display_name}' está inactivo y no puede asignarse.");
        }

        return UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'granted_by' => $admin->id,
            'expires_at' => $data['expires_at'] ?? null,
        ]);
    }

    /**
     * Actualiza la fecha de expiración de una asignación de rol.
     */
    public function updateAssignment(UserRole $userRole, array $data): UserRole
    {
        // '?? null' borraba la fecha de expiracion en cualquier PATCH que no
        // la incluyese, convirtiendo en permanente una asignacion temporal sin
        // que nadie se enterase. Solo se escribe si el cliente la envio.
        if (array_key_exists('expires_at', $data)) {
            $userRole->update(['expires_at' => $data['expires_at']]);
        }

        return $userRole->fresh();
    }

    /**
     * Revoca un rol asignado individualmente a un usuario.
     */
    public function revokeRole(UserRole $userRole): void
    {
        $userRole->delete();
    }

    // =========================================================================
    // REGLAS DE GRUPO
    // =========================================================================

    /**
     * Lista todas las reglas de asignación por grupo.
     */
    public function getAllGroupAssignments(array $filters = []): Collection
    {
        $query = GroupRoleAssignment::with(['role', 'grantedBy']);

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (isset($filters['group_type'])) {
            $query->where('group_type', $filters['group_type']);
        }

        return $query->orderBy('group_type')->orderBy('group_value')->get();
    }

    /**
     * Crea una nueva regla de asignación de rol por grupo.
     */
    public function createGroupAssignment(array $data, User $admin): GroupRoleAssignment
    {
        $role = Role::findOrFail($data['role_id']);

        if (! $role->is_active) {
            throw new \RuntimeException("El rol '{$role->display_name}' está inactivo y no puede asignarse.");
        }

        return GroupRoleAssignment::create([
            'role_id' => $data['role_id'],
            'group_type' => $data['group_type'],
            'group_value' => $data['group_value'],
            'granted_by' => $admin->id,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Activa o desactiva una regla de asignación por grupo.
     */
    public function toggleGroupAssignment(GroupRoleAssignment $assignment): GroupRoleAssignment
    {
        $assignment->update(['is_active' => ! $assignment->is_active]);

        return $assignment->fresh();
    }

    /**
     * Elimina una regla de asignación por grupo.
     */
    public function deleteGroupAssignment(GroupRoleAssignment $assignment): void
    {
        $assignment->delete();
    }
}
