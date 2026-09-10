<?php

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reportes de uso. Todo se calcula sobre reservas cuya franja comienza en
 * el rango pedido; las estadisticas de asistencia solo consideran las que
 * exigian check-in.
 */
class ReportService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(Carbon $from, Carbon $to): array
    {
        $base = $this->inRange($from, $to);

        $byStatus = (clone $base)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $byType = (clone $base)
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        $withCheckIn = (clone $base)
            ->whereNotNull('check_in_code')
            ->whereIn('status', [Reservation::STATUS_COMPLETED, Reservation::STATUS_NO_SHOW]);

        $attended = (clone $withCheckIn)->where('status', Reservation::STATUS_COMPLETED)->count();
        $noShows = (clone $withCheckIn)->where('status', Reservation::STATUS_NO_SHOW)->count();
        $decided = $attended + $noShows;

        $bookedMinutes = (int) (clone $base)
            ->whereIn('status', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_COMPLETED])
            ->sum(DB::raw('TIMESTAMPDIFF(MINUTE, start_time, end_time)'));

        return [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'total' => (int) $byStatus->sum(),
            'by_status' => collect(array_keys(Reservation::TRANSITIONS))
                ->mapWithKeys(fn (string $status) => [$status => (int) ($byStatus[$status] ?? 0)])
                ->all(),
            'by_type' => [
                'equipment' => (int) ($byType[Reservation::TYPE_EQUIPMENT] ?? 0),
                'lab' => (int) ($byType[Reservation::TYPE_LAB] ?? 0),
            ],
            'booked_hours' => round($bookedMinutes / 60, 1),
            'attendance' => [
                'attended' => $attended,
                'no_shows' => $noShows,
                'no_show_rate' => $decided > 0 ? round($noShows / $decided * 100, 1) : null,
            ],
            'top_labs' => $this->topLabs($from, $to),
            'top_users' => $this->topUsers($from, $to),
        ];
    }

    /**
     * Ocupacion: minutos reservados por dia y numero de reservas por hora
     * de inicio, opcionalmente de un solo laboratorio.
     *
     * @return array<string, mixed>
     */
    public function occupancy(Carbon $from, Carbon $to, ?int $labId = null): array
    {
        $query = $this->inRange($from, $to)
            ->whereIn('status', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_COMPLETED, Reservation::STATUS_NO_SHOW])
            ->when($labId, function (Builder $q) use ($labId) {
                $q->where(function (Builder $inner) use ($labId) {
                    $inner->where('lab_id', $labId)
                        ->orWhereHas('equipment', fn ($eq) => $eq->where('lab_id', $labId));
                });
            });

        $byDay = (clone $query)
            ->select(
                DB::raw('DATE(start_time) as day'),
                DB::raw('COUNT(*) as reservations'),
                DB::raw('SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as minutes')
            )
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->day,
                'reservations' => (int) $row->reservations,
                'hours' => round(((int) $row->minutes) / 60, 1),
            ]);

        $byHour = (clone $query)
            ->select(DB::raw('HOUR(start_time) as hour'), DB::raw('COUNT(*) as reservations'))
            ->groupBy('hour')
            ->pluck('reservations', 'hour');

        $byWeekday = (clone $query)
            ->select(DB::raw('DAYOFWEEK(start_time) - 1 as weekday'), DB::raw('COUNT(*) as reservations'))
            ->groupBy('weekday')
            ->pluck('reservations', 'weekday');

        return [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'lab_id' => $labId,
            'by_day' => $byDay->values()->all(),
            'by_hour' => collect(range(0, 23))
                ->map(fn (int $hour) => ['hour' => $hour, 'reservations' => (int) ($byHour[$hour] ?? 0)])
                ->all(),
            'by_weekday' => collect(range(0, 6))
                ->map(fn (int $weekday) => ['weekday' => $weekday, 'reservations' => (int) ($byWeekday[$weekday] ?? 0)])
                ->all(),
        ];
    }

    /**
     * Filas para la exportacion CSV, en bloques para no cargar la tabla
     * entera en memoria.
     *
     * @return \Generator<int, array<int, string|int|null>>
     */
    public function exportRows(Carbon $from, Carbon $to): \Generator
    {
        yield ['id', 'tipo', 'estado', 'usuario', 'correo', 'laboratorio', 'equipo', 'inicio', 'fin', 'minutos', 'motivo', 'llegada', 'inasistencia'];

        $query = $this->inRange($from, $to)
            ->with(['user', 'lab', 'equipment.lab'])
            ->orderBy('start_time');

        foreach ($query->lazy(500) as $r) {
            yield [
                $r->id,
                $r->type,
                $r->status,
                $r->user?->name,
                $r->user?->email,
                $r->lab?->name ?? $r->equipment?->lab?->name,
                $r->equipment?->identifier,
                $r->start_time->toDateTimeString(),
                $r->end_time->toDateTimeString(),
                $r->start_time->diffInMinutes($r->end_time),
                $r->purpose,
                $r->checked_in_at?->toDateTimeString(),
                $r->no_show_at?->toDateTimeString(),
            ];
        }
    }

    /**
     * @return Builder<Reservation>
     */
    private function inRange(Carbon $from, Carbon $to): Builder
    {
        return Reservation::query()->whereBetween('start_time', [$from->startOfDay(), $to->copy()->endOfDay()]);
    }

    private function topLabs(Carbon $from, Carbon $to): Collection
    {
        // Query builder plano: el alias impide que el scope de SoftDeletes
        // resuelva la columna, asi que el filtro de baja logica va a mano.
        return DB::table('reservations as r')
            ->leftJoin('equipment as e', 'e.id', '=', 'r.equipment_id')
            ->join('labs as l', 'l.id', '=', DB::raw('COALESCE(r.lab_id, e.lab_id)'))
            ->whereBetween('r.start_time', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->whereIn('r.status', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_COMPLETED])
            ->whereNull('r.deleted_at')
            ->select(
                'l.id as lab_id',
                'l.name as lab_name',
                DB::raw('COUNT(*) as reservations'),
                DB::raw('SUM(TIMESTAMPDIFF(MINUTE, r.start_time, r.end_time)) as minutes')
            )
            ->groupBy('l.id', 'l.name')
            ->orderByDesc('minutes')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'lab_id' => (int) $row->lab_id,
                'lab_name' => $row->lab_name,
                'reservations' => (int) $row->reservations,
                'hours' => round(((int) $row->minutes) / 60, 1),
            ]);
    }

    private function topUsers(Carbon $from, Carbon $to): Collection
    {
        return $this->inRange($from, $to)
            ->join('users', 'users.id', '=', 'reservations.user_id')
            ->whereIn('reservations.status', [Reservation::STATUS_CONFIRMED, Reservation::STATUS_COMPLETED, Reservation::STATUS_NO_SHOW])
            ->select('users.id', 'users.name', 'users.role', DB::raw('COUNT(*) as reservations'))
            ->groupBy('users.id', 'users.name', 'users.role')
            ->orderByDesc('reservations')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'user_id' => (int) $row->id,
                'name' => $row->name,
                'role' => $row->role,
                'reservations' => (int) $row->reservations,
            ]);
    }
}
