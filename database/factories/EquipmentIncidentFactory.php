<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\EquipmentIncident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentIncident>
 */
class EquipmentIncidentFactory extends Factory
{
    protected $model = EquipmentIncident::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipment_id' => Equipment::factory(),
            'reported_by' => User::factory(),
            'description' => fake()->sentence(8),
            'severity' => 'medium',
            'status' => EquipmentIncident::STATUS_OPEN,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => EquipmentIncident::STATUS_RESOLVED,
            'resolution' => 'Reparado',
            'resolved_at' => now(),
        ]);
    }

    public function forEquipment(Equipment $equipment): static
    {
        return $this->state(fn () => ['equipment_id' => $equipment->id]);
    }
}
