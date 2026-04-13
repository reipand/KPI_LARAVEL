<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiIndicator;
use App\Repositories\Contracts\KpiIndicatorRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentKpiIndicatorRepository implements KpiIndicatorRepositoryInterface
{
    public function getByRole(int $roleId): Collection
    {
        return KpiIndicator::query()
            ->where('role_id', $roleId)
            ->orderBy('id')
            ->get();
    }

    public function findById(int $id): ?KpiIndicator
    {
        return KpiIndicator::query()
            ->with('role')
            ->find($id);
    }
}
