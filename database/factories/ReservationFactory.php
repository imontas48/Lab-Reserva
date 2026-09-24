<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    /**
     * Por defecto: una franja futura de una hora sobre un equipo, confirmada.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::tomorrow()->setHour(9)->setMinute(0)->setSecond(0);

        return [
            'user_id' => User::factory(),
            'type' => Reservation::TYPE_EQUIPMENT,
            'equipment_id' => Equipment::factory(),
            'lab_id' => null,
            'start_time' => $start,
            'end_time' => (clone $start)->addHour(),
            'status' => Reservation::STATUS_CONFIRMED,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => Reservation::STATUS_CONFIRMED]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => Reservation::STATUS_PENDING]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => Reservation::STATUS_CANCELLED]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => Reservation::STATUS_COMPLETED]);
    }

    public function rejected(string $reason = 'Motivo de prueba'): static
    {
        return $this->state(fn () => [
            'status' => Reservation::STATUS_REJECTED,
            'rejection_reason' => $reason,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['status' => Reservation::STATUS_EXPIRED]);
    }

    /**
     * Franja ya terminada. Util para el job que marca reservas como completed
     * y para comprobar que no participa en la deteccion de solapamiento.
     */
    public function past(): static
    {
        $start = Carbon::yesterday()->setHour(9)->setMinute(0)->setSecond(0);

        return $this->state(fn () => [
            'start_time' => $start,
            'end_time' => (clone $start)->addHour(),
        ]);
    }

    public function future(): static
    {
        $start = Carbon::tomorrow()->setHour(9)->setMinute(0)->setSecond(0);

        return $this->state(fn () => [
            'start_time' => $start,
            'end_time' => (clone $start)->addHour(),
        ]);
    }

    /**
     * Franja explicita. Es el estado que usan las pruebas de solapamiento,
     * donde los limites exactos son el objeto de la prueba.
     */
    public function between(Carbon $start, Carbon $end): static
    {
        return $this->state(fn () => [
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    public function forEquipment(Equipment $equipment): static
    {
        return $this->state(fn () => [
            'type' => Reservation::TYPE_EQUIPMENT,
            'equipment_id' => $equipment->id,
            'lab_id' => null,
        ]);
    }

    /**
     * Reserva de laboratorio completo. Nace pendiente, como la solicitud de
     * un profesor; encadenar ->confirmed() simula la aprobacion.
     */
    public function forLab(Lab $lab, string $purpose = 'Clase de prueba'): static
    {
        return $this->state(fn () => [
            'type' => Reservation::TYPE_LAB,
            'lab_id' => $lab->id,
            'equipment_id' => null,
            'purpose' => $purpose,
            'status' => Reservation::STATUS_PENDING,
        ]);
    }

    /**
     * Reserva que exige check-in (tiene codigo), como las que crea la API.
     */
    public function withCheckIn(string $code = 'ABC123'): static
    {
        return $this->state(fn () => ['check_in_code' => $code]);
    }

    public function checkedIn(): static
    {
        return $this->state(fn () => ['checked_in_at' => now()]);
    }

    public function noShow(): static
    {
        return $this->state(fn () => [
            'status' => Reservation::STATUS_NO_SHOW,
            'no_show_at' => now(),
        ]);
    }

    public function inSeries(string $group): static
    {
        return $this->state(fn () => ['recurrence_group' => $group]);
    }

    public function withPurpose(string $purpose): static
    {
        return $this->state(fn () => ['purpose' => $purpose]);
    }

    public function reviewedBy(User $reviewer): static
    {
        return $this->state(fn () => [
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }
}
