<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLabRequest;
use App\Http\Requests\UpdateLabRequest;
use App\Http\Resources\LabResource;
use App\Models\labs;
use App\Services\LabService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LabController extends Controller
{
    public function __construct(
        private readonly LabService $labService
    ) {
        // Aplicamos las políticas de autorización
        $this->authorizeResource(labs::class, 'lab');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $labs = $this->labService->getAllLabs();

        return LabResource::collection($labs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreLabRequest $request): LabResource
    {
        $lab = $this->labService->createLab($request->validated());

        return new LabResource($lab);
    }

    /**
     * Display the specified resource.
     */
    public function show(labs $lab): LabResource
    {
        return new LabResource($lab);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLabRequest $request, labs $lab): LabResource
    {
        $updatedLab = $this->labService->updateLab($lab, $request->validated());

        return new LabResource($updatedLab);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(labs $lab): JsonResponse
    {
        $this->labService->deleteLab($lab);

        return response()->json([
            'message' => 'Laboratorio eliminado exitosamente'
        ]);
    }
}
