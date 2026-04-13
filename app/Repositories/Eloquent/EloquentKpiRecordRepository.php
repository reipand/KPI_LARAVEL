<?php

namespace App\Repositories\Eloquent;

use App\Models\KpiRecord;
use App\Repositories\Contracts\KpiRecordRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentKpiRecordRepository implements KpiRecordRepositoryInterface
{
    public function upsert(array $identity, array $values): KpiRecord
    {
        $record = KpiRecord::query()
            ->where('user_id', $identity['user_id'])
            ->where('indicator_id', $identity['indicator_id'])
            ->where('period_type', $identity['period_type'])
            ->whereDate('period_start', $identity['period_start'])
            ->first() ?? new KpiRecord($identity);

        return tap($record, function (KpiRecord $record) use ($values) {
            $record->fill($values);
            $record->save();
        });
    }

    public function getUserRecordsForPeriod(int $userId, string $periodType, string $periodStart): Collection
    {
        return KpiRecord::query()
            ->with('indicator.role')
            ->where('user_id', $userId)
            ->where('period_type', $periodType)
            ->whereDate('period_start', $periodStart)
            ->get()
            ->keyBy('indicator_id');
    }
}
