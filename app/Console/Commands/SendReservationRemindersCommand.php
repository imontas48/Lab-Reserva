<?php

namespace App\Console\Commands;

use App\Services\ReservationService;
use Illuminate\Console\Command;

/**
 * Envia el recordatorio de las reservas confirmadas que empiezan dentro de
 * la ventana configurada (lab-reserva.reservations.reminder_hours_before).
 * Cada reserva recibe un unico recordatorio (reminder_sent_at).
 */
class SendReservationRemindersCommand extends Command
{
    protected $signature = 'reservations:send-reminders';

    protected $description = 'Envia el recordatorio de las reservas confirmadas que empiezan pronto';

    public function handle(ReservationService $reservations): int
    {
        $count = $reservations->sendUpcomingReminders();

        $this->info($count === 0
            ? 'No habia reservas pendientes de recordatorio.'
            : "Recordatorios enviados: {$count}.");

        return self::SUCCESS;
    }
}
