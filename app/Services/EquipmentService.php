<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Equipment;
use App\Models\Lab;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class EquipmentService
{
    /**
     * Get all equipment with optional filtering and pagination.
     */
    public function getAllEquipment(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $query = Equipment::with(['lab', 'software']);

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

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'identifier';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        // Retornar paginado o colección completa
        return $perPage ? $query->paginate($perPage) : $query->get();
    }

    /**
     * Get equipment for a specific lab.
     */
    public function getEquipmentByLab(Lab $lab, array $filters = []): Collection
    {
        $query = $lab->equipment()->with('software');

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
     * Get only operational equipment.
     */
    public function getOperationalEquipment(): Collection
    {
        return Equipment::with(['lab', 'software'])
            ->operational()
            ->orderBy('identifier')
            ->get();
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
        return $equipment->load(['lab', 'software']);
    }

    /**
     * Get equipment by ID.
     *
     * @throws ModelNotFoundException
     */
    public function getEquipmentById(int $id): Equipment
    {
        return Equipment::with(['lab', 'software'])->findOrFail($id);
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
        $activeReservations = $equipment->reservations()
            ->where('status', 'confirmed')
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

    /**
     * Toggle operational status of equipment.
     */
    public function toggleOperationalStatus(Equipment $equipment): Equipment
    {
        $equipment->update(['is_operational' => ! $equipment->is_operational]);

        return $equipment->fresh(['lab', 'software']);
    }

    /**
     * Assign software to equipment.
     */
    public function assignSoftware(Equipment $equipment, array $softwareIds): Equipment
    {
        $equipment->software()->sync($softwareIds);

        return $equipment->fresh(['lab', 'software']);
    }

    /**
     * Get equipment types (unique).
     */
    public function getEquipmentTypes(): Collection
    {
        return Equipment::select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');
    }

    /**
     * Get equipment availability for a date range.
     */
    public function isAvailable(Equipment $equipment, string $startTime, string $endTime): bool
    {
        // Verificar si el equipo está operacional
        if (! $equipment->is_operational) {
            return false;
        }

        // Verificar si hay reservas confirmadas que se solapen
        $hasConflict = $equipment->reservations()
            ->where('status', 'confirmed')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            })
            ->exists();

        return ! $hasConflict;
    }
}
