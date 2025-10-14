<?php

namespace App\Services;

use App\Models\labs;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class LabService
{
    /**
     * Get all labs with optional filtering and pagination.
     *
     * @param array $filters
     * @param int|null $perPage
     * @return Collection|LengthAwarePaginator
     */
    public function getAllLabs(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $query = labs::query();

        // Aplicar filtro de búsqueda por nombre o ubicación
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        // Filtrar por estado activo/inactivo
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Ordenamiento (por defecto por nombre)
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        // Retornar paginado o colección completa
        return $perPage ? $query->paginate($perPage) : $query->get();
    }

    /**
     * Get only active labs.
     *
     * @return Collection
     */
    public function getActiveLabs(): Collection
    {
        return labs::active()->orderBy('name')->get();
    }

    /**
     * Create a new lab.
     *
     * @param array $data
     * @return labs
     */
    public function createLab(array $data): labs
    {
        // Establecer valores por defecto si no se proporcionan
        $data['is_active'] = $data['is_active'] ?? true;

        return labs::create($data);
    }

    /**
     * Get a lab by ID.
     *
     * @param int $id
     * @return labs
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getLabById(int $id): labs
    {
        return labs::findOrFail($id);
    }

    /**
     * Update an existing lab.
     *
     * @param labs $lab
     * @param array $data
     * @return labs
     */
    public function updateLab(labs $lab, array $data): labs
    {
        $lab->update($data);

        return $lab->fresh();
    }

    /**
     * Delete a lab.
     *
     * @param labs $lab
     * @return bool
     * @throws \Exception
     */
    public function deleteLab(labs $lab): bool
    {
        // Verificar si el laboratorio tiene equipos asociados
        if ($lab->equipment()->exists()) {
            throw new \Exception(
                'No se puede eliminar el laboratorio porque tiene equipos asociados. ' .
                'Primero elimine o reasigne los equipos.'
            );
        }

        return $lab->delete();
    }

    /**
     * Toggle active status of a lab.
     *
     * @param labs $lab
     * @return labs
     */
    public function toggleActiveStatus(labs $lab): labs
    {
        $lab->update(['is_active' => !$lab->is_active]);

        return $lab->fresh();
    }

    /**
     * Get labs with their equipment count.
     *
     * @return Collection
     */
    public function getLabsWithEquipmentCount(): Collection
    {
        return labs::withCount('equipment')->orderBy('name')->get();
    }

    /**
     * Get labs with their operational equipment count.
     *
     * @return Collection
     */
    public function getLabsWithOperationalEquipmentCount(): Collection
    {
        return labs::withCount('operationalEquipment')->orderBy('name')->get();
    }
}
