<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cierre puntual de un laboratorio (o de todos, si lab_id es nulo):
 * festivos, mantenimiento, eventos.
 *
 * @property int $id
 * @property int|null $lab_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string $reason
 * @property int|null $created_by
 * @property-read Lab|null $lab
 */
class LabClosure extends Model
{
    use HasFactory;

    protected $fillable = ['lab_id', 'starts_at', 'ends_at', 'reason', 'created_by'];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Cierres que afectan al laboratorio: los suyos y los globales.
     *
     * @param  Builder<LabClosure>  $query
     */
    public function scopeAffectingLab($query, int $labId)
    {
        return $query->where(function (Builder $q) use ($labId) {
            $q->whereNull('lab_id')->orWhere('lab_id', $labId);
        });
    }

    /**
     * Misma semantica de intervalo semiabierto que las reservas.
     *
     * @param  Builder<LabClosure>  $query
     */
    public function scopeOverlapping($query, mixed $start, mixed $end)
    {
        return $query->where('starts_at', '<', Reservation::normalizeInstant($end))
            ->where('ends_at', '>', Reservation::normalizeInstant($start));
    }

    public function scopeUpcoming($query)
    {
        return $query->where('ends_at', '>=', now());
    }

    public function isGlobal(): bool
    {
        return $this->lab_id === null;
    }
}
