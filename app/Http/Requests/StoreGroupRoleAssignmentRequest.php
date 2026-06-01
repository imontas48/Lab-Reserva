<?php

namespace App\Http\Requests;

use App\Models\GroupRoleAssignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupRoleAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'role_id' => [
                'required',
                'integer',
                'exists:roles,id',
                // No se puede repetir la misma regla de grupo
                Rule::unique('group_role_assignments')
                    ->where('group_type', $this->input('group_type'))
                    ->where('group_value', $this->input('group_value')),
            ],
            'group_type' => [
                'required',
                'string',
                Rule::in([GroupRoleAssignment::GROUP_TYPE_USER_TYPE]),
            ],
            'group_value' => [
                'required',
                'string',
                'max:64',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role_id.required'    => 'El rol es obligatorio.',
            'role_id.exists'      => 'El rol seleccionado no existe.',
            'role_id.unique'      => 'Ya existe una regla que asigna ese rol a ese grupo.',
            'group_type.required' => 'El tipo de grupo es obligatorio.',
            'group_type.in'       => 'El tipo de grupo no es válido.',
            'group_value.required'=> 'El valor del grupo es obligatorio.',
        ];
    }
}
