<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermissionOverrideRequest;
use App\Http\Requests\UpdatePermissionOverrideRequest;
use App\Http\Resources\PermissionOverrideResource;
use App\Models\PermissionOverride;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PermissionOverrideController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissionService
    ) {}

    /**
     * GET /api/v1/users/{user}/permission-overrides
     */
    public function index(User $user): AnonymousResourceCollection
    {
        abort_unless(request()->user()->isAdmin(), 403);

        $overrides = $this->permissionService->getOverridesForUser($user);

        return PermissionOverrideResource::collection($overrides);
    }

    /**
     * POST /api/v1/users/{user}/permission-overrides
     */
    public function store(StorePermissionOverrideRequest $request, User $user): PermissionOverrideResource
    {
        $override = $this->permissionService->createOverride(
            $user,
            $request->validated(),
            $request->user()
        );

        return new PermissionOverrideResource($override->load(['permission', 'grantedBy']));
    }

    /**
     * GET /api/v1/users/{user}/permission-overrides/{permissionOverride}
     */
    public function show(User $user, PermissionOverride $permissionOverride): PermissionOverrideResource
    {
        abort_unless(request()->user()->isAdmin(), 403);
        abort_unless($permissionOverride->user_id === $user->id, 404);

        return new PermissionOverrideResource(
            $permissionOverride->load(['permission', 'grantedBy'])
        );
    }

    /**
     * PATCH /api/v1/users/{user}/permission-overrides/{permissionOverride}
     */
    public function update(
        UpdatePermissionOverrideRequest $request,
        User $user,
        PermissionOverride $permissionOverride
    ): PermissionOverrideResource {
        abort_unless($permissionOverride->user_id === $user->id, 404);

        $updated = $this->permissionService->updateOverride($permissionOverride, $request->validated());

        return new PermissionOverrideResource($updated->load(['permission', 'grantedBy']));
    }

    /**
     * DELETE /api/v1/users/{user}/permission-overrides/{permissionOverride}
     */
    public function destroy(User $user, PermissionOverride $permissionOverride): JsonResponse
    {
        abort_unless(request()->user()->isAdmin(), 403);
        abort_unless($permissionOverride->user_id === $user->id, 404);

        $this->permissionService->deleteOverride($permissionOverride);

        return response()->json(['message' => 'Sobreescritura de permiso eliminada correctamente.']);
    }
}
