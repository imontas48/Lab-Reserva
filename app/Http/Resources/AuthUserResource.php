<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Services\PermissionService;
use App\Support\ReservationLimits;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 *
 * El usuario autenticado tal como lo consume el frontend.
 *
 * Incluye sus permisos efectivos para que la interfaz decida qué mostrar
 * (por ejemplo, la opción de reservar un laboratorio completo) sin una
 * llamada adicional y sin depender solo de users.role. La autorización real
 * la sigue haciendo el backend en cada petición.
 */
class AuthUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'permissions' => app(PermissionService::class)->effectivePermissionKeys($this->resource),
            // Para validar en cliente antes de enviar; el servidor vuelve a
            // aplicar los mismos limites.
            'reservation_limits' => ReservationLimits::forUser($this->resource),
            'no_show_count' => (int) $this->no_show_count,
            'reservation_blocked_until' => $this->reservation_blocked_until?->toIso8601String(),
        ];
    }
}
