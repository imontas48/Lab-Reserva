<?php

namespace App\Services;

use App\Models\software;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SoftwareService
{
    /**
     * Get all software with optional filtering and pagination.
     *
     * @param array $filters
     * @param int|null $perPage
     * @return Collection|LengthAwarePaginator
     */
    public function getAllSoftware(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $query = software::query();

        // Aplicar filtro de búsqueda por nombre o versión
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('version', 'like', "%{$search}%");
            });
        }

        // Ordenamiento (por defecto por nombre)
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        // Retornar paginado o colección completa
        return $perPage ? $query->paginate($perPage) : $query->get();
    }

    /**
     * Create a new software.
     *
     * @param array $data
     * @return software
     */
    public function createSoftware(array $data): software
    {
        return software::create($data);
    }

    /**
     * Get a software by ID.
     *
     * @param int $id
     * @return software
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getSoftwareById(int $id): software
    {
        return software::findOrFail($id);
    }

    /**
     * Update an existing software.
     *
     * @param software $software
     * @param array $data
     * @return software
     */
    public function updateSoftware(software $software, array $data): software
    {
        $software->update($data);

        return $software->fresh();
    }

    /**
     * Delete a software.
     * Verifica que no esté asignado a ningún equipo antes de eliminar.
     *
     * @param software $software
     * @return bool
     * @throws \Exception
     */
    public function deleteSoftware(software $software): bool
    {
        // Verificar si el software está asignado a algún equipo
        if ($software->equipment()->exists()) {
            throw new \Exception(
                'No se puede eliminar el software porque está asignado a uno o más equipos. ' .
                'Primero desasigne el software de todos los equipos.'
            );
        }

        return $software->delete();
    }

    /**
     * Get software with their equipment count.
     *
     * @return Collection
     */
    public function getSoftwareWithEquipmentCount(): Collection
    {
        return software::withCount('equipment')->orderBy('name')->get();
    }

    /**
     * Search software by name.
     *
     * @param string $search
     * @return Collection
     */
    public function searchSoftware(string $search): Collection
    {
        return software::search($search)->get();
    }

    /**
     * Get software assigned to a specific equipment.
     *
     * @param int $equipmentId
     * @return Collection
     */
    public function getSoftwareByEquipment(int $equipmentId): Collection
    {
        return software::whereHas('equipment', function ($query) use ($equipmentId) {
            $query->where('equipment_id', $equipmentId);
        })->orderBy('name')->get();
    }

    /**
     * Get the most used software (by equipment count).
     *
     * @param int $limit
     * @return Collection
     */
    public function getMostUsedSoftware(int $limit = 10): Collection
    {
        return software::withCount('equipment')
            ->orderByDesc('equipment_count')
            ->limit($limit)
            ->get();
    }
}
