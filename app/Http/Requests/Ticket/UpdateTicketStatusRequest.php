<?php

namespace App\Http\Requests\Ticket;

use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TicketStatus::class)],
            'notes'  => ['nullable', 'string'],
            'delivery_otp' => ['nullable', 'string', 'max:10'],
        ];
    }
}
