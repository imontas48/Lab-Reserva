<?php

namespace Tests\Feature\Reservations;

use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HistoryAndSchedulingTest extends TestCase
{
    /**
     * markExpiredReservationsAsCompleted existia pero no la llamaba nadie:
     * routes/console.php solo definia 'inspire'. Ninguna reserva llegaba nunca
     * a 'completed', de modo que las pasadas seguian entrando en cada
     * comprobacion de solapamiento y las estadisticas devolvian siempre cero.
     */
    public function test_el_comando_cierra_las_reservas_vencidas(): void
    {
        $vencida = Reservation::factory()->past()->confirmed()->create();
        $futura = Reservation::factory()->future()->confirmed()->create();

        $this->artisan('reservations:complete-expired')->assertSuccessful();

        $this->assertSame(Reservation::STATUS_COMPLETED, $vencida->fresh()->status);
        $this->assertSame(Reservation::STATUS_CONFIRMED, $futura->fresh()->status);
    }

    public function test_el_comando_no_toca_las_canceladas(): void
    {
        $cancelada = Reservation::factory()->past()->cancelled()->create();

        $this->artisan('reservations:complete-expired')->assertSuccessful();

        $this->assertSame(Reservation::STATUS_CANCELLED, $cancelada->fresh()->status);
    }

    public function test_una_reserva_vencida_deja_de_bloquear_la_franja(): void
    {
        $equipment = Equipment::factory()->create();
        $inicio = Carbon::yesterday()->setTimeFromTimeString('10:00:00');
        $fin = Carbon::yesterday()->setTimeFromTimeString('11:00:00');

        Reservation::factory()
            ->forEquipment($equipment)
            ->between($inicio, $fin)
            ->confirmed()
            ->create();

        $this->assertSame(1, Reservation::query()->blocking($inicio, $fin)->count());

        $this->artisan('reservations:complete-expired');

        $this->assertSame(0, Reservation::query()->blocking($inicio, $fin)->count());
    }

    /**
     * reservations.user_id y equipment_id eran onDelete('cascade'): dar de baja
     * a un alumno graduado borraba todo su historial de uso, y retirar un
     * equipo antiguo se llevaba por delante sus reservas pasadas.
     */
    public function test_dar_de_baja_a_un_usuario_conserva_su_historial(): void
    {
        $user = User::factory()->create();
        $reserva = Reservation::factory()->forUser($user)->past()->create();

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseHas('reservations', [
            'id' => $reserva->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_dar_de_baja_un_equipo_conserva_sus_reservas(): void
    {
        $equipment = Equipment::factory()->create();
        $reserva = Reservation::factory()->forEquipment($equipment)->past()->create();

        $equipment->delete();

        $this->assertSoftDeleted('equipment', ['id' => $equipment->id]);
        $this->assertDatabaseHas('reservations', ['id' => $reserva->id]);
    }

    public function test_un_borrado_fisico_con_reservas_queda_bloqueado_por_la_base_de_datos(): void
    {
        $user = User::factory()->create();
        Reservation::factory()->forUser($user)->past()->create();

        // Ultima linea de defensa: aunque alguien llame a forceDelete, la clave
        // foranea en restrict impide destruir el historial.
        $this->expectException(QueryException::class);

        $user->forceDelete();
    }
}
