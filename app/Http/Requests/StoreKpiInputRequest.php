<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreKpiInputRequest extends SanitizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'indicator_id' => ['required', 'integer', 'exists:kpi_indicators,id'],
            'target_value' => ['required', 'numeric', 'min:0'],
            'actual_value' => ['required', 'numeric', 'min:0'],
            'period_type' => ['required', Rule::in(['weekly', 'monthly'])],
            'period' => ['required', 'date'],
        ];
    }
}
