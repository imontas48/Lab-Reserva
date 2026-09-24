<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Horario completo de un laboratorio. Un dia ausente es un dia cerrado; una
 * lista vacia deja el laboratorio sin restriccion horaria.
 */
class SyncOpeningHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermission('schedule', 'manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hours' => ['present', 'array', 'max:7'],
            'hours.*.weekday' => ['required', 'integer', 'between:0,6', 'distinct'],
            'hours.*.opens_at' => ['required', 'date_format:H:i'],
            'hours.*.closes_at' => [
                'required',
                'date_format:H:i',
                fn ($attribute, $value, $fail) => $this->validateClosesAfterOpens($attribute, $value, $fail),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hours.present' => 'Indica el horario (puede ser una lista vacía).',
            'hours.*.weekday.between' => 'El día debe estar entre 0 (domingo) y 6 (sábado).',
            'hours.*.weekday.distinct' => 'Cada día solo puede aparecer una vez.',
            'hours.*.opens_at.date_format' => 'La hora de apertura debe tener el formato HH:MM.',
            'hours.*.closes_at.date_format' => 'La hora de cierre debe tener el formato HH:MM.',
        ];
    }

    private function validateClosesAfterOpens(string $attribute, mixed $value, \Closure $fail): void
    {
        $index = explode('.', $attribute)[1] ?? null;
        $opensAt = $this->input("hours.{$index}.opens_at");

        if (is_string($opensAt) && is_string($value) && $value <= $opensAt) {
            $fail('La hora de cierre debe ser posterior a la de apertura.');
        }
    }
}
