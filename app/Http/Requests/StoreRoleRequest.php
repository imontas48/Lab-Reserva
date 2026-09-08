<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Role::class);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('roles', 'name'),
            ],
            'display_name' => [
                'required',
                'string',
                'max:128',
            ],
            'description' => [
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
            // Los permisos opcionales permiten asignarlos al crear el rol
            'permission_ids' => [
                'sometimes',
                'array',
            ],
            'permission_ids.*' => [
                'integer',
                'exists:permissions,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El slug del rol es obligatorio.',
            'name.unique' => 'Ya existe un rol con ese slug.',
            'name.regex' => 'El slug solo puede contener letras minúsculas, números y guiones bajos.',
            'name.max' => 'El slug no puede superar los 64 caracteres.',
            'display_name.required' => 'El nombre del rol es obligatorio.',
            'color.in' => 'El color debe ser: blue, green, red, yellow, purple, orange o gray.',
            'permission_ids.*.exists' => 'Uno o más permisos seleccionados no existen.',
        ];
    }
}
