<?php

namespace App\Http\Requests;

use App\Models\Lab;
use App\Models\Reservation;
use App\Services\LabScheduleService;
use App\Support\ReservationLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * Base de las peticiones que crean una reserva (de equipo o de laboratorio).
 *
 * Concentra lo que ambas comparten: la normalizacion de instantes, la
 * ventana temporal (futuro, duracion minima y maxima, antelacion maxima) y
 * la cuota de reservas activas. Los limites salen de ReservationLimits y
 * dependen del rol del usuario.
 *
 * Todas las reglas de closure comprueban que las fechas sean interpretables
 * antes de usarlas: se evaluan aunque 'date' ya haya fallado, y sin la
 * guarda una fecha ilegible acababa en Carbon::parse y salia como 500 en
 * vez de como 422.
 */
abstract class ReservationWindowRequest extends FormRequest
{
    /**
     * Reglas propias del objetivo de la reserva (equipo o laboratorio).
     *
     * @return array<string, mixed>
     */
    abstract protected function targetRules(): array;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->targetRules() + [
            'start_time' => [
                'required',
                'date',
                'after:now',
                fn ($attribute, $value, $fail) => $this->validateAdvanceWindow($value, $fail),
                fn ($attribute, $value, $fail) => $this->validateQuota($fail),
            ],
            'end_time' => [
                'required',
                'date',
                'after:start_time',
                fn ($attribute, $value, $fail) => $this->validateDuration($value, $fail),
            ],
            'purpose' => $this->purposeRules(),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function purposeRules(): array
    {
        return ['nullable', 'string', 'max:255'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_time.required' => 'La fecha y hora de inicio es obligatoria.',
            'start_time.after' => 'La reserva debe ser para una fecha y hora futura.',
            'end_time.required' => 'La fecha y hora de fin es obligatoria.',
            'end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'purpose.max' => 'El motivo no puede superar los 255 caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'start_time' => 'fecha y hora de inicio',
            'end_time' => 'fecha y hora de fin',
            'purpose' => 'motivo',
        ];
    }

    /**
     * @return array{max_active: int|null, max_advance_days: int|null, max_hours: int|null, min_minutes: int}
     */
    protected function limits(): array
    {
        return ReservationLimits::forUser($this->user());
    }

    protected function hasParsableWindow(): bool
    {
        $start = $this->input('start_time');
        $end = $this->input('end_time');

        return is_string($start) && is_string($end)
            && strtotime($start) !== false && strtotime($end) !== false;
    }

    /**
     * Motivo por el que el horario del laboratorio impide la franja, o null.
     * Comprobacion temprana; la definitiva la hace ReservationService.
     */
    protected function scheduleViolation(Lab $lab): ?string
    {
        if (! $this->hasParsableWindow()) {
            return null;
        }

        return app(LabScheduleService::class)->violationFor(
            $lab,
            $this->input('start_time'),
            $this->input('end_time')
        );
    }

    /**
     * Normaliza los instantes al huso de la aplicación antes de validar.
     *
     * El frontend manda dos formatos distintos al mismo endpoint —el
     * calendario, hora local sin offset; el formulario, UTC con sufijo Z— y
     * las columnas son DATETIME sin huso, así que sin normalizar aquí el mismo
     * horario se guardaba desplazado según por dónde se hubiese creado la
     * reserva. A partir de aquí todo el backend trabaja en el huso de la
     * aplicación: validación, almacenamiento y comparación de solapamiento.
     */
    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['start_time', 'end_time'] as $field) {
            $value = $this->input($field);

            if (! is_string($value) || $value === '') {
                continue;
            }

            try {
                $normalized[$field] = Reservation::normalizeInstant($value);
            } catch (\Exception) {
                // Formato irreconocible: se deja tal cual para que la regla
                // 'date' produzca un error de validación legible.
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    private function validateAdvanceWindow(mixed $value, \Closure $fail): void
    {
        $maxDays = $this->limits()['max_advance_days'];

        if ($maxDays === null || ! is_string($value) || strtotime($value) === false) {
            return;
        }

        if (Carbon::parse($value)->greaterThan(now()->addDays($maxDays))) {
            $fail("Tu rol solo permite reservar con hasta {$maxDays} días de antelación.");
        }
    }

    private function validateQuota(\Closure $fail): void
    {
        if ($this->user()->isBlockedFromReserving()) {
            $fail('Tus reservas están bloqueadas hasta el '
                .$this->user()->reservation_blocked_until->format('d/m/Y H:i')
                .' por inasistencias reiteradas.');

            return;
        }

        $maxActive = $this->limits()['max_active'];

        if ($maxActive === null) {
            return;
        }

        if ($this->user()->activeReservations()->count() >= $maxActive) {
            $fail("Has alcanzado el máximo de {$maxActive} reservas activas de tu rol. Cancela alguna para poder crear otra.");
        }
    }

    private function validateDuration(mixed $value, \Closure $fail): void
    {
        if (! $this->hasParsableWindow()) {
            return;
        }

        $limits = $this->limits();
        $minutes = Carbon::parse($this->input('start_time'))->diffInMinutes(Carbon::parse($value), false);

        if ($minutes < $limits['min_minutes']) {
            $fail("La duración mínima de una reserva es de {$limits['min_minutes']} minutos.");
        }

        if ($limits['max_hours'] !== null && $minutes > $limits['max_hours'] * 60) {
            $fail("La duración máxima de una reserva para tu rol es de {$limits['max_hours']} horas.");
        }
    }
}
