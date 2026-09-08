<?php

namespace App\Services;

use App\Models\Lab;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

class LabService
{
    /**
     * Get all labs with optional filtering and pagination.
     */
    public function getAllLabs(array $filters = [], ?int $perPage = null): Collection|LengthAwarePaginator
    {
        $query = Lab::query();

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
     */
    public function getActiveLabs(): Collection
    {
        return Lab::active()->orderBy('name')->get();
    }

    /**
     * Create a new lab.
     */
    public function createLab(array $data): labs
    {
        // Establecer valores por defecto si no se proporcionan
        $data['is_active'] = $data['is_active'] ?? true;

        return Lab::create($data);
    }

    /**
     * Get a lab by ID.
     *
     * @throws ModelNotFoundException
     */
    public function getLabById(int $id): labs
    {
        return Lab::findOrFail($id);
    }

    /**
     * Update an existing lab.
     *
     * @param  labs  $lab
     */
    public function updateLab(Lab $lab, array $data): labs
    {
        $lab->update($data);

        return $lab->fresh();
    }

    /**
     * Delete a lab.
     *
     * @param  labs  $lab
     *
     * @throws \Exception
     */
    public function deleteLab(Lab $lab): bool
    {
        // Verificar si el laboratorio tiene equipos asociados
        if ($lab->equipment()->exists()) {
            throw new \Exception(
                'No se puede eliminar el laboratorio porque tiene equipos asociados. '.
                'Primero elimine o reasigne los equipos.'
            );
        }

        return $lab->delete();
    }

    /**
     * Toggle active status of a lab.
     *
     * @param  labs  $lab
     */
    public function toggleActiveStatus(Lab $lab): labs
    {
        $lab->update(['is_active' => ! $lab->is_active]);

        return $lab->fresh();
    }

    /**
     * Get labs with their equipment count.
     */
    public function getLabsWithEquipmentCount(): Collection
    {
        return Lab::withCount('equipment')->orderBy('name')->get();
    }

    /**
     * Get labs with their operational equipment count.
     */
    public function getLabsWithOperationalEquipmentCount(): Collection
    {
        return Lab::withCount('operationalEquipment')->orderBy('name')->get();
    }
}
