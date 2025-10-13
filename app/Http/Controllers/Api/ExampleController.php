<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExampleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de ejemplo - Demostración de Thin Controller
 * 
 * Principios aplicados:
 * - THIN CONTROLLER: Solo recibe Request, invoca Service, devuelve Response
 * - NO contiene lógica de negocio
 * - NO accede directamente a modelos (excepto para find simple)
 * - Toda la lógica está en ExampleService
 */
class ExampleController extends Controller
{
    /**
     * Inyección de dependencias del servicio
     */
    public function __construct(
        protected ExampleService $exampleService
    ) {}

    /**
     * Obtiene lista de recursos
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // 1. Invocar el servicio
        $resources = $this->exampleService->getResources();

        // 2. Devolver respuesta (idealmente con API Resource)
        return response()->json([
            'success' => true,
            'data' => $resources,
        ]);
    }

    /**
     * Crea un nuevo recurso
     * 
     * @param Request $request Debe ser un Form Request en producción
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        // En producción, usa StoreExampleRequest en lugar de Request
        // public function store(StoreExampleRequest $request): JsonResponse
        
        // 1. Obtener datos validados (ya validados por Form Request)
        $data = $request->all();

        // 2. Invocar el servicio
        $resource = $this->exampleService->createResource($data);

        // 3. Devolver respuesta con código 201 Created
        return response()->json([
            'success' => true,
            'message' => 'Recurso creado exitosamente',
            'data' => $resource,
        ], 201);
    }

    /**
     * Actualiza un recurso existente
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // En producción: public function update(UpdateExampleRequest $request, int $id)
        
        // 1. Obtener datos validados
        $data = $request->all();

        // 2. Invocar el servicio
        $resource = $this->exampleService->updateResource($id, $data);

        // 3. Devolver respuesta
        return response()->json([
            'success' => true,
            'message' => 'Recurso actualizado exitosamente',
            'data' => $resource,
        ]);
    }

    /**
     * Elimina un recurso
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        // 1. Invocar el servicio
        $deleted = $this->exampleService->deleteResource($id);

        // 2. Devolver respuesta
        return response()->json([
            'success' => true,
            'message' => 'Recurso eliminado exitosamente',
        ]);
    }
}
