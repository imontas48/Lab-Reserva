<?php

namespace Tests\Feature\Rbac;

use App\Models\Permission;
use App\Models\PermissionOverride;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Services\PermissionService;
use App\Support\PermissionCatalog;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Resolucion de permisos efectivos: roles por grupo, roles individuales
 * vigentes y sobreescrituras, con REVOKE por encima de GRANT.
 *
 * Estas 40 lineas de logica no tenian ni una sola prueba y contenian al menos
 * cuatro fallos.
 */
class PermissionResolutionTest extends TestCase
{
    private function permission(string $subject, string $action): Permission
    {
        return Permission::where('subject', $subject)->where('action', $action)->firstOrFail();
    }

    public function test_el_rol_base_concede_permisos_por_asignacion_de_grupo(): void
    {
        $student = User::factory()->student()->create();

        // Sin ninguna asignacion individual: los permisos llegan por la regla
        // de grupo que vincula users.role con el rol del RBAC.
        $this->assertTrue($student->hasPermission('labs', 'viewAny'));
        $this->assertTrue($student->hasPermission('reservations', 'create'));
        $this->assertFalse($student->hasPermission('labs', 'create'));
    }

    /**
     * La unica diferencia entre profesor y estudiante: apartar un laboratorio
     * completo. Aprobar sigue siendo del administrador.
     */
    public function test_el_profesor_puede_solicitar_laboratorios_pero_no_aprobarlos(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $this->assertTrue($teacher->hasPermission('reservations', 'createLab'));
        $this->assertFalse($teacher->hasPermission('reservations', 'approve'));
        $this->assertFalse($student->hasPermission('reservations', 'createLab'));
        $this->assertFalse($student->hasPermission('reservations', 'approve'));
        $this->assertTrue($student->hasPermission('schedule', 'view'));
        $this->assertFalse($teacher->hasPermission('schedule', 'manage'));
    }

    public function test_el_administrador_tiene_todo_el_catalogo(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (PermissionCatalog::allKeys() as $key) {
            [$subject, $action] = explode('.', $key);
            $this->assertTrue(
                $admin->hasPermission($subject, $action),
                "Al administrador le falta {$key}"
            );
        }
    }

    public function test_desactivar_un_rol_revoca_sus_permisos(): void
    {
        $student = User::factory()->student()->create();
        $this->assertTrue($student->hasPermission('reservations', 'create'));

        // Este era el bug mas serio de la resolucion: resolveEffectivePermissions
        // no filtraba por roles.is_active, asi que el interruptor no hacia nada.
        Role::where('name', 'student')->firstOrFail()->update(['is_active' => false]);

        $this->assertFalse($student->fresh()->hasPermission('reservations', 'create'));
    }

    public function test_un_revoke_quita_un_permiso_del_rol(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        PermissionOverride::create([
            'user_id' => $student->id,
            'permission_id' => $this->permission('reservations', 'create')->id,
            'type' => PermissionOverride::TYPE_REVOKE,
            'granted_by' => $admin->id,
        ]);

        $this->assertFalse($student->fresh()->hasPermission('reservations', 'create'));
        $this->assertTrue($student->fresh()->hasPermission('labs', 'viewAny'));
    }

    public function test_un_grant_anade_un_permiso_que_el_rol_no_da(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        PermissionOverride::create([
            'user_id' => $student->id,
            'permission_id' => $this->permission('labs', 'create')->id,
            'type' => PermissionOverride::TYPE_GRANT,
            'granted_by' => $admin->id,
        ]);

        $this->assertTrue($student->fresh()->hasPermission('labs', 'create'));
    }

    public function test_una_sobreescritura_caducada_no_se_aplica(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $override = PermissionOverride::create([
            'user_id' => $student->id,
            'permission_id' => $this->permission('labs', 'create')->id,
            'type' => PermissionOverride::TYPE_GRANT,
            'granted_by' => $admin->id,
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $this->assertTrue($student->fresh()->hasPermission('labs', 'create'));

        $this->travelTo(Carbon::now()->addHours(2));
        PermissionService::flushCache();

        $this->assertFalse($student->fresh()->hasPermission('labs', 'create'));
        $this->assertNotNull($override->fresh(), 'La fila debe conservarse para auditoria.');
    }

    public function test_un_rol_individual_caducado_deja_de_conceder(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $adminRole = Role::where('name', 'admin')->firstOrFail();

        UserRole::create([
            'user_id' => $student->id,
            'role_id' => $adminRole->id,
            'granted_by' => $admin->id,
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $this->assertTrue($student->fresh()->hasPermission('labs', 'delete'));

        $this->travelTo(Carbon::now()->addHours(2));
        PermissionService::flushCache();

        $this->assertFalse($student->fresh()->hasPermission('labs', 'delete'));
    }

    public function test_un_rol_individual_eleva_a_un_estudiante(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $this->assertFalse($student->hasPermission('labs', 'create'));

        UserRole::create([
            'user_id' => $student->id,
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'granted_by' => $admin->id,
        ]);

        $this->assertTrue($student->fresh()->hasPermission('labs', 'create'));
    }
}
