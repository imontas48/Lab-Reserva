<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSoftwareRequest;
use App\Http\Requests\UpdateSoftwareRequest;
use App\Http\Resources\SoftwareResource;
use App\Models\software;
use App\Services\SoftwareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SoftwareController extends Controller
{
    public function __construct(
        private readonly SoftwareService $softwareService
    ) {
        // Aplicamos las políticas de autorización
        $this->authorizeResource(software::class, 'software');
    }

    /**
     * Display a listing of all software.
     * GET /api/v1/software
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = [
            'search' => $request->input('search'),
            'sort_by' => $request->input('sort_by', 'name'),
            'sort_order' => $request->input('sort_order', 'asc'),
        ];

        $perPage = $request->input('per_page');

        $software = $this->softwareService->getAllSoftware($filters, $perPage);

        return SoftwareResource::collection($software);
    }

    /**
     * Store a newly created software in storage.
     * POST /api/v1/software
     */
    public function store(StoreSoftwareRequest $request): JsonResponse
    {
        $software = $this->softwareService->createSoftware($request->validated());

        return (new SoftwareResource($software))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified software.
     * GET /api/v1/software/{software}
     */
    public function show(software $software): SoftwareResource
    {
        // Cargamos el contador de equipos
        $software->loadCount('equipment');

        return new SoftwareResource($software);
    }

    /**
     * Update the specified software in storage.
     * PUT/PATCH /api/v1/software/{software}
     */
    public function update(UpdateSoftwareRequest $request, software $software): SoftwareResource
    {
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
    public function destroy(software $software): JsonResponse
    {
        $this->softwareService->deleteSoftware($software);

        return response()->json([
            'message' => 'Software eliminado exitosamente'
        ]);
    }
}
