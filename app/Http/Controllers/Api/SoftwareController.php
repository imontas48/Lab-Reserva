<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexSoftwareRequest;
use App\Http\Requests\StoreSoftwareRequest;
use App\Http\Requests\UpdateSoftwareRequest;
use App\Http\Resources\SoftwareResource;
use App\Models\Software;
use App\Services\SoftwareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SoftwareController extends Controller
{
    public function __construct(
        private readonly SoftwareService $softwareService
    ) {}

    /**
     * Display a listing of all software.
     * GET /api/v1/software
     */
    public function index(IndexSoftwareRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Software::class);

        $software = $this->softwareService->getAllSoftware($request->filters(), $request->perPage());

        return SoftwareResource::collection($software);
    }

    /**
     * Store a newly created software in storage.
     * POST /api/v1/software
     */
    public function store(StoreSoftwareRequest $request): JsonResponse
    {
        $this->authorize('create', Software::class);

        $software = $this->softwareService->createSoftware($request->validated());

        return (new SoftwareResource($software))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified software.
     * GET /api/v1/software/{software}
     */
    public function show(Software $software): SoftwareResource
    {
        $this->authorize('view', $software);

        // Cargamos el contador de equipos
        $software->loadCount('equipment');

        return new SoftwareResource($software);
    }

    /**
     * Update the specified software in storage.
     * PUT/PATCH /api/v1/software/{software}
     */
    public function update(UpdateSoftwareRequest $request, Software $software): SoftwareResource
    {
        $this->authorize('update', $software);

        $updatedSoftware = $this->softwareService->updateSoftware(
            $software,
            $request->validated()
        );

        return new SoftwareResource($updatedSoftware);
    }

    /**
     * Remove the specified software from storage.
     * DELETE /api/v1/software/{software}
     */
    public function destroy(Software $software): JsonResponse
    {
        $this->authorize('delete', $software);

        $this->softwareService->deleteSoftware($software);

        return response()->json([
            'message' => 'Software eliminado exitosamente',
        ]);
    }
}
