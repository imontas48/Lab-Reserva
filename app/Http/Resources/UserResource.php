<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Usuario visto por quien gestiona usuarios.
 *
 * @mixin User
 */
class UserResource extends JsonResource
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
            'no_show_count' => (int) $this->no_show_count,
            'reservation_blocked_until' => $this->reservation_blocked_until?->toIso8601String(),
            'is_blocked' => $this->isBlockedFromReserving(),
            'must_change_password' => $this->mustChangePassword(),
            'reservations_count' => $this->whenCounted('reservations'),
            'active_reservations_count' => $this->whenCounted('activeReservations'),
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
