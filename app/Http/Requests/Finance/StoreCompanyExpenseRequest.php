<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category'    => ['required', 'string', 'max:100'],
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'currency'    => ['nullable', 'string', 'size:3'],
            'description' => ['nullable', 'string'],
            'expense_date'=> ['required', 'date'],
        ];
    }
}
