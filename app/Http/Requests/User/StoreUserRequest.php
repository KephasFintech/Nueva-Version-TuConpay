<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use App\Enums\PermissionEnum;

class StoreUserRequest extends FormRequest
{
    private ?array $allowedRoles = null;

    private function getAllowedRoles(): array
    {
        if ($this->allowedRoles !== null) {
            return $this->allowedRoles;
        }

        $user = $this->user();
        if (!$user) {
            return $this->allowedRoles = [];
        }

        if ($user->can(PermissionEnum::USER_MANAGE->value)) {
            return $this->allowedRoles = UserRole::values();
        }

        $roles = [];
        if ($user->can(PermissionEnum::CLIENT_CREATE->value))         $roles[] = UserRole::CLIENT->value;
        if ($user->can(PermissionEnum::COURIER_CREATE->value))        $roles[] = UserRole::COURIER->value;
        if ($user->can(PermissionEnum::BROKER_CREATE->value))         $roles[] = UserRole::BROKER->value;
        if ($user->can(PermissionEnum::EXTERNAL_ADMIN_CREATE->value)) $roles[] = UserRole::EXTERNAL_ADMIN->value;
        if ($user->can(PermissionEnum::PROVIDER_CREATE->value))       $roles[] = UserRole::PROVIDER->value;

        return $this->allowedRoles = $roles;
    }

    public function authorize(): bool
    {
        return count($this->getAllowedRoles()) > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', Password::min(8)->letters()->mixedCase()->numbers()],
            'phone'     => ['nullable', 'string', 'max:30'],
            'role'      => ['required', Rule::in($this->getAllowedRoles())],
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
            'role.in'           => 'El rol seleccionado no es válido. Valores permitidos: ' . implode(', ', $this->getAllowedRoles()),
        ];
    }
}
