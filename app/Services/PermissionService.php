<?php

namespace App\Services;

use App\Models\GroupRoleAssignment;
use App\Models\Permission;
use App\Models\PermissionOverride;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class PermissionService
{
    /**
     * Lista el catálogo completo de permisos, opcionalmente agrupados por subject.
     */
    public function getAllPermissions(array $filters = []): Collection
    {
        $query = Permission::query();

        if (isset($filters['subject'])) {
            $query->where('subject', $filters['subject']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('subject')->orderBy('action')->get();
    }

    /**
     * Crea un nuevo permiso en el catálogo.
     */
    public function createPermission(array $data): Permission
    {
        return Permission::create([
            'subject' => $data['subject'],
            'action' => $data['action'],
            'description' => $data['description'],
        ]);
    }

    /**
     * Actualiza la descripción de un permiso.
     * Subject y action son inmutables para no romper código que depende de ellos.
     */
    public function updatePermission(Permission $permission, array $data): Permission
    {
        $permission->update(['description' => $data['description']]);

        return $permission->fresh();
    }

    /**
     * Elimina un permiso del catálogo.
     * El cascadeOnDelete de la FK elimina role_permissions y permission_overrides relacionados.
     */
    public function deletePermission(Permission $permission): void
    {
        $permission->delete();
    }

    // =========================================================================
    // RESOLUCIÓN DE PERMISOS EFECTIVOS
    // =========================================================================

    /**
     * Calcula el conjunto de permisos efectivos de un usuario.
     *
     * ALGORITMO DE RESOLUCIÓN (de menor a mayor prioridad):
     *   1. Permisos de roles asignados por grupo (group_role_assignments activas)
     *   2. Permisos de roles asignados individualmente (user_roles vigentes)
     *   3. Se aplican sobreescrituras activas (REVOKE primero, luego GRANT)
     *
     * @return Collection<Permission> Colección de permisos con campo 'source' adicional.
     */
    public function resolveEffectivePermissions(User $user): Collection
    {
        // ── Paso 1: IDs de roles por grupo ────────────────────────────────────
        $groupRoleIds = GroupRoleAssignment::active()
            ->forGroupValue(GroupRoleAssignment::GROUP_TYPE_USER_TYPE, $user->role)
            ->pluck('role_id');

        // ── Paso 2: IDs de roles individuales vigentes ────────────────────────
        $individualRoleIds = $user->userRoles()
            ->active()
            ->pluck('role_id');

        $allRoleIds = $groupRoleIds->merge($individualRoleIds)->unique();

        // ── Paso 3: Permisos base de todos los roles ──────────────────────────
        $basePermissions = Permission::whereHas('roles', fn ($q) => $q->whereIn('roles.id', $allRoleIds))
            ->get()
            ->keyBy('id');

        // ── Paso 4: Sobreescrituras activas del usuario ───────────────────────
        $overrides = $user->permissionOverrides()
            ->active()
            ->with('permission')
            ->get();

        // Aplicar REVOKEs primero (mayor prioridad)
        $overrides->where('type', PermissionOverride::TYPE_REVOKE)
            ->each(fn ($o) => $basePermissions->forget($o->permission_id));

        // Aplicar GRANTs (añade permisos extra no presentes por roles)
        $overrides->where('type', PermissionOverride::TYPE_GRANT)
            ->each(function ($o) use ($basePermissions) {
                if ($o->permission && ! $basePermissions->has($o->permission_id)) {
                    $basePermissions->put($o->permission_id, $o->permission);
                }
            });

        return $basePermissions->values();
    }

    // =========================================================================
    // GESTIÓN DE SOBREESCRITURAS (PERMISSION OVERRIDES)
    // =========================================================================

    /**
     * Lista las sobreescrituras de permisos de un usuario.
     */
    public function getOverridesForUser(User $user): Collection
    {
        return $user->permissionOverrides()
            ->with(['permission', 'grantedBy'])
            ->orderBy('type')
            ->get();
    }

    /**
     * Crea una sobreescritura de permiso para un usuario.
     */
    public function createOverride(User $user, array $data, User $admin): PermissionOverride
    {
        return PermissionOverride::create([
            'user_id' => $user->id,
            'permission_id' => $data['permission_id'],
            'type' => $data['type'],
            'reason' => $data['reason'] ?? null,
            'granted_by' => $admin->id,
            'expires_at' => $data['expires_at'] ?? null,
        ]);
    }

    /**
     * Actualiza una sobreescritura de permiso.
     */
    public function updateOverride(PermissionOverride $override, array $data): PermissionOverride
    {
        $override->update(array_filter([
            'type' => $data['type'] ?? null,
            'reason' => $data['reason'] ?? null,
            'expires_at' => array_key_exists('expires_at', $data) ? $data['expires_at'] : null,
        ], fn ($v) => $v !== null));

        return $override->fresh();
    }

    /**
     * Elimina una sobreescritura de permiso.
     */
    public function deleteOverride(PermissionOverride $override): void
    {
        $override->delete();
    }
}
