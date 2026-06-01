<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Models\Permission;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PermissionController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissionService
    ) {}

    /**
     * GET /api/v1/permissions
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Permission::class);

        $permissions = $this->permissionService->getAllPermissions(
            $request->only(['subject', 'search'])
        );

        return PermissionResource::collection($permissions);
    }

    /**
     * POST /api/v1/permissions
     */
    public function store(StorePermissionRequest $request): PermissionResource
    {
        $permission = $this->permissionService->createPermission($request->validated());

        return new PermissionResource($permission);
    }

    /**
     * GET /api/v1/permissions/{permission}
     */
    public function show(Permission $permission): PermissionResource
    {
        $this->authorize('view', $permission);

        return new PermissionResource($permission);
    }

    /**
     * PUT /api/v1/permissions/{permission}
     */
    public function update(UpdatePermissionRequest $request, Permission $permission): PermissionResource
    {
        $updated = $this->permissionService->updatePermission($permission, $request->validated());

        return new PermissionResource($updated);
    }

    /**
     * DELETE /api/v1/permissions/{permission}
     */
    public function destroy(Permission $permission): JsonResponse
    {
        $this->authorize('delete', $permission);

        $this->permissionService->deletePermission($permission);

        return response()->json(['message' => 'Permiso eliminado correctamente.']);
    }

    /**
     * GET /api/v1/users/{user}/effective-permissions
     * Devuelve los permisos efectivos calculados para el usuario dado.
     */
    public function effectivePermissions(User $user): AnonymousResourceCollection
    {
        $this->authorize('viewEffective', Permission::class);

        $permissions = $this->permissionService->resolveEffectivePermissions($user);

        return PermissionResource::collection($permissions);
    }
}
