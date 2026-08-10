<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use App\Enums\PermissionEnum;

class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user')?->id ?? $this->route('id');

        return [
            'name'      => ['sometimes', 'string', 'max:255'],
            'email'     => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password'  => ['sometimes', Password::min(8)->letters()->mixedCase()->numbers()],
            'phone'     => ['sometimes', 'nullable', 'string', 'max:30'],
            'role'      => ['sometimes', Rule::in(UserRole::values())],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
