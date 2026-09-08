<?php

namespace Tests\Feature\Rbac;

use App\Models\Lab;
use App\Models\Permission;
use App\Models\PermissionOverride;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Services\PermissionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Antes de esta fase, las cinco tablas del RBAC se leian y escribian solo a si
 * mismas: ninguna policy las consultaba y las 41 decisiones de autorizacion se
 * tomaban con la columna ENUM users.role. Asignar un rol a un usuario no le
 * concedia ningun permiso real.
 *
 * Estas pruebas verifican lo contrario de extremo a extremo: un cambio en las
 * tablas cambia lo que la API deja hacer.
 */
class RbacGovernsAccessTest extends TestCase
{
    public function test_conceder_un_permiso_habilita_el_endpoint(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $this->actingAs($student, 'sanctum');
        $this->postJson('/api/v1/labs', [
            'name' => 'Lab de prueba', 'location' => 'A', 'capacity' => 10,
        ])->assertForbidden();

        PermissionOverride::create([
            'user_id' => $student->id,
            'permission_id' => Permission::where('subject', 'labs')->where('action', 'create')->firstOrFail()->id,
            'type' => PermissionOverride::TYPE_GRANT,
            'granted_by' => $admin->id,
        ]);

        $this->postJson('/api/v1/labs', [
            'name' => 'Lab de prueba', 'location' => 'A', 'capacity' => 10,
        ])->assertCreated();
    }

    public function test_revocar_un_permiso_cierra_el_endpoint(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $this->actingAs($student, 'sanctum');
        $this->getJson('/api/v1/labs')->assertOk();

        PermissionOverride::create([
            'user_id' => $student->id,
            'permission_id' => Permission::where('subject', 'labs')->where('action', 'viewAny')->firstOrFail()->id,
            'type' => PermissionOverride::TYPE_REVOKE,
            'granted_by' => $admin->id,
        ]);

        $this->getJson('/api/v1/labs')->assertForbidden();
    }

    public function test_desactivar_el_rol_de_estudiante_cierra_el_acceso_por_http(): void
    {
        $student = User::factory()->student()->create();
        $this->actingAs($student, 'sanctum');

        $this->getJson('/api/v1/labs')->assertOk();

        Role::where('name', 'student')->firstOrFail()->update(['is_active' => false]);

        $this->getJson('/api/v1/labs')->assertForbidden();
    }

    public function test_un_rol_individual_de_administrador_habilita_el_borrado(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $lab = Lab::factory()->create();

        $this->actingAs($student, 'sanctum');
        $this->deleteJson("/api/v1/labs/{$lab->id}")->assertForbidden();

        UserRole::create([
            'user_id' => $student->id,
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'granted_by' => $admin->id,
        ]);

        $this->deleteJson("/api/v1/labs/{$lab->id}")->assertOk();
    }

    /**
     * La caché es lo que hace viable consultar el RBAC en cada authorize(),
     * pero en control de acceso una entrada obsoleta es un fallo de seguridad.
     */
    public function test_la_cache_se_invalida_ante_cualquier_escritura_del_rbac(): void
    {
        $student = User::factory()->student()->create();

        // Primera resolución: se cachea.
        $this->assertTrue($student->hasPermission('labs', 'viewAny'));

        Role::where('name', 'student')->firstOrFail()->update(['is_active' => false]);

        // Sin invalidación, esto seguiría devolviendo true.
        $this->assertFalse($student->fresh()->hasPermission('labs', 'viewAny'));
    }

    public function test_la_cache_evita_repetir_la_resolucion(): void
    {
        $student = User::factory()->student()->create();

        $service = app(PermissionService::class);
        $service->effectivePermissionKeys($student);

        DB::enableQueryLog();
        $service->effectivePermissionKeys($student);
        $consultas = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(0, $consultas, 'La segunda resolución debía salir de caché.');
    }

    /**
     * La administración del propio RBAC sigue anclada al rol base y no a un
     * permiso otorgable: si 'roles.update' fuese concedible, otorgarlo
     * equivaldría a otorgar todo, porque quien lo tuviera podría añadirse
     * cualquier permiso.
     */
    public function test_conceder_permisos_no_permite_administrar_el_rbac(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        // Se le concede el permiso más alto del catálogo de recursos.
        UserRole::create([
            'user_id' => $student->id,
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'granted_by' => $admin->id,
        ]);

        $this->actingAs($student, 'sanctum');

        // Puede administrar laboratorios...
        $this->getJson('/api/v1/labs')->assertOk();

        // ...pero no el sistema de permisos, porque su users.role sigue siendo
        // 'student' y esa es la frontera que no se puede cruzar desde datos.
        $this->getJson('/api/v1/roles')->assertForbidden();
        $this->getJson('/api/v1/permissions')->assertForbidden();
    }

    public function test_una_sobreescritura_caducada_puede_sustituirse(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $permiso = Permission::where('subject', 'labs')->where('action', 'create')->firstOrFail();

        PermissionOverride::create([
            'user_id' => $student->id,
            'permission_id' => $permiso->id,
            'type' => PermissionOverride::TYPE_GRANT,
            'granted_by' => $admin->id,
            'expires_at' => Carbon::now()->addHour(),
        ]);

        $this->travelTo(Carbon::now()->addHours(2));

        // El UNIQUE(user_id, permission_id) bloqueaba para siempre la creación
        // de otra sobreescritura sobre el mismo permiso, incluso caducada la
        // anterior, y la UI no ofrecía ninguna salida.
        $this->actingAs($admin, 'sanctum');

        $this->postJson("/api/v1/users/{$student->id}/permission-overrides", [
            'permission_id' => $permiso->id,
            'type' => PermissionOverride::TYPE_GRANT,
        ])->assertSuccessful();

        $this->assertTrue($student->fresh()->hasPermission('labs', 'create'));
    }
}
