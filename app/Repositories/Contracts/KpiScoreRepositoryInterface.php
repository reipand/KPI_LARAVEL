<?php

namespace App\Repositories\Contracts;

use App\Models\KpiScore;
use Illuminate\Support\Collection;

interface KpiScoreRepositoryInterface
{
    public function upsert(array $identity, array $values): KpiScore;

    public function findUserScore(int $userId, string $periodType, string $periodStart): ?KpiScore;

    public function getLeaderboard(string $periodType, string $periodStart, ?int $roleId = null): Collection;
}
