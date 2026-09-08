<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexEquipmentRequest;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
use App\Models\Lab;
use App\Services\EquipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EquipmentController extends Controller
{
    public function __construct(
        private readonly EquipmentService $equipmentService
    ) {}

    /**
     * Display a listing of all equipment.
     * GET /api/v1/equipment
     */
    public function index(IndexEquipmentRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Equipment::class);

        $equipment = $this->equipmentService->getAllEquipment($request->filters(), $request->perPage());

        return EquipmentResource::collection($equipment);
    }

    /**
     * Display equipment for a specific lab (nested resource).
     * GET /api/v1/labs/{lab}/equipment
     */
    public function indexByLab(Lab $lab, Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Equipment::class);

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
        $this->authorize('create', Equipment::class);

        $equipment = $this->equipmentService->createEquipment($request->validated());

        return (new EquipmentResource($equipment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified equipment.
     * GET /api/v1/equipment/{equipment}
     */
    public function show(Equipment $equipment): EquipmentResource
    {
        $this->authorize('view', $equipment);

        // Cargamos las relaciones necesarias
        $equipment->load(['lab', 'software']);

        return new EquipmentResource($equipment);
    }

    /**
     * Update the specified equipment in storage.
     * PUT/PATCH /api/v1/equipment/{equipment}
     */
    public function update(UpdateEquipmentRequest $request, Equipment $equipment): EquipmentResource
    {
        $this->authorize('update', $equipment);

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
    public function destroy(Equipment $equipment): JsonResponse
    {
        $this->authorize('delete', $equipment);

        $this->equipmentService->deleteEquipment($equipment);

        return response()->json([
            'message' => 'Equipo eliminado exitosamente',
        ]);
    }
}
