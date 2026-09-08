<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\Lab;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lab_id' => Lab::factory(),
            // equipment tiene UNIQUE(lab_id, identifier); unique() evita colisiones
            // aunque varios equipos caigan en el mismo laboratorio.
            'identifier' => 'PC-'.fake()->unique()->numerify('####'),
            'type' => fake()->randomElement(['PC', 'Laptop', 'Servidor', 'Impresora 3D']),
            // specifications se valida como string libre (max 2000), no como JSON.
            'specifications' => 'CPU: '.fake()->randomElement(['Intel Core i5', 'Intel Core i7', 'AMD Ryzen 5'])
                .', RAM: '.fake()->randomElement(['8GB', '16GB', '32GB']),
            'is_operational' => true,
        ];
    }

    public function nonOperational(): static
    {
        return $this->state(fn () => ['is_operational' => false]);
    }

    public function inLab(Lab $lab): static
    {
        return $this->state(fn () => ['lab_id' => $lab->id]);
    }
}
