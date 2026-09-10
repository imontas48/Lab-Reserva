<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Asistencia: check-in, no-show y politica de reincidencia.
 *
 * Separado de ReservationService para que la creacion de reservas no
 * arrastre la logica de asistencia, que tiene su propio ciclo (comando
 * programado, kiosco, sanciones).
 */
class AttendanceService
{
    /**
     * Codigo corto y legible (sin 0/O ni 1/I) que se entrega al confirmar.
     */
    public static function generateCode(): string
    {
        return strtoupper(Str::random(6));
    }

    /**
     * Registra la llegada. El dueño necesita el codigo; quien puede ver
     * cualquier reserva (kiosco del administrador) puede omitirlo.
     *
     * @throws BusinessRuleException
     */
    public function checkIn(Reservation $reservation, User $actor, ?string $code): Reservation
    {
        if ($reservation->status !== Reservation::STATUS_CONFIRMED) {
            throw new BusinessRuleException('Solo se puede registrar la llegada de una reserva confirmada.');
        }

        if ($reservation->isCheckedIn()) {
            throw new BusinessRuleException('La llegada ya estaba registrada.');
        }

        if (! $reservation->requiresCheckIn()) {
            throw new BusinessRuleException('Esta reserva no requiere check-in.');
        }

        ['from' => $from, 'until' => $until] = $reservation->checkInWindow();

        if (now()->lessThan($from)) {
            throw new BusinessRuleException('Aún es pronto: el check-in se abre a las '.$from->format('H:i').'.');
        }

        if (now()->greaterThan($until)) {
            throw new BusinessRuleException('El periodo de check-in terminó a las '.$until->format('H:i').'.');
        }

        $canSkipCode = $actor->hasPermission('reservations', 'viewAny');

        if (! $canSkipCode && strtoupper(trim((string) $code)) !== $reservation->check_in_code) {
            throw new BusinessRuleException('El código de check-in no es correcto.');
        }

        $reservation->forceFill(['checked_in_at' => now()])->save();

        return $reservation->fresh(ReservationService::DETAIL_RELATIONS);
    }

    /**
     * Marca una inasistencia a mano (administrador).
     *
     * @throws BusinessRuleException
     */
    public function markNoShow(Reservation $reservation): Reservation
    {
        if (! $reservation->canTransitionTo(Reservation::STATUS_NO_SHOW)) {
            throw new BusinessRuleException('Solo una reserva confirmada puede marcarse como inasistencia.');
        }

        if ($reservation->isCheckedIn()) {
            throw new BusinessRuleException('El usuario registró su llegada: no es una inasistencia.');
        }

        if ($reservation->start_time->isFuture()) {
            throw new BusinessRuleException('La franja aún no ha comenzado.');
        }

        $this->applyNoShow($reservation);

        return $reservation->fresh(ReservationService::DETAIL_RELATIONS);
    }

    /**
     * Inasistencias automaticas: confirmadas con codigo, sin llegada
     * registrada y con el periodo de gracia vencido.
     *
     * @return int Reservas marcadas
     */
    public function markOverdueNoShows(): int
    {
        $grace = (int) config('lab-reserva.check_in.grace_minutes', 15);

        $overdue = Reservation::query()
            ->confirmed()
            ->whereNotNull('check_in_code')
            ->whereNull('checked_in_at')
            ->where('start_time', '<=', now()->subMinutes($grace))
            ->get();

        foreach ($overdue as $reservation) {
            $this->applyNoShow($reservation);
        }

        return $overdue->count();
    }

    private function applyNoShow(Reservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            $reservation->forceFill([
                'status' => Reservation::STATUS_NO_SHOW,
                'no_show_at' => now(),
            ])->save();

            $user = User::query()->lockForUpdate()->findOrFail($reservation->user_id);
            $user->increment('no_show_count');

            $maxStrikes = (int) config('lab-reserva.no_show.max_strikes', 3);
            $windowDays = (int) config('lab-reserva.no_show.window_days', 30);
            $blockDays = (int) config('lab-reserva.no_show.block_days', 7);

            $recentStrikes = Reservation::query()
                ->where('user_id', $user->id)
                ->where('status', Reservation::STATUS_NO_SHOW)
                ->where('no_show_at', '>=', now()->subDays($windowDays))
                ->count();

            if ($maxStrikes > 0 && $recentStrikes >= $maxStrikes) {
                $user->forceFill(['reservation_blocked_until' => now()->addDays($blockDays)])->save();
            }
        });
    }
}
