<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $targetUserId = $this->route('user')->id;

        return [
            'permission_id' => [
                'required',
                'integer',
                'exists:permissions,id',
                // Un usuario solo puede tener una sobreescritura por permiso
                Rule::unique('permission_overrides')
                    ->where('user_id', $targetUserId),
            ],
            'type' => [
                'required',
                'string',
                Rule::in(['grant', 'revoke']),
            ],
            'reason' => [
                'nullable',
                'string',
                'max:255',
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
            'permission_id.required' => 'El permiso es obligatorio.',
            'permission_id.exists' => 'El permiso seleccionado no existe.',
            'permission_id.unique' => 'Este usuario ya tiene una sobreescritura para ese permiso.',
            'type.required' => 'El tipo (grant/revoke) es obligatorio.',
            'type.in' => 'El tipo debe ser "grant" o "revoke".',
            'expires_at.after' => 'La fecha de expiración debe ser en el futuro.',
        ];
    }
}
