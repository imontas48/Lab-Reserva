<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\Lab;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Plano del laboratorio: cuadricula y posicion de los puestos.
 */
class LabLayoutService
{
    /**
     * @param  array<int, array{equipment_id: int, row: int, col: int}>  $positions
     */
    public function update(Lab $lab, int $rows, int $cols, array $positions): Lab
    {
        return DB::transaction(function () use ($lab, $rows, $cols, $positions) {
            $lab->update(['grid_rows' => $rows, 'grid_cols' => $cols]);

            $lab->equipment()->update(['grid_row' => null, 'grid_col' => null]);

            foreach ($positions as $position) {
                Equipment::query()
                    ->whereKey($position['equipment_id'])
                    ->where('lab_id', $lab->id)
                    ->update(['grid_row' => $position['row'], 'grid_col' => $position['col']]);
            }

            return $lab->fresh();
        });
    }

    /**
     * Equipos del laboratorio con su estado en tiempo real y sus incidencias
     * abiertas, para el mapa.
     */
    public function equipmentForMap(Lab $lab): Collection
    {
        return $lab->equipment()
            ->with(['software', ...Equipment::statusRelations()])
            ->withCount('openIncidents')
            ->orderBy('grid_row')
            ->orderBy('grid_col')
            ->orderBy('identifier')
            ->get();
    }
}
