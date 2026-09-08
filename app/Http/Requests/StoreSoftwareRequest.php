<?php

namespace App\Http\Requests;

use App\Models\Software;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSoftwareRequest extends FormRequest
{
    /**
     * Consulta la misma policy que el controlador.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Software::class);
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
                'unique:software,name', // El nombre del software debe ser único
            ],
            'version' => [
                'nullable',
                'string',
                'max:50',
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
            'name.required' => 'El nombre del software es obligatorio.',
            'name.unique' => 'Ya existe un software con este nombre.',
            'name.max' => 'El nombre no puede exceder los 255 caracteres.',
            'version.max' => 'La versión no puede exceder los 50 caracteres.',
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
            'name' => 'nombre del software',
            'version' => 'versión',
        ];
    }
}
