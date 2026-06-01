<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGroupRoleAssignmentRequest;
use App\Http\Resources\GroupRoleAssignmentResource;
use App\Models\GroupRoleAssignment;
use App\Services\UserRoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GroupRoleAssignmentController extends Controller
{
    public function __construct(
        private readonly UserRoleService $userRoleService
    ) {}

    /**
     * GET /api/v1/group-role-assignments
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        abort_unless($request->user()->isAdmin(), 403);

        $assignments = $this->userRoleService->getAllGroupAssignments(
            $request->only(['is_active', 'group_type'])
        );

        return GroupRoleAssignmentResource::collection($assignments);
    }

    /**
     * POST /api/v1/group-role-assignments
     */
    public function store(StoreGroupRoleAssignmentRequest $request): GroupRoleAssignmentResource
    {
        try {
            $assignment = $this->userRoleService->createGroupAssignment(
                $request->validated(),
                $request->user()
            );
        } catch (\RuntimeException $e) {
            abort(422, $e->getMessage());
        }

        return new GroupRoleAssignmentResource($assignment->load(['role', 'grantedBy']));
    }

    /**
     * GET /api/v1/group-role-assignments/{groupRoleAssignment}
     */
    public function show(GroupRoleAssignment $groupRoleAssignment): GroupRoleAssignmentResource
    {
        abort_unless(request()->user()->isAdmin(), 403);

        return new GroupRoleAssignmentResource(
            $groupRoleAssignment->load(['role.permissions', 'grantedBy'])
        );
    }

    /**
     * PATCH /api/v1/group-role-assignments/{groupRoleAssignment}/toggle
     * Activa o desactiva una regla de grupo.
     */
    public function toggle(GroupRoleAssignment $groupRoleAssignment): GroupRoleAssignmentResource
    {
        abort_unless(request()->user()->isAdmin(), 403);

        $updated = $this->userRoleService->toggleGroupAssignment($groupRoleAssignment);

        return new GroupRoleAssignmentResource($updated->load(['role', 'grantedBy']));
    }

    /**
     * DELETE /api/v1/group-role-assignments/{groupRoleAssignment}
     */
    public function destroy(GroupRoleAssignment $groupRoleAssignment): JsonResponse
    {
        abort_unless(request()->user()->isAdmin(), 403);

        $this->userRoleService->deleteGroupAssignment($groupRoleAssignment);

        return response()->json(['message' => 'Regla de grupo eliminada correctamente.']);
    }
}
