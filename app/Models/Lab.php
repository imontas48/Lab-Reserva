<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $location
 * @property int $capacity
 * @property string|null $description
 * @property bool $is_active
 * @property int $grid_rows
 * @property int $grid_cols
 * @property-read Collection<int, LabOpeningHour> $openingHours
 * @property-read Reservation|null $currentReservation
 */
class Lab extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'location',
        'capacity',
        'description',
        'is_active',
        'grid_rows',
        'grid_cols',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all equipment belonging to this lab.
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class, 'lab_id');
    }

    /**
     * Get only operational equipment for this lab.
     */
    public function operationalEquipment(): HasMany
    {
        return $this->hasMany(Equipment::class, 'lab_id')->where('is_operational', true);
    }

    /**
     * Reservas del laboratorio completo (no incluye las de equipos sueltos).
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'lab_id');
    }

    /**
     * La clase que ocupa el laboratorio completo en este instante, si la hay.
     * Permite que el estado de cada equipo refleje el bloqueo por clase sin
     * consultas adicionales (se carga con 'lab.currentReservation').
     */
    public function currentReservation(): HasOne
    {
        return $this->hasOne(Reservation::class, 'lab_id')->ofMany(
            ['start_time' => 'max'],
            fn ($query) => $query
                ->where('type', Reservation::TYPE_LAB)
                ->confirmed()
                ->where('start_time', '<=', now())
                ->where('end_time', '>', now())
        );
    }

    /**
     * Horario de apertura por dia de la semana. Sin filas = sin restriccion.
     */
    public function openingHours(): HasMany
    {
        return $this->hasMany(LabOpeningHour::class)->orderBy('weekday');
    }

    /**
     * Cierres propios del laboratorio (los globales tienen lab_id nulo).
     */
    public function closures(): HasMany
    {
        return $this->hasMany(LabClosure::class);
    }

    /**
     * Scope to get only active labs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
