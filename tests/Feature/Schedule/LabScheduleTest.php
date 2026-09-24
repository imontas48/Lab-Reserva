<?php

namespace Tests\Feature\Schedule;

use App\Models\Equipment;
use App\Models\Lab;
use App\Models\LabClosure;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Horario de apertura y cierres: gestion y cumplimiento al reservar.
 */
class LabScheduleTest extends TestCase
{
    /**
     * Manana a la hora dada, en el huso de la aplicacion.
     */
    private function tomorrowAt(string $time): Carbon
    {
        return Carbon::tomorrow()->setTimeFromTimeString($time);
    }

    /**
     * @return array<string, mixed>
     */
    private function equipmentPayload(Equipment $equipment, string $from, string $to): array
    {
        return [
            'equipment_id' => $equipment->id,
            'start_time' => $this->tomorrowAt($from)->toIso8601String(),
            'end_time' => $this->tomorrowAt($to)->toIso8601String(),
        ];
    }

    private function openTomorrow(Lab $lab, string $opens, string $closes): void
    {
        $lab->openingHours()->create([
            'weekday' => Carbon::tomorrow()->dayOfWeek,
            'opens_at' => $opens,
            'closes_at' => $closes,
        ]);
    }

    public function test_sin_horario_configurado_no_hay_restriccion(): void
    {
        $equipment = Equipment::factory()->create();
        $this->actingAsStudent();

        $this->postJson('/api/v1/reservations', $this->equipmentPayload($equipment, '06:00', '07:00'))
            ->assertCreated();
    }

    public function test_no_se_reserva_fuera_del_horario_de_apertura(): void
    {
        $lab = Lab::factory()->create();
        $equipment = Equipment::factory()->inLab($lab)->create();
        $this->openTomorrow($lab, '08:00:00', '18:00:00');
        $this->actingAsStudent();

        $this->postJson('/api/v1/reservations', $this->equipmentPayload($equipment, '07:00', '09:00'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['equipment_id']);

        $this->postJson('/api/v1/reservations', $this->equipmentPayload($equipment, '17:00', '19:00'))
            ->assertStatus(422);

        $this->postJson('/api/v1/reservations', $this->equipmentPayload($equipment, '08:00', '10:00'))
            ->assertCreated();
    }

    public function test_un_dia_sin_horario_esta_cerrado(): void
    {
        $lab = Lab::factory()->create();
        $equipment = Equipment::factory()->inLab($lab)->create();
        // Horario solo para otro dia de la semana: manana no abre.
        $lab->openingHours()->create([
            'weekday' => (Carbon::tomorrow()->dayOfWeek + 1) % 7,
            'opens_at' => '08:00:00',
            'closes_at' => '18:00:00',
        ]);
        $this->actingAsStudent();

        $this->postJson('/api/v1/reservations', $this->equipmentPayload($equipment, '09:00', '10:00'))
            ->assertStatus(422);
    }

    public function test_un_cierre_global_bloquea_todos_los_laboratorios(): void
    {
        $equipment = Equipment::factory()->create();
        LabClosure::factory()->between($this->tomorrowAt('00:00'), $this->tomorrowAt('23:59:59'))->create();
        $this->actingAsStudent();

        $this->postJson('/api/v1/reservations', $this->equipmentPayload($equipment, '09:00', '10:00'))
            ->assertStatus(422);
    }

    public function test_un_cierre_de_otro_laboratorio_no_afecta(): void
    {
        $equipment = Equipment::factory()->create();
        LabClosure::factory()
            ->forLab(Lab::factory()->create())
            ->between($this->tomorrowAt('00:00'), $this->tomorrowAt('23:59:59'))
            ->create();
        $this->actingAsStudent();

        $this->postJson('/api/v1/reservations', $this->equipmentPayload($equipment, '09:00', '10:00'))
            ->assertCreated();
    }

    public function test_el_cierre_tambien_bloquea_las_clases(): void
    {
        $lab = Lab::factory()->create();
        LabClosure::factory()->forLab($lab)->between($this->tomorrowAt('09:00'), $this->tomorrowAt('11:00'))->create();
        $this->actingAsTeacher();

        $this->postJson('/api/v1/lab-reservations', [
            'lab_id' => $lab->id,
            'purpose' => 'Clase de prueba',
            'start_time' => $this->tomorrowAt('10:00')->toIso8601String(),
            'end_time' => $this->tomorrowAt('12:00')->toIso8601String(),
        ])->assertStatus(422)->assertJsonValidationErrors(['lab_id']);
    }

    public function test_el_administrador_configura_el_horario_y_cualquiera_lo_consulta(): void
    {
        $lab = Lab::factory()->create();
        $this->actingAsAdmin();

        $this->putJson("/api/v1/labs/{$lab->id}/opening-hours", [
            'hours' => [
                ['weekday' => 1, 'opens_at' => '08:00', 'closes_at' => '20:00'],
                ['weekday' => 2, 'opens_at' => '08:00', 'closes_at' => '14:00'],
            ],
        ])->assertOk()->assertJsonCount(2, 'data');

        $this->putJson("/api/v1/labs/{$lab->id}/opening-hours", [
            'hours' => [['weekday' => 1, 'opens_at' => '10:00', 'closes_at' => '09:00']],
        ])->assertStatus(422);

        $this->actingAsStudent();

        $this->getJson("/api/v1/labs/{$lab->id}/schedule")
            ->assertOk()
            ->assertJsonCount(2, 'data.opening_hours')
            ->assertJsonPath('data.opening_hours.0.weekday_name', 'lunes')
            ->assertJsonPath('data.opening_hours.0.closes_at', '20:00');
    }

    public function test_solo_quien_gestiona_el_horario_puede_modificarlo(): void
    {
        $lab = Lab::factory()->create();

        $this->actingAsTeacher();
        $this->putJson("/api/v1/labs/{$lab->id}/opening-hours", ['hours' => []])->assertForbidden();
        $this->postJson('/api/v1/closures', [
            'starts_at' => $this->tomorrowAt('08:00')->toIso8601String(),
            'ends_at' => $this->tomorrowAt('12:00')->toIso8601String(),
            'reason' => 'Mantenimiento',
        ])->assertForbidden();

        $this->actingAsStudent();
        $this->getJson('/api/v1/closures')->assertOk();
    }

    public function test_el_administrador_crea_y_elimina_cierres(): void
    {
        $lab = Lab::factory()->create();
        $this->actingAsAdmin();

        $response = $this->postJson('/api/v1/closures', [
            'lab_id' => $lab->id,
            'starts_at' => $this->tomorrowAt('08:00')->toIso8601String(),
            'ends_at' => $this->tomorrowAt('12:00')->toIso8601String(),
            'reason' => 'Mantenimiento de red',
        ])->assertCreated()->assertJsonPath('data.lab_name', $lab->name);

        $this->postJson('/api/v1/closures', [
            'starts_at' => $this->tomorrowAt('12:00')->toIso8601String(),
            'ends_at' => $this->tomorrowAt('08:00')->toIso8601String(),
            'reason' => 'Al revés',
        ])->assertStatus(422)->assertJsonValidationErrors(['ends_at']);

        $this->deleteJson("/api/v1/closures/{$response->json('data.id')}")->assertOk();
        $this->assertDatabaseCount('lab_closures', 0);
    }

    public function test_el_estado_del_equipo_refleja_una_clase_en_curso(): void
    {
        $lab = Lab::factory()->create();
        $equipment = Equipment::factory()->inLab($lab)->create();
        Reservation::factory()
            ->forLab($lab)
            ->confirmed()
            ->between(Carbon::now()->subMinutes(30), Carbon::now()->addMinutes(30))
            ->create();

        $this->actingAsStudent();

        $this->getJson("/api/v1/equipment/{$equipment->id}")
            ->assertOk()
            ->assertJsonPath('data.status.status', 'in_use')
            ->assertJsonPath('data.status.color', 'purple');
    }
}
