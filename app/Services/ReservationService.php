<?php

namespace App\Services;

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
     * @param array $validatedData
     * @param User $user
     * @return reservations
     * @throws \Exception
     */
    public function createReservation(array $validatedData, User $user): reservations
    {
        return DB::transaction(function () use ($validatedData, $user) {
            // Asignar el usuario autenticado
            $validatedData['user_id'] = $user->id;
            $validatedData['status'] = 'confirmed';

            // Doble comprobación de disponibilidad dentro de la transacción
            // Esto protege contra race conditions
            $hasConflict = $this->checkEquipmentAvailability(
                $validatedData['equipment_id'],
                $validatedData['start_time'],
                $validatedData['end_time']
            );

            if ($hasConflict) {
                throw new \Exception(
                    'El equipo ya no está disponible en el rango de tiempo seleccionado. ' .
                    'Por favor, seleccione otro horario.'
                );
            }

            // Verificar nuevamente que el equipo esté operacional
            $equipment = Equipment::lockForUpdate()->findOrFail($validatedData['equipment_id']);

            if (!$equipment->is_operational) {
                throw new \Exception(
                    'El equipo seleccionado no está operacional en este momento.'
                );
            }

            // Crear la reserva
            $reservation = Reservation::create($validatedData);

            // Cargar las relaciones para la respuesta
            return $reservation->load(['user', 'equipment.lab']);
        });
    }

    /**
     * Cancel a reservation.
     * Solo cancela si está en estado 'confirmed' y no ha comenzado.
     *
     * @param reservations $reservation
     * @return reservations
     * @throws \Exception
     */
    public function cancelReservation(Reservation $reservation): reservations
    {
        // Verificar que la reserva esté confirmada
        if ($reservation->status !== 'confirmed') {
            throw new \Exception(
                'Solo se pueden cancelar reservas confirmadas. ' .
                'Esta reserva ya está en estado: ' . $reservation->status
            );
        }

        // Verificar que la reserva no haya comenzado
        if ($reservation->start_time <= now()) {
            throw new \Exception(
                'No se puede cancelar una reserva que ya ha comenzado o pasado.'
            );
        }

        // Cambiar el estado a cancelled
        $reservation->update(['status' => 'cancelled']);

        // Recargar con relaciones
        return $reservation->fresh(['user', 'equipment.lab']);
    }

    /**
     * Update a reservation (admin only - mainly for status changes).
     *
     * @param reservations $reservation
     * @param array $data
     * @return reservations
     */
    public function updateReservation(Reservation $reservation, array $data): reservations
    {
        $reservation->update($data);

        return $reservation->fresh(['user', 'equipment.lab']);
    }

    /**
     * Delete a reservation (admin only).
     *
     * @param reservations $reservation
     * @return bool
     */
    public function deleteReservation(Reservation $reservation): bool
    {
        return $reservation->delete();
    }

    /**
     * Get all reservations with advanced filtering (admin only).
     *
     * @param array $filters
     * @param int|null $perPage
     * @return LengthAwarePaginator
     */
    public function getAllReservations(array $filters = [], ?int $perPage = null): LengthAwarePaginator
    {
        $query = Reservation::with(['user', 'equipment.lab']);

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
     *
     * @param User $user
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getReservationsForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Reservation::where('user_id', $user->id)
            ->with(['equipment.lab']);

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
     * @param string $role - 'student' | 'teacher'
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getReservationsByUserRole(string $role, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Reservation::with(['user', 'equipment.lab'])
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
     *
     * @param equipment $equipment
     * @param array $filters
     * @return Collection
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
     * Get active reservations (currently in progress).
     *
     * @return Collection
     */
    public function getActiveReservations(): Collection
    {
        return Reservation::with(['user', 'equipment.lab'])
            ->where('status', 'confirmed')
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->orderBy('end_time', 'asc')
            ->get();
    }

    /**
     * Get upcoming reservations (confirmed and in the future).
     *
     * @param int $limit
     * @return Collection
     */
    public function getUpcomingReservations(int $limit = 10): Collection
    {
        return Reservation::with(['user', 'equipment.lab'])
            ->confirmed()
            ->where('start_time', '>', now())
            ->orderBy('start_time', 'asc')
            ->limit($limit)
            ->get();
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
     * Get reservation statistics for a user.
     *
     * @param User $user
     * @return array
     */
    public function getUserStatistics(User $user): array
    {
        $total = Reservation::where('user_id', $user->id)->count();
        $confirmed = Reservation::where('user_id', $user->id)->confirmed()->count();
        $cancelled = Reservation::where('user_id', $user->id)->cancelled()->count();
        $completed = Reservation::where('user_id', $user->id)->completed()->count();

        return [
            'total' => $total,
            'confirmed' => $confirmed,
            'cancelled' => $cancelled,
            'completed' => $completed,
        ];
    }

    /**
     * Get reservation statistics for an equipment.
     *
     * @param equipment $equipment
     * @return array
     */
    public function getEquipmentStatistics(Equipment $equipment): array
    {
        $total = $equipment->reservations()->count();
        $confirmed = $equipment->reservations()->confirmed()->count();
        $completed = $equipment->reservations()->completed()->count();

        return [
            'total' => $total,
            'confirmed' => $confirmed,
            'completed' => $completed,
        ];
    }

    /**
     * Check if equipment is available for a given time range.
     * Método privado para verificación de disponibilidad.
     *
     * @param int $equipmentId
     * @param string $startTime
     * @param string $endTime
     * @param int|null $excludeReservationId Para actualización de reservas
     * @return bool True si hay conflicto, False si está disponible
     */
    private function checkEquipmentAvailability(
        int $equipmentId,
        string $startTime,
        string $endTime,
        ?int $excludeReservationId = null
    ): bool {
        $query = Reservation::where('equipment_id', $equipmentId)
            ->where('status', 'confirmed')
            ->where(function ($q) use ($startTime, $endTime) {
                // Mismo algoritmo de detección de solapamiento que en la validación
                $q->whereBetween('start_time', [$startTime, $endTime])
                  ->orWhereBetween('end_time', [$startTime, $endTime])
                  ->orWhere(function ($query) use ($startTime, $endTime) {
                      $query->where('start_time', '>=', $startTime)
                            ->where('end_time', '<=', $endTime);
                  })
                  ->orWhere(function ($query) use ($startTime, $endTime) {
                      $query->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                  });
            });

        // Excluir una reserva específica (útil para actualizaciones)
        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return $query->exists();
    }

    /**
     * Get available time slots for an equipment on a specific date.
     * Útil para el frontend mostrar slots disponibles.
     *
     * @param equipment $equipment
     * @param string $date Formato: Y-m-d
     * @param int $slotDuration En minutos (default: 60)
     * @return array
     */
    public function getAvailableTimeSlots(
        equipment $equipment,
        string $date,
        int $slotDuration = 60
    ): array {
        // Horario de operación (ejemplo: 8 AM a 8 PM)
        $startHour = 8;
        $endHour = 20;

        $availableSlots = [];
        $currentTime = new \DateTime("$date $startHour:00:00");
        $endTime = new \DateTime("$date $endHour:00:00");

        while ($currentTime < $endTime) {
            $slotEnd = clone $currentTime;
            $slotEnd->modify("+$slotDuration minutes");

            // Verificar si el slot está disponible
            $isAvailable = !$this->checkEquipmentAvailability(
                $equipment->id,
                $currentTime->format('Y-m-d H:i:s'),
                $slotEnd->format('Y-m-d H:i:s')
            );

            $availableSlots[] = [
                'start' => $currentTime->format('H:i'),
                'end' => $slotEnd->format('H:i'),
                'available' => $isAvailable,
            ];

            $currentTime = $slotEnd;
        }

        return $availableSlots;
    }
}
