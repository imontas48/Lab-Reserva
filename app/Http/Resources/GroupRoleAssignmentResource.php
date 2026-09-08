<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupRoleAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role_id' => $this->role_id,
            'group_type' => $this->group_type,
            'group_value' => $this->group_value,
            'is_active' => $this->is_active,

            // Rol completo (solo si la relación fue cargada)
            'role' => new RoleResource($this->whenLoaded('role')),

            // Trazabilidad
            'granted_by' => $this->granted_by,
            'granted_by_name' => $this->when(
                $this->relationLoaded('grantedBy') && $this->grantedBy,
                fn () => $this->grantedBy->name
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
