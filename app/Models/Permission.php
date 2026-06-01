<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permission extends Model
{
    protected $fillable = [
        'subject',
        'action',
        'description',
    ];

    // =========================================================================
    // RELACIONES
    // =========================================================================

    /**
     * Roles que incluyen este permiso.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    /**
     * Sobreescrituras individuales que referencian este permiso.
     */
    public function overrides(): HasMany
    {
        return $this->hasMany(PermissionOverride::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Filtra permisos por subject (recurso del sistema).
     */
    public function scopeForSubject(Builder $query, string $subject): Builder
    {
        return $query->where('subject', $subject);
    }

    /**
     * Filtra permisos por action.
     */
    public function scopeForAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }
}
