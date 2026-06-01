<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'display_name' => $this->display_name,
            'description'  => $this->description,
            'color'        => $this->color,
            'is_system'    => $this->is_system,
            'is_active'    => $this->is_active,

            // Permisos del rol (solo si la relación fue cargada)
            'permissions'       => PermissionResource::collection($this->whenLoaded('permissions')),
            'permissions_count' => $this->whenCounted('permissions'),

            // Conteo de usuarios con este rol
            'user_roles_count' => $this->whenCounted('userRoles'),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
