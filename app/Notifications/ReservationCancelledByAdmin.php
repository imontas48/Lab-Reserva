<?php

namespace App\Notifications;

class ReservationCancelledByAdmin extends ReservationNotification
{
    public function key(): string
    {
        return 'reservation_cancelled_by_admin';
    }

    public function title(): string
    {
        return 'Reserva cancelada por el administrador';
    }

    public function body(): string
    {
        return "Tu reserva de {$this->target()} fue cancelada por un administrador.";
    }
}
