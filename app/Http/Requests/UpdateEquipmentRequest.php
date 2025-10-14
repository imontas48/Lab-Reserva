<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipmentRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Obtenemos el ID del equipo desde la ruta
        $equipmentId = $this->route('equipment');

        return [
            'lab_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:labs,id',
            ],
            'identifier' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                // Si se actualiza el identificador, debe ser único dentro del laboratorio
                // Ignoramos el equipo actual y validamos contra el lab_id (actual o nuevo)
                Rule::unique('equipment')->where(function ($query) {
                    $labId = $this->input('lab_id') ?? $this->route('equipment')->lab_id;
                    return $query->where('lab_id', $labId);
                })->ignore($equipmentId),
            ],
            'type' => [
                'sometimes',
                'string',
                'max:100',
            ],
            'specifications' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'is_operational' => [
                'sometimes',
                'boolean',
            ],
            'software' => [
                'sometimes',
                'array',
            ],
            'software.*' => [
                'integer',
                'exists:software,id',
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
