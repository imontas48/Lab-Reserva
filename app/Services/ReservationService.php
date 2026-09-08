<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Equipment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    /**
     * Create a new reservation.
     * Usa transacciones para evitar race conditions.
     *
     * @throws \Exception
     */
    public function createReservation(array $validatedData, User $user): Reservation
    {
        return DB::transaction(function () use ($validatedData, $user) {
            // Asignar el usuario autenticado
            $validatedData['user_id'] = $user->id;
            $validatedData['status'] = Reservation::STATUS_CONFIRMED;

            // El bloqueo va PRIMERO. Antes se comprobaba la disponibilidad con
            // un SELECT sin bloqueo y solo despues se bloqueaba la fila de
            // equipment, de modo que el lock no protegia nada: dos peticiones
            // simultaneas leian ambas "sin conflicto" y ambas insertaban.
            //
            // Bloquear la fila del equipo serializa todos los intentos de
            // reserva sobre ese equipo, que es la seccion critica real. MySQL
            // no admite restricciones de exclusion sobre intervalos (eso es
            // EXCLUDE de PostgreSQL), asi que este lock es la garantia: no hay
            // red de seguridad posible en el esquema.
            $equipment = Equipment::lockForUpdate()->findOrFail($validatedData['equipment_id']);

            if (! $equipment->is_operational) {
                throw new BusinessRuleException(
                    'El equipo seleccionado no está operacional en este momento.'
                );
            }

            // Ya dentro del lock. La comprobacion tambien es una lectura con
            // bloqueo: bajo REPEATABLE READ una lectura normal veria el
            // snapshot del inicio de la transaccion y no las filas que la otra
            // transaccion acaba de confirmar.
            $hasConflict = $this->checkEquipmentAvailability(
                $validatedData['equipment_id'],
                $validatedData['start_time'],
                $validatedData['end_time']
            );

            if ($hasConflict) {
                throw new BusinessRuleException(
                    'El equipo ya no está disponible en el rango de tiempo seleccionado. '.
                    'Por favor, seleccione otro horario.'
                );
            }

            // Crear la reserva
            $reservation = Reservation::create($validatedData);

            // Cargar las relaciones para la respuesta
            return $reservation->load(['user', 'equipment.lab', 'equipment.currentReservation', 'equipment.nextReservation']);
        });
    }

    /**
     * Cancel a reservation.
     * Solo cancela si está en estado 'confirmed' y no ha comenzado.
     *
     *
     * @throws \Exception
     */
    public function cancelReservation(Reservation $reservation): Reservation
    {
        // Verificar que la reserva esté confirmada
        if (! $reservation->canTransitionTo(Reservation::STATUS_CANCELLED)) {
            throw new BusinessRuleException(
                'Solo se pueden cancelar reservas confirmadas. '.
                'Esta reserva ya está en estado: '.$reservation->status
            );
        }

        // Verificar que la reserva no haya comenzado
        if ($reservation->start_time <= now()) {
            throw new BusinessRuleException(
                'No se puede cancelar una reserva que ya ha comenzado o pasado.'
            );
        }

        // Cambiar el estado a cancelled
        $reservation->update(['status' => 'cancelled']);

        // Recargar con relaciones
        return $reservation->fresh(['user', 'equipment.lab', 'equipment.currentReservation', 'equipment.nextReservation']);
    }

    /**
     * Update a reservation (admin only - mainly for status changes).
     */
    public function updateReservation(Reservation $reservation, array $data): Reservation
    {
        // Antes esto era un update() directo. Como UpdateReservationRequest
        // acepta cualquiera de los tres estados y ReservationPolicy::update
        // autoriza al dueño, un usuario podia cancelar su reserva, esperar a
        // que otro ocupase la franja y volver a confirmarla: dos reservas
        // solapadas sin pasar por ninguna comprobacion de disponibilidad.
        // Tambien podia marcarse reservas como 'completed' a voluntad y
        // falsear las estadisticas.
        if (isset($data['status']) && $data['status'] !== $reservation->status) {
            if (! $reservation->canTransitionTo($data['status'])) {
                $allowed = $reservation->allowedTransitions();

                throw new BusinessRuleException(
                    "No se puede pasar de '{$reservation->status}' a '{$data['status']}'. ".
                    ($allowed === []
                        ? "El estado '{$reservation->status}' es final."
                        : 'Transiciones permitidas: '.implode(', ', $allowed).'.')
                );
            }
        }

        $reservation->update($data);

        return $reservation->fresh(['user', 'equipment.lab', 'equipment.currentReservation', 'equipment.nextReservation']);
    }

    /**
     * Delete a reservation (admin only).
     */
    public function deleteReservation(Reservation $reservation): bool
    {
        return $reservation->delete();
    }

    /**
     * Get all reservations with advanced filtering (admin only).
     */
    public function getAllReservations(array $filters = [], ?int $perPage = null): LengthAwarePaginator
    {
        $query = Reservation::with(['user', 'equipment.lab', 'equipment.currentReservation', 'equipment.nextReservation']);

        // Filtrar por usuario
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filtrar por equipo
        if (isset($filters['equipment_id'])) {
            $query->where('equipment_id', $filters['equipment_id']);
        }

        // Filtrar por laboratorio (a través de la relación con equipment)
        if (isset($filters['lab_id'])) {
            $query->whereHas('equipment', function ($q) use ($filters) {
                $q->where('lab_id', $filters['lab_id']);
            });
        }

        // Filtrar por estado
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtrar por rango de fechas
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->betweenDates($filters['start_date'], $filters['end_date']);
        } elseif (isset($filters['start_date'])) {
            $query->where('start_time', '>=', $filters['start_date']);
        } elseif (isset($filters['end_date'])) {
            $query->where('end_time', '<=', $filters['end_date']);
        }

        // Búsqueda general (por nombre de usuario o identificador de equipo)
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                    ->orWhereHas('equipment', function ($equipmentQuery) use ($search) {
                        $equipmentQuery->where('identifier', 'like', "%{$search}%");
                    });
            });
        }

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'start_time';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Retornar paginado
        return $query->paginate($perPage ?? 15);
    }

    /**
     * Get reservations for a specific user.
     */
    public function getReservationsForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Reservation::where('user_id', $user->id)
            ->with(['equipment.lab', 'equipment.currentReservation', 'equipment.nextReservation']);

        // Filtrar por estado si se proporciona
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtrar por rango de fechas
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->betweenDates($filters['start_date'], $filters['end_date']);
        }

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'start_time';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get reservations filtered by the user's role (admin only).
     *
     * Permite al administrador ver todas las reservas hechas por
     * un tipo de usuario específico (student o teacher).
     *
     * @param  string  $role  - 'student' | 'teacher'
     */
    public function getReservationsByUserRole(string $role, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Reservation::with(['user', 'equipment.lab', 'equipment.currentReservation', 'equipment.nextReservation'])
            ->whereHas('user', function ($q) use ($role) {
                $q->where('role', $role);
            });

        // Filtrar por estado
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtrar por rango de fechas
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->betweenDates($filters['start_date'], $filters['end_date']);
        } elseif (isset($filters['start_date'])) {
            $query->where('start_time', '>=', $filters['start_date']);
        } elseif (isset($filters['end_date'])) {
            $query->where('end_time', '<=', $filters['end_date']);
        }

        // Búsqueda general
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                    ->orWhereHas('equipment', function ($equipmentQuery) use ($search) {
                        $equipmentQuery->where('identifier', 'like', "%{$search}%");
                    });
            });
        }

        $sortBy = $filters['sort_by'] ?? 'start_time';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get reservations for a specific equipment.
     * Útil para calendarios y visualización de disponibilidad.
     */
    public function getReservationsForEquipment(Equipment $equipment, array $filters = []): Collection
    {
        $query = $equipment->reservations()->with('user');

        // Por defecto solo reservas confirmadas (importante para calendarios)
        $status = $filters['status'] ?? 'confirmed';
        $query->where('status', $status);

        // Filtrar por rango de fechas (esencial para calendarios)
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->betweenDates($filters['start_date'], $filters['end_date']);
        }

        // Ordenar por fecha de inicio
        $query->orderBy('start_time', 'asc');

        return $query->get();
    }

    /**
     * Mark expired reservations as completed.
     * Este método puede ser llamado por un comando scheduled.
     *
     * @return int Number of reservations updated
     */
    public function markExpiredReservationsAsCompleted(): int
    {
        return Reservation::where('status', 'confirmed')
            ->where('end_time', '<', now())
            ->update(['status' => 'completed']);
    }

    /**
     * Check if equipment is available for a given time range.
     * Método privado para verificación de disponibilidad.
     *
     * @param  int|null  $excludeReservationId  Para actualización de reservas
     * @return bool True si hay conflicto, False si está disponible
     */
    private function checkEquipmentAvailability(
        int $equipmentId,
        string $startTime,
        string $endTime,
        ?int $excludeReservationId = null
    ): bool {
        $query = Reservation::query()
            ->forEquipment($equipmentId)
            ->blocking($startTime, $endTime);

        // Excluir una reserva específica (útil para actualizaciones)
        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        // Lectura con bloqueo: ver createReservation. Dentro de la transaccion
        // es lo que garantiza leer las filas que otra transaccion acaba de
        // confirmar, en vez del snapshot de REPEATABLE READ. En autocommit el
        // bloqueo se toma y se libera dentro de la propia sentencia.
        return $query->lockForUpdate()->exists();
    }
}
