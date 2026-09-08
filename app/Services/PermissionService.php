<?php

namespace App\Services;

use App\Models\GroupRoleAssignment;
use App\Models\Permission;
use App\Models\PermissionOverride;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    /**
     * Contador global cuyo valor forma parte de la clave de cache de cada
     * usuario. Incrementarlo invalida todas las entradas a la vez.
     */
    private const VERSION_KEY = 'rbac:version';

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
        $permission = Permission::create([
            'subject' => $data['subject'],
            'action' => $data['action'],
            'description' => $data['description'],
        ]);

        return $permission;
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
            ->notExpired()
            ->pluck('role_id');

        $allRoleIds = $groupRoleIds->merge($individualRoleIds)->unique();

        // ── Paso 3: Permisos base de todos los roles ──────────────────────────
        //
        // El filtro por roles.is_active es imprescindible y faltaba: sin él,
        // desactivar un rol no revocaba nada. Todos los usuarios que ya lo
        // tenían conservaban sus permisos, de modo que el interruptor que la
        // migración describe como "permite desactivar un rol sin eliminarlo"
        // no hacía absolutamente nada.
        $basePermissions = Permission::whereHas('roles', fn ($q) => $q
            ->whereIn('roles.id', $allRoleIds)
            ->where('roles.is_active', true)
        )
            ->get()
            ->keyBy('id');

        // ── Paso 4: Sobreescrituras activas del usuario ───────────────────────
        $overrides = $user->permissionOverrides()
            ->notExpired()
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
    // RESOLUCIÓN CACHEADA PARA EL CONTROL DE ACCESO
    // =========================================================================

    /**
     * Clave "subject.action" de cada permiso efectivo del usuario.
     *
     * Es lo que consultan las policies. Se cachea porque resolveEffectivePermissions
     * cuesta cinco consultas y antes solo se invocaba una vez bajo demanda desde
     * una pantalla de administración; al conectarlo al control de acceso pasa a
     * ejecutarse en cada authorize(), varias veces por petición.
     *
     * La clave incluye la versión global del RBAC, así que cualquier cambio en
     * roles, permisos, asignaciones o sobreescrituras invalida la caché de todos
     * los usuarios de golpe. Es más grosero que invalidar por usuario, pero
     * imposible de dejar obsoleto: en control de acceso una caché sucia es un
     * fallo de seguridad, y las escrituras del RBAC son raras.
     *
     * @return array<int, string>
     */
    public function effectivePermissionKeys(User $user): array
    {
        return Cache::remember(
            $this->cacheKeyFor($user),
            now()->addMinutes(30),
            fn () => $this->resolveEffectivePermissions($user)
                ->map(fn (Permission $p) => "{$p->subject}.{$p->action}")
                ->all()
        );
    }

    public function userHasPermission(User $user, string $subject, string $action): bool
    {
        return in_array("{$subject}.{$action}", $this->effectivePermissionKeys($user), true);
    }

    /**
     * Invalida la caché de permisos de todos los usuarios.
     *
     * Se llama desde los servicios que escriben en el RBAC.
     */
    public static function flushCache(): void
    {
        // increment() no crea la clave si no existe en todos los drivers,
        // asi que se escribe el valor de forma explicita.
        Cache::forever(self::VERSION_KEY, ((int) Cache::get(self::VERSION_KEY, 0)) + 1);
    }

    private function cacheKeyFor(User $user): string
    {
        $version = Cache::get(self::VERSION_KEY, 0);

        return "rbac:v{$version}:user:{$user->id}:permissions";
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
        // updateOrCreate y no create: la tabla tiene UNIQUE(user_id,
        // permission_id), de modo que una sobreescritura caducada bloqueaba
        // para siempre la creación de otra sobre el mismo permiso. La fila
        // caducada se sustituye por la nueva, que es justo lo que el
        // administrador quiere al volver a conceder o revocar algo.
        return PermissionOverride::updateOrCreate(
            [
                'user_id' => $user->id,
                'permission_id' => $data['permission_id'],
            ],
            [
                'type' => $data['type'],
                'reason' => $data['reason'] ?? null,
                'granted_by' => $admin->id,
                'expires_at' => $data['expires_at'] ?? null,
            ]
        );
    }

    /**
     * Actualiza una sobreescritura de permiso.
     */
    public function updateOverride(PermissionOverride $override, array $data): PermissionOverride
    {
        // El array_filter descartaba los null DESPUES de haberlos calculado, de
        // modo que el array_key_exists de expires_at no servia de nada: enviar
        // expires_at: null respondia 200 y no cambiaba el valor. Una
        // sobreescritura temporal no podia convertirse en permanente, ni podia
        // vaciarse el motivo.
        $override->update(
            array_intersect_key($data, array_flip(['type', 'reason', 'expires_at']))
        );

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
