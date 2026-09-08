<?php

namespace App\Console\Commands;

use App\Services\ReservationService;
use Illuminate\Console\Command;

/**
 * Cierra las reservas cuya franja ya termino.
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
 */
class CompleteExpiredReservationsCommand extends Command
{
    protected $signature = 'reservations:complete-expired';

    protected $description = 'Marca como completadas las reservas confirmadas cuya franja ya termino';

    public function handle(ReservationService $reservations): int
    {
        $count = $reservations->markExpiredReservationsAsCompleted();

        $this->info($count === 0
            ? 'No habia reservas vencidas.'
            : "Reservas marcadas como completadas: {$count}.");

        return self::SUCCESS;
    }
}
