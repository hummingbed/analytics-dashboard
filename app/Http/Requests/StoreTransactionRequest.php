<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_id' => ['required', 'uuid'],
            'user_name' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', Rule::in(['credit', 'debit'])],
            'status' => ['required', Rule::in(['pending', 'successful', 'failed'])],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'transacted_at' => ['required', 'date'],
        ];
    }
}
