<?php

namespace Database\Seeders;

use App\Models\GroupRoleAssignment;
use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Puebla el catalogo de permisos, los roles base y su vinculacion.
 *
 * No existia ningun seeder para permissions, roles, role_permissions ni
 * group_role_assignments, de modo que en una instalacion limpia las cinco
 * tablas del RBAC estaban vacias y resolveEffectivePermissions() devolvia una
 * lista vacia para todo el mundo.
 *
 * Es idempotente: se puede reejecutar tras anadir permisos al catalogo sin
 * duplicar filas ni perder las asignaciones individuales existentes.
 */
class RbacSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $permissions = $this->seedPermissions();
            $roles = $this->seedRoles();

            $this->attachPermissionsToRoles($roles, $permissions);
            $this->seedGroupAssignments($roles);
        });

        $this->command?->info('RBAC sembrado: '
            .count(PermissionCatalog::allKeys()).' permisos, '
            .count(PermissionCatalog::BASE_ROLES).' roles base.');
    }

    /**
     * @return array<string, Permission> indexado por "subject.action"
     */
    private function seedPermissions(): array
    {
        $permissions = [];

        foreach (PermissionCatalog::CATALOG as $subject => $actions) {
            foreach ($actions as $action => $description) {
                $permission = Permission::updateOrCreate(
                    ['subject' => $subject, 'action' => $action],
                    ['description' => $description]
                );

                $permissions["{$subject}.{$action}"] = $permission;
            }
        }

        return $permissions;
    }

    /**
     * @return array<string, Role>
     */
    private function seedRoles(): array
    {
        $roles = [];

        foreach (PermissionCatalog::BASE_ROLES as $name => $meta) {
            $roles[$name] = Role::updateOrCreate(
                ['name' => $name],
                [
                    'display_name' => $meta['display_name'],
                    'description' => $meta['description'],
                    'color' => $meta['color'],
                    // is_system impide que se borren o se les cambien los
                    // permisos desde la API: son la base del control de acceso.
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
        }

        return $roles;
    }

    /**
     * @param  array<string, Role>  $roles
     * @param  array<string, Permission>  $permissions
     */
    private function attachPermissionsToRoles(array $roles, array $permissions): void
    {
        foreach ($roles as $name => $role) {
            $ids = collect(PermissionCatalog::keysForRole($name))
                ->map(fn (string $key) => $permissions[$key]->id ?? null)
                ->filter()
                ->all();

            $role->permissions()->sync($ids);
        }
    }

    /**
     * Vincula cada valor del ENUM users.role con su rol del RBAC.
     *
     * Es lo que hace que un usuario recien registrado tenga permisos sin
     * necesidad de una asignacion individual.
     *
     * @param  array<string, Role>  $roles
     */
    private function seedGroupAssignments(array $roles): void
    {
        foreach ($roles as $name => $role) {
            GroupRoleAssignment::updateOrCreate(
                [
                    'group_type' => GroupRoleAssignment::GROUP_TYPE_USER_TYPE,
                    'group_value' => $name,
                    'role_id' => $role->id,
                ],
                ['is_active' => true]
            );
        }
    }
}
