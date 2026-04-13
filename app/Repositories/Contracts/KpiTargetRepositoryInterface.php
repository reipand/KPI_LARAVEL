<?php

namespace App\Repositories\Contracts;

use App\Models\KpiTarget;
use Illuminate\Support\Collection;

interface KpiTargetRepositoryInterface
{
    public function upsert(array $identity, array $values): KpiTarget;

    public function getByIndicatorsAndPeriod(array $indicatorIds, string $periodType, string $periodStart): Collection;
}
