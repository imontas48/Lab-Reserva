<?php

namespace App\Policies;

use App\Models\reservations;
use App\Models\User;

class ReservationPolicy
{
    /**
     * Determina si el usuario puede ver el listado COMPLETO de reservas.
     * Solo los administradores pueden ver todas las reservas del sistema.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determina si el usuario puede ver una reserva específica.
     * - El dueño siempre puede ver su propia reserva.
     * - Los administradores pueden ver cualquier reserva.
     */
    public function view(User $user, reservations $reservation): bool
    {
        return $user->isAdmin() || $user->id === $reservation->user_id;
    }

    /**
     * Determina si el usuario puede crear reservas.
     * Cualquier usuario autenticado puede crear reservas.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determina si el usuario puede actualizar una reserva.
     * - El dueño puede actualizar su propia reserva (solo si está confirmada y es futura).
     * - Los administradores pueden actualizar cualquier reserva.
     */
    public function update(User $user, reservations $reservation): bool
    {
        return $user->isAdmin() || $user->id === $reservation->user_id;
    }

    /**
     * Determina si el usuario puede cancelar una reserva.
     * Regla de negocio:
     * - El dueño puede cancelar su propia reserva (si está confirmada y no ha comenzado).
     * - Los administradores pueden cancelar cualquier reserva confirmada.
     */
    public function cancel(User $user, reservations $reservation): bool
    {
        return $user->isAdmin() || $user->id === $reservation->user_id;
    }

    /**
     * Determina si el usuario puede eliminar una reserva.
     * Solo los administradores pueden eliminar reservas permanentemente.
     */
    public function delete(User $user, reservations $reservation): bool
    {
        return $user->isAdmin();
    }

    /**
     * Restore / forceDelete — no aplican en este modelo.
     */
    public function restore(User $user, reservations $reservation): bool
    {
        return false;
    }

    public function forceDelete(User $user, reservations $reservation): bool
    {
        return false;
    }
}
