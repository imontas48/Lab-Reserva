<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupRoleAssignment extends Model
{
    protected $fillable = [
        'role_id',
        'group_type',
        'group_value',
        'granted_by',
        'is_active',
    ];

    // =========================================================================
    // CASTS
    // =========================================================================

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // =========================================================================
    // CONSTANTES
    // =========================================================================

    /**
     * Tipos de grupo soportados.
     * Diseñado para extensión futura sin cambios de esquema.
     */
    public const GROUP_TYPE_USER_TYPE = 'user_type';

    // =========================================================================
    // RELACIONES
    // =========================================================================

    /**
     * Rol que se asigna al grupo.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Administrador que creó la regla.
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Solo reglas activas.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Filtra por tipo de grupo.
     */
    public function scopeOfType(Builder $query, string $groupType): Builder
    {
        return $query->where('group_type', $groupType);
    }

    /**
     * Reglas que aplican al valor de grupo dado (ej. 'student').
     */
    public function scopeForGroupValue(Builder $query, string $groupType, string $groupValue): Builder
    {
        return $query->where('group_type', $groupType)
                     ->where('group_value', $groupValue);
    }
}
