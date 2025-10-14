<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Http\Resources\EquipmentResource;
use App\Models\equipment;
use App\Models\labs;
use App\Services\EquipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EquipmentController extends Controller
{
    public function __construct(
        private readonly EquipmentService $equipmentService
    ) {
        // TODO: Implementar autorización con middleware o policies
        // $this->authorizeResource(equipment::class, 'equipment');
    }

    /**
     * Display a listing of all equipment.
     * GET /api/v1/equipment
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = [
            'search' => $request->input('search'),
            'lab_id' => $request->input('lab_id'),
            'type' => $request->input('type'),
            'is_operational' => $request->input('is_operational'),
            'sort_by' => $request->input('sort_by', 'identifier'),
            'sort_order' => $request->input('sort_order', 'asc'),
        ];

        $perPage = $request->input('per_page');

        $equipment = $this->equipmentService->getAllEquipment($filters, $perPage);

        return EquipmentResource::collection($equipment);
    }

    /**
     * Display equipment for a specific lab (nested resource).
     * GET /api/v1/labs/{lab}/equipment
     */
    public function indexByLab(labs $lab, Request $request): AnonymousResourceCollection
    {
        // No necesitamos autorización adicional aquí,
        // ya que heredamos el permiso de viewAny del resource

        $filters = [
            'search' => $request->input('search'),
            'type' => $request->input('type'),
            'is_operational' => $request->input('is_operational'),
            'sort_by' => $request->input('sort_by', 'identifier'),
            'sort_order' => $request->input('sort_order', 'asc'),
        ];

        $equipment = $this->equipmentService->getEquipmentByLab($lab, $filters);

        // Eager load reservations para cálculo de estado
        $equipment->load(['reservations' => function ($query) {
            $query->where('start_time', '>=', now()->subHours(2))
                  ->where('status', '!=', 'cancelled')
                  ->orderBy('start_time', 'asc');
        }]);

        return EquipmentResource::collection($equipment);
    }

    /**
     * Store a newly created equipment in storage.
     * POST /api/v1/equipment
     */
    public function store(StoreEquipmentRequest $request): JsonResponse
    {
        $equipment = $this->equipmentService->createEquipment($request->validated());

        return (new EquipmentResource($equipment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified equipment.
     * GET /api/v1/equipment/{equipment}
     */
    public function show(equipment $equipment): EquipmentResource
    {
        // Cargamos las relaciones necesarias
        $equipment->load(['lab', 'software']);

        return new EquipmentResource($equipment);
    }

    /**
     * Update the specified equipment in storage.
     * PUT/PATCH /api/v1/equipment/{equipment}
     */
    public function update(UpdateEquipmentRequest $request, equipment $equipment): EquipmentResource
    {
        $updatedEquipment = $this->equipmentService->updateEquipment(
            $equipment,
            $request->validated()
        );

        return new EquipmentResource($updatedEquipment);
    }

    /**
     * Remove the specified equipment from storage.
     * DELETE /api/v1/equipment/{equipment}
     */
    public function destroy(equipment $equipment): JsonResponse
    {
        $this->equipmentService->deleteEquipment($equipment);

        return response()->json([
            'message' => 'Equipo eliminado exitosamente'
        ]);
    }
}
