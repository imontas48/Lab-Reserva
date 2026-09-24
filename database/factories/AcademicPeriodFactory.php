<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<AcademicPeriod>
 */
class AcademicPeriodFactory extends Factory
{
    protected $model = AcademicPeriod::class;

    /**
     * Por defecto: un semestre que contiene la fecha actual.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Periodo '.fake()->unique()->numerify('####-#'),
            'starts_on' => Carbon::now()->subMonth()->toDateString(),
            'ends_on' => Carbon::now()->addMonths(3)->toDateString(),
            'is_active' => true,
        ];
    }
}
