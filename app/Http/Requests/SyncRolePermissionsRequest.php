<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('syncPermissions', $this->route('role'));
    }

    public function rules(): array
    {
        return [
            'permission_ids' => [
                'required',
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
            'permission_ids.required'    => 'Debes enviar el listado de permisos (puede ser un array vacío para revocarlos todos).',
            'permission_ids.array'        => 'Los permisos deben enviarse como un array de IDs.',
            'permission_ids.*.exists'     => 'Uno o más permisos seleccionados no existen.',
        ];
    }
}
