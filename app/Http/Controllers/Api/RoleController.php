<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\SyncRolePermissionsRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService
    ) {}

    /**
     * GET /api/v1/roles
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Role::class);

        $roles = $this->roleService->getAllRoles(
            filters: $request->only(['search', 'is_active']),
            perPage: $request->integer('per_page') ?: null,
        );

        return RoleResource::collection($roles);
    }

    /**
     * POST /api/v1/roles
     */
    public function store(StoreRoleRequest $request): RoleResource
    {
        $role = $this->roleService->createRole($request->validated());

        return new RoleResource($role);
    }

    /**
     * GET /api/v1/roles/{role}
     */
    public function show(Role $role): RoleResource
    {
        $this->authorize('view', $role);

        return new RoleResource(
            $this->roleService->getRoleWithPermissions($role)
        );
    }

    /**
     * PUT /api/v1/roles/{role}
     */
    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        $updated = $this->roleService->updateRole($role, $request->validated());

        return new RoleResource($updated);
    }

    /**
     * DELETE /api/v1/roles/{role}
     */
    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        try {
            $this->roleService->deleteRole($role);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Rol eliminado correctamente.']);
    }

    /**
     * POST /api/v1/roles/{role}/permissions
     * Reemplaza el conjunto completo de permisos del rol (sync).
     */
    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): RoleResource
    {
        try {
            $updated = $this->roleService->syncPermissions(
                $role,
                $request->validated('permission_ids')
            );
        } catch (\RuntimeException $e) {
            abort(422, $e->getMessage());
        }

        return new RoleResource($updated);
    }
}
