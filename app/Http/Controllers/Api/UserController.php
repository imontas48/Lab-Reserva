<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $users
    ) {}

    /**
     * GET /api/v1/users
     */
    public function index(IndexUserRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        return UserResource::collection(
            $this->users->getAll($request->filters(), $request->perPage() ?? 15)
        );
    }

    /**
     * POST /api/v1/users
     *
     * La contrasena temporal viaja fuera de "data" y solo en esta respuesta:
     * UserResource nunca la incluye.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $invitation = $this->users->invite($request->validated());

        return (new UserResource($invitation->user))
            ->additional(['temporary_password' => $invitation->temporaryPassword])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/v1/users/{user}
     */
    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user->loadCount(['reservations', 'activeReservations']));
    }

    /**
     * PATCH /api/v1/users/{user}
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        return new UserResource($this->users->update($user, $request->validated(), $request->user()));
    }

    /**
     * DELETE /api/v1/users/{user}
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->users->delete($user, $request->user());

        return response()->json(['message' => 'Usuario dado de baja exitosamente']);
    }

    /**
     * PATCH /api/v1/users/{user}/unblock
     */
    public function unblock(User $user): UserResource
    {
        $this->authorize('update', $user);

        return new UserResource($this->users->unblock($user));
    }
}
