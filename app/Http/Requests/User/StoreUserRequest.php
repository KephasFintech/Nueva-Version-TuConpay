<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use App\Enums\PermissionEnum;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can(PermissionEnum::USER_MANAGE->value);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()],
            'phone'    => ['nullable', 'string', 'max:30'],
            'role'     => ['required', Rule::in(UserRole::values())],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'     => 'El nombre es obligatorio.',
            'email.required'    => 'El correo electrónico es obligatorio.',
            'email.unique'      => 'Este correo electrónico ya está en uso.',
            'password.required' => 'La contraseña es obligatoria.',
            'role.required'     => 'El rol es obligatorio.',
            'role.in'           => 'El rol seleccionado no es válido. Valores permitidos: ' . implode(', ', UserRole::values()),
        ];
    }
}
