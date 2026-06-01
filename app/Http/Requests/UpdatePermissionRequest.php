<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('permission'));
    }

    public function rules(): array
    {
        return [
            // Solo la descripción es editable; subject y action son inmutables
            // para no romper código que depende de esos valores.
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
            'description.required' => 'La descripción es obligatoria.',
        ];
    }
}
