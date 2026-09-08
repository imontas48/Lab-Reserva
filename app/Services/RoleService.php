<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RoleService
{
    /**
     * Lista todos los roles con filtros opcionales.
     */
    public function getAllRoles(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $query = Role::withCount(['permissions', 'userRoles']);

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%");
            });
        }

        $query->orderBy('display_name');

        return $perPage ? $query->paginate($perPage) : $query->get();
    }

    /**
     * Obtiene un rol por su ID con sus permisos cargados.
     */
    public function getRoleWithPermissions(Role $role): Role
    {
        return $role->loadCount(['permissions', 'userRoles'])
            ->load('permissions');
    }

    /**
     * Crea un nuevo rol y opcionalmente asigna permisos.
     *
     * @param  array{name: string, display_name: string, description: ?string, color: ?string, is_active: ?bool, permission_ids: ?array<int>}  $data
     */
    public function createRole(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'display_name' => $data['display_name'],
                'description' => $data['description'] ?? null,
                'color' => $data['color'] ?? 'blue',
                'is_active' => $data['is_active'] ?? true,
                'is_system' => false, // Los roles creados por admins nunca son de sistema
            ]);

            if (! empty($data['permission_ids'])) {
                $role->permissions()->sync($data['permission_ids']);
            }

            return $role->load('permissions');
        });
    }

    /**
     * Actualiza los datos descriptivos de un rol.
     * El slug (name) es inmutable para evitar romper referencias en código.
     *
     * @param  array{display_name?: string, description?: ?string, color?: string, is_active?: bool}  $data
     */
    public function updateRole(Role $role, array $data): Role
    {
        // Antes esto era un array_filter que descartaba los null, de modo que
        // enviar description: null respondia 200 pero no vaciaba el campo. Lo
        // correcto es distinguir "clave ausente" de "clave con valor null":
        // solo se escriben las claves que el cliente envio de verdad.
        $role->update(
            array_intersect_key($data, array_flip(['display_name', 'description', 'color', 'is_active']))
        );

        return $role->fresh();
    }

    /**
     * Elimina un rol personalizado.
     * Lanza excepción si es un rol de sistema.
     *
     * @throws \RuntimeException
     */
    public function deleteRole(Role $role): void
    {
        if ($role->is_system) {
            throw new \RuntimeException('Los roles del sistema no pueden eliminarse.');
        }

        DB::transaction(function () use ($role) {
            // Las FKs con cascadeOnDelete limpian role_permissions,
            // user_roles y group_role_assignments automáticamente.
            $role->delete();
        });
    }

    /**
     * Reemplaza el conjunto de permisos de un rol de forma atómica (sync).
     * Enviar un array vacío revoca todos los permisos del rol.
     *
     * @param  int[]  $permissionIds
     *
     * @throws \RuntimeException
     */
    public function syncPermissions(Role $role, array $permissionIds): Role
    {
        if ($role->is_system) {
            throw new \RuntimeException('Los permisos de un rol del sistema no pueden modificarse.');
        }

        $role->permissions()->sync($permissionIds);

        // sync() sobre la tabla pivote no dispara eventos de modelo, asi que la
        // invalidacion centralizada no la cubre.
        PermissionService::flushCache();

        return $role->load('permissions');
    }
}
