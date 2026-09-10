<?php

namespace Database\Factories;

use App\Models\Lab;
use App\Models\LabClosure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<LabClosure>
 */
class LabClosureFactory extends Factory
{
    protected $model = LabClosure::class;

    /**
     * Por defecto: cierre global de un dia entero, manana.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lab_id' => null,
            'starts_at' => Carbon::tomorrow()->startOfDay(),
            'ends_at' => Carbon::tomorrow()->endOfDay(),
            'reason' => 'Día festivo',
            'created_by' => null,
        ];
    }

    public function forLab(Lab $lab): static
    {
        return $this->state(fn () => ['lab_id' => $lab->id]);
    }

    public function between(Carbon $start, Carbon $end): static
    {
        return $this->state(fn () => ['starts_at' => $start, 'ends_at' => $end]);
    }
}
