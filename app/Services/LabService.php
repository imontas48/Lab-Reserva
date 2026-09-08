<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Lab;
use Illuminate\Database\Eloquent\Collection;
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
     * Create a new lab.
     */
    public function createLab(array $data): Lab
    {
        // Establecer valores por defecto si no se proporcionan
        $data['is_active'] = $data['is_active'] ?? true;

        return Lab::create($data);
    }

    /**
     * Update an existing lab.
     */
    public function updateLab(Lab $lab, array $data): Lab
    {
        $lab->update($data);

        return $lab->fresh();
    }

    /**
     * Delete a lab.
     *
     *
     * @throws \Exception
     */
    public function deleteLab(Lab $lab): bool
    {
        // Verificar si el laboratorio tiene equipos asociados
        if ($lab->equipment()->exists()) {
            throw new BusinessRuleException(
                'No se puede eliminar el laboratorio porque tiene equipos asociados. '.
                'Primero elimine o reasigne los equipos.'
            );
        }

        return $lab->delete();
    }
}
