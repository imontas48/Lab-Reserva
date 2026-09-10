<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $lab_id
 * @property int $weekday
 * @property string $opens_at
 * @property string $closes_at
 */
class LabOpeningHour extends Model
{
    use HasFactory;

    /**
     * Nombres de los dias, indexados como Carbon::dayOfWeek.
     *
     * @var array<int, string>
     */
    public const WEEKDAYS = [
        0 => 'domingo',
        1 => 'lunes',
        2 => 'martes',
        3 => 'miércoles',
        4 => 'jueves',
        5 => 'viernes',
        6 => 'sábado',
    ];

    protected $fillable = ['lab_id', 'weekday', 'opens_at', 'closes_at'];

    protected $casts = ['weekday' => 'integer'];

    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    public function weekdayName(): string
    {
        return self::WEEKDAYS[$this->weekday] ?? (string) $this->weekday;
    }
}
