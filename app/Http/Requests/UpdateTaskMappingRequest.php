<?php

namespace App\Http\Requests;

class UpdateTaskMappingRequest extends SanitizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kpi_indicator_id' => ['nullable', 'exists:kpi_indicators,id]'],
            'manual_score' => ['nullable', 'numeric', 'min:0', 'max:5'],
        ];
    }
}
