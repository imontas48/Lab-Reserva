<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Lab;
use App\Models\LabClosure;
use App\Models\LabOpeningHour;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Horario de apertura y cierres de un laboratorio.
 *
 * Es la unica fuente de la regla "¿se puede reservar a esta hora?": la
 * consultan los FormRequests para dar un 422 temprano y ReservationService
 * para hacerla cumplir dentro de la transaccion.
 */
class LabScheduleService
{
    /**
     * Sustituye el horario completo del laboratorio.
     *
     * @param  array<int, array{weekday: int, opens_at: string, closes_at: string}>  $hours
     */
    public function syncOpeningHours(Lab $lab, array $hours): Collection
    {
        return DB::transaction(function () use ($lab, $hours) {
            $lab->openingHours()->delete();

            foreach ($hours as $row) {
                $lab->openingHours()->create([
                    'weekday' => (int) $row['weekday'],
                    'opens_at' => self::normalizeTime($row['opens_at']),
                    'closes_at' => self::normalizeTime($row['closes_at']),
                ]);
            }

            return $lab->openingHours()->get();
        });
    }

    /**
     * Motivo por el que la franja no es reservable, o null si lo es.
     */
    public function violationFor(Lab $lab, mixed $startTime, mixed $endTime): ?string
    {
        $timezone = config('app.timezone');
        $start = Carbon::parse($startTime)->setTimezone($timezone);
        $end = Carbon::parse($endTime)->setTimezone($timezone);

        // Un fin a las 00:00 del dia siguiente cuenta como "hasta medianoche".
        $lastInstant = $end->copy()->subSecond();

        if (! $start->isSameDay($lastInstant)) {
            return 'La reserva debe empezar y terminar el mismo día.';
        }

        $hours = $lab->relationLoaded('openingHours') ? $lab->openingHours : $lab->openingHours()->get();

        if ($hours->isNotEmpty()) {
            $day = $hours->firstWhere('weekday', $start->dayOfWeek);
            $dayName = LabOpeningHour::WEEKDAYS[$start->dayOfWeek];

            if ($day === null) {
                return "El laboratorio no abre los {$dayName}.";
            }

            $endClock = $lastInstant->isSameDay($end) ? $end->format('H:i:s') : '24:00:00';

            if ($start->format('H:i:s') < $day->opens_at || $endClock > $day->closes_at) {
                return sprintf(
                    'Los %s el laboratorio abre de %s a %s.',
                    $dayName,
                    substr($day->opens_at, 0, 5),
                    substr($day->closes_at, 0, 5)
                );
            }
        }

        $closure = LabClosure::query()
            ->affectingLab($lab->id)
            ->overlapping($start, $end)
            ->orderBy('starts_at')
            ->first();

        if ($closure !== null) {
            return sprintf(
                'El laboratorio está cerrado del %s al %s: %s.',
                $closure->starts_at->format('d/m H:i'),
                $closure->ends_at->format('d/m H:i'),
                $closure->reason
            );
        }

        return null;
    }

    /**
     * @throws BusinessRuleException
     */
    public function assertReservable(Lab $lab, mixed $startTime, mixed $endTime): void
    {
        $violation = $this->violationFor($lab, $startTime, $endTime);

        if ($violation !== null) {
            throw new BusinessRuleException($violation);
        }
    }

    public function getClosures(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = LabClosure::query()->with(['lab', 'creator']);

        if (isset($filters['lab_id'])) {
            $query->affectingLab((int) $filters['lab_id']);
        }

        if (empty($filters['include_past'])) {
            $query->upcoming();
        }

        return $query->orderBy('starts_at')->paginate($perPage);
    }

    public function createClosure(array $data, User $creator): LabClosure
    {
        $closure = LabClosure::create($data + ['created_by' => $creator->id]);

        return $closure->load(['lab', 'creator']);
    }

    public function updateClosure(LabClosure $closure, array $data): LabClosure
    {
        $closure->update($data);

        return $closure->fresh(['lab', 'creator']);
    }

    public function deleteClosure(LabClosure $closure): void
    {
        $closure->delete();
    }

    /**
     * 'HH:MM' o 'HH:MM:SS' -> 'HH:MM:SS'.
     */
    private static function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? "{$time}:00" : $time;
    }
}
