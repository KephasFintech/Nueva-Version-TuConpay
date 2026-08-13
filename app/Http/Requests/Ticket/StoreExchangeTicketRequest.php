<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

use App\Enums\PermissionEnum;
use App\Enums\UserRole;

class StoreExchangeTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->can(PermissionEnum::TICKET_CREATE->value);
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        if ($user && $user->hasSystemRole(UserRole::ADMIN)) {
            $this->merge([
                'external_admin_id' => $user->id,
            ]);
        }
    }

    public function rules(): array
    {
        $currencies = config('exchange.currencies', []);
        $user = $this->user();

        $rules = [
            'client_id'         => ['required', 'exists:users,id'],
            'broker_id'         => ['nullable', 'exists:users,id'],
            'external_admin_id' => ['nullable', 'exists:users,id'],
            'provider_id'       => ['nullable', 'exists:users,id'],
            'courier_id'        => ['nullable', 'exists:users,id'],
            'currency_from'     => ['required', 'string', 'in:' . implode(',', $currencies)],
            'currency_to'       => ['required', 'string', 'in:' . implode(',', $currencies)],
            'amount_requested'  => ['required', 'numeric', 'min:1'],
            'rate_type'         => ['nullable', 'string', 'in:fixed,percentage'],
            'exchange_rate'     => ['nullable', 'numeric', 'min:0.000001'],
            'amount_to_deliver' => ['nullable', 'numeric', 'min:0'],
            'notes'             => ['nullable', 'string'],
        ];

        if ($user && $user->hasSystemRole(UserRole::DIRECTION)) {
            $rules['external_admin_id'] = ['required', 'exists:users,id'];
        }

        return $rules;
    }
}
