<?php

namespace Database\Factories;

use App\Models\Lab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lab>
 */
class LabFactory extends Factory
{
    protected $model = Lab::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // labs.name es UNIQUE: sin unique() las factories chocan al crear varios.
            'name' => 'Laboratorio '.fake()->unique()->numerify('###'),
            'location' => 'Edificio '.fake()->randomLetter().', Piso '.fake()->numberBetween(1, 5),
            'capacity' => fake()->numberBetween(10, 40),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
