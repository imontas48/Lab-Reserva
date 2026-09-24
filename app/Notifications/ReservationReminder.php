<?php

namespace App\Notifications;

class ReservationReminder extends ReservationNotification
{
    public function key(): string
    {
        return 'reservation_reminder';
    }

    public function title(): string
    {
        return 'Recordatorio de reserva';
    }

    public function body(): string
    {
        return "Tienes una reserva próxima de {$this->target()}.";
    }
}
