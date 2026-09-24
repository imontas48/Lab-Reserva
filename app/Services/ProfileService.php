<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }

    /**
     * Cambia la contraseña y revoca el resto de tokens: si la contraseña se
     * cambia porque alguien mas la conocia, sus sesiones deben caer.
     *
     * Tambien levanta la marca de contrasena temporal: es justo lo que el
     * usuario invitado tiene que hacer para poder usar el sistema.
     */
    public function changePassword(User $user, string $password, int|string|null $keepTokenId = null): void
    {
        $user->forceFill([
            'password' => Hash::make($password),
            'must_change_password' => false,
        ])->save();

        $user->tokens()
            ->when($keepTokenId !== null, fn ($q) => $q->whereKeyNot($keepTokenId))
            ->delete();
    }
}
