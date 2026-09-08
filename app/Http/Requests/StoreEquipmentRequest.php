<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * La autorización se manejará en la Policy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lab_id' => [
                'required',
                'integer',
                'exists:labs,id', // El laboratorio debe existir
            ],
            'identifier' => [
                'required',
                'string',
                'max:255',
                // El identificador debe ser único dentro del mismo laboratorio
                // Validación compuesta: único para la combinación lab_id + identifier
                Rule::unique('equipment')->where(function ($query) {
                    return $query->where('lab_id', $this->lab_id);
                }),
            ],
            'type' => [
                'sometimes',
                'string',
                'max:100',
            ],
            'specifications' => [
                'nullable',
                'string',
                'max:2000', // Limitamos las especificaciones a 2000 caracteres
            ],
            'is_operational' => [
                'sometimes',
                'boolean',
            ],
            'software' => [
                'sometimes',
                'array', // Array de IDs de software
            ],
            'software.*' => [
                'integer',
                'exists:software,id', // Cada software debe existir
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
            'lab_id.required' => 'El laboratorio es obligatorio.',
            'lab_id.exists' => 'El laboratorio seleccionado no existe.',
            'identifier.required' => 'El identificador del equipo es obligatorio.',
            'identifier.unique' => 'Ya existe un equipo con este identificador en el laboratorio seleccionado.',
            'identifier.max' => 'El identificador no puede exceder los 255 caracteres.',
            'type.max' => 'El tipo no puede exceder los 100 caracteres.',
            'specifications.max' => 'Las especificaciones no pueden exceder los 2000 caracteres.',
            'is_operational.boolean' => 'El estado operacional debe ser verdadero o falso.',
            'software.array' => 'El software debe ser un array de identificadores.',
            'software.*.exists' => 'Uno o más programas de software seleccionados no existen.',
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
            'lab_id' => 'laboratorio',
            'identifier' => 'identificador',
            'type' => 'tipo',
            'specifications' => 'especificaciones',
            'is_operational' => 'estado operacional',
            'software' => 'software',
        ];
    }
}
