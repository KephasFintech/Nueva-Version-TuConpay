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
        $user = $this->user();

        return $user && (
            $user->can(PermissionEnum::USER_MANAGE->value) ||
            $user->can(PermissionEnum::CLIENT_CREATE->value)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Si el usuario solo tiene client:create (ATC), fuerza el rol a 'client'
        $user = $this->user();
        $roleRule = $user && $user->can(PermissionEnum::USER_MANAGE->value)
            ? ['required', Rule::in(UserRole::values())]
            : ['required', Rule::in([UserRole::CLIENT->value])];

        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', Password::min(8)->letters()->mixedCase()->numbers()],
            'phone'     => ['nullable', 'string', 'max:30'],
            'role'      => $roleRule,
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $user          = $this->user();
        $allowedRoles  = ($user && $user->can(PermissionEnum::USER_MANAGE->value))
            ? UserRole::values()
            : [UserRole::CLIENT->value];

        return [
            'name.required'     => 'El nombre es obligatorio.',
            'email.required'    => 'El correo electrónico es obligatorio.',
            'email.unique'      => 'Este correo electrónico ya está en uso.',
            'password.required' => 'La contraseña es obligatoria.',
            'role.required'     => 'El rol es obligatorio.',
            'role.in'           => 'El rol seleccionado no es válido. Valores permitidos: ' . implode(', ', $allowedRoles),
        ];
    }
}
