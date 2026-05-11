<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiIndicator;
use App\Repositories\Contracts\KpiIndicatorRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentKpiIndicatorRepository implements KpiIndicatorRepositoryInterface
{
    private function scopedQuery()
    {
        $query = KpiIndicator::query();
        $tenantId = app()->bound('current_tenant_id') ? (int) app('current_tenant_id') : 0;

        if ($tenantId > 0) {
            $query->where('tenant_id', $tenantId);
        }

        return $query;
    }

    public function getByDepartment(int $departmentId): Collection
    {
        return $this->scopedQuery()
            ->where(function ($query) use ($departmentId) {
                $query->where('department_id', $departmentId)
                    ->orWhereHas('position', fn ($position) => $position->where('department_id', $departmentId));
            })
            ->with(['department', 'position'])
            ->orderBy('id')
            ->get();
    }

    public function getForUser(?int $roleId, ?int $departmentId): Collection
    {
        if ($departmentId) {
            return $this->getByDepartment($departmentId);
        }

        return collect();
    }

    public function findById(int $id): ?KpiIndicator
    {
        return $this->scopedQuery()
            ->with(['department'])
            ->find($id);
    }

    public function all(): Collection
    {
        return $this->scopedQuery()
            ->with(['department'])
            ->orderBy('department_id')
            ->orderBy('id')
            ->get();
    }
}
