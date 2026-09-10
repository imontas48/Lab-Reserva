<?php

namespace Tests\Feature\Reservations;

use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Reservation;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Cuotas y ventanas de reserva por rol (config/lab-reserva.php).
 */
class QuotaTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function payload(Equipment $equipment, string $day = 'tomorrow', int $hours = 1): array
    {
        $start = Carbon::parse($day)->setTimeFromTimeString('10:00:00');

        return [
            'equipment_id' => $equipment->id,
            'start_time' => $start->toIso8601String(),
            'end_time' => (clone $start)->addHours($hours)->toIso8601String(),
        ];
    }

    public function test_la_cuota_de_reservas_activas_limita_al_estudiante(): void
    {
        config()->set('lab-reserva.reservations.roles.student.max_active', 1);
        $student = $this->actingAsStudent();

        $this->postJson('/api/v1/reservations', $this->payload(Equipment::factory()->create()))
            ->assertCreated();

        $this->postJson('/api/v1/reservations', $this->payload(Equipment::factory()->create()))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);

        $this->assertSame(1, $student->reservations()->count());
    }

    public function test_las_canceladas_y_las_pasadas_no_cuentan_para_la_cuota(): void
    {
        config()->set('lab-reserva.reservations.roles.student.max_active', 1);
        $student = $this->actingAsStudent();

        Reservation::factory()->forUser($student)->future()->cancelled()->create();
        Reservation::factory()->forUser($student)->past()->completed()->create();

        $this->postJson('/api/v1/reservations', $this->payload(Equipment::factory()->create()))
            ->assertCreated();
    }

    public function test_una_solicitud_pendiente_cuenta_para_la_cuota(): void
    {
        config()->set('lab-reserva.reservations.roles.teacher.max_active', 1);
        $teacher = $this->actingAsTeacher();

        Reservation::factory()->forLab(Lab::factory()->create())->forUser($teacher)->future()->create();

        $this->postJson('/api/v1/reservations', $this->payload(Equipment::factory()->create()))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);
    }

    public function test_la_antelacion_maxima_depende_del_rol(): void
    {
        config()->set('lab-reserva.reservations.roles.student.max_advance_days', 7);
        config()->set('lab-reserva.reservations.roles.teacher.max_advance_days', 60);

        $equipment = Equipment::factory()->create();

        $this->actingAsStudent();
        $this->postJson('/api/v1/reservations', $this->payload($equipment, '+10 days'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);

        $this->actingAsTeacher();
        $this->postJson('/api/v1/reservations', $this->payload($equipment, '+10 days'))
            ->assertCreated();
    }

    public function test_la_duracion_maxima_depende_del_rol(): void
    {
        config()->set('lab-reserva.reservations.roles.student.max_hours', 4);
        config()->set('lab-reserva.reservations.roles.teacher.max_hours', 8);

        $this->actingAsStudent();
        $this->postJson('/api/v1/reservations', $this->payload(Equipment::factory()->create(), 'tomorrow', 5))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_time']);

        $this->actingAsTeacher();
        $this->postJson('/api/v1/reservations', $this->payload(Equipment::factory()->create(), 'tomorrow', 5))
            ->assertCreated();
    }

    public function test_un_limite_nulo_significa_sin_limite(): void
    {
        config()->set('lab-reserva.reservations.roles.admin.max_active', null);
        $admin = $this->actingAsAdmin();

        Reservation::factory()->count(5)->forUser($admin)->future()->confirmed()->create();

        $this->postJson('/api/v1/reservations', $this->payload(Equipment::factory()->create()))
            ->assertCreated();
    }
}
