<?php

namespace App\Repositories\Contracts;

use App\Models\KpiIndicator;
use Illuminate\Support\Collection;

interface KpiIndicatorRepositoryInterface
{
    public function getByDepartment(int $departmentId): Collection;

    /** Return indicators for a user scoped to their department. */
    public function getForUser(?int $roleId, ?int $departmentId): Collection;

    public function findById(int $id): ?KpiIndicator;

    public function all(): Collection;
}
