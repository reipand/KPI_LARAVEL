<?php

namespace App\Http\Controllers\Api;

use App\Models\Department;
use App\Models\KpiIndicator;
use App\Models\Position;
use App\Models\User;
use App\Repositories\Contracts\KpiIndicatorRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class KpiIndicatorController extends ApiController
{
    public function __construct(
        private readonly KpiIndicatorRepositoryInterface $indicatorRepository,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $scopedTenantId = $this->resolveScopedTenantId($request->user());
        $departmentId = $request->filled('department_id')
            ? $request->integer('department_id')
            : $this->resolveDepartmentScope($request);

        $positionId = $request->filled('position_id')
            ? $request->integer('position_id')
            : $this->resolvePositionScope($request);

        $indicators = KpiIndicator::query()
            ->with(['department', 'position'])
            ->where('tenant_id', $scopedTenantId)
            ->when($departmentId, function ($query) use ($departmentId) {
                $query->where(function ($scoped) use ($departmentId) {
                    $scoped->where('department_id', $departmentId)
                        ->orWhereHas('position', fn ($position) => $position->where('department_id', $departmentId));
                });
            })
            ->when($positionId, function ($query) use ($positionId) {
                // Tampilkan indikator yang berlaku untuk jabatan ini ATAU yang berlaku untuk semua jabatan (position_id = null)
                $query->where(function ($q) use ($positionId) {
                    $q->whereNull('position_id')
                        ->orWhere('position_id', $positionId);
                });
            })
            ->orderBy('department_id')
            ->orderBy('id')
            ->get()
            ->map(fn (KpiIndicator $indicator) => [
                'id' => $indicator->id,
                'name' => $indicator->name,
                'description' => $indicator->description,
                'weight' => (float) $indicator->weight,
                'default_target_value' => (float) $indicator->default_target_value,
                'formula' => $indicator->formula,
                'formula_type_label' => $indicator->getFormulaTypeLabel(),
                'department_id' => $indicator->department_id,
                'department' => $indicator->department ? [
                    'id' => $indicator->department->id,
                    'nama' => $indicator->department->nama,
                ] : null,
                'position_id' => $indicator->position_id,
                'role_id' => $indicator->position_id,
                'position' => $indicator->position ? [
                    'id' => $indicator->position->id,
                    'nama' => $indicator->position->nama,
                ] : null,
                'role' => $indicator->position ? [
                    'id' => $indicator->position->id,
                    'name' => $indicator->position->nama,
                ] : null,
            ]);

        return $this->success(['items' => $indicators]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->resolveScopedTenantId($request->user());
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'default_target_value' => ['nullable', 'numeric', 'min:0'],
            'formula' => ['nullable', 'array'],
            'formula.type' => ['required_with:formula', Rule::in(['percentage', 'conditional', 'threshold', 'zero_penalty', 'flat'])],
            'formula.thresholds' => ['required_if:formula.type,threshold', 'array'],
            'formula.score' => ['required_if:formula.type,flat', 'numeric', 'min:0', 'max:1'],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'position_id' => ['nullable', Rule::exists('positions', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'role_id' => ['nullable', Rule::exists('positions', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
        ]);

        $data['position_id'] = $data['position_id'] ?? $data['role_id'] ?? null;
        unset($data['role_id']);

        if (empty($data['department_id']) && empty($data['position_id'])) {
            return $this->error('Pilih departemen atau jabatan.', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $indicator = KpiIndicator::query()->create(array_merge($data, ['tenant_id' => $tenantId]));
        $indicator->load(['department', 'position']);

        return $this->success($indicator, 'Indikator KPI berhasil dibuat.', Response::HTTP_CREATED);
    }

    public function show(KpiIndicator $kpiIndicator): JsonResponse
    {
        $this->ensureIndicatorAccessible(request()->user(), $kpiIndicator);
        $kpiIndicator->load(['department', 'position']);

        return $this->success($kpiIndicator);
    }

    public function update(Request $request, KpiIndicator $kpiIndicator): JsonResponse
    {
        $this->ensureIndicatorAccessible($request->user(), $kpiIndicator);
        $tenantId = $this->resolveScopedTenantId($request->user());
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'weight' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'default_target_value' => ['nullable', 'numeric', 'min:0'],
            'formula' => ['nullable', 'array'],
            'formula.type' => ['required_with:formula', Rule::in(['percentage', 'conditional', 'threshold', 'zero_penalty', 'flat'])],
            'formula.thresholds' => ['required_if:formula.type,threshold', 'array'],
            'formula.score' => ['required_if:formula.type,flat', 'numeric', 'min:0', 'max:1'],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'position_id' => ['nullable', Rule::exists('positions', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'role_id' => ['nullable', Rule::exists('positions', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
        ]);

        $data['position_id'] = $data['position_id'] ?? $data['role_id'] ?? $kpiIndicator->position_id;
        unset($data['role_id']);

        $kpiIndicator->update($data);
        $kpiIndicator->load(['department', 'position']);

        return $this->success($kpiIndicator, 'Indikator KPI berhasil diperbarui.');
    }

    public function destroy(KpiIndicator $kpiIndicator): JsonResponse
    {
        $this->ensureIndicatorAccessible(request()->user(), $kpiIndicator);
        $kpiIndicator->delete();

        return $this->success(null, 'Indikator KPI berhasil dihapus.');
    }

    public function meta(): JsonResponse
    {
        $tenantId = $this->resolveScopedTenantId(request()->user());

        return $this->success([
            'departments' => Department::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->orderBy('nama')
                ->get(['id', 'nama', 'kode']),
            'positions' => Position::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->orderBy('nama')
                ->get(['id', 'nama', 'kode', 'department_id']),
            'roles' => Position::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->orderBy('nama')
                ->get(['id', 'nama as name', 'kode', 'department_id']),
            'formula_types' => [
                ['value' => 'percentage', 'label' => 'Persentase (aktual/target x bobot)'],
                ['value' => 'conditional', 'label' => 'Kondisional (penuh jika tercapai)'],
                ['value' => 'threshold', 'label' => 'Bertahap (skor per rentang %)'],
                ['value' => 'zero_penalty', 'label' => 'Zero Penalty (penuh jika nol pelanggaran)'],
                ['value' => 'flat', 'label' => 'Tetap (persentase tetap dari bobot)'],
            ],
        ]);
    }

    private function resolveDepartmentScope(Request $request): ?int
    {
        if ($request->filled('user_id')) {
            return User::query()->whereKey($request->integer('user_id'))->value('department_id');
        }

        if ($request->filled('assigned_to')) {
            return User::query()->whereKey($request->integer('assigned_to'))->value('department_id');
        }

        $user = $request->user();

        if ($user && ! $user->canManageAllData()) {
            return $user->department_id ? (int) $user->department_id : -1;
        }

        return null;
    }

    private function resolvePositionScope(Request $request): ?int
    {
        if ($request->filled('user_id')) {
            return User::query()->whereKey($request->integer('user_id'))->value('position_id');
        }

        if ($request->filled('assigned_to')) {
            return User::query()->whereKey($request->integer('assigned_to'))->value('position_id');
        }

        $user = $request->user();

        if ($user && ! $user->canManageAllData()) {
            return $user->position_id ? (int) $user->position_id : null;
        }

        return null;
    }

    private function ensureIndicatorAccessible(User $actor, KpiIndicator $kpiIndicator): void
    {
        if ((int) $kpiIndicator->tenant_id !== $this->resolveScopedTenantId($actor)) {
            abort(Response::HTTP_FORBIDDEN, 'Indikator KPI ini berada di tenant lain.');
        }
    }

    private function resolveScopedTenantId(User $actor): int
    {
        $tenantId = app()->bound('current_tenant_id') ? (int) app('current_tenant_id') : 0;

        if ($tenantId > 0) {
            return $tenantId;
        }

        return (int) $actor->tenant_id;
    }
}
