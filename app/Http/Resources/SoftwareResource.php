<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SoftwareResource extends JsonResource
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
            'version' => $this->version,

            // Contador de equipos que tienen instalado este software (solo si está cargado)
            'equipment_count' => $this->whenCounted('equipment'),

            // Relación con equipos (solo si está cargada)
            'equipment' => EquipmentResource::collection($this->whenLoaded('equipment')),

            // Nombre completo con versión
            'full_name' => $this->version
                ? "{$this->name} {$this->version}"
                : $this->name,

            // Timestamps formateados
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Timestamps legibles
            'created_at_human' => $this->created_at?->diffForHumans(),
            'updated_at_human' => $this->updated_at?->diffForHumans(),
        ];
    }
}
