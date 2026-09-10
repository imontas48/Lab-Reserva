<?php

namespace App\Console\Commands;

use App\Services\AttendanceService;
use App\Services\ReservationService;
use Illuminate\Console\Command;

/**
 * Cierra las reservas cuya franja ya termino y expira las solicitudes que
 * llegaron a su hora sin decision.
 *
 * ReservationService::markExpiredReservationsAsCompleted existia desde el
 * principio con un docblock que decia "puede ser llamado por un comando
 * scheduled", pero no habia ni comando ni planificacion: routes/console.php
 * solo definia 'inspire'. La consecuencia era que ninguna reserva llegaba
 * nunca a 'completed', asi que:
 *
 *  - las reservas pasadas seguian contando como confirmadas y entraban en cada
 *    comprobacion de solapamiento, haciendo crecer el conjunto candidato sin
 *    limite;
 *  - EquipmentService::deleteEquipment las veia como reservas activas;
 *  - las estadisticas de completadas devolvian siempre cero.
 *
 * Con el flujo de aprobacion, una solicitud pendiente tambien ocupa la franja
 * y hay que liberarla si nadie la resolvio a tiempo.
 */
class CompleteExpiredReservationsCommand extends Command
{
    protected $signature = 'reservations:complete-expired';

    protected $description = 'Marca como completadas las reservas confirmadas cuya franja ya termino y expira las solicitudes pendientes sin decision';

    public function handle(ReservationService $reservations, AttendanceService $attendance): int
    {
        // Primero las inasistencias: una reserva con codigo y sin llegada
        // registrada es un no_show, no una completada.
        $noShows = $attendance->markOverdueNoShows();
        $completed = $reservations->markExpiredReservationsAsCompleted();
        $expired = $reservations->expirePendingReservations();

        $this->info($noShows === 0
            ? 'No habia inasistencias que registrar.'
            : "Inasistencias registradas: {$noShows}.");

        $this->info($completed === 0
            ? 'No habia reservas vencidas.'
            : "Reservas marcadas como completadas: {$completed}.");

        $this->info($expired === 0
            ? 'No habia solicitudes pendientes vencidas.'
            : "Solicitudes expiradas sin decision: {$expired}.");

        return self::SUCCESS;
    }
}
