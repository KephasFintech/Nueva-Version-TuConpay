<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class StoreExchangeTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $currencies = config('exchange.currencies', []);

        return [
            'client_id'         => ['required', 'exists:users,id'],
            'broker_id'         => ['nullable', 'exists:users,id'],
            'external_admin_id' => ['nullable', 'exists:users,id'],
            'provider_id'       => ['nullable', 'exists:users,id'],
            'courier_id'        => ['nullable', 'exists:users,id'],
            'currency_from'     => ['required', 'string', 'in:' . implode(',', $currencies)],
            'currency_to'       => ['required', 'string', 'in:' . implode(',', $currencies)],
            'amount_requested'  => ['required', 'numeric', 'min:1'],
            'exchange_rate'     => ['nullable', 'numeric', 'min:0.000001'],
            'amount_to_deliver' => ['nullable', 'numeric', 'min:0'],
            'notes'             => ['nullable', 'string'],
        ];
    }
}
