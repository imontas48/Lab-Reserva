<?php

namespace Tests\Feature\Attendance;

use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CheckInTest extends TestCase
{
    /**
     * Reserva confirmada con codigo que empieza en 5 minutos: dentro de la
     * ventana de check-in (se abre 10 minutos antes).
     */
    private function startingSoon(User $owner): Reservation
    {
        return Reservation::factory()
            ->forUser($owner)
            ->between(now()->addMinutes(5), now()->addMinutes(65))
            ->confirmed()
            ->withCheckIn('ABC123')
            ->create();
    }

    public function test_las_reservas_creadas_por_la_api_llevan_codigo_visible_solo_para_el_dueño(): void
    {
        $student = $this->actingAsStudent();

        $response = $this->postJson('/api/v1/reservations', [
            'equipment_id' => Equipment::factory()->create()->id,
            'start_time' => Carbon::tomorrow()->setTimeFromTimeString('10:00:00')->toIso8601String(),
            'end_time' => Carbon::tomorrow()->setTimeFromTimeString('11:00:00')->toIso8601String(),
        ])->assertCreated()->assertJsonPath('data.requires_check_in', true);

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $response->json('data.check_in_code'));

        // Un tercero ve la ocupacion del equipo, pero no el codigo.
        $this->actingAsStudent();
        $this->getJson("/api/v1/equipment/{$response->json('data.equipment_id')}/reservations")
            ->assertOk()
            ->assertJsonPath('data.0.check_in_code', null);
    }

    public function test_el_dueño_registra_su_llegada_con_el_codigo(): void
    {
        $student = $this->actingAsStudent();
        $reserva = $this->startingSoon($student);

        $this->postJson("/api/v1/reservations/{$reserva->id}/check-in", ['code' => 'abc123'])
            ->assertOk()
            ->assertJsonPath('data.can_check_in', false);

        $this->assertNotNull($reserva->fresh()->checked_in_at);
    }

    public function test_un_codigo_incorrecto_se_rechaza(): void
    {
        $student = $this->actingAsStudent();
        $reserva = $this->startingSoon($student);

        $this->postJson("/api/v1/reservations/{$reserva->id}/check-in", ['code' => 'XXXXXX'])->assertStatus(422);
        $this->assertNull($reserva->fresh()->checked_in_at);
    }

    public function test_fuera_de_la_ventana_no_se_puede_hacer_check_in(): void
    {
        $student = $this->actingAsStudent();

        $pronto = Reservation::factory()->forUser($student)->future()->withCheckIn()->create();
        $this->postJson("/api/v1/reservations/{$pronto->id}/check-in", ['code' => 'ABC123'])->assertStatus(422);

        $tarde = Reservation::factory()->forUser($student)
            ->between(now()->subMinutes(30), now()->addMinutes(30))->confirmed()->withCheckIn()->create();
        $this->postJson("/api/v1/reservations/{$tarde->id}/check-in", ['code' => 'ABC123'])->assertStatus(422);
    }

    public function test_otro_usuario_no_puede_hacer_el_check_in_ajeno(): void
    {
        $reserva = $this->startingSoon(User::factory()->student()->create());
        $this->actingAsStudent();

        $this->postJson("/api/v1/reservations/{$reserva->id}/check-in", ['code' => 'ABC123'])->assertForbidden();
    }

    public function test_el_administrador_registra_la_llegada_sin_codigo(): void
    {
        $reserva = $this->startingSoon(User::factory()->student()->create());
        $this->actingAsAdmin();

        $this->postJson("/api/v1/reservations/{$reserva->id}/check-in")->assertOk();
        $this->assertNotNull($reserva->fresh()->checked_in_at);
    }

    public function test_una_reserva_sin_codigo_no_exige_check_in(): void
    {
        $student = $this->actingAsStudent();
        $antigua = Reservation::factory()->forUser($student)
            ->between(now()->addMinutes(5), now()->addMinutes(65))->confirmed()->create();

        $this->getJson("/api/v1/reservations/{$antigua->id}")
            ->assertOk()
            ->assertJsonPath('data.requires_check_in', false)
            ->assertJsonPath('data.can_check_in', false);
    }
}
