<?php

namespace App\Http\Requests;

use App\Models\AcademicPeriod;
use Illuminate\Support\Carbon;

/**
 * Serie semanal de clases. Hereda todas las reglas de la solicitud de
 * laboratorio (la primera ocurrencia debe ser valida) y añade hasta cuando
 * y que dias se repite.
 */
class StoreRecurringLabReservationRequest extends StoreLabReservationRequest
{
    protected function targetRules(): array
    {
        return parent::targetRules() + [
            'repeat_until' => [
                'required',
                'date',
                'after:start_time',
                fn ($attribute, $value, $fail) => $this->validateHorizon($value, $fail),
            ],
            'weekdays' => ['sometimes', 'array', 'min:1', 'max:7'],
            'weekdays.*' => ['integer', 'between:0,6', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return parent::messages() + [
            'repeat_until.required' => 'Indica hasta qué fecha se repite la clase.',
            'repeat_until.after' => 'La fecha de fin de la serie debe ser posterior a la primera clase.',
            'weekdays.*.between' => 'Los días deben estar entre 0 (domingo) y 6 (sábado).',
            'weekdays.*.distinct' => 'Cada día solo puede aparecer una vez.',
        ];
    }

    /**
     * La serie no puede ir mas alla del periodo academico vigente o, sin
     * periodo, del maximo de semanas configurado.
     */
    private function validateHorizon(mixed $value, \Closure $fail): void
    {
        if (! is_string($value) || strtotime($value) === false || ! $this->hasParsableWindow()) {
            return;
        }

        $until = Carbon::parse($value);
        $period = AcademicPeriod::current();

        if ($period !== null && $until->gt($period->ends_on->endOfDay())) {
            $fail("La serie no puede superar el fin del periodo académico {$period->name} ({$period->ends_on->format('d/m/Y')}).");

            return;
        }

        $maxWeeks = (int) config('lab-reserva.reservations.recurrence_max_weeks', 16);

        if ($period === null && $until->gt(Carbon::parse($this->input('start_time'))->addWeeks($maxWeeks))) {
            $fail("La serie no puede superar las {$maxWeeks} semanas.");
        }
    }
}
