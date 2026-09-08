<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
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
            'user_id' => $this->user_id,
            'equipment_id' => $this->equipment_id,
            'start_time' => $this->start_time?->toIso8601String(),
            'end_time' => $this->end_time?->toIso8601String(),
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relaciones (solo si están cargadas).
            //
            // Los datos personales solo se exponen al administrador y al dueño
            // de la reserva. GET /equipment/{id}/reservations es accesible a
            // cualquier usuario autenticado para poder consultar disponibilidad,
            // y devolvía nombre y correo: bastaba con recorrer los equipos para
            // extraer el padrón completo de la institución.
            'user' => $this->whenLoaded('user', function () use ($request) {
                $viewer = $request->user();
                $canSeeIdentity = $viewer && ($viewer->isAdmin() || $viewer->id === $this->user_id);

                return $canSeeIdentity
                    ? [
                        'id' => $this->user->id,
                        'name' => $this->user->name,
                        'email' => $this->user->email,
                    ]
                    : [
                        'id' => $this->user->id,
                    ];
            }),
            'equipment' => new EquipmentResource($this->whenLoaded('equipment')),

            // Campos calculados útiles para el frontend
            'duration_minutes' => $this->start_time && $this->end_time
                ? $this->start_time->diffInMinutes($this->end_time)
                : null,

            'is_active' => $this->start_time && $this->end_time
                ? now()->between($this->start_time, $this->end_time)
                : false,

            'is_past' => $this->end_time
                ? now()->greaterThan($this->end_time)
                : false,

            'is_future' => $this->start_time
                ? now()->lessThan($this->start_time)
                : false,
        ];
    }
}
