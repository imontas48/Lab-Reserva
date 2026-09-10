<?php

namespace Database\Factories;

use App\Models\Lab;
use App\Models\LabOpeningHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LabOpeningHour>
 */
class LabOpeningHourFactory extends Factory
{
    protected $model = LabOpeningHour::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lab_id' => Lab::factory(),
            'weekday' => 1,
            'opens_at' => '08:00:00',
            'closes_at' => '20:00:00',
        ];
    }
}
