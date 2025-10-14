<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLabRequest extends FormRequest
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
            'description' => 'descripción',
            'is_active' => 'estado activo',
        ];
    }
}
