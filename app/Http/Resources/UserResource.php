<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $department = $this->relationLoaded('department') ? $this->getRelation('department') : null;
        $tenant = $this->relationLoaded('primaryTenant') ? $this->getRelation('primaryTenant') : null;
        $position = $this->relationLoaded('positionRef') ? $this->getRelation('positionRef') : null;

        return [
            'id' => $this->id,
            'nip' => $this->nip,
            'nama' => $this->nama,
            'jabatan' => $this->jabatan,
            'departemen' => $this->departemen,
            'department_id' => $this->department_id,
            'department' => $this->when(
                $department !== null,
                fn () => $department->only(['id', 'nama', 'kode'])
            ),
            'position_id' => $this->position_id,
            'tenant_id' => $this->tenant_id,
            'tenant' => $this->when(
                $tenant !== null,
                fn () => $tenant->only(['id', 'tenant_code', 'tenant_name', 'status'])
            ),
            'role_ref' => $this->when(
                $position !== null,
                fn () => [
                    'id' => $position->id,
                    'name' => $position->nama,
                    'slug' => $position->kode,
                ]
            ),
            'status_karyawan' => $this->status_karyawan,
            'is_active' => (bool) $this->is_active,
            'tanggal_masuk' => optional($this->tanggal_masuk)->toDateString(),
            'no_hp' => $this->no_hp,
            'email' => $this->email,
            'role' => $this->role,
            'spatie_roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
