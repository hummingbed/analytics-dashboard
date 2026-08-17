<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMetricEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_id' => ['required', 'uuid'],
            'type' => ['required', Rule::in(['sale', 'page_view', 'click', 'operation', 'financial'])],
            'value' => ['sometimes', 'numeric'],
            'source' => ['sometimes', 'string', 'max:80'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'occurred_at' => ['required', 'date'],
        ];
    }
}
