<?php

namespace App\Http\Requests;

use App\Models\AcademicPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AcademicPeriod::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('academic_periods', 'name')->ignore($this->route('academic_period'))],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del periodo es obligatorio.',
            'name.unique' => 'Ya existe un periodo con ese nombre.',
            'starts_on.required' => 'La fecha de inicio es obligatoria.',
            'ends_on.after_or_equal' => 'El fin del periodo no puede ser anterior a su inicio.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'starts_on' => 'inicio',
            'ends_on' => 'fin',
            'is_active' => 'activo',
        ];
    }
}
