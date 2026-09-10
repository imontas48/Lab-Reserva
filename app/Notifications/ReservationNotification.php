<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * Base de las notificaciones sobre una reserva.
 *
 * Dos canales: 'database' alimenta el centro de notificaciones de la
 * aplicacion y 'mail' el correo. Se encolan (QUEUE_CONNECTION) y se envian
 * tras confirmar la transaccion que las origina, para no notificar una
 * reserva que finalmente no se guardo.
 */
abstract class ReservationNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Reservation $reservation)
    {
        $this->afterCommit();
    }

    abstract public function key(): string;

    abstract public function title(): string;

    abstract public function body(): string;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'key' => $this->key(),
            'title' => $this->title(),
            'message' => $this->body(),
            'reservation_id' => $this->reservation->id,
            'url' => "/reservations/{$this->reservation->id}",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Lab-Reserva] '.$this->title())
            ->greeting("Hola {$notifiable->name},")
            ->line($this->body())
            ->line($this->window())
            ->action('Ver reserva', url("/reservations/{$this->reservation->id}"));
    }

    protected function target(): string
    {
        return $this->reservation->targetDescription();
    }

    protected function window(): string
    {
        return sprintf(
            'Franja: %s de %s a %s.',
            $this->reservation->start_time->translatedFormat('l d/m/Y'),
            $this->reservation->start_time->format('H:i'),
            $this->reservation->end_time->format('H:i')
        );
    }
}
