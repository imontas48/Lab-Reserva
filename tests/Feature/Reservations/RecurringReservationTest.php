<?php

namespace Tests\Feature\Reservations;

use App\Models\AcademicPeriod;
use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecurringReservationTest extends TestCase
{
    private function nextMonday(): Carbon
    {
        return Carbon::now()->next(Carbon::MONDAY)->setTimeFromTimeString('08:00:00');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Lab $lab, array $overrides = []): array
    {
        $start = $this->nextMonday();

        return array_merge([
            'lab_id' => $lab->id,
            'purpose' => 'Redes I, grupo A',
            'start_time' => $start->toIso8601String(),
            'end_time' => $start->copy()->addHours(2)->toIso8601String(),
            'repeat_until' => $start->copy()->addWeeks(3)->toDateString(),
        ], $overrides);
    }

    public function test_el_profesor_crea_una_serie_semanal_pendiente(): void
    {
        $lab = Lab::factory()->create();
        $this->actingAsTeacher();

        $response = $this->postJson('/api/v1/lab-reservations/recurring', $this->payload($lab))
            ->assertCreated()
            ->assertJsonCount(4, 'data.created')
            ->assertJsonCount(0, 'data.skipped')
            ->assertJsonPath('data.created.0.status', Reservation::STATUS_PENDING);

        $group = $response->json('data.recurrence_group');
        $this->assertSame(4, Reservation::query()->inRecurrenceGroup($group)->count());

        $dias = Reservation::query()->inRecurrenceGroup($group)->orderBy('start_time')->pluck('start_time')
            ->map(fn ($d) => $d->dayOfWeek)->unique()->all();
        $this->assertSame([Carbon::MONDAY], $dias);
    }

    public function test_varios_dias_por_semana(): void
    {
        $lab = Lab::factory()->create();
        $this->actingAsAdmin();

        $this->postJson('/api/v1/lab-reservations/recurring', $this->payload($lab, [
            'weekdays' => [Carbon::MONDAY, Carbon::WEDNESDAY],
            'repeat_until' => $this->nextMonday()->addWeeks(1)->addDays(3)->toDateString(),
        ]))->assertCreated()->assertJsonCount(4, 'data.created')->assertJsonPath('data.created.0.status', Reservation::STATUS_CONFIRMED);
    }

    public function test_las_ocurrencias_en_conflicto_se_omiten_y_se_informan(): void
    {
        $lab = Lab::factory()->create();
        $equipment = Equipment::factory()->inLab($lab)->create();
        $segundoLunes = $this->nextMonday()->addWeek();
        Reservation::factory()->forEquipment($equipment)->confirmed()
            ->between($segundoLunes, $segundoLunes->copy()->addHour())->create();

        $this->actingAsTeacher();

        $this->postJson('/api/v1/lab-reservations/recurring', $this->payload($lab))
            ->assertCreated()
            ->assertJsonCount(3, 'data.created')
            ->assertJsonCount(1, 'data.skipped')
            ->assertJsonPath('data.skipped.0.date', $segundoLunes->toDateString());
    }

    public function test_si_ninguna_fecha_esta_libre_no_se_crea_nada(): void
    {
        $lab = Lab::factory()->create();
        $start = $this->nextMonday();
        Reservation::factory()->forLab($lab)->confirmed()->between($start, $start->copy()->addHours(2))->create();

        $this->actingAsTeacher();

        $this->postJson('/api/v1/lab-reservations/recurring', $this->payload($lab, [
            'repeat_until' => $start->toDateString(),
        ]))->assertStatus(422);

        $this->assertSame(1, Reservation::count());
    }

    public function test_la_serie_no_supera_el_periodo_academico_vigente(): void
    {
        $lab = Lab::factory()->create();
        AcademicPeriod::factory()->create([
            'starts_on' => now()->subMonth()->toDateString(),
            'ends_on' => $this->nextMonday()->addWeek()->toDateString(),
        ]);
        $this->actingAsTeacher();

        $this->postJson('/api/v1/lab-reservations/recurring', $this->payload($lab, [
            'repeat_until' => $this->nextMonday()->addWeeks(6)->toDateString(),
        ]))->assertStatus(422)->assertJsonValidationErrors(['repeat_until']);
    }

    public function test_un_estudiante_no_puede_crear_series(): void
    {
        $this->actingAsStudent();

        $this->postJson('/api/v1/lab-reservations/recurring', $this->payload(Lab::factory()->create()))
            ->assertForbidden();
    }

    public function test_cancelar_la_serie_solo_afecta_a_las_ocurrencias_futuras(): void
    {
        $teacher = $this->actingAsTeacher();
        $lab = Lab::factory()->create();
        $group = (string) Str::uuid();

        $pasada = Reservation::factory()->forLab($lab)->forUser($teacher)->confirmed()->past()->inSeries($group)->create();
        $futura1 = Reservation::factory()->forLab($lab)->forUser($teacher)->confirmed()->future()->inSeries($group)->create();
        $futura2 = Reservation::factory()->forLab($lab)->forUser($teacher)
            ->between(now()->addWeek(), now()->addWeek()->addHour())->inSeries($group)->create();

        $this->patchJson("/api/v1/reservations/{$futura1->id}/cancel-series")
            ->assertOk()
            ->assertJsonPath('data.cancelled', 2);

        $this->assertSame(Reservation::STATUS_CONFIRMED, $pasada->fresh()->status);
        $this->assertSame(Reservation::STATUS_CANCELLED, $futura1->fresh()->status);
        $this->assertSame(Reservation::STATUS_CANCELLED, $futura2->fresh()->status);
    }
}
