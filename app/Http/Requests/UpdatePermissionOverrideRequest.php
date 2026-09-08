<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'type' => [
                'sometimes',
                'string',
                Rule::in(['grant', 'revoke']),
            ],
            'reason' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'expires_at' => [
                'sometimes',
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'El tipo debe ser "grant" o "revoke".',
            'expires_at.after' => 'La fecha de expiración debe ser en el futuro.',
        ];
    }
}
