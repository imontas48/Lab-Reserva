<?php

namespace App\Http\Resources;

use App\Models\Lab;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Lab
 */
class LabResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'capacity' => $this->capacity,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'grid_rows' => (int) $this->grid_rows,
            'grid_cols' => (int) $this->grid_cols,

            // Contadores de equipos (solo si están cargados)
            'equipment_count' => $this->whenCounted('equipment'),
            'operational_equipment_count' => $this->whenCounted('operationalEquipment'),

            // Relación de equipos (solo si está cargada)
            'equipment' => EquipmentResource::collection($this->whenLoaded('equipment')),

            // Timestamps formateados
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Timestamps en formato legible para humanos
            'created_at_human' => $this->created_at?->diffForHumans(),
            'updated_at_human' => $this->updated_at?->diffForHumans(),
        ];
    }
}
