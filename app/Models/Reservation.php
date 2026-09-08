<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    /**
     * Transiciones permitidas del ciclo de vida de una reserva.
     *
     * cancelled y completed son terminales. Reactivar una reserva cancelada
     * permitia crear un solapamiento sin pasar por ninguna comprobacion de
     * disponibilidad: bastaba cancelar, esperar a que otro ocupase la franja y
     * volver a confirmar.
     *
     * @var array<string, array<int, string>>
     */
    public const TRANSITIONS = [
        self::STATUS_CONFIRMED => [self::STATUS_CANCELLED, self::STATUS_COMPLETED],
        self::STATUS_CANCELLED => [],
        self::STATUS_COMPLETED => [],
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'equipment_id',
        'start_time',
        'end_time',
        'status',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the user that made this reservation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the equipment for this reservation.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Scope to get confirmed reservations.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope to get cancelled reservations.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope to get completed reservations.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Reservas que se solapan con el intervalo dado.
     *
     * Unica definicion de solapamiento del sistema. Estaba duplicada en cuatro
     * sitios (StoreReservationRequest, ReservationService, EquipmentService y
     * Equipment) con cuatro casos redundantes, y una de las copias solo tenia
     * tres, de modo que era mas permisiva que las otras.
     *
     * Semantica de intervalo medio abierto [inicio, fin): dos reservas se
     * solapan si `inicio < otro_fin AND fin > otro_inicio`. Con whereBetween,
     * que es inclusivo en ambos extremos, una reserva de 11:00-12:00 chocaba
     * con otra de 10:00-11:00: las franjas consecutivas, que son el caso normal
     * de un laboratorio por horas, se rechazaban siempre.
     *
     * @param  Builder<Reservation>  $query
     */
    public function scopeOverlapping($query, mixed $start, mixed $end)
    {
        return $query->where('start_time', '<', self::normalizeInstant($end))
            ->where('end_time', '>', self::normalizeInstant($start));
    }

    /**
     * Normaliza un instante al formato con el que se almacena en la columna.
     *
     * Es imprescindible. Pasar la cadena ISO-8601 tal cual hacia que MySQL la
     * convirtiese aplicando el huso de la sesion: "2026-09-09T10:30:00+00:00"
     * se convertia en 2026-09-09 06:30:00 con una sesion en -04:00, cuatro
     * horas por debajo del valor realmente almacenado. La comparacion nunca
     * casaba y el solapamiento no se detectaba.
     *
     * Las columnas son DATETIME sin huso, y el contrato del proyecto es
     * almacenar en UTC (config('app.timezone')), asi que aqui se convierte al
     * huso de la aplicacion y se formatea sin offset.
     */
    public static function normalizeInstant(mixed $value): string
    {
        return Carbon::parse($value)
            ->setTimezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');
    }

    /**
     * Reservas que bloquean una franja: solapan y siguen confirmadas.
     *
     * @param  Builder<Reservation>  $query
     */
    public function scopeBlocking($query, mixed $start, mixed $end)
    {
        return $query->confirmed()->overlapping($start, $end);
    }

    /**
     * Scope to get reservations for a specific date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('start_time', [$startDate, $endDate])
                ->orWhereBetween('end_time', [$startDate, $endDate])
                ->orWhere(function ($query) use ($startDate, $endDate) {
                    $query->where('start_time', '<=', $startDate)
                        ->where('end_time', '>=', $endDate);
                });
        });
    }

    /**
     * Scope to get reservations for a specific equipment.
     */
    public function scopeForEquipment($query, $equipmentId)
    {
        return $query->where('equipment_id', $equipmentId);
    }

    /**
     * ¿Es válido pasar de este estado al indicado?
     */
    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    /**
     * Estados a los que puede pasar la reserva desde su estado actual.
     *
     * @return array<int, string>
     */
    public function allowedTransitions(): array
    {
        return self::TRANSITIONS[$this->status] ?? [];
    }

    /**
     * Check if the reservation is currently active.
     */
    public function getIsActiveAttribute(): bool
    {
        $now = Carbon::now();

        return $this->status === 'confirmed'
            && $this->start_time <= $now
            && $this->end_time >= $now;
    }

    /**
     * Get the duration of the reservation in minutes.
     */
    public function getDurationInMinutesAttribute(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }
}
