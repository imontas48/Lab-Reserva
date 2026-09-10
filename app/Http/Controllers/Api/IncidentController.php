<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexIncidentRequest;
use App\Http\Requests\StoreIncidentRequest;
use App\Http\Requests\UpdateIncidentRequest;
use App\Http\Resources\IncidentResource;
use App\Models\Equipment;
use App\Models\EquipmentIncident;
use App\Services\IncidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IncidentController extends Controller
{
    public function __construct(
        private readonly IncidentService $incidents
    ) {}

    /**
     * GET /api/v1/incidents
     */
    public function index(IndexIncidentRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', EquipmentIncident::class);

        return IncidentResource::collection(
            $this->incidents->getAll($request->filters(), $request->perPage() ?? 15)
        );
    }

    /**
     * GET /api/v1/equipment/{equipment}/incidents
     */
    public function indexForEquipment(Equipment $equipment): AnonymousResourceCollection
    {
        $this->authorize('view', $equipment);

        return IncidentResource::collection($this->incidents->getForEquipment($equipment));
    }

    /**
     * POST /api/v1/equipment/{equipment}/incidents
     */
    public function store(StoreIncidentRequest $request, Equipment $equipment): JsonResponse
    {
        $incident = $this->incidents->report($equipment, $request->validated(), $request->user());

        return (new IncidentResource($incident))->response()->setStatusCode(201);
    }

    /**
     * GET /api/v1/incidents/{incident}
     */
    public function show(EquipmentIncident $incident): IncidentResource
    {
        $this->authorize('view', $incident);

        return new IncidentResource($incident->load(IncidentService::RELATIONS));
    }

    /**
     * PATCH /api/v1/incidents/{incident}
     */
    public function update(UpdateIncidentRequest $request, EquipmentIncident $incident): IncidentResource
    {
        return new IncidentResource($this->incidents->update($incident, $request->validated(), $request->user()));
    }
}
