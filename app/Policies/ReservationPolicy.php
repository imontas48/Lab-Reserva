<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

/**
 * Autorización de reservas.
 *
 * A diferencia de las policies de catálogo, aquí conviven dos cosas distintas:
 *
 *  - el PERMISO, que dice qué clase de acción puede hacer el usuario y sale del
 *    RBAC (roles por grupo, roles individuales y sobreescrituras);
 *  - la PROPIEDAD, que dice sobre qué fila puede hacerla y es contexto de la
 *    petición, no algo que se pueda conceder desde una tabla.
 *
 * El patrón es siempre el mismo: quien tiene el permiso 'viewAny' actúa sobre
 * cualquier reserva; quien solo tiene el permiso concreto actúa únicamente
 * sobre las suyas.
 */
class ReservationPolicy
{
    /**
     * Ver el listado COMPLETO de reservas, de cualquier usuario.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('reservations', 'viewAny');
    }

    /**
     * Ver una reserva concreta: las propias, o todas si se tiene 'viewAny'.
     */
    public function view(User $user, Reservation $reservation): bool
    {
        if ($user->hasPermission('reservations', 'viewAny')) {
            return true;
        }

        return $user->hasPermission('reservations', 'view')
            && $user->id === $reservation->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('reservations', 'create');
    }

    /**
     * Solicitar un laboratorio completo para una clase.
     */
    public function createLab(User $user): bool
    {
        return $user->hasPermission('reservations', 'createLab');
    }

    /**
     * Ver la cola de solicitudes pendientes.
     */
    public function viewPending(User $user): bool
    {
        return $user->hasPermission('reservations', 'approve');
    }

    /**
     * Aprobar una solicitud. Que esté pendiente y no haya comenzado lo
     * comprueba ReservationService.
     */
    public function approve(User $user, Reservation $reservation): bool
    {
        return $user->hasPermission('reservations', 'approve');
    }

    public function reject(User $user, Reservation $reservation): bool
    {
        return $user->hasPermission('reservations', 'approve');
    }

    /**
     * Registrar la llegada: el dueño de la reserva, o quien ve cualquiera
     * (kiosco del administrador).
     */
    public function checkIn(User $user, Reservation $reservation): bool
    {
        if ($user->hasPermission('reservations', 'viewAny')) {
            return true;
        }

        return $user->hasPermission('reservations', 'view')
            && $user->id === $reservation->user_id;
    }

    /**
     * Marcar una inasistencia a mano.
     */
    public function markNoShow(User $user, Reservation $reservation): bool
    {
        return $user->hasPermission('reservations', 'approve');
    }

    /**
     * Modificar una reserva.
     *
     * Qué transiciones de estado son válidas no se decide aquí, sino en la
     * máquina de estados del modelo: la policy responde "sobre qué filas",
     * no "a qué estado".
     */
    public function update(User $user, Reservation $reservation): bool
    {
        if ($user->hasPermission('reservations', 'viewAny')) {
            return true;
        }

        return $user->hasPermission('reservations', 'update')
            && $user->id === $reservation->user_id;
    }

    /**
     * Cancelar una reserva o retirar una solicitud. Que esté en un estado
     * cancelable y no haya comenzado lo comprueba ReservationService.
     */
    public function cancel(User $user, Reservation $reservation): bool
    {
        if ($user->hasPermission('reservations', 'viewAny')) {
            return true;
        }

        return $user->hasPermission('reservations', 'cancel')
            && $user->id === $reservation->user_id;
    }

    /**
     * Borrado permanente: no se acota por propiedad a propósito.
     */
    public function delete(User $user, Reservation $reservation): bool
    {
        return $user->hasPermission('reservations', 'delete');
    }

    /**
     * Restore / forceDelete — no aplican en este modelo.
     */
    public function restore(User $user, Reservation $reservation): bool
    {
        return false;
    }

    public function forceDelete(User $user, Reservation $reservation): bool
    {
        return false;
    }
}
