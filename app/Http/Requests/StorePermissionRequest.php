<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Permission::class);
    }

    public function rules(): array
    {
        return [
            'subject' => [
                'required',
                'string',
                'max:64',
                Rule::unique('permissions')->where('action', $this->input('action')),
            ],
            'action' => [
                'required',
                'string',
                'max:64',
            ],
            'description' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required'     => 'El recurso (subject) es obligatorio.',
            'subject.unique'       => 'Ya existe un permiso con esa combinación de subject y action.',
            'action.required'      => 'La acción es obligatoria.',
            'description.required' => 'La descripción es obligatoria.',
        ];
    }
}
