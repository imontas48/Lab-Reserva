<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentResource extends JsonResource
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
            'lab_id' => $this->lab_id,
            'identifier' => $this->identifier,
            'type' => $this->type,
            'specifications' => $this->specifications,
            'is_operational' => (bool) $this->is_operational,

            // Relación con el laboratorio (solo si está cargada)
            'lab' => new LabResource($this->whenLoaded('lab')),

            // Relación con el software (solo si está cargada)
            'software' => SoftwareResource::collection($this->whenLoaded('software')),

            // IDs de software (útil para formularios de edición)
            'software_ids' => $this->when(
                $this->relationLoaded('software'),
                fn() => $this->software->pluck('id')
            ),

            // Contadores de reservas (solo si están cargados)
            'reservations_count' => $this->whenCounted('reservations'),
            'active_reservations_count' => $this->whenCounted('activeReservations'),

            // Identificador completo (laboratorio + identificador)
            'full_identifier' => $this->when(
                $this->relationLoaded('lab'),
                fn() => "{$this->lab->name} - {$this->identifier}"
            ),

            // Timestamps formateados
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Timestamps legibles
            'created_at_human' => $this->created_at?->diffForHumans(),
            'updated_at_human' => $this->updated_at?->diffForHumans(),
        ];
    }
}
