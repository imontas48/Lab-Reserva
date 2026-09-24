<?php

namespace App\Support;

use App\Models\User;

/**
 * Resultado de dar de alta a un usuario con contrasena temporal.
 *
 * La contrasena en claro solo existe aqui, en el momento de crearla: se
 * entrega una vez a quien hizo el alta y no se guarda en ningun otro sitio.
 */
final class InvitedUser
{
    public function __construct(
        public readonly User $user,
        public readonly string $temporaryPassword,
    ) {}
}
