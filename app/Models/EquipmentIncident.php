<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Incidencia sobre un equipo (averia, software que falla, periferico roto).
 *
 * @property int $id
 * @property int $equipment_id
 * @property int|null $reported_by
 * @property string $description
 * @property string $severity
 * @property string $status
 * @property string|null $resolution
 * @property int|null $resolved_by
 * @property Carbon|null $resolved_at
 * @property-read Equipment $equipment
 * @property-read User|null $reporter
 * @property-read User|null $resolver
 */
class EquipmentIncident extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const SEVERITIES = ['low', 'medium', 'high'];

    public const STATUSES = [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_RESOLVED];

    protected $fillable = [
        'equipment_id',
        'reported_by',
        'description',
        'severity',
        'status',
        'resolution',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_PROGRESS]);
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }
}
