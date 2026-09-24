<?php

namespace App\Policies;

use App\Models\EquipmentIncident;
use App\Models\User;

class IncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('incidents', 'viewAny');
    }

    /**
     * El que la reporto la puede ver; quien atiende incidencias ve todas.
     */
    public function view(User $user, EquipmentIncident $incident): bool
    {
        return $user->hasPermission('incidents', 'viewAny')
            || $incident->reported_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('incidents', 'create');
    }

    public function update(User $user, EquipmentIncident $incident): bool
    {
        return $user->hasPermission('incidents', 'update');
    }
}
