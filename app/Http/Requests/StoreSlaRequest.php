<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreSlaRequest extends SanitizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app()->bound('current_tenant_id')
            ? app('current_tenant_id')
            : $this->user()?->tenant_id;

        return [
            'nama_pekerjaan' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:255'],
            'position_id' => [
                'nullable',
                Rule::exists('positions', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'durasi_jam' => ['required', 'integer', 'min:1'],
            'keterangan' => ['nullable', 'string'],
        ];
    }
}
