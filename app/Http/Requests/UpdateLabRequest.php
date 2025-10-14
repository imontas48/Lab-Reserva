<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLabRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * La autorización se manejará en la Policy, aquí solo verificamos que esté autenticado.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Obtenemos el ID del laboratorio desde la ruta
        $labId = $this->route('lab');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                // Ignoramos el laboratorio actual al validar unicidad
                Rule::unique('labs', 'name')->ignore($labId),
            ],
            'location' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'capacity' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'max:1000',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del laboratorio es obligatorio.',
            'name.unique' => 'Ya existe un laboratorio con este nombre.',
            'name.max' => 'El nombre no puede exceder los 255 caracteres.',
            'location.required' => 'La ubicación del laboratorio es obligatoria.',
            'location.max' => 'La ubicación no puede exceder los 255 caracteres.',
            'capacity.required' => 'La capacidad del laboratorio es obligatoria.',
            'capacity.integer' => 'La capacidad debe ser un número entero.',
            'capacity.min' => 'La capacidad debe ser al menos 1 persona.',
            'capacity.max' => 'La capacidad no puede exceder las 1000 personas.',
            'description.max' => 'La descripción no puede exceder los 1000 caracteres.',
            'is_active.boolean' => 'El estado debe ser verdadero o falso.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre del laboratorio',
            'location' => 'ubicación',
            'capacity' => 'capacidad',
            'description' => 'descripción',
            'is_active' => 'estado activo',
        ];
    }
}
