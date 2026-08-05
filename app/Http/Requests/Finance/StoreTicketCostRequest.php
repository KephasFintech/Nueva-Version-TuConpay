<?php

namespace App\Http\Requests\Finance;

use App\Enums\CostType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cost_type'   => ['required', Rule::enum(CostType::class)],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'currency'    => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
