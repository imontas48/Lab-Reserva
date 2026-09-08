<?php

namespace Database\Factories;

use App\Models\Software;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Software>
 */
class SoftwareFactory extends Factory
{
    protected $model = Software::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // software.name es UNIQUE.
            'name' => ucfirst(fake()->unique()->word()).' '.fake()->randomElement(['Studio', 'Suite', 'IDE', 'Lab']),
            'version' => fake()->numerify('#.#.#'),
        ];
    }
}
