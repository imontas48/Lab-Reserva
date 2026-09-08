<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
    ) {
        // TODO: Implementar autorización con middleware o policies
        // $this->authorizeResource(Lab::class, 'lab');
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
    public function show(Lab $lab): LabResource
    {
        return new LabResource($lab);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateLabRequest $request, Lab $lab): LabResource
    {
        $updatedLab = $this->labService->updateLab($lab, $request->validated());

        return new LabResource($updatedLab);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Lab $lab): JsonResponse
    {
        $this->labService->deleteLab($lab);

        return response()->json([
            'message' => 'Laboratorio eliminado exitosamente',
        ]);
    }
}
