<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Solo admins pueden asignar roles a usuarios
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $targetUserId = $this->route('user')->id;

        return [
            'role_id' => [
                'required',
                'integer',
                'exists:roles,id',
                // No se puede asignar el mismo rol dos veces al mismo usuario
                Rule::unique('user_roles')->where('user_id', $targetUserId),
            ],
            'expires_at' => [
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role_id.required' => 'El rol es obligatorio.',
            'role_id.exists' => 'El rol seleccionado no existe.',
            'role_id.unique' => 'Este usuario ya tiene ese rol asignado.',
            'expires_at.after' => 'La fecha de expiración debe ser en el futuro.',
        ];
    }
}
