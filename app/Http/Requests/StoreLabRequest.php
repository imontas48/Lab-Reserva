<?php

namespace App\Http\Requests;

use App\Models\Lab;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLabRequest extends FormRequest
{
    /**
     * Consulta la misma policy que el controlador.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Lab::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:labs,name', // El nombre del laboratorio debe ser único
            ],
            'location' => [
                'required',
                'string',
                'max:255',
            ],
            'capacity' => [
                'required',
                'integer',
                'min:1',
                'max:1000', // Capacidad máxima razonable
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000', // Limitamos la descripción a 1000 caracteres
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
