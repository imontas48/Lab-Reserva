<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Equipment;
use App\Models\EquipmentIncident;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class IncidentService
{
    /**
     * @var array<int, string>
     */
    public const RELATIONS = ['equipment.lab', 'reporter', 'resolver'];

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = EquipmentIncident::query()->with(self::RELATIONS);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        } elseif (empty($filters['include_resolved'])) {
            $query->open();
        }

        if (isset($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (isset($filters['lab_id'])) {
            $query->whereHas('equipment', fn (Builder $q) => $q->where('lab_id', $filters['lab_id']));
        }

        if (isset($filters['equipment_id'])) {
            $query->where('equipment_id', $filters['equipment_id']);
        }

        return $query
            ->orderByRaw("FIELD(severity, 'high', 'medium', 'low')")
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getForEquipment(Equipment $equipment): Collection
    {
        return $equipment->incidents()->with(['reporter', 'resolver'])->latest()->limit(20)->get();
    }

    public function report(Equipment $equipment, array $data, User $reporter): EquipmentIncident
    {
        $incident = $equipment->incidents()->create([
            'reported_by' => $reporter->id,
            'description' => $data['description'],
            'severity' => $data['severity'] ?? 'medium',
        ]);

        // fresh(): el estado por defecto lo pone la base de datos.
        return $incident->fresh(self::RELATIONS);
    }

    /**
     * Atiende o resuelve la incidencia. Al resolver se puede fijar el estado
     * operativo del equipo en la misma operacion (volver a servicio o dejarlo
     * fuera hasta la reparacion).
     *
     * @throws BusinessRuleException
     */
    public function update(EquipmentIncident $incident, array $data, User $actor): EquipmentIncident
    {
        if ($incident->isResolved() && ($data['status'] ?? null) !== EquipmentIncident::STATUS_RESOLVED) {
            throw new BusinessRuleException('Una incidencia resuelta no se puede reabrir; crea una nueva.');
        }

        return DB::transaction(function () use ($incident, $data, $actor) {
            $status = $data['status'] ?? $incident->status;
            $wasResolved = $incident->isResolved();

            $incident->fill([
                'status' => $status,
                'resolution' => $data['resolution'] ?? $incident->resolution,
            ]);

            if ($status === EquipmentIncident::STATUS_RESOLVED && ! $wasResolved) {
                $incident->resolved_by = $actor->id;
                $incident->resolved_at = now();
            }

            $incident->save();

            if (array_key_exists('equipment_operational', $data) && $data['equipment_operational'] !== null) {
                $incident->equipment->update(['is_operational' => (bool) $data['equipment_operational']]);
            }

            return $incident->fresh(self::RELATIONS);
        });
    }
}
