<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\AuthUserResource;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profile
    ) {}

    /**
     * PATCH /api/v1/profile
     */
    public function update(UpdateProfileRequest $request): AuthUserResource
    {
        return new AuthUserResource($this->profile->update($request->user(), $request->validated()));
    }

    /**
     * PUT /api/v1/profile/password
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->profile->changePassword(
            $request->user(),
            $request->validated('password'),
            $request->user()->currentAccessToken()?->id
        );

        return response()->json(['message' => 'Contraseña actualizada. Las demás sesiones se han cerrado.']);
    }
}
