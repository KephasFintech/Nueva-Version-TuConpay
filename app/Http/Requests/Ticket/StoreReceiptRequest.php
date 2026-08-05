<?php

namespace App\Http\Requests\Ticket;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file'  => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
