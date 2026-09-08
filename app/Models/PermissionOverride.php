<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionOverride extends Model
{
    // =========================================================================
    // CONSTANTES
    // =========================================================================

    /** Concede un permiso extra que el usuario no obtendría por sus roles. */
    public const TYPE_GRANT = 'grant';

    /** Revoca un permiso que el usuario obtendría por sus roles. */
    public const TYPE_REVOKE = 'revoke';

    protected $fillable = [
        'user_id',
        'permission_id',
        'type',
        'reason',
        'granted_by',
        'expires_at',
    ];

    // =========================================================================
    // CASTS
    // =========================================================================

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    // =========================================================================
    // RELACIONES
    // =========================================================================

    /**
     * Usuario al que aplica la sobreescritura.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Permiso afectado.
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    /**
     * Administrador que aplicó la sobreescritura.
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Solo sobreescrituras vigentes (no expiradas).
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Solo sobreescrituras de tipo GRANT.
     */
    public function scopeGrants(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_GRANT);
    }

    /**
     * Solo sobreescrituras de tipo REVOKE.
     */
    public function scopeRevocations(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_REVOKE);
    }
}
