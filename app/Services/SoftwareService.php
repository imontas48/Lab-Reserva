<?php

namespace App\Services;

use App\Models\Software;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class SoftwareService
{
    /**
     * Get all software with optional filtering and pagination.
     */
    public function getAllSoftware(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $query = Software::query();

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
     */
    public function createSoftware(array $data): Software
    {
        return Software::create($data);
    }

    /**
     * Get a software by ID.
     *
     * @throws ModelNotFoundException
     */
    public function getSoftwareById(int $id): Software
    {
        return Software::findOrFail($id);
    }

    /**
     * Update an existing software.
     */
    public function updateSoftware(Software $software, array $data): Software
    {
        $software->update($data);

        return $software->fresh();
    }

    /**
     * Delete a software.
     * Verifica que no esté asignado a ningún equipo antes de eliminar.
     *
     * @throws \Exception
     */
    public function deleteSoftware(Software $software): bool
    {
        // Verificar si el software está asignado a algún equipo
        if ($software->equipment()->exists()) {
            throw new \Exception(
                'No se puede eliminar el software porque está asignado a uno o más equipos. '.
                'Primero desasigne el software de todos los equipos.'
            );
        }

        return $software->delete();
    }

    /**
     * Get software with their equipment count.
     */
    public function getSoftwareWithEquipmentCount(): Collection
    {
        return Software::withCount('equipment')->orderBy('name')->get();
    }

    /**
     * Search software by name.
     */
    public function searchSoftware(string $search): Collection
    {
        return Software::search($search)->get();
    }

    /**
     * Get software assigned to a specific equipment.
     */
    public function getSoftwareByEquipment(int $equipmentId): Collection
    {
        return Software::whereHas('equipment', function ($query) use ($equipmentId) {
            $query->where('equipment_id', $equipmentId);
        })->orderBy('name')->get();
    }

    /**
     * Get the most used software (by equipment count).
     */
    public function getMostUsedSoftware(int $limit = 10): Collection
    {
        return Software::withCount('equipment')
            ->orderByDesc('equipment_count')
            ->limit($limit)
            ->get();
    }
}
