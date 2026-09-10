<?php

namespace Tests\Feature\Attendance;

use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NoShowTest extends TestCase
{
    public function test_el_comando_marca_inasistencia_tras_el_periodo_de_gracia_y_libera_la_franja(): void
    {
        config()->set('lab-reserva.check_in.grace_minutes', 15);
        $inicio = now()->subMinutes(20);
        $fin = now()->addMinutes(40);

        $sinLlegada = Reservation::factory()->between($inicio, $fin)->confirmed()->withCheckIn()->create();
        $conLlegada = Reservation::factory()->between($inicio, $fin)->confirmed()->withCheckIn()->checkedIn()->create();
        $sinCodigo = Reservation::factory()->between($inicio, $fin)->confirmed()->create();
        $reciente = Reservation::factory()->between(now()->subMinutes(5), $fin)->confirmed()->withCheckIn()->create();

        $this->artisan('reservations:complete-expired')->assertSuccessful();

        $this->assertSame(Reservation::STATUS_NO_SHOW, $sinLlegada->fresh()->status);
        $this->assertSame(Reservation::STATUS_CONFIRMED, $conLlegada->fresh()->status);
        $this->assertSame(Reservation::STATUS_CONFIRMED, $sinCodigo->fresh()->status);
        $this->assertSame(Reservation::STATUS_CONFIRMED, $reciente->fresh()->status);

        $this->assertSame(0, Reservation::query()->forEquipment($sinLlegada->equipment_id)->blocking($inicio, $fin)->count());
        $this->assertSame(1, $sinLlegada->user->fresh()->no_show_count);
    }

    public function test_una_reserva_terminada_con_llegada_se_completa(): void
    {
        $reserva = Reservation::factory()->past()->confirmed()->withCheckIn()->checkedIn()->create();

        $this->artisan('reservations:complete-expired')->assertSuccessful();

        $this->assertSame(Reservation::STATUS_COMPLETED, $reserva->fresh()->status);
    }

    public function test_el_administrador_marca_inasistencia_a_mano(): void
    {
        $reserva = Reservation::factory()
            ->between(now()->subMinutes(5), now()->addMinutes(55))->confirmed()->withCheckIn()->create();

        $this->actingAsTeacher();
        $this->postJson("/api/v1/reservations/{$reserva->id}/no-show")->assertForbidden();

        $this->actingAsAdmin();
        $this->postJson("/api/v1/reservations/{$reserva->id}/no-show")
            ->assertOk()
            ->assertJsonPath('data.status', Reservation::STATUS_NO_SHOW);

        $futura = Reservation::factory()->future()->confirmed()->withCheckIn()->create();
        $this->postJson("/api/v1/reservations/{$futura->id}/no-show")->assertStatus(422);
    }

    public function test_tres_inasistencias_en_un_mes_bloquean_las_reservas(): void
    {
        config()->set('lab-reserva.no_show.max_strikes', 3);
        config()->set('lab-reserva.no_show.block_days', 7);

        $student = User::factory()->student()->create();
        Reservation::factory()->count(2)->forUser($student)->past()->noShow()->create();
        $tercera = Reservation::factory()->forUser($student)
            ->between(now()->subMinutes(30), now()->addMinutes(30))->confirmed()->withCheckIn()->create();

        $this->artisan('reservations:complete-expired')->assertSuccessful();

        $bloqueado = $student->fresh();
        $this->assertTrue($bloqueado->isBlockedFromReserving());
        $this->assertEqualsWithDelta(now()->addDays(7)->timestamp, $bloqueado->reservation_blocked_until->timestamp, 60);

        $this->actingAs($bloqueado, 'sanctum');
        $this->postJson('/api/v1/reservations', [
            'equipment_id' => Equipment::factory()->create()->id,
            'start_time' => Carbon::tomorrow()->setTimeFromTimeString('10:00:00')->toIso8601String(),
            'end_time' => Carbon::tomorrow()->setTimeFromTimeString('11:00:00')->toIso8601String(),
        ])->assertStatus(422)->assertJsonValidationErrors(['start_time']);

        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.no_show_count', 1);
    }

    public function test_dos_inasistencias_no_bloquean(): void
    {
        config()->set('lab-reserva.no_show.max_strikes', 3);
        $student = User::factory()->student()->create();
        Reservation::factory()->forUser($student)->past()->noShow()->create();
        Reservation::factory()->forUser($student)
            ->between(now()->subMinutes(30), now()->addMinutes(30))->confirmed()->withCheckIn()->create();

        $this->artisan('reservations:complete-expired')->assertSuccessful();

        $this->assertFalse($student->fresh()->isBlockedFromReserving());
    }
}
