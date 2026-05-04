<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiIndicator;
use App\Repositories\Contracts\KpiIndicatorRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentKpiIndicatorRepository implements KpiIndicatorRepositoryInterface
{
    public function getByDepartment(int $departmentId): Collection
    {
        return KpiIndicator::query()
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
        return KpiIndicator::query()
            ->with(['department'])
            ->find($id);
    }

    public function all(): Collection
    {
        return KpiIndicator::query()
            ->with(['department'])
            ->orderBy('department_id')
            ->orderBy('id')
            ->get();
    }
}
