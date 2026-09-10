<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Equipment;
use App\Models\Lab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EquipmentService
{
    /**
     * Get all equipment with optional filtering and pagination.
     */
    public function getAllEquipment(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $query = Equipment::with(['lab', 'software', ...Equipment::statusRelations()]);

        // Filtrar por laboratorio
        if (isset($filters['lab_id'])) {
            $query->where('lab_id', $filters['lab_id']);
        }

        // Filtrar por tipo de equipo
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filtrar por estado operacional
        if (isset($filters['is_operational'])) {
            $query->where('is_operational', $filters['is_operational']);
        }

        // Búsqueda por identificador o especificaciones
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('identifier', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('specifications', 'like', "%{$search}%");
            });
        }

        $this->applyCapabilityFilters($query, $filters);

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'identifier';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        // Retornar paginado o colección completa
        return $perPage ? $query->paginate($perPage) : $query->get();
    }

    /**
     * "Un equipo con X libre de A a B": software instalado y ausencia de
     * reservas que bloqueen la franja, tanto del equipo como de su
     * laboratorio (una clase ocupa todos los puestos).
     *
     * @param  Builder<Equipment>  $query
     */
    private function applyCapabilityFilters($query, array $filters): void
    {
        if (isset($filters['software_id'])) {
            $query->whereHas('software', fn ($q) => $q->where('software.id', $filters['software_id']));
        }

        if (isset($filters['available_from'], $filters['available_to'])) {
            $from = $filters['available_from'];
            $to = $filters['available_to'];

            $query->where('is_operational', true)
                ->whereDoesntHave('reservations', fn ($q) => $q->blocking($from, $to))
                ->whereDoesntHave('lab.reservations', fn ($q) => $q->blocking($from, $to));
        }
    }

    /**
     * Get equipment for a specific lab.
     */
    public function getEquipmentByLab(Lab $lab, array $filters = []): Collection
    {
        $query = $lab->equipment()->with(['software', ...Equipment::statusRelations()]);

        // Filtrar por tipo
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filtrar por estado operacional
        if (isset($filters['is_operational'])) {
            $query->where('is_operational', $filters['is_operational']);
        }

        // Búsqueda
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('identifier', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('specifications', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'identifier';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->get();
    }

    /**
     * Create a new equipment.
     */
    public function createEquipment(array $data): Equipment
    {
        // Establecer valores por defecto
        $data['type'] = $data['type'] ?? 'PC';
        $data['is_operational'] = $data['is_operational'] ?? true;

        // Extraer la relación de software si existe
        $softwareIds = $data['software'] ?? [];
        unset($data['software']);

        // Crear el equipo
        $equipment = Equipment::create($data);

        // Asociar el software si se proporcionó
        if (! empty($softwareIds)) {
            $equipment->software()->sync($softwareIds);
        }

        // Recargar con relaciones
        return $equipment->load(['lab', 'software', ...Equipment::statusRelations()]);
    }

    /**
     * Update an existing equipment.
     */
    public function updateEquipment(Equipment $equipment, array $data): Equipment
    {
        // Extraer la relación de software si existe
        $softwareIds = $data['software'] ?? null;
        unset($data['software']);

        // Actualizar los campos del equipo
        $equipment->update($data);

        // Sincronizar software si se proporcionó
        if ($softwareIds !== null) {
            $equipment->software()->sync($softwareIds);
        }

        // Recargar con relaciones actualizadas
        return $equipment->fresh(['lab', 'software']);
    }

    /**
     * Delete an equipment.
     *
     * @throws \Exception
     */
    public function deleteEquipment(Equipment $equipment): bool
    {
        // Verificar si el equipo tiene reservas activas
        $activeReservations = $equipment->activeReservations()
            ->where('end_time', '>=', now())
            ->exists();

        if ($activeReservations) {
            throw new BusinessRuleException(
                'No se puede eliminar el equipo porque tiene reservas activas. '.
                'Cancele las reservas primero.'
            );
        }

        // Las relaciones many-to-many se eliminan automáticamente (equipment_software)
        // Las reservas se eliminan en cascada según la migración
        return $equipment->delete();
    }
}
