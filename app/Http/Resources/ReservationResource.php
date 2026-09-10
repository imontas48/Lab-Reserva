<?php

namespace App\Http\Resources;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Reservation
 */
class ReservationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $canSeeIdentity = $this->viewerCanSeeIdentity($request);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'equipment_id' => $this->equipment_id,
            'lab_id' => $this->lab_id,
            'start_time' => $this->start_time?->toIso8601String(),
            'end_time' => $this->end_time?->toIso8601String(),
            'status' => $this->status,
            'purpose' => $this->purpose,
            'rejection_reason' => $this->rejection_reason,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),

            // Asistencia. El codigo solo lo ve quien puede hacer check-in con
            // el: el dueño o quien ve cualquier reserva.
            'requires_check_in' => $this->requiresCheckIn(),
            'check_in_code' => $canSeeIdentity ? $this->check_in_code : null,
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'no_show_at' => $this->no_show_at?->toIso8601String(),
            'can_check_in' => $this->isCheckInOpen(),
            'check_in_opens_at' => $this->requiresCheckIn()
                ? $this->checkInWindow()['from']->toIso8601String()
                : null,
            'recurrence_group' => $this->recurrence_group,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relaciones (solo si están cargadas).
            //
            // Los datos personales solo se exponen al administrador y al dueño
            // de la reserva. GET /equipment/{id}/reservations es accesible a
            // cualquier usuario autenticado para poder consultar disponibilidad,
            // y devolvía nombre y correo: bastaba con recorrer los equipos para
            // extraer el padrón completo de la institución.
            'user' => $this->whenLoaded('user', fn () => $this->identityOf($this->user, $canSeeIdentity)),
            'reviewer' => $this->whenLoaded('reviewer', fn () => $this->reviewer
                ? $this->identityOf($this->reviewer, $canSeeIdentity)
                : null),
            'equipment' => new EquipmentResource($this->whenLoaded('equipment')),
            'lab' => new LabResource($this->whenLoaded('lab')),

            // Campos calculados útiles para el frontend
            'duration_minutes' => $this->start_time && $this->end_time
                ? $this->start_time->diffInMinutes($this->end_time)
                : null,

            'is_pending' => $this->status === Reservation::STATUS_PENDING,

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

    /**
     * Mismo criterio que ReservationPolicy::view: quien puede ver las reservas
     * de cualquiera ve también su identidad. Se consulta el permiso y no
     * isAdmin() para que un usuario elevado por rol individual reciba el
     * mismo trato.
     */
    private function viewerCanSeeIdentity(Request $request): bool
    {
        $viewer = $request->user();

        return $viewer !== null && (
            $viewer->hasPermission('reservations', 'viewAny')
            || $viewer->id === $this->user_id
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function identityOf(mixed $user, bool $canSeeIdentity): array
    {
        return $canSeeIdentity
            ? ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]
            : ['id' => $user->id];
    }
}
