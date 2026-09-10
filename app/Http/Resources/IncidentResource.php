<?php

namespace App\Http\Resources;

use App\Models\EquipmentIncident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EquipmentIncident
 */
class IncidentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'equipment_id' => $this->equipment_id,
            'description' => $this->description,
            'severity' => $this->severity,
            'status' => $this->status,
            'resolution' => $this->resolution,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'equipment' => $this->whenLoaded('equipment', fn () => [
                'id' => $this->equipment->id,
                'identifier' => $this->equipment->identifier,
                'is_operational' => (bool) $this->equipment->is_operational,
                'lab_id' => $this->equipment->lab_id,
                'lab_name' => $this->equipment->relationLoaded('lab') ? $this->equipment->lab?->name : null,
            ]),
            'reporter' => $this->whenLoaded('reporter', fn () => $this->reporter ? ['id' => $this->reporter->id, 'name' => $this->reporter->name] : null),
            'resolver' => $this->whenLoaded('resolver', fn () => $this->resolver ? ['id' => $this->resolver->id, 'name' => $this->resolver->name] : null),
        ];
    }
}
