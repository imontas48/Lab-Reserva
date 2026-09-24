<?php

namespace App\Notifications;

class ReservationApproved extends ReservationNotification
{
    public function key(): string
    {
        return 'reservation_approved';
    }

    public function title(): string
    {
        return 'Solicitud aprobada';
    }

    public function body(): string
    {
        return "Tu solicitud de {$this->target()} fue aprobada.";
    }
}
