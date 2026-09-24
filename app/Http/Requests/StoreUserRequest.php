<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Alta de un usuario por un administrador (POST /api/v1/users).
 *
 * A diferencia del registro publico, aqui SI se acepta el rol: quien invoca
 * ya paso por users.create, que solo tienen los administradores. La
 * contrasena es opcional: si no viene, el servicio genera una temporal.
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(['admin', 'teacher', 'student'])],
            'password' => ['nullable', 'string', Password::min(8)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'role.in' => 'El rol debe ser admin, teacher o student.',
        ];
    }
}
