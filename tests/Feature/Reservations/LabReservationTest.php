<?php

namespace Tests\Feature\Reservations;

use App\Models\Lab;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Reserva de laboratorio completo: quien puede pedirla, con que datos y en
 * que estado nace.
 */
class LabReservationTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function payload(Lab $lab, array $overrides = []): array
    {
        return array_merge([
            'lab_id' => $lab->id,
            'start_time' => Carbon::tomorrow()->setTimeFromTimeString('10:00:00')->toIso8601String(),
            'end_time' => Carbon::tomorrow()->setTimeFromTimeString('12:00:00')->toIso8601String(),
            'purpose' => 'Práctica de redes, grupo 3B',
        ], $overrides);
    }

    public function test_un_profesor_crea_una_solicitud_pendiente(): void
    {
        $lab = Lab::factory()->create();
        $teacher = $this->actingAsTeacher();

        $response = $this->postJson('/api/v1/lab-reservations', $this->payload($lab));

        $response->assertCreated()
            ->assertJsonPath('data.type', Reservation::TYPE_LAB)
            ->assertJsonPath('data.status', Reservation::STATUS_PENDING)
            ->assertJsonPath('data.is_pending', true)
            ->assertJsonPath('data.lab_id', $lab->id)
            ->assertJsonPath('data.equipment_id', null)
            ->assertJsonPath('data.purpose', 'Práctica de redes, grupo 3B')
            ->assertJsonPath('data.lab.name', $lab->name);

        $this->assertDatabaseHas('reservations', [
            'user_id' => $teacher->id,
            'type' => Reservation::TYPE_LAB,
            'lab_id' => $lab->id,
            'equipment_id' => null,
            'status' => Reservation::STATUS_PENDING,
            'reviewed_by' => null,
        ]);
    }

    /**
     * Obligar al administrador a solicitarse y aprobarse en dos pasos no
     * protege nada.
     */
    public function test_un_administrador_crea_la_reserva_ya_confirmada(): void
    {
        $lab = Lab::factory()->create();
        $admin = $this->actingAsAdmin();

        $this->postJson('/api/v1/lab-reservations', $this->payload($lab))
            ->assertCreated()
            ->assertJsonPath('data.status', Reservation::STATUS_CONFIRMED)
            ->assertJsonPath('data.reviewer.id', $admin->id);
    }

    public function test_un_estudiante_no_puede_reservar_un_laboratorio_completo(): void
    {
        $lab = Lab::factory()->create();
        $this->actingAsStudent();

        $this->postJson('/api/v1/lab-reservations', $this->payload($lab))
            ->assertForbidden();

        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_sin_autenticar_no_se_puede_solicitar(): void
    {
        $lab = Lab::factory()->create();

        $this->postJson('/api/v1/lab-reservations', $this->payload($lab))
            ->assertUnauthorized();
    }

    public function test_el_motivo_es_obligatorio(): void
    {
        $lab = Lab::factory()->create();
        $this->actingAsTeacher();

        $this->postJson('/api/v1/lab-reservations', $this->payload($lab, ['purpose' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['purpose']);
    }

    public function test_un_laboratorio_inactivo_no_se_puede_reservar(): void
    {
        $lab = Lab::factory()->inactive()->create();
        $this->actingAsTeacher();

        $this->postJson('/api/v1/lab-reservations', $this->payload($lab))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lab_id']);
    }

    public function test_dos_clases_consecutivas_en_el_mismo_laboratorio_no_chocan(): void
    {
        $lab = Lab::factory()->create();
        $this->actingAsTeacher();

        $this->postJson('/api/v1/lab-reservations', $this->payload($lab))->assertCreated();

        $this->postJson('/api/v1/lab-reservations', $this->payload($lab, [
            'start_time' => Carbon::tomorrow()->setTimeFromTimeString('12:00:00')->toIso8601String(),
            'end_time' => Carbon::tomorrow()->setTimeFromTimeString('14:00:00')->toIso8601String(),
        ]))->assertCreated();
    }

    public function test_dos_clases_solapadas_en_el_mismo_laboratorio_chocan(): void
    {
        $lab = Lab::factory()->create();
        $this->actingAsTeacher();

        $this->postJson('/api/v1/lab-reservations', $this->payload($lab))->assertCreated();

        $this->postJson('/api/v1/lab-reservations', $this->payload($lab, [
            'start_time' => Carbon::tomorrow()->setTimeFromTimeString('11:00:00')->toIso8601String(),
            'end_time' => Carbon::tomorrow()->setTimeFromTimeString('13:00:00')->toIso8601String(),
        ]))->assertStatus(422)->assertJsonValidationErrors(['lab_id']);

        $this->assertDatabaseCount('reservations', 1);
    }

    public function test_el_laboratorio_expone_su_ocupacion_para_el_calendario(): void
    {
        $lab = Lab::factory()->create();
        Reservation::factory()->forLab($lab)->confirmed()->future()->create();

        $this->actingAsStudent();

        $this->getJson("/api/v1/labs/{$lab->id}/reservations")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', Reservation::TYPE_LAB);
    }
}
