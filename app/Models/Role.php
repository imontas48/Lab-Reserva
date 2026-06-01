<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'color',
        'is_system',
        'is_active',
    ];

    // =========================================================================
    // CASTS
    // =========================================================================

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // =========================================================================
    // RELACIONES
    // =========================================================================

    /**
     * Permisos asignados a este rol (tabla pivote role_permissions).
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    /**
     * Usuarios con asignación individual de este rol.
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * Reglas de grupo que usan este rol.
     */
    public function groupAssignments(): HasMany
    {
        return $this->hasMany(GroupRoleAssignment::class);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Solo roles activos.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Solo roles del sistema (no editables desde la UI).
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    /**
     * Solo roles personalizados (creados por admins).
     */
    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }
}
