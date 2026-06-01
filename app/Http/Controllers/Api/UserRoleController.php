<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRoleRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Http\Resources\UserRoleResource;
use App\Models\User;
use App\Models\UserRole;
use App\Services\UserRoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserRoleController extends Controller
{
    public function __construct(
        private readonly UserRoleService $userRoleService
    ) {}

    /**
     * GET /api/v1/users/{user}/roles
     * Lista todas las asignaciones de roles del usuario.
     */
    public function index(User $user): AnonymousResourceCollection
    {
        abort_unless(request()->user()->isAdmin(), 403);

        $userRoles = $this->userRoleService->getRolesForUser($user);

        return UserRoleResource::collection($userRoles);
    }

    /**
     * POST /api/v1/users/{user}/roles
     * Asigna un rol al usuario.
     */
    public function store(StoreUserRoleRequest $request, User $user): UserRoleResource
    {
        try {
            $userRole = $this->userRoleService->assignRole(
                $user,
                $request->validated(),
                $request->user()
            );
        } catch (\RuntimeException $e) {
            abort(422, $e->getMessage());
        }

        return new UserRoleResource($userRole->load(['role', 'grantedBy']));
    }

    /**
     * PATCH /api/v1/users/{user}/roles/{userRole}
     * Actualiza la fecha de expiración de una asignación de rol.
     */
    public function update(UpdateUserRoleRequest $request, User $user, UserRole $userRole): UserRoleResource
    {
        abort_unless($userRole->user_id === $user->id, 404);

        $updated = $this->userRoleService->updateAssignment($userRole, $request->validated());

        return new UserRoleResource($updated->load(['role', 'grantedBy']));
    }

    /**
     * DELETE /api/v1/users/{user}/roles/{userRole}
     * Revoca un rol del usuario.
     */
    public function destroy(User $user, UserRole $userRole): JsonResponse
    {
        abort_unless(request()->user()->isAdmin(), 403);
        abort_unless($userRole->user_id === $user->id, 404);

        $this->userRoleService->revokeRole($userRole);

        return response()->json(['message' => 'Rol revocado correctamente.']);
    }
}
