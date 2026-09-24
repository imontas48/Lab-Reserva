<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Equipment;
use App\Models\Lab;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\LabReservationRequested;
use App\Notifications\ReservationApproved;
use App\Notifications\ReservationCancelledByAdmin;
use App\Notifications\ReservationRejected;
use App\Notifications\ReservationReminder;
use App\Support\ReservationLimits;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class ReservationService
{
    /**
     * Relaciones que necesita ReservationResource para serializar una reserva
     * sin consultas adicionales. Una sola lista para todos los caminos: antes
     * estaba repetida seis veces y bastaba olvidar una para reabrir el N+1.
     *
     * @var array<int, string>
     */
    public const DETAIL_RELATIONS = [
        'user',
        'lab',
        'reviewer',
        'equipment.lab.currentReservation',
        'equipment.currentReservation',
        'equipment.nextReservation',
    ];

    public function __construct(
        private readonly LabScheduleService $schedule
    ) {}

    /**
     * Reserva de un equipo individual. Nace confirmada.
     *
     * Orden de bloqueo fijo en todo el servicio: labs -> equipment ->
     * reservations. La fila del laboratorio es el mutex de la franja, tanto
     * para reservas de equipo como de laboratorio completo: asi una clase y
     * una reserva de equipo del mismo laboratorio nunca se comprueban en
     * paralelo. MySQL no admite restricciones de exclusion sobre intervalos
     * (eso es EXCLUDE de PostgreSQL), asi que este lock es la garantia.
     *
     * @throws \Exception
     */
    public function createReservation(array $validatedData, User $user): Reservation
    {
        return DB::transaction(function () use ($validatedData, $user) {
            // Lectura sin bloqueo solo para conocer el laboratorio: el orden
            // de locks exige tomar primero la fila de labs.
            $labId = Equipment::query()
                ->whereKey($validatedData['equipment_id'])
                ->value('lab_id');

            if ($labId === null) {
                throw new BusinessRuleException('El equipo seleccionado no existe.');
            }

            $lab = Lab::lockForUpdate()->findOrFail($labId);

            // Ya dentro del mutex del laboratorio. El bloqueo del equipo se
            // mantiene como seccion critica propia: es lo que serializa los
            // intentos sobre ese equipo concreto y lo que verifica la suite.
            $equipment = Equipment::lockForUpdate()->findOrFail($validatedData['equipment_id']);

            if (! $equipment->is_operational) {
                throw new BusinessRuleException(
                    'El equipo seleccionado no está operacional en este momento.'
                );
            }

            $this->schedule->assertReservable($lab, $validatedData['start_time'], $validatedData['end_time']);

            $this->assertSlotIsFree(
                $lab->id,
                $equipment->id,
                $validatedData['start_time'],
                $validatedData['end_time']
            );

            $this->assertWithinQuota($user, $validatedData['start_time']);

            $reservation = Reservation::create([
                'user_id' => $user->id,
                'type' => Reservation::TYPE_EQUIPMENT,
                'equipment_id' => $equipment->id,
                'lab_id' => null,
                'start_time' => $validatedData['start_time'],
                'end_time' => $validatedData['end_time'],
                'purpose' => $validatedData['purpose'] ?? null,
                'status' => Reservation::STATUS_CONFIRMED,
                'check_in_code' => AttendanceService::generateCode(),
            ]);

            return $reservation->load(self::DETAIL_RELATIONS);
        });
    }

    /**
     * Serie semanal de clases: una reserva por ocurrencia, en una sola
     * transaccion. Las ocurrencias que chocan con otra reserva o con un
     * cierre se omiten y se devuelven como informe; el resto se crea.
     *
     * La cuota de reservas activas y la antelacion se comprueban una vez, al
     * inicio: una serie de 16 semanas es una decision, no 16.
     *
     * @return array{created: Collection<int, Reservation>, skipped: array<int, array{date: string, reason: string}>, recurrence_group: string}
     *
     * @throws \Exception
     */
    public function createRecurringLabReservations(array $validatedData, User $user): array
    {
        return DB::transaction(function () use ($validatedData, $user) {
            $lab = Lab::lockForUpdate()->findOrFail($validatedData['lab_id']);

            if (! $lab->is_active) {
                throw new BusinessRuleException('El laboratorio seleccionado no está activo en este momento.');
            }

            $this->assertWithinQuota($user, $validatedData['start_time']);

            $timezone = config('app.timezone');
            $firstStart = Carbon::parse($validatedData['start_time'])->setTimezone($timezone);
            $firstEnd = Carbon::parse($validatedData['end_time'])->setTimezone($timezone);
            $repeatUntil = Carbon::parse($validatedData['repeat_until'])->setTimezone($timezone)->endOfDay();
            $weekdays = array_map('intval', $validatedData['weekdays'] ?? [$firstStart->dayOfWeek]);

            $autoApproved = $user->hasPermission('reservations', 'approve');
            $group = (string) Str::uuid();
            $created = new Collection;
            $skipped = [];

            foreach ($this->occurrences($firstStart, $firstEnd, $repeatUntil, $weekdays) as [$start, $end]) {
                $reason = $this->schedule->violationFor($lab, $start, $end)
                    ?? $this->slotConflict($lab->id, null, $start, $end);

                if ($reason !== null) {
                    $skipped[] = ['date' => $start->toDateString(), 'reason' => $reason];

                    continue;
                }

                $created->push(Reservation::create([
                    'user_id' => $user->id,
                    'type' => Reservation::TYPE_LAB,
                    'lab_id' => $lab->id,
                    'equipment_id' => null,
                    'start_time' => $start,
                    'end_time' => $end,
                    'purpose' => $validatedData['purpose'],
                    'status' => $autoApproved ? Reservation::STATUS_CONFIRMED : Reservation::STATUS_PENDING,
                    'reviewed_by' => $autoApproved ? $user->id : null,
                    'reviewed_at' => $autoApproved ? now() : null,
                    'check_in_code' => $autoApproved ? AttendanceService::generateCode() : null,
                    'recurrence_group' => $group,
                ]));
            }

            if ($created->isEmpty()) {
                throw new BusinessRuleException(
                    'Ninguna de las fechas de la serie está disponible. '.
                    ($skipped[0]['reason'] ?? '')
                );
            }

            $created->each(fn (Reservation $r) => $r->load(self::DETAIL_RELATIONS));

            if (! $autoApproved) {
                Notification::send(User::admins()->get(), new LabReservationRequested($created->first()));
            }

            return ['created' => $created, 'skipped' => $skipped, 'recurrence_group' => $group];
        });
    }

    /**
     * Cancela todas las ocurrencias futuras de una serie que aun ocupan
     * franja. Devuelve cuantas se cancelaron.
     */
    public function cancelSeries(Reservation $reservation, ?User $actor = null): int
    {
        if ($reservation->recurrence_group === null) {
            throw new BusinessRuleException('Esta reserva no forma parte de una serie.');
        }

        $future = Reservation::query()
            ->inRecurrenceGroup($reservation->recurrence_group)
            ->whereIn('status', Reservation::BLOCKING_STATUSES)
            ->where('start_time', '>', now())
            ->get();

        foreach ($future as $occurrence) {
            $this->cancelReservation($occurrence, $actor);
        }

        return $future->count();
    }

    /**
     * Fechas de la serie: cada semana, los dias indicados, desde la primera
     * ocurrencia hasta repeat_until.
     *
     * @param  array<int, int>  $weekdays
     * @return \Generator<int, array{0: Carbon, 1: Carbon}>
     */
    private function occurrences(Carbon $firstStart, Carbon $firstEnd, Carbon $until, array $weekdays): \Generator
    {
        $duration = $firstStart->diffInMinutes($firstEnd);
        $cursor = $firstStart->copy()->startOfWeek(Carbon::SUNDAY);

        while ($cursor->lte($until)) {
            foreach ($weekdays as $weekday) {
                $start = $cursor->copy()->addDays($weekday)->setTimeFrom($firstStart);

                if ($start->lt($firstStart) || $start->gt($until)) {
                    continue;
                }

                yield [$start, $start->copy()->addMinutes($duration)];
            }

            $cursor->addWeek();
        }
    }

    /**
     * Reserva de un laboratorio completo para una clase.
     *
     * Nace pendiente de aprobacion, salvo que quien la crea pueda aprobarla
     * el mismo (administrador): obligarle a solicitar y aprobarse en dos
     * pasos no protege nada y solo anade burocracia.
     *
     * @throws \Exception
     */
    public function createLabReservation(array $validatedData, User $user): Reservation
    {
        return DB::transaction(function () use ($validatedData, $user) {
            $lab = Lab::lockForUpdate()->findOrFail($validatedData['lab_id']);

            if (! $lab->is_active) {
                throw new BusinessRuleException(
                    'El laboratorio seleccionado no está activo en este momento.'
                );
            }

            $this->schedule->assertReservable($lab, $validatedData['start_time'], $validatedData['end_time']);

            $this->assertSlotIsFree(
                $lab->id,
                null,
                $validatedData['start_time'],
                $validatedData['end_time']
            );

            $this->assertWithinQuota($user, $validatedData['start_time']);

            $autoApproved = $user->hasPermission('reservations', 'approve');

            $reservation = Reservation::create([
                'user_id' => $user->id,
                'type' => Reservation::TYPE_LAB,
                'lab_id' => $lab->id,
                'equipment_id' => null,
                'start_time' => $validatedData['start_time'],
                'end_time' => $validatedData['end_time'],
                'purpose' => $validatedData['purpose'],
                'status' => $autoApproved ? Reservation::STATUS_CONFIRMED : Reservation::STATUS_PENDING,
                'reviewed_by' => $autoApproved ? $user->id : null,
                'reviewed_at' => $autoApproved ? now() : null,
                'check_in_code' => $autoApproved ? AttendanceService::generateCode() : null,
            ]);

            $reservation->load(self::DETAIL_RELATIONS);

            if ($reservation->isPending()) {
                // Quien aprueba es el administrador (users.role): es a quien
                // le llega la solicitud. Se envia tras el commit.
                Notification::send(User::admins()->get(), new LabReservationRequested($reservation));
            }

            return $reservation;
        });
    }

    /**
     * Aprueba una solicitud pendiente.
     *
     * Vuelve a comprobar la franja aunque el invariante "pending bloquea" ya
     * la proteja: es defensa en profundidad frente a datos cargados a mano o
     * a un cambio futuro de la regla, y cuesta dos consultas indexadas.
     *
     * @throws \Exception
     */
    public function approveReservation(Reservation $reservation, User $reviewer): Reservation
    {
        return DB::transaction(function () use ($reservation, $reviewer) {
            // Mismo orden que en la creacion: primero el laboratorio, luego
            // la fila de la reserva. Invertirlo abriria un interbloqueo con
            // una creacion concurrente sobre el mismo laboratorio.
            $labId = $this->labIdOf($reservation);
            Lab::lockForUpdate()->findOrFail($labId);

            $locked = Reservation::lockForUpdate()->findOrFail($reservation->id);

            $this->assertTransition($locked, Reservation::STATUS_CONFIRMED);

            if ($locked->start_time <= now()) {
                throw new BusinessRuleException(
                    'No se puede aprobar una solicitud cuya franja ya ha comenzado.'
                );
            }

            $this->assertSlotIsFree(
                $labId,
                $locked->equipment_id,
                $locked->start_time,
                $locked->end_time,
                $locked->id
            );

            $locked->update([
                'status' => Reservation::STATUS_CONFIRMED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
                'check_in_code' => AttendanceService::generateCode(),
            ]);

            $approved = $locked->fresh(self::DETAIL_RELATIONS);
            $approved->user->notify(new ReservationApproved($approved));

            return $approved;
        });
    }

    /**
     * Rechaza una solicitud pendiente. El motivo es obligatorio: el
     * solicitante tiene que poder saber por que y volver a intentarlo.
     *
     * @throws \Exception
     */
    public function rejectReservation(Reservation $reservation, User $reviewer, string $reason): Reservation
    {
        return DB::transaction(function () use ($reservation, $reviewer, $reason) {
            $locked = Reservation::lockForUpdate()->findOrFail($reservation->id);

            $this->assertTransition($locked, Reservation::STATUS_REJECTED);

            $locked->update([
                'status' => Reservation::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);

            $rejected = $locked->fresh(self::DETAIL_RELATIONS);
            $rejected->user->notify(new ReservationRejected($rejected));

            return $rejected;
        });
    }

    /**
     * Cancela una reserva confirmada o retira una solicitud pendiente.
     * Solo antes de que la franja comience.
     *
     * @param  User|null  $actor  quien cancela; si no es el dueño, se le avisa al dueño
     *
     * @throws \Exception
     */
    public function cancelReservation(Reservation $reservation, ?User $actor = null): Reservation
    {
        if (! $reservation->canTransitionTo(Reservation::STATUS_CANCELLED)) {
            throw new BusinessRuleException(
                'Solo se pueden cancelar reservas pendientes o confirmadas. '.
                'Esta reserva ya está en estado: '.$reservation->status
            );
        }

        if ($reservation->start_time <= now()) {
            throw new BusinessRuleException(
                'No se puede cancelar una reserva que ya ha comenzado o pasado.'
            );
        }

        $reservation->update(['status' => Reservation::STATUS_CANCELLED]);

        $cancelled = $reservation->fresh(self::DETAIL_RELATIONS);

        if ($actor !== null && $actor->id !== $cancelled->user_id) {
            $cancelled->user->notify(new ReservationCancelledByAdmin($cancelled));
        }

        return $cancelled;
    }

    /**
     * Envia el recordatorio de las reservas confirmadas que empiezan dentro
     * de la ventana configurada y aun no lo recibieron.
     *
     * @return int Recordatorios enviados
     */
    public function sendUpcomingReminders(): int
    {
        $hours = (int) config('lab-reserva.reservations.reminder_hours_before', 24);

        $upcoming = Reservation::query()
            ->with(self::DETAIL_RELATIONS)
            ->confirmed()
            ->whereNull('reminder_sent_at')
            ->whereBetween('start_time', [now(), now()->addHours($hours)])
            ->get();

        foreach ($upcoming as $reservation) {
            $reservation->user->notify(new ReservationReminder($reservation));
            $reservation->forceFill(['reminder_sent_at' => now()])->save();
        }

        return $upcoming->count();
    }

    /**
     * Update a reservation (admin only - mainly for status changes).
     */
    public function updateReservation(Reservation $reservation, array $data): Reservation
    {
        // Antes esto era un update() directo. Como UpdateReservationRequest
        // acepta cualquiera de los estados y ReservationPolicy::update
        // autoriza al dueño, un usuario podia cancelar su reserva, esperar a
        // que otro ocupase la franja y volver a confirmarla: dos reservas
        // solapadas sin pasar por ninguna comprobacion de disponibilidad.
        // Tambien podia marcarse reservas como 'completed' a voluntad y
        // falsear las estadisticas.
        if (isset($data['status']) && $data['status'] !== $reservation->status) {
            // Resolver una solicitud es una decision con autor y motivo, y
            // pasa por approve/reject. Por aqui el dueño podria confirmarse
            // a si mismo una clase sin que nadie la revisase.
            if ($reservation->isPending() && in_array($data['status'], [
                Reservation::STATUS_CONFIRMED,
                Reservation::STATUS_REJECTED,
            ], true)) {
                throw new BusinessRuleException(
                    'Las solicitudes pendientes se resuelven aprobándolas o rechazándolas.'
                );
            }

            $this->assertTransition($reservation, $data['status']);
        }

        $reservation->update($data);

        return $reservation->fresh(self::DETAIL_RELATIONS);
    }

    /**
     * Delete a reservation (admin only).
     */
    public function deleteReservation(Reservation $reservation): bool
    {
        return $reservation->delete();
    }

    /**
     * Get all reservations with advanced filtering (admin only).
     */
    public function getAllReservations(array $filters = [], ?int $perPage = null): LengthAwarePaginator
    {
        $query = Reservation::with(self::DETAIL_RELATIONS);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        $this->applyCommonFilters($query, $filters);

        return $query->paginate($perPage ?? 15);
    }

    /**
     * Solicitudes pendientes de decision, las mas proximas primero.
     */
    public function getPendingReservations(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Reservation::with(self::DETAIL_RELATIONS)->pending();

        $this->applyCommonFilters($query, $filters + ['sort_by' => 'start_time', 'sort_order' => 'asc']);

        return $query->paginate($perPage);
    }

    /**
     * Get reservations for a specific user.
     */
    public function getReservationsForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Reservation::where('user_id', $user->id)
            ->with(['lab', 'reviewer', 'equipment.lab.currentReservation', 'equipment.currentReservation', 'equipment.nextReservation']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->betweenDates($filters['start_date'], $filters['end_date']);
        }

        $sortBy = $filters['sort_by'] ?? 'start_time';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get reservations filtered by the user's role (admin only).
     *
     * Permite al administrador ver todas las reservas hechas por
     * un tipo de usuario específico (student o teacher).
     *
     * @param  string  $role  - 'student' | 'teacher'
     */
    public function getReservationsByUserRole(string $role, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Reservation::with(self::DETAIL_RELATIONS)
            ->whereHas('user', function ($q) use ($role) {
                $q->where('role', $role);
            });

        $this->applyCommonFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * Ocupacion de un equipo para el calendario: sus propias reservas y las
     * clases que bloquean su laboratorio. Sin las segundas, el estudiante
     * elegia una franja aparentemente libre y recibia un 422.
     */
    public function getReservationsForEquipment(Equipment $equipment, array $filters = []): Collection
    {
        $query = Reservation::query()
            ->with(['user', 'lab'])
            ->where(function (Builder $q) use ($equipment) {
                $q->forEquipment($equipment->id)
                    ->orWhere(fn (Builder $labQuery) => $labQuery->forLab($equipment->lab_id));
            });

        return $this->applyCalendarFilters($query, $filters)->get();
    }

    /**
     * Ocupacion de un laboratorio para el calendario: sus clases y las
     * reservas de cualquiera de sus equipos, que tambien impiden apartarlo.
     */
    public function getReservationsForLab(Lab $lab, array $filters = []): Collection
    {
        $query = Reservation::query()
            ->with(['user', 'lab', 'equipment'])
            ->where(function (Builder $q) use ($lab) {
                $q->forLab($lab->id)
                    ->orWhere(fn (Builder $equipmentQuery) => $equipmentQuery->forEquipmentInLab($lab->id));
            });

        return $this->applyCalendarFilters($query, $filters)->get();
    }

    /**
     * Mark expired reservations as completed.
     *
     * @return int Number of reservations updated
     */
    public function markExpiredReservationsAsCompleted(): int
    {
        return Reservation::confirmed()
            ->where('end_time', '<', now())
            ->update(['status' => Reservation::STATUS_COMPLETED]);
    }

    /**
     * Expira las solicitudes cuya franja llego sin decision. Libera la
     * franja para el historial y distingue "nadie decidio" de "se rechazo".
     *
     * @return int Number of reservations updated
     */
    public function expirePendingReservations(): int
    {
        return Reservation::pending()
            ->where('start_time', '<=', now())
            ->update(['status' => Reservation::STATUS_EXPIRED]);
    }

    /**
     * Comprueba que la franja esta libre en el laboratorio y, si procede, en
     * el equipo. Debe llamarse con la fila del laboratorio ya bloqueada.
     *
     * Dos consultas en vez de un OR para que cada una use su propio indice
     * (reservations_lab_availability_index y reservations_availability_index).
     * Ambas son lecturas con bloqueo: bajo REPEATABLE READ una lectura normal
     * veria el snapshot del inicio de la transaccion y no las filas que otra
     * transaccion acaba de confirmar.
     *
     * @param  int|null  $equipmentId  null = se quiere el laboratorio completo
     * @param  int|null  $excludeReservationId  la propia reserva, al aprobar
     *
     * @throws BusinessRuleException
     */
    private function assertSlotIsFree(
        int $labId,
        ?int $equipmentId,
        mixed $startTime,
        mixed $endTime,
        ?int $excludeReservationId = null
    ): void {
        $conflict = $this->slotConflict($labId, $equipmentId, $startTime, $endTime, $excludeReservationId);

        if ($conflict !== null) {
            throw new BusinessRuleException($conflict);
        }
    }

    /**
     * Motivo del conflicto de la franja, o null si esta libre.
     */
    private function slotConflict(
        int $labId,
        ?int $equipmentId,
        mixed $startTime,
        mixed $endTime,
        ?int $excludeReservationId = null
    ): ?string {
        $labConflict = Reservation::query()
            ->forLab($labId)
            ->blocking($startTime, $endTime)
            ->when($excludeReservationId, fn ($q) => $q->whereKeyNot($excludeReservationId))
            ->lockForUpdate()
            ->exists();

        if ($labConflict) {
            return 'El laboratorio está reservado para una clase en el horario seleccionado. '.
                'Por favor, seleccione otro horario.';
        }

        $equipmentQuery = $equipmentId !== null
            ? Reservation::query()->forEquipment($equipmentId)
            : Reservation::query()->forEquipmentInLab($labId);

        $equipmentConflict = $equipmentQuery
            ->blocking($startTime, $endTime)
            ->when($excludeReservationId, fn ($q) => $q->whereKeyNot($excludeReservationId))
            ->lockForUpdate()
            ->exists();

        if ($equipmentConflict) {
            return $equipmentId !== null
                ? 'El equipo ya no está disponible en el rango de tiempo seleccionado. Por favor, seleccione otro horario.'
                : 'Hay equipos del laboratorio ya reservados en el horario seleccionado. Por favor, seleccione otro horario.';
        }

        return null;
    }

    /**
     * Cuota de reservas activas y ventana de antelacion del rol del usuario.
     *
     * Regla blanda a proposito: dos peticiones simultaneas del mismo usuario
     * podrian superar la cuota por una. Bloquear la fila del usuario lo
     * evitaria, pero alargaria la seccion critica para un abuso improbable.
     *
     * @throws BusinessRuleException
     */
    private function assertWithinQuota(User $user, mixed $startTime): void
    {
        if ($user->isBlockedFromReserving()) {
            throw new BusinessRuleException(
                'Tus reservas están bloqueadas hasta el '
                .$user->reservation_blocked_until->format('d/m/Y H:i')
                .' por inasistencias reiteradas.'
            );
        }

        $limits = ReservationLimits::forUser($user);

        if ($limits['max_active'] !== null
            && $user->activeReservations()->count() >= $limits['max_active']) {
            throw new BusinessRuleException(
                "Has alcanzado el máximo de {$limits['max_active']} reservas activas de tu rol. ".
                'Cancela alguna para poder crear otra.'
            );
        }

        if ($limits['max_advance_days'] !== null
            && Carbon::parse($startTime)->greaterThan(now()->addDays($limits['max_advance_days']))) {
            throw new BusinessRuleException(
                "Tu rol solo permite reservar con hasta {$limits['max_advance_days']} días de antelación."
            );
        }
    }

    /**
     * @throws BusinessRuleException
     */
    private function assertTransition(Reservation $reservation, string $status): void
    {
        if ($reservation->canTransitionTo($status)) {
            return;
        }

        $allowed = $reservation->allowedTransitions();

        throw new BusinessRuleException(
            "No se puede pasar de '{$reservation->status}' a '{$status}'. ".
            ($allowed === []
                ? "El estado '{$reservation->status}' es final."
                : 'Transiciones permitidas: '.implode(', ', $allowed).'.')
        );
    }

    private function labIdOf(Reservation $reservation): int
    {
        if ($reservation->lab_id !== null) {
            return $reservation->lab_id;
        }

        $labId = Equipment::query()->whereKey($reservation->equipment_id)->value('lab_id');

        if ($labId === null) {
            throw new BusinessRuleException('La reserva no está asociada a ningún laboratorio.');
        }

        return $labId;
    }

    /**
     * Filtros compartidos por los listados administrativos.
     *
     * @param  Builder<Reservation>  $query
     */
    private function applyCommonFilters(Builder $query, array $filters): void
    {
        if (isset($filters['equipment_id'])) {
            $query->where('equipment_id', $filters['equipment_id']);
        }

        // Una reserva "del laboratorio X" es la del laboratorio completo o la
        // de cualquiera de sus equipos.
        if (isset($filters['lab_id'])) {
            $query->where(function (Builder $q) use ($filters) {
                $q->where('lab_id', $filters['lab_id'])
                    ->orWhereHas('equipment', fn ($eq) => $eq->where('lab_id', $filters['lab_id']));
            });
        }

        if (isset($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->betweenDates($filters['start_date'], $filters['end_date']);
        } elseif (isset($filters['start_date'])) {
            $query->where('start_time', '>=', $filters['start_date']);
        } elseif (isset($filters['end_date'])) {
            $query->where('end_time', '<=', $filters['end_date']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                    ->orWhereHas('equipment', fn ($eq) => $eq->where('identifier', 'like', "%{$search}%"))
                    ->orWhereHas('lab', fn ($lab) => $lab->where('name', 'like', "%{$search}%"))
                    ->orWhere('purpose', 'like', "%{$search}%");
            });
        }

        $query->orderBy($filters['sort_by'] ?? 'start_time', $filters['sort_order'] ?? 'desc');
    }

    /**
     * Filtros de los calendarios: por defecto solo lo que ocupa la franja.
     *
     * @param  Builder<Reservation>  $query
     * @return Builder<Reservation>
     */
    private function applyCalendarFilters(Builder $query, array $filters): Builder
    {
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->whereIn('status', Reservation::BLOCKING_STATUSES);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->betweenDates($filters['start_date'], $filters['end_date']);
        }

        return $query->orderBy('start_time', 'asc');
    }
}
