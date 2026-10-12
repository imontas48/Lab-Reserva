<?php

namespace App\Policies;

use App\Models\User;

/**
 * Registro de actividades: solo lectura y solo para el administrador.
 *
 * Igual que RolePolicy, se ancla al rol base del ENUM users.role y no a un
 * permiso del RBAC: el registro sirve para auditar a quien administra el RBAC,
 * asi que no debe poder concederse con el propio RBAC.
 *
 * No hay create, update ni delete: el registro no se modifica desde la API.
 */
class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }
}
