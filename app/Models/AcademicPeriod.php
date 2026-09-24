<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Periodo academico (semestre, cuatrimestre). Acota las reservas
 * recurrentes y sirve de referencia al calendario.
 *
 * @property int $id
 * @property string $name
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property bool $is_active
 */
class AcademicPeriod extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'starts_on', 'ends_on', 'is_active'];

    protected $casts = [
        'starts_on' => 'date',
        'ends_on' => 'date',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Periodo que contiene la fecha dada (hoy por defecto).
     */
    public function scopeContaining($query, mixed $date = null)
    {
        $day = Carbon::parse($date ?? now())->toDateString();

        return $query->whereDate('starts_on', '<=', $day)->whereDate('ends_on', '>=', $day);
    }

    public static function current(): ?self
    {
        return static::query()->active()->containing()->orderBy('ends_on')->first();
    }
}
