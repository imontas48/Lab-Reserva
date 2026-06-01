<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'user_id'    => $this->user_id,
            'role_id'    => $this->role_id,

            // Rol completo (solo si la relación fue cargada)
            'role' => new RoleResource($this->whenLoaded('role')),

            // Datos de trazabilidad
            'granted_by' => $this->granted_by,
            'granted_by_name' => $this->when(
                $this->relationLoaded('grantedBy') && $this->grantedBy,
                fn () => $this->grantedBy->name
            ),

            // Expiración
            'expires_at'      => $this->expires_at?->toISOString(),
            'is_expired'      => $this->expires_at !== null && $this->expires_at->isPast(),
            'expires_at_human'=> $this->expires_at?->diffForHumans(),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
