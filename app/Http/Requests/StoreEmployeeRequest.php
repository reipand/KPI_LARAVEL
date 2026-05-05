<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends SanitizedFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id;
        $allowedRoles = $this->user()?->isTenantAdmin()
            ? ['employee', 'tenant_admin']
            : ['employee', 'tenant_admin', 'hr_manager', 'direktur'];

        return [
            'nip' => ['required', 'string', 'max:50', Rule::unique('users', 'nip')->ignore($employeeId)],
            'nama' => ['required', 'string', 'max:255'],
            'jabatan' => ['required', 'string', 'max:255'],
            'departemen' => ['required', 'string', 'max:255'],
            'status_karyawan' => ['required', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'tanggal_masuk' => ['required', 'date'],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employeeId)],
            'role' => ['required', Rule::in($allowedRoles)],
            'department_id' => ['nullable', 'exists:departments,id'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'tenant_id' => ['nullable', 'exists:tenants,id'],
        ];
    }
}
