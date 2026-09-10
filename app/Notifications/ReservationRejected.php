<?php

namespace App\Notifications;

class ReservationRejected extends ReservationNotification
{
    public function key(): string
    {
        return 'reservation_rejected';
    }

    public function title(): string
    {
        return 'Solicitud rechazada';
    }

    public function body(): string
    {
        return "Tu solicitud de {$this->target()} fue rechazada. Motivo: {$this->reservation->rejection_reason}.";
    }
}
