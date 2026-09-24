<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Plano del laboratorio: tamaño de la cuadricula y posicion de cada equipo.
 * Los equipos no incluidos en positions quedan sin posicion.
 */
class UpdateLabLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('lab'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $lab = $this->route('lab');

        return [
            'grid_rows' => ['required', 'integer', 'between:1,30'],
            'grid_cols' => ['required', 'integer', 'between:1,30'],
            'positions' => ['present', 'array'],
            'positions.*.equipment_id' => [
                'required', 'integer', 'distinct',
                Rule::exists('equipment', 'id')->where('lab_id', $lab?->id),
            ],
            'positions.*.row' => ['required', 'integer', 'min:1', 'lte:grid_rows'],
            'positions.*.col' => ['required', 'integer', 'min:1', 'lte:grid_cols'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'positions.*.equipment_id.exists' => 'Uno de los equipos no pertenece a este laboratorio.',
            'positions.*.equipment_id.distinct' => 'Un equipo aparece dos veces en el plano.',
            'positions.*.row.lte' => 'Una fila está fuera de la cuadrícula.',
            'positions.*.col.lte' => 'Una columna está fuera de la cuadrícula.',
        ];
    }

    /**
     * Dos equipos no pueden ocupar la misma celda.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $cells = collect($this->input('positions', []))
                ->map(fn ($p) => ($p['row'] ?? '').'-'.($p['col'] ?? ''));

            if ($cells->count() !== $cells->unique()->count()) {
                $v->errors()->add('positions', 'Dos equipos ocupan la misma celda del plano.');
            }
        });
    }
}
