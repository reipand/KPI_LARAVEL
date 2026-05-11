<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreKpiReportRequest extends SanitizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app()->bound('current_tenant_id')
            ? (int) app('current_tenant_id')
            : (int) ($this->user()?->tenant_id ?? 0);

        return [
            'user_id' => ['nullable', Rule::exists('users', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'kpi_indicator_id' => ['required', Rule::exists('kpi_indicators', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'period_type' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'tanggal' => ['required', 'date'],
            'period_label' => ['required', 'string', 'max:100'],
            'nilai_target' => ['nullable', 'numeric', 'min:0'],
            'nilai_aktual' => ['required', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'submitted', 'approved', 'rejected'])],
        ];
    }
}
