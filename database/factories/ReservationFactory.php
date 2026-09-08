<?php

namespace Database\Factories;

use App\Models\Equipment;
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
     * Por defecto: una franja futura de una hora, confirmada.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::tomorrow()->setHour(9)->setMinute(0)->setSecond(0);

        return [
            'user_id' => User::factory(),
            'equipment_id' => Equipment::factory(),
            'start_time' => $start,
            'end_time' => (clone $start)->addHour(),
            'status' => 'confirmed',
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => 'confirmed']);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed']);
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
        return $this->state(fn () => ['equipment_id' => $equipment->id]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }
}
