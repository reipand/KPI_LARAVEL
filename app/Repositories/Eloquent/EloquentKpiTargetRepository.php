<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiTarget;
use App\Repositories\Contracts\KpiTargetRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentKpiTargetRepository implements KpiTargetRepositoryInterface
{
    public function upsert(array $identity, array $values): KpiTarget
    {
        $target = KpiTarget::query()
            ->where('indicator_id', $identity['indicator_id'])
            ->where('period_type', $identity['period_type'])
            ->whereDate('period_start', $identity['period_start'])
            ->first() ?? new KpiTarget($identity);

        return tap($target, function (KpiTarget $target) use ($values) {
            $target->fill($values);
            $target->save();
        });
    }

    public function getByIndicatorsAndPeriod(array $indicatorIds, string $periodType, string $periodStart): Collection
    {
        if ($indicatorIds === []) {
            return collect();
        }

        return KpiTarget::query()
            ->whereIn('indicator_id', $indicatorIds)
            ->where('period_type', $periodType)
            ->whereDate('period_start', $periodStart)
            ->get()
            ->keyBy('indicator_id');
    }
}
