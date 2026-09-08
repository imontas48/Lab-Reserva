<?php

namespace Tests\Feature\Rbac;

use App\Models\Permission;
use App\Models\PermissionOverride;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Tres servicios hermanos trataban los campos opcionales de forma incoherente,
 * con dos fallos opuestos y silenciosos: unos no podian vaciarse nunca y otro
 * se vaciaba solo.
 */
class RbacUpdateSemanticsTest extends TestCase
{
    public function test_un_patch_sin_expires_at_no_borra_la_expiracion(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $expira = Carbon::now()->addDays(7)->startOfSecond();

        $asignacion = UserRole::create([
            'user_id' => $student->id,
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'granted_by' => $admin->id,
            'expires_at' => $expira,
        ]);

        $this->actingAs($admin, 'sanctum');

        // UserRoleService hacia update(['expires_at' => $data['expires_at'] ?? null]),
        // de modo que cualquier PATCH que no incluyese el campo convertia una
        // asignacion temporal en permanente sin que nadie se enterase.
        $this->patchJson("/api/v1/users/{$student->id}/roles/{$asignacion->id}", [])
            ->assertSuccessful();

        $this->assertNotNull(
            $asignacion->fresh()->expires_at,
            'La expiración no debía borrarse al no enviarla.'
        );
    }

    public function test_un_patch_con_expires_at_nulo_si_la_borra(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $asignacion = UserRole::create([
            'user_id' => $student->id,
            'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
            'granted_by' => $admin->id,
            'expires_at' => Carbon::now()->addDays(7),
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->patchJson("/api/v1/users/{$student->id}/roles/{$asignacion->id}", [
            'expires_at' => null,
        ])->assertSuccessful();

        $this->assertNull($asignacion->fresh()->expires_at);
    }

    public function test_una_sobreescritura_temporal_puede_hacerse_permanente(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $override = PermissionOverride::create([
            'user_id' => $student->id,
            'permission_id' => Permission::where('subject', 'labs')->where('action', 'create')->firstOrFail()->id,
            'type' => PermissionOverride::TYPE_GRANT,
            'granted_by' => $admin->id,
            'expires_at' => Carbon::now()->addDays(3),
        ]);

        $this->actingAs($admin, 'sanctum');

        // El array_filter descartaba los null despues de calcularlos, asi que
        // el array_key_exists no servia de nada: la API respondia 200 y el
        // valor no cambiaba.
        $this->patchJson("/api/v1/users/{$student->id}/permission-overrides/{$override->id}", [
            'expires_at' => null,
        ])->assertSuccessful();

        $this->assertNull($override->fresh()->expires_at);
    }

    public function test_la_descripcion_de_un_rol_puede_vaciarse(): void
    {
        $admin = User::factory()->admin()->create();
        $rol = Role::create([
            'name' => 'auxiliar',
            'display_name' => 'Auxiliar',
            'description' => 'Texto que sobra',
            'color' => 'gray',
            'is_system' => false,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'sanctum');

        $this->patchJson("/api/v1/roles/{$rol->id}", ['description' => null])
            ->assertSuccessful();

        $this->assertNull($rol->fresh()->description);
    }
}
