<?php

namespace App\Http\Requests;

use App\Models\LabClosure;
use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;

class StoreLabClosureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', LabClosure::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lab_id' => ['nullable', 'integer', 'exists:labs,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lab_id.exists' => 'El laboratorio seleccionado no existe.',
            'starts_at.required' => 'La fecha y hora de inicio del cierre es obligatoria.',
            'ends_at.after' => 'El fin del cierre debe ser posterior a su inicio.',
            'reason.required' => 'Indica el motivo del cierre.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'lab_id' => 'laboratorio',
            'starts_at' => 'inicio',
            'ends_at' => 'fin',
            'reason' => 'motivo',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['starts_at', 'ends_at'] as $field) {
            $value = $this->input($field);

            if (is_string($value) && $value !== '') {
                try {
                    $normalized[$field] = Reservation::normalizeInstant($value);
                } catch (\Exception) {
                    // La regla 'date' dara el error legible.
                }
            }
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }
}
