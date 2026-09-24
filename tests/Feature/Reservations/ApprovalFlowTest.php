<?php

namespace Tests\Feature\Reservations;

use App\Models\Lab;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Ciclo de vida de una solicitud de laboratorio completo: aprobar, rechazar,
 * retirar, y quien puede hacer cada cosa.
 */
class ApprovalFlowTest extends TestCase
{
    private function solicitud(?User $teacher = null): Reservation
    {
        return Reservation::factory()
            ->forLab(Lab::factory()->create())
            ->forUser($teacher ?? User::factory()->teacher()->create())
            ->future()
            ->create();
    }

    public function test_el_administrador_aprueba_una_solicitud(): void
    {
        $solicitud = $this->solicitud();
        $admin = $this->actingAsAdmin();

        $this->patchJson("/api/v1/reservations/{$solicitud->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', Reservation::STATUS_CONFIRMED)
            ->assertJsonPath('data.reviewer.id', $admin->id)
            ->assertJsonPath('data.is_pending', false);

        $fresh = $solicitud->fresh();
        $this->assertSame($admin->id, $fresh->reviewed_by);
        $this->assertNotNull($fresh->reviewed_at);
    }

    public function test_el_administrador_rechaza_con_motivo(): void
    {
        $solicitud = $this->solicitud();
        $admin = $this->actingAsAdmin();

        $this->patchJson("/api/v1/reservations/{$solicitud->id}/reject", [
            'reason' => 'El laboratorio está en mantenimiento ese día.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', Reservation::STATUS_REJECTED)
            ->assertJsonPath('data.rejection_reason', 'El laboratorio está en mantenimiento ese día.')
            ->assertJsonPath('data.reviewer.id', $admin->id);
    }

    public function test_rechazar_sin_motivo_es_un_error_de_validacion(): void
    {
        $solicitud = $this->solicitud();
        $this->actingAsAdmin();

        $this->patchJson("/api/v1/reservations/{$solicitud->id}/reject", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        $this->assertSame(Reservation::STATUS_PENDING, $solicitud->fresh()->status);
    }

    public function test_no_se_puede_aprobar_lo_que_no_esta_pendiente(): void
    {
        $this->actingAsAdmin();

        $confirmada = Reservation::factory()->forLab(Lab::factory()->create())->confirmed()->future()->create();
        $rechazada = Reservation::factory()->forLab(Lab::factory()->create())->rejected()->future()->create();

        $this->patchJson("/api/v1/reservations/{$confirmada->id}/approve")->assertStatus(422);
        $this->patchJson("/api/v1/reservations/{$rechazada->id}/approve")->assertStatus(422);
        $this->patchJson("/api/v1/reservations/{$confirmada->id}/reject", ['reason' => 'tarde'])->assertStatus(422);
    }

    public function test_no_se_aprueba_una_solicitud_cuya_franja_ya_comenzo(): void
    {
        $this->actingAsAdmin();

        $solicitud = Reservation::factory()
            ->forLab(Lab::factory()->create())
            ->between(Carbon::now()->subHour(), Carbon::now()->addHour())
            ->create();

        $this->patchJson("/api/v1/reservations/{$solicitud->id}/approve")->assertStatus(422);
        $this->assertSame(Reservation::STATUS_PENDING, $solicitud->fresh()->status);
    }

    public function test_profesores_y_estudiantes_no_pueden_aprobar_ni_rechazar(): void
    {
        $solicitud = $this->solicitud();

        $this->actingAsTeacher();
        $this->patchJson("/api/v1/reservations/{$solicitud->id}/approve")->assertForbidden();
        $this->patchJson("/api/v1/reservations/{$solicitud->id}/reject", ['reason' => 'no'])->assertForbidden();

        $this->actingAsStudent();
        $this->patchJson("/api/v1/reservations/{$solicitud->id}/approve")->assertForbidden();

        $this->assertSame(Reservation::STATUS_PENDING, $solicitud->fresh()->status);
    }

    /**
     * Resolver una solicitud tiene autor y motivo: no puede hacerse por el
     * PATCH generico, y menos por el propio solicitante.
     */
    public function test_el_solicitante_no_puede_confirmarse_su_propia_solicitud(): void
    {
        $teacher = $this->actingAsTeacher();
        $solicitud = $this->solicitud($teacher);

        $this->patchJson("/api/v1/reservations/{$solicitud->id}", [
            'status' => Reservation::STATUS_CONFIRMED,
        ])->assertStatus(422);

        $this->assertSame(Reservation::STATUS_PENDING, $solicitud->fresh()->status);
    }

    public function test_el_solicitante_puede_retirar_su_solicitud(): void
    {
        $teacher = $this->actingAsTeacher();
        $solicitud = $this->solicitud($teacher);

        $this->patchJson("/api/v1/reservations/{$solicitud->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', Reservation::STATUS_CANCELLED);
    }

    public function test_otro_usuario_no_puede_retirar_una_solicitud_ajena(): void
    {
        $solicitud = $this->solicitud();
        $this->actingAsTeacher();

        $this->patchJson("/api/v1/reservations/{$solicitud->id}/cancel")->assertForbidden();
    }

    public function test_la_cola_de_pendientes_es_solo_para_quien_aprueba(): void
    {
        $this->solicitud();
        $this->solicitud();
        Reservation::factory()->forLab(Lab::factory()->create())->confirmed()->future()->create();

        $this->actingAsTeacher();
        $this->getJson('/api/v1/reservations/pending')->assertForbidden();

        $this->actingAsAdmin();
        $this->getJson('/api/v1/reservations/pending')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.status', Reservation::STATUS_PENDING);
    }

    public function test_tras_el_rechazo_la_franja_vuelve_a_estar_libre(): void
    {
        $lab = Lab::factory()->create();
        $solicitud = Reservation::factory()->forLab($lab)->future()->create();

        $this->actingAsAdmin();
        $this->patchJson("/api/v1/reservations/{$solicitud->id}/reject", ['reason' => 'Sin cupo'])->assertOk();

        $this->assertSame(
            0,
            Reservation::query()->forLab($lab->id)->blocking($solicitud->start_time, $solicitud->end_time)->count()
        );
    }
}
