<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property int|null $equipment_id
 * @property int|null $lab_id
 * @property Carbon $start_time
 * @property Carbon $end_time
 * @property string $status
 * @property string|null $purpose
 * @property string|null $rejection_reason
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $reminder_sent_at
 * @property string|null $check_in_code
 * @property Carbon|null $checked_in_at
 * @property Carbon|null $no_show_at
 * @property string|null $recurrence_group
 * @property-read Lab|null $lab
 * @property-read Equipment|null $equipment
 * @property-read User $user
 * @property-read User|null $reviewer
 */
class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_NO_SHOW = 'no_show';

    public const TYPE_EQUIPMENT = 'equipment';

    public const TYPE_LAB = 'lab';

    /**
     * Estados que ocupan la franja.
     *
     * Una solicitud pendiente bloquea igual que una confirmada: si no lo
     * hiciera, un estudiante podria ocupar un equipo mientras el administrador
     * revisa la solicitud del profesor, y la aprobacion llegaria a una franja
     * ya imposible. Rechazar o expirar la solicitud libera la franja.
     *
     * @var array<int, string>
     */
    public const BLOCKING_STATUSES = [self::STATUS_PENDING, self::STATUS_CONFIRMED];

    /**
     * Transiciones permitidas del ciclo de vida de una reserva.
     *
     * pending es el estado inicial de una solicitud de laboratorio completo:
     * el administrador la confirma o la rechaza, el solicitante puede
     * retirarla (cancelled) y el planificador la expira si la franja llega
     * sin decision.
     *
     * cancelled, completed, rejected y expired son terminales. Reactivar una
     * reserva cancelada permitia crear un solapamiento sin pasar por ninguna
     * comprobacion de disponibilidad: bastaba cancelar, esperar a que otro
     * ocupase la franja y volver a confirmar.
     *
     * @var array<string, array<int, string>>
     */
    public const TRANSITIONS = [
        self::STATUS_PENDING => [
            self::STATUS_CONFIRMED,
            self::STATUS_REJECTED,
            self::STATUS_CANCELLED,
            self::STATUS_EXPIRED,
        ],
        self::STATUS_CONFIRMED => [self::STATUS_CANCELLED, self::STATUS_COMPLETED, self::STATUS_NO_SHOW],
        self::STATUS_CANCELLED => [],
        self::STATUS_COMPLETED => [],
        self::STATUS_REJECTED => [],
        self::STATUS_EXPIRED => [],
        self::STATUS_NO_SHOW => [],
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'type',
        'equipment_id',
        'lab_id',
        'start_time',
        'end_time',
        'status',
        'purpose',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'reminder_sent_at',
        'check_in_code',
        'checked_in_at',
        'no_show_at',
        'recurrence_group',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'reviewed_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'no_show_at' => 'datetime',
    ];

    /**
     * Get the user that made this reservation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Equipo reservado. Nulo en una reserva de laboratorio completo.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * Laboratorio reservado completo. Nulo en una reserva de equipo.
     */
    public function lab(): BelongsTo
    {
        return $this->belongsTo(Lab::class);
    }

    /**
     * Administrador que aprobo o rechazo la solicitud.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope to get confirmed reservations.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get cancelled reservations.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope to get completed reservations.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
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
     * Reservas que bloquean una franja: solapan y siguen ocupandola
     * (pendientes o confirmadas).
     *
     * @param  Builder<Reservation>  $query
     */
    public function scopeBlocking($query, mixed $start, mixed $end)
    {
        return $query->whereIn('status', self::BLOCKING_STATUSES)
            ->overlapping($start, $end);
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
     * Reservas de laboratorio completo sobre el laboratorio dado.
     */
    public function scopeForLab($query, $labId)
    {
        return $query->where('type', self::TYPE_LAB)->where('lab_id', $labId);
    }

    /**
     * Reservas de equipo individual sobre cualquier equipo del laboratorio.
     *
     * Subconsulta sin el scope de soft deletes a proposito: un equipo dado de
     * baja con una reserva vigente sigue ocupando el laboratorio hasta que
     * esa reserva termine.
     */
    public function scopeForEquipmentInLab($query, $labId)
    {
        return $query->where('type', self::TYPE_EQUIPMENT)
            ->whereIn('equipment_id', function ($subquery) use ($labId) {
                $subquery->select('id')->from('equipment')->where('lab_id', $labId);
            });
    }

    public function isLabReservation(): bool
    {
        return $this->type === self::TYPE_LAB;
    }

    /**
     * Que se reservo, en una linea, para notificaciones y correos.
     */
    public function targetDescription(): string
    {
        if ($this->isLabReservation()) {
            $name = $this->lab?->name ?? "laboratorio #{$this->lab_id}";

            return "{$name} (laboratorio completo)";
        }

        $identifier = $this->equipment?->identifier ?? "equipo #{$this->equipment_id}";
        $lab = $this->equipment?->lab?->name;

        return $lab ? "{$identifier} · {$lab}" : $identifier;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Solo las reservas con codigo exigen check-in; las anteriores a la
     * funcionalidad se completan como siempre.
     */
    public function requiresCheckIn(): bool
    {
        return $this->check_in_code !== null;
    }

    public function isCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }

    /**
     * Ventana en la que se acepta el check-in: desde unos minutos antes del
     * inicio hasta el fin del periodo de gracia (y nunca tras el fin).
     */
    public function checkInWindow(): array
    {
        $opensBefore = (int) config('lab-reserva.check_in.opens_minutes_before', 10);
        $grace = (int) config('lab-reserva.check_in.grace_minutes', 15);

        return [
            'from' => $this->start_time->copy()->subMinutes($opensBefore),
            'until' => $this->start_time->copy()->addMinutes($grace)->min($this->end_time),
        ];
    }

    public function isCheckInOpen(): bool
    {
        if ($this->status !== self::STATUS_CONFIRMED || $this->isCheckedIn() || ! $this->requiresCheckIn()) {
            return false;
        }

        ['from' => $from, 'until' => $until] = $this->checkInWindow();

        return now()->between($from, $until);
    }

    public function scopeInRecurrenceGroup($query, string $group)
    {
        return $query->where('recurrence_group', $group);
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

        return $this->status === self::STATUS_CONFIRMED
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
