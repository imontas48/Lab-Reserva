<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexLabRequest;
use App\Http\Requests\StoreLabRequest;
use App\Http\Requests\UpdateLabRequest;
use App\Http\Resources\LabResource;
use App\Models\Lab;
use App\Services\LabService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LabController extends Controller
{
    public function __construct(
        private readonly LabService $labService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexLabRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Lab::class);

        // getAllLabs implementa busqueda, filtro por estado, orden y paginacion,
        // pero se llamaba sin argumentos: los filtros de la API no hacian nada
        // y el listado nunca paginaba.
        $labs = $this->labService->getAllLabs($request->filters(), $request->perPage());

        return LabResource::collection($labs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLabRequest $request): LabResource
    {
        $this->authorize('create', Lab::class);

        $lab = $this->labService->createLab($request->validated());

        return new LabResource($lab);
    }

    /**
     * Display the specified resource.
     */
    public function show(Lab $lab): LabResource
    {
        $this->authorize('view', $lab);

        return new LabResource($lab);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLabRequest $request, Lab $lab): LabResource
    {
        $this->authorize('update', $lab);

        $updatedLab = $this->labService->updateLab($lab, $request->validated());

        return new LabResource($updatedLab);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lab $lab): JsonResponse
    {
        $this->authorize('delete', $lab);

        $this->labService->deleteLab($lab);

        return response()->json([
            'message' => 'Laboratorio eliminado exitosamente',
        ]);
    }
}
