<?php

namespace App\Repositories\Contracts;

use App\Models\KpiRecord;
use Illuminate\Support\Collection;

interface KpiRecordRepositoryInterface
{
    public function upsert(array $identity, array $values): KpiRecord;

    public function getUserRecordsForPeriod(int $userId, string $periodType, string $periodStart): Collection;
}
