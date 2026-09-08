<?php

namespace App\Http\Resources;

use App\Models\PermissionOverride;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionOverrideResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'permission_id' => $this->permission_id,
            'type' => $this->type,
            // Etiqueta legible del tipo
            'type_label' => $this->type === PermissionOverride::TYPE_GRANT ? 'Concedido' : 'Revocado',
            'reason' => $this->reason,

            // Permiso completo (solo si la relación fue cargada)
            'permission' => new PermissionResource($this->whenLoaded('permission')),

            // Trazabilidad
            'granted_by' => $this->granted_by,
            'granted_by_name' => $this->when(
                $this->relationLoaded('grantedBy') && $this->grantedBy,
                fn () => $this->grantedBy->name
            ),

            // Expiración
            'expires_at' => $this->expires_at?->toISOString(),
            'is_expired' => $this->expires_at !== null && $this->expires_at->isPast(),
            'expires_at_human' => $this->expires_at?->diffForHumans(),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
