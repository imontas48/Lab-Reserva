<?php

namespace Tests\Feature\Security;

use App\Models\ActivityLog;
use App\Models\Lab;
use App\Models\Permission;
use App\Models\PermissionOverride;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\ReservationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use LogicException;
use Tests\TestCase;

/**
 * Registro de actividades: que usuario hizo que, para cualquier escritura, y
 * sin que nadie pueda borrarlo ni editarlo desde la API.
 */
class ActivityLogTest extends TestCase
{
    /**
     * @return Collection<int, ActivityLog>
     */
    private function logsFor(string $subjectType, ?int $subjectId = null)
    {
        return ActivityLog::query()
            ->where('subject_type', $subjectType)
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->orderBy('id')
            ->get();
    }

    public function test_crear_editar_y_borrar_un_laboratorio_queda_registrado_con_su_autor(): void
    {
        $admin = $this->actingAsAdmin();

        $id = $this->postJson('/api/v1/labs', ['name' => 'Lab Norte', 'location' => 'A', 'capacity' => 10])
            ->assertCreated()
            ->json('data.id');
        $this->putJson("/api/v1/labs/{$id}", ['name' => 'Lab Sur', 'location' => 'A', 'capacity' => 10])->assertOk();
        $this->deleteJson("/api/v1/labs/{$id}")->assertSuccessful();

        $logs = $this->logsFor('lab', $id);

        $this->assertSame(['created', 'updated', 'deleted'], $logs->pluck('event')->all());
        $this->assertTrue($logs->every(fn (ActivityLog $log) => $log->actor_id === $admin->id
            && $log->actor_email === $admin->email
            && $log->source === ActivityLog::SOURCE_WEB
            && $log->ip_address !== null));
        $this->assertSame(['name' => 'Lab Norte'], $logs[1]->properties['old']);
        $this->assertSame(['name' => 'Lab Sur'], $logs[1]->properties['new']);
        $this->assertSame('«Lab Sur»', $logs[2]->subject_label);
    }

    public function test_aprobar_una_reserva_registra_el_cambio_de_estado(): void
    {
        $solicitud = Reservation::factory()
            ->forLab(Lab::factory()->create())
            ->forUser(User::factory()->teacher()->create())
            ->future()
            ->create();
        $admin = $this->actingAsAdmin();

        $this->patchJson("/api/v1/reservations/{$solicitud->id}/approve")->assertOk();

        $log = $this->logsFor('reservation', $solicitud->id)->firstWhere('event', 'updated');

        $this->assertSame($admin->id, $log->actor_id);
        $this->assertSame(Reservation::STATUS_PENDING, $log->properties['old']['status']);
        $this->assertSame(Reservation::STATUS_CONFIRMED, $log->properties['new']['status']);
        // El codigo de asistencia nace con la aprobacion y no se guarda en claro.
        $this->assertSame(ActivityLogService::REDACTED, $log->properties['new']['check_in_code']);
    }

    public function test_cambiar_la_contrasena_queda_registrado_sin_el_hash(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Anterior2026')]);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile/password', [
            'current_password' => 'Anterior2026',
            'password' => 'Definitiva2026',
            'password_confirmation' => 'Definitiva2026',
        ])->assertOk();

        $log = $this->logsFor('user', $user->id)->firstWhere('event', 'updated');

        $this->assertSame($user->id, $log->actor_id);
        $this->assertSame(ActivityLogService::REDACTED, $log->properties['new']['password']);
        $this->assertSame(ActivityLogService::REDACTED, $log->properties['old']['password']);
        $this->assertStringNotContainsString('$2y$', json_encode($log->properties));
    }

    public function test_el_inicio_de_sesion_y_los_intentos_fallidos_quedan_registrados(): void
    {
        $user = User::factory()->create(['password' => Hash::make('la-buena')]);

        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'la-mala'])->assertStatus(422);
        $this->postJson('/api/v1/login', ['email' => 'nadie@lab-reserva.test', 'password' => 'x'])->assertStatus(422);
        $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'la-buena'])->assertOk();

        $failed = ActivityLog::where('event', 'login_failed')->orderBy('id')->get();
        $this->assertCount(2, $failed);
        $this->assertNull($failed[0]->actor_id);
        $this->assertSame($user->id, $failed[0]->subject_id);
        $this->assertSame('nadie@lab-reserva.test', $failed[1]->properties['email']);
        $this->assertStringNotContainsString('la-mala', json_encode($failed->pluck('properties')));

        $login = ActivityLog::where('event', 'login')->sole();
        $this->assertSame($user->id, $login->actor_id);
    }

    public function test_el_registro_publico_tiene_como_autor_al_propio_usuario(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Nueva',
            'email' => 'nueva@lab-reserva.test',
            'password' => 'contrasena-larga',
            'password_confirmation' => 'contrasena-larga',
        ])->assertCreated();

        $user = User::where('email', 'nueva@lab-reserva.test')->sole();
        $log = $this->logsFor('user', $user->id)->firstWhere('event', 'created');

        $this->assertSame($user->id, $log->actor_id);
        $this->assertSame(ActivityLogService::REDACTED, $log->properties['attributes']['password']);
    }

    public function test_los_cambios_de_permisos_de_un_rol_se_registran_en_un_solo_evento(): void
    {
        $role = Role::create(['name' => 'monitor', 'display_name' => 'Monitor', 'is_system' => false]);
        $permissions = Permission::where('subject', 'labs')->whereIn('action', ['viewAny', 'view'])->pluck('id');
        $this->actingAsAdmin();

        $this->postJson("/api/v1/roles/{$role->id}/permissions", ['permission_ids' => $permissions->all()])->assertOk();

        $log = ActivityLog::where('event', 'permissions_synced')->sole();
        $this->assertSame($role->id, $log->subject_id);
        $this->assertEqualsCanonicalizing(['labs.view', 'labs.viewAny'], $log->properties['added']);
        $this->assertSame([], $log->properties['removed']);
    }

    public function test_el_horario_de_apertura_se_registra_como_un_solo_evento(): void
    {
        $lab = Lab::factory()->create();
        $this->actingAsAdmin();

        $this->putJson("/api/v1/labs/{$lab->id}/opening-hours", ['hours' => [
            ['weekday' => 1, 'opens_at' => '08:00', 'closes_at' => '12:00'],
            ['weekday' => 2, 'opens_at' => '08:00', 'closes_at' => '12:00'],
        ]])->assertOk();

        $log = ActivityLog::where('event', 'opening_hours_synced')->sole();
        $this->assertSame(['lunes 08:00-12:00', 'martes 08:00-12:00'], $log->properties['new']);
    }

    public function test_los_cambios_masivos_del_programador_quedan_a_nombre_del_sistema(): void
    {
        $vencida = Reservation::factory()->confirmed()->past()->create();

        $this->assertSame(1, app(ReservationService::class)->markExpiredReservationsAsCompleted());

        $log = ActivityLog::where('event', 'reservations_auto_completed')->sole();
        $this->assertNull($log->actor_id);
        $this->assertSame(ActivityLog::SOURCE_CONSOLE, $log->source);
        $this->assertSame([$vencida->id], $log->properties['reservation_ids']);

        // Sin filas que tocar no se registra nada.
        app(ReservationService::class)->markExpiredReservationsAsCompleted();
        $this->assertSame(1, ActivityLog::where('event', 'reservations_auto_completed')->count());
    }

    public function test_solo_el_administrador_puede_consultar_el_registro(): void
    {
        $this->actingAsTeacher();
        $this->getJson('/api/v1/activity-logs')->assertForbidden();

        $this->actingAsStudent();
        $this->getJson('/api/v1/activity-logs')->assertForbidden();

        $this->actingAsAdmin();
        $this->getJson('/api/v1/activity-logs')->assertOk();
    }

    public function test_no_se_puede_conceder_con_una_excepcion_de_permisos(): void
    {
        $teacher = User::factory()->teacher()->create();

        // Ni con todos los permisos del catalogo concedidos uno a uno.
        foreach (Permission::all() as $permission) {
            PermissionOverride::create([
                'user_id' => $teacher->id,
                'permission_id' => $permission->id,
                'type' => PermissionOverride::TYPE_GRANT,
            ]);
        }

        Sanctum::actingAs($teacher);
        $this->getJson('/api/v1/activity-logs')->assertForbidden();
    }

    public function test_la_api_no_permite_editar_ni_borrar_registros(): void
    {
        $this->actingAsAdmin();
        Lab::factory()->create();
        $log = ActivityLog::latest('id')->firstOrFail();

        // Ni siquiera existe una ruta por registro; sobre el listado, solo GET.
        $this->deleteJson("/api/v1/activity-logs/{$log->id}")->assertNotFound();
        $this->putJson("/api/v1/activity-logs/{$log->id}", ['description' => 'x'])->assertNotFound();
        $this->patchJson("/api/v1/activity-logs/{$log->id}", ['description' => 'x'])->assertNotFound();
        $this->deleteJson('/api/v1/activity-logs')->assertStatus(405);
        $this->postJson('/api/v1/activity-logs', ['description' => 'x'])->assertStatus(405);

        $this->assertSame($log->description, $log->fresh()->description);
    }

    public function test_el_modelo_es_inmutable(): void
    {
        Lab::factory()->create();
        $log = ActivityLog::latest('id')->firstOrFail();

        try {
            $log->update(['description' => 'alterado']);
            $this->fail('Se pudo modificar un registro.');
        } catch (LogicException) {
        }

        try {
            $log->delete();
            $this->fail('Se pudo borrar un registro.');
        } catch (LogicException) {
        }

        $this->assertNotSame('alterado', $log->fresh()->description);
    }

    public function test_el_listado_filtra_por_autor_evento_y_fechas(): void
    {
        $admin = $this->actingAsAdmin();
        $otro = User::factory()->admin()->create();

        Lab::factory()->create();
        Sanctum::actingAs($otro);
        Lab::factory()->create();
        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/activity-logs?actor_id={$otro->id}&event=created&subject_type=lab")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.actor.id', $otro->id);

        $this->getJson('/api/v1/activity-logs?from=2000-01-01&to=2000-01-02')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/v1/activity-logs?event=inventado')->assertStatus(422);
    }
}
