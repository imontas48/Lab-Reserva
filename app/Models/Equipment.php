<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $lab_id
 * @property string $identifier
 * @property string $type
 * @property string|null $specifications
 * @property bool $is_operational
 * @property int|null $grid_row
 * @property int|null $grid_col
 * @property-read Lab $lab
 * @property-read Reservation|null $currentReservation
 * @property-read Reservation|null $nextReservation
 */
class Equipment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'lab_id',
        'identifier',
        'type',
        'specifications',
        'is_operational',
        'grid_row',
        'grid_col',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_operational' => 'boolean',
    ];

    /**
     * The table associated with the model.
     */
    protected $table = 'equipment';

    /**
     * Get the lab that owns this equipment.
     */
    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class, 'lab_id');
    }

    /**
     * Get all software installed on this equipment.
     */
    public function software(): BelongsToMany
    {
        return $this->belongsToMany(Software::class, 'equipment_software');
    }

    /**
     * Get all reservations for this equipment.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(EquipmentIncident::class);
    }

    public function openIncidents(): HasMany
    {
        return $this->hasMany(EquipmentIncident::class)->open();
    }

    /**
     * La reserva que ocupa el equipo en este instante, si la hay.
     *
     * Existe para que getCurrentStatus() pueda resolverse con datos ya cargados.
     * Antes consultaba con $this->reservations(), un query builder nuevo que
     * ignora cualquier eager loading, de modo que serializar un listado de
     * equipos disparaba dos consultas por equipo (y cuatro, porque el Resource
     * llamaba al método dos veces).
     */
    public function currentReservation(): HasOne
    {
        return $this->hasOne(Reservation::class)->ofMany(
            ['start_time' => 'max'],
            fn ($query) => $query
                ->confirmed()
                ->where('start_time', '<=', now())
                ->where('end_time', '>', now())
        );
    }

    /**
     * La siguiente reserva futura del equipo, si la hay.
     */
    public function nextReservation(): HasOne
    {
        return $this->hasOne(Reservation::class)->ofMany(
            ['start_time' => 'min'],
            fn ($query) => $query
                ->confirmed()
                ->where('start_time', '>', now())
        );
    }

    /**
     * Relaciones necesarias para resolver getCurrentStatus() sin consultas extra.
     *
     * @return array<int, string>
     */
    public static function statusRelations(): array
    {
        return ['currentReservation', 'nextReservation', 'lab.currentReservation'];
    }

    /**
     * Get active reservations for this equipment.
     */
    public function activeReservations(): HasMany
    {
        return $this->hasMany(Reservation::class)
            ->whereIn('status', Reservation::BLOCKING_STATUSES);
    }

    /**
     * Obtiene el estado actual del equipo basado en reservas y disponibilidad física.
     *
     * Estados posibles:
     * - 'available': Disponible para reservar
     * - 'in_use': En uso actualmente (tiene reserva activa)
     * - 'reserved': Tiene reservas futuras pero no está en uso ahora
     * - 'out_of_service': Fuera de servicio (mantenimiento)
     *
     * @return array ['status' => string, 'details' => string|null]
     */
    public function getCurrentStatus(): array
    {
        // Si el equipo está fuera de servicio físicamente
        if (! $this->is_operational) {
            return [
                'status' => 'out_of_service',
                'details' => 'Equipo en mantenimiento',
                'color' => 'red',
                'icon' => 'wrench',
            ];
        }

        // Una clase en curso bloquea todos los equipos del laboratorio. Solo se
        // consulta si el laboratorio vino cargado, para no reabrir el N+1 en
        // los listados (statusRelations() incluye lab.currentReservation).
        $labClass = $this->relationLoaded('lab') ? $this->lab?->currentReservation : null;

        if ($labClass) {
            return [
                'status' => 'in_use',
                'details' => 'Laboratorio en clase hasta '.$labClass->end_time->format('H:i'),
                'until' => $labClass->end_time,
                'color' => 'purple',
                'icon' => 'academic',
            ];
        }

        // Acceso a la RELACIÓN, no al query builder: si el listado la cargó por
        // adelantado no se dispara ninguna consulta, y si no, se resuelve al
        // vuelo igual que antes.
        $activeReservation = $this->currentReservation;

        if ($activeReservation) {
            return [
                'status' => 'in_use',
                'details' => 'En uso hasta '.$activeReservation->end_time->format('H:i'),
                'until' => $activeReservation->end_time,
                'color' => 'blue',
                'icon' => 'clock',
            ];
        }

        $nextReservation = $this->nextReservation;

        if ($nextReservation) {
            return [
                'status' => 'reserved',
                'details' => 'Reservado para '.$nextReservation->start_time->format('d/m H:i'),
                'next_reservation' => $nextReservation->start_time,
                'color' => 'yellow',
                'icon' => 'calendar',
            ];
        }

        // Si no hay reservas y está operacional, está disponible
        return [
            'status' => 'available',
            'details' => 'Disponible',
            'color' => 'green',
            'icon' => 'check',
        ];
    }
}
