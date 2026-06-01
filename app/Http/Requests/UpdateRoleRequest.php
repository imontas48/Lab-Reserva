<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('role'));
    }

    public function rules(): array
    {
        $roleId = $this->route('role')->id;

        return [
            'display_name' => [
                'sometimes',
                'string',
                'max:128',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
            'color' => [
                'sometimes',
                'string',
                Rule::in(['blue', 'green', 'red', 'yellow', 'purple', 'orange', 'gray']),
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
            // El slug (name) NO es editable una vez creado para no romper referencias en código
        ];
    }

    public function messages(): array
    {
        return [
            'color.in'         => 'El color debe ser: blue, green, red, yellow, purple, orange o gray.',
            'display_name.max' => 'El nombre del rol no puede superar los 128 caracteres.',
        ];
    }
}
