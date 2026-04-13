<?php

namespace App\Repositories\Contracts;

use App\Models\KpiIndicator;
use Illuminate\Support\Collection;

interface KpiIndicatorRepositoryInterface
{
    public function getByRole(int $roleId): Collection;

    public function findById(int $id): ?KpiIndicator;
}
