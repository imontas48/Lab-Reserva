<?php

namespace App\Notifications;

/**
 * Al administrador: un profesor solicito un laboratorio completo.
 */
class LabReservationRequested extends ReservationNotification
{
    public function key(): string
    {
        return 'lab_reservation_requested';
    }

    public function title(): string
    {
        return 'Nueva solicitud de laboratorio';
    }

    public function body(): string
    {
        $requester = $this->reservation->user?->name ?? 'Un usuario';

        return "{$requester} solicita {$this->target()} para: {$this->reservation->purpose}.";
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['url' => '/reservations/pending'] + parent::toArray($notifiable);
    }
}
