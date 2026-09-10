<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLabLayoutRequest;
use App\Http\Resources\EquipmentResource;
use App\Http\Resources\LabResource;
use App\Models\Lab;
use App\Services\LabLayoutService;
use Illuminate\Http\JsonResponse;

class LabLayoutController extends Controller
{
    public function __construct(
        private readonly LabLayoutService $layout
    ) {}

    /**
     * Mapa del laboratorio: cuadricula + equipos con estado en tiempo real.
     * GET /api/v1/labs/{lab}/map
     */
    public function show(Lab $lab): JsonResponse
    {
        $this->authorize('view', $lab);

        return response()->json([
            'data' => [
                'lab' => (new LabResource($lab))->resolve(),
                'equipment' => EquipmentResource::collection($this->layout->equipmentForMap($lab))->resolve(),
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * PUT /api/v1/labs/{lab}/layout
     */
    public function update(UpdateLabLayoutRequest $request, Lab $lab): JsonResponse
    {
        $updated = $this->layout->update(
            $lab,
            (int) $request->validated('grid_rows'),
            (int) $request->validated('grid_cols'),
            $request->validated('positions')
        );

        return response()->json([
            'data' => [
                'lab' => (new LabResource($updated))->resolve(),
                'equipment' => EquipmentResource::collection($this->layout->equipmentForMap($updated))->resolve(),
            ],
        ]);
    }
}
