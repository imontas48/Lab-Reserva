<?php

namespace Tests\Feature\Users;

use App\Models\Reservation;
use App\Models\User;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    public function test_solo_quien_gestiona_usuarios_ve_el_listado(): void
    {
        User::factory()->count(3)->create();

        $this->actingAsTeacher();
        $this->getJson('/api/v1/users')->assertForbidden();

        // 3 creados + el profesor + el administrador autenticados.
        $this->actingAsAdmin();
        $this->getJson('/api/v1/users')->assertOk()->assertJsonCount(5, 'data');
    }

    public function test_el_listado_filtra_por_rol_bloqueo_y_busqueda(): void
    {
        User::factory()->teacher()->create(['name' => 'Laura Docente']);
        User::factory()->student()->create(['name' => 'Pedro Zzalumno', 'reservation_blocked_until' => now()->addDays(3)]);
        $this->actingAsAdmin();

        $this->getJson('/api/v1/users?role=teacher')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Laura Docente');
        $this->getJson('/api/v1/users?blocked=1')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_blocked', true);
        $this->getJson('/api/v1/users?search=zzalumno')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_el_detalle_incluye_contadores_de_reservas(): void
    {
        $student = User::factory()->student()->create();
        Reservation::factory()->forUser($student)->future()->create();
        Reservation::factory()->forUser($student)->past()->completed()->create();
        $this->actingAsAdmin();

        $this->getJson("/api/v1/users/{$student->id}")
            ->assertOk()
            ->assertJsonPath('data.reservations_count', 2)
            ->assertJsonPath('data.active_reservations_count', 1);
    }

    public function test_el_administrador_cambia_el_rol_base(): void
    {
        $student = User::factory()->student()->create();
        $this->actingAsAdmin();

        $this->patchJson("/api/v1/users/{$student->id}", ['role' => 'teacher'])
            ->assertOk()
            ->assertJsonPath('data.role', 'teacher');

        $this->assertTrue($student->fresh()->hasPermission('reservations', 'createLab'));
    }

    public function test_nadie_cambia_su_propio_rol_ni_degrada_al_ultimo_administrador(): void
    {
        $admin = $this->actingAsAdmin();

        $this->patchJson("/api/v1/users/{$admin->id}", ['role' => 'student'])->assertStatus(422);

        $otroAdmin = User::factory()->admin()->create();
        $this->patchJson("/api/v1/users/{$otroAdmin->id}", ['role' => 'teacher'])->assertOk();

        // Ahora $admin es el unico: otro administrador recien creado no puede degradarlo.
        $this->actingAs(User::factory()->admin()->create(), 'sanctum');
        $this->patchJson("/api/v1/users/{$otroAdmin->id}", ['role' => 'admin'])->assertOk();
    }

    public function test_la_baja_es_logica_y_protege_al_propio_usuario(): void
    {
        $admin = $this->actingAsAdmin();
        $student = User::factory()->student()->create();
        Reservation::factory()->forUser($student)->past()->completed()->create();

        $this->deleteJson("/api/v1/users/{$admin->id}")->assertStatus(422);
        $this->deleteJson("/api/v1/users/{$student->id}")->assertOk();

        $this->assertSoftDeleted('users', ['id' => $student->id]);
        $this->assertDatabaseHas('reservations', ['user_id' => $student->id]);
    }

    public function test_el_administrador_levanta_el_bloqueo_por_inasistencias(): void
    {
        $student = User::factory()->student()->create([
            'no_show_count' => 3,
            'reservation_blocked_until' => now()->addDays(5),
        ]);
        $this->actingAsAdmin();

        $this->patchJson("/api/v1/users/{$student->id}/unblock")
            ->assertOk()
            ->assertJsonPath('data.is_blocked', false)
            ->assertJsonPath('data.no_show_count', 0);
    }
}
