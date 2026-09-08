<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'lab_id',
        'identifier',
        'type',
        'specifications',
        'is_operational',
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

    /**
     * Get active reservations for this equipment.
     */
    public function activeReservations(): HasMany
    {
        return $this->hasMany(Reservation::class)->where('status', 'confirmed');
    }

    /**
     * Scope to get only operational equipment.
     */
    public function scopeOperational($query)
    {
        return $query->where('is_operational', true);
    }

    /**
     * Scope to filter by lab.
     */
    public function scopeInLab($query, $labId)
    {
        return $query->where('lab_id', $labId);
    }

    /**
     * Scope to filter by equipment type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the full identifier including lab name.
     */
    public function getFullIdentifierAttribute(): string
    {
        return "{$this->lab->name} - {$this->identifier}";
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

        $now = now();

        // Buscar reserva activa (está en uso AHORA)
        $activeReservation = $this->reservations()
            ->where('status', 'confirmed')
            ->where('start_time', '<=', $now)
            ->where('end_time', '>', $now)
            ->first();

        if ($activeReservation) {
            return [
                'status' => 'in_use',
                'details' => 'En uso hasta '.$activeReservation->end_time->format('H:i'),
                'until' => $activeReservation->end_time,
                'color' => 'blue',
                'icon' => 'clock',
            ];
        }

        // Buscar próxima reserva futura
        $nextReservation = $this->reservations()
            ->where('status', 'confirmed')
            ->where('start_time', '>', $now)
            ->orderBy('start_time', 'asc')
            ->first();

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

    /**
     * Verifica si el equipo está disponible en un rango de tiempo específico.
     *
     * @param  string  $startTime  Fecha/hora de inicio
     * @param  string  $endTime  Fecha/hora de fin
     */
    public function isAvailableInRange($startTime, $endTime): bool
    {
        // Si está fuera de servicio, no está disponible
        if (! $this->is_operational) {
            return false;
        }

        // Verificar si hay conflictos con reservas existentes
        $conflicts = $this->reservations()
            ->where('status', 'confirmed')
            ->where(function ($query) use ($startTime, $endTime) {
                // Detectar solapamiento de rangos de tiempo
                $query->whereBetween('start_time', [$startTime, $endTime])
                    ->orWhereBetween('end_time', [$startTime, $endTime])
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            })
            ->exists();

        return ! $conflicts;
    }

    /**
     * Scope para obtener solo equipos disponibles AHORA.
     */
    public function scopeCurrentlyAvailable($query)
    {
        $now = now();

        return $query->where('is_operational', true)
            ->whereDoesntHave('reservations', function ($q) use ($now) {
                $q->where('status', 'confirmed')
                    ->where('start_time', '<=', $now)
                    ->where('end_time', '>', $now);
            });
    }
}
