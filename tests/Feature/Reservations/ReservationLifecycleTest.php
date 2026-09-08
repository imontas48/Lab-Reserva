<?php

namespace Tests\Feature\Reservations;

use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReservationLifecycleTest extends TestCase
{
    /**
     * UpdateReservationRequest aceptaba cualquiera de los tres estados y
     * ReservationPolicy::update autoriza al dueño, mientras que
     * updateReservation hacia un update() directo sin comprobar nada. Un
     * usuario podia cancelar su reserva, esperar a que otro ocupase la franja y
     * volver a confirmarla: dos reservas solapadas sin pasar por ninguna
     * comprobacion de disponibilidad.
     */
    public function test_una_reserva_cancelada_no_puede_reactivarse(): void
    {
        $duenio = $this->actingAsStudent();
        $reserva = Reservation::factory()
            ->forUser($duenio)
            ->cancelled()
            ->create();

        $this->patchJson("/api/v1/reservations/{$reserva->id}", [
            'status' => Reservation::STATUS_CONFIRMED,
        ])->assertStatus(422);

        $this->assertSame(Reservation::STATUS_CANCELLED, $reserva->fresh()->status);
    }

    public function test_la_reactivacion_no_puede_usarse_para_robar_una_franja(): void
    {
        $equipment = Equipment::factory()->create();
        $inicio = Carbon::tomorrow()->setTimeFromTimeString('10:00:00');
        $fin = Carbon::tomorrow()->setTimeFromTimeString('11:00:00');

        $primero = $this->actingAsStudent();
        $cancelada = Reservation::factory()
            ->forUser($primero)
            ->forEquipment($equipment)
            ->between($inicio, $fin)
            ->cancelled()
            ->create();

        // Otro usuario ocupa la franja liberada.
        Reservation::factory()
            ->forUser(User::factory()->create())
            ->forEquipment($equipment)
            ->between($inicio, $fin)
            ->confirmed()
            ->create();

        $this->patchJson("/api/v1/reservations/{$cancelada->id}", [
            'status' => Reservation::STATUS_CONFIRMED,
        ])->assertStatus(422);

        $this->assertSame(
            1,
            Reservation::query()->forEquipment($equipment->id)->blocking($inicio, $fin)->count()
        );
    }

    public function test_una_reserva_completada_es_estado_final(): void
    {
        $duenio = $this->actingAsStudent();
        $reserva = Reservation::factory()->forUser($duenio)->completed()->create();

        $this->patchJson("/api/v1/reservations/{$reserva->id}", [
            'status' => Reservation::STATUS_CANCELLED,
        ])->assertStatus(422);
    }

    public function test_confirmada_si_puede_cancelarse(): void
    {
        $duenio = $this->actingAsStudent();
        $reserva = Reservation::factory()->forUser($duenio)->future()->confirmed()->create();

        $this->patchJson("/api/v1/reservations/{$reserva->id}", [
            'status' => Reservation::STATUS_CANCELLED,
        ])->assertOk();

        $this->assertSame(Reservation::STATUS_CANCELLED, $reserva->fresh()->status);
    }

    public function test_no_se_cancela_una_reserva_ya_comenzada(): void
    {
        $duenio = $this->actingAsStudent();
        $reserva = Reservation::factory()->forUser($duenio)->past()->confirmed()->create();

        $this->patchJson("/api/v1/reservations/{$reserva->id}/cancel")
            ->assertStatus(422);
    }

    /**
     * El lock llegaba DESPUES de comprobar la disponibilidad, y ademas sobre la
     * tabla equipment, no sobre reservations. El comentario del codigo decia
     * "esto protege contra race conditions" pero no protegia nada.
     *
     * Una prueba con concurrencia real necesitaria dos procesos; lo que si se
     * puede verificar de forma determinista es el invariante que la hacia
     * fallar: que la fila del equipo se bloquea ANTES de consultar las reservas.
     */
    public function test_el_equipo_se_bloquea_antes_de_comprobar_disponibilidad(): void
    {
        $equipment = Equipment::factory()->create();
        $user = User::factory()->create();

        DB::enableQueryLog();

        app(ReservationService::class)->createReservation([
            'equipment_id' => $equipment->id,
            'start_time' => Carbon::tomorrow()->setTimeFromTimeString('10:00:00'),
            'end_time' => Carbon::tomorrow()->setTimeFromTimeString('11:00:00'),
        ], $user);

        $consultas = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();

        $posicionLock = null;
        $posicionComprobacion = null;

        foreach ($consultas as $i => $sql) {
            if ($posicionLock === null && str_contains($sql, 'equipment') && str_contains($sql, 'for update')) {
                $posicionLock = $i;
            }
            if ($posicionComprobacion === null && str_contains($sql, 'from `reservations`')) {
                $posicionComprobacion = $i;
            }
        }

        $this->assertNotNull($posicionLock, 'No se bloqueo la fila del equipo.');
        $this->assertNotNull($posicionComprobacion, 'No se consulto la disponibilidad.');
        $this->assertLessThan(
            $posicionComprobacion,
            $posicionLock,
            'El bloqueo del equipo debe preceder a la comprobacion de disponibilidad.'
        );
    }
}
