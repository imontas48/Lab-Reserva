<?php

namespace App\Services;

use App\Models\equipment;
use App\Models\labs;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EquipmentService
{
    /**
     * Get all equipment with optional filtering and pagination.
     *
     * @param array $filters
     * @param int|null $perPage
     * @return Collection|LengthAwarePaginator
     */
    public function getAllEquipment(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $query = equipment::with(['lab', 'software']);

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
     *
     * @param labs $lab
     * @param array $filters
     * @return Collection
     */
    public function getEquipmentByLab(labs $lab, array $filters = []): Collection
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
     *
     * @return Collection
     */
    public function getOperationalEquipment(): Collection
    {
        return equipment::with(['lab', 'software'])
            ->operational()
            ->orderBy('identifier')
            ->get();
    }

    /**
     * Create a new equipment.
     *
     * @param array $data
     * @return equipment
     */
    public function createEquipment(array $data): equipment
    {
        // Establecer valores por defecto
        $data['type'] = $data['type'] ?? 'PC';
        $data['is_operational'] = $data['is_operational'] ?? true;

        // Extraer la relación de software si existe
        $softwareIds = $data['software'] ?? [];
        unset($data['software']);

        // Crear el equipo
        $equipment = equipment::create($data);

        // Asociar el software si se proporcionó
        if (!empty($softwareIds)) {
            $equipment->software()->sync($softwareIds);
        }

        // Recargar con relaciones
        return $equipment->load(['lab', 'software']);
    }

    /**
     * Get equipment by ID.
     *
     * @param int $id
     * @return equipment
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getEquipmentById(int $id): equipment
    {
        return equipment::with(['lab', 'software'])->findOrFail($id);
    }

    /**
     * Update an existing equipment.
     *
     * @param equipment $equipment
     * @param array $data
     * @return equipment
     */
    public function updateEquipment(equipment $equipment, array $data): equipment
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
     * @param equipment $equipment
     * @return bool
     * @throws \Exception
     */
    public function deleteEquipment(equipment $equipment): bool
    {
        // Verificar si el equipo tiene reservas activas
        $activeReservations = $equipment->reservations()
            ->where('status', 'confirmed')
            ->where('end_time', '>=', now())
            ->exists();

        if ($activeReservations) {
            throw new \Exception(
                'No se puede eliminar el equipo porque tiene reservas activas. ' .
                'Cancele las reservas primero.'
            );
        }

        // Las relaciones many-to-many se eliminan automáticamente (equipment_software)
        // Las reservas se eliminan en cascada según la migración
        return $equipment->delete();
    }

    /**
     * Toggle operational status of equipment.
     *
     * @param equipment $equipment
     * @return equipment
     */
    public function toggleOperationalStatus(equipment $equipment): equipment
    {
        $equipment->update(['is_operational' => !$equipment->is_operational]);

        return $equipment->fresh(['lab', 'software']);
    }

    /**
     * Assign software to equipment.
     *
     * @param equipment $equipment
     * @param array $softwareIds
     * @return equipment
     */
    public function assignSoftware(equipment $equipment, array $softwareIds): equipment
    {
        $equipment->software()->sync($softwareIds);

        return $equipment->fresh(['lab', 'software']);
    }

    /**
     * Get equipment types (unique).
     *
     * @return Collection
     */
    public function getEquipmentTypes(): Collection
    {
        return equipment::select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');
    }

    /**
     * Get equipment availability for a date range.
     *
     * @param equipment $equipment
     * @param string $startTime
     * @param string $endTime
     * @return bool
     */
    public function isAvailable(equipment $equipment, string $startTime, string $endTime): bool
    {
        // Verificar si el equipo está operacional
        if (!$equipment->is_operational) {
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

        return !$hasConflict;
    }
}
