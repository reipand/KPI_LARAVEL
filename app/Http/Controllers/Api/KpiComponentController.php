<?php

namespace App\Http\Controllers\Api;

use App\Models\KpiComponent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class KpiComponentController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->resolveScopedTenantId($request->user());

        $components = KpiComponent::query()
            ->with(['department:id,nama,kode', 'positionRef:id,nama,kode,department_id'])
            ->where('tenant_id', $tenantId)
            ->when($request->filled('department_id'), fn ($query) => $query->where('department_id', $request->integer('department_id')))
            ->when($request->filled('position_id'), fn ($query) => $query->where('position_id', $request->integer('position_id')))
            ->orderByDesc('is_active')
            ->orderBy('objectives')
            ->get()
            ->map(fn (KpiComponent $component) => $this->transformComponent($component));

        return $this->success($components);
    }

    public function store(Request $request): JsonResponse
    {
        $tenantId = $this->resolveScopedTenantId($request->user());
        $payload = $this->validatedPayload($request, false, $tenantId);

        $component = KpiComponent::query()->create(array_merge($payload, ['tenant_id' => $tenantId]));
        $component->load(['department:id,nama,kode', 'positionRef:id,nama,kode,department_id']);

        return $this->success(
            $this->transformComponent($component),
            'Komponen KPI berhasil dibuat.',
            Response::HTTP_CREATED
        );
    }

    public function update(Request $request, KpiComponent $kpiComponent): JsonResponse
    {
        $this->ensureComponentAccessible($request->user(), $kpiComponent);
        $tenantId = $this->resolveScopedTenantId($request->user());

        $kpiComponent->update($this->validatedPayload($request, true, $tenantId));
        $kpiComponent->load(['department:id,nama,kode', 'positionRef:id,nama,kode,department_id']);

        return $this->success(
            $this->transformComponent($kpiComponent),
            'Komponen KPI berhasil diperbarui.'
        );
    }

    public function destroy(KpiComponent $kpiComponent): JsonResponse
    {
        $this->ensureComponentAccessible(request()->user(), $kpiComponent);
        $kpiComponent->delete();

        return $this->success(null, 'Komponen KPI berhasil dihapus.');
    }

    private function validatedPayload(Request $request, bool $partial = false, ?int $tenantId = null): array
    {
        $required = $partial ? 'sometimes' : 'required';
        $tenantId ??= $this->resolveScopedTenantId($request->user());

        $data = $request->validate([
            'jabatan' => [$required, 'string', 'max:255'],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'position_id' => ['nullable', Rule::exists('positions', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'objectives' => [$required, 'string', 'max:255'],
            'strategy' => ['nullable', 'string'],
            'bobot' => [$required, 'numeric', 'min:0', 'max:100'],
            'target' => ['nullable', 'numeric', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'tipe' => ['nullable', 'string', 'max:100'],
            'kpi_type' => ['nullable', Rule::in(['number', 'percentage', 'boolean'])],
            'period' => ['nullable', 'string', 'max:50'],
            'catatan' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (!empty($data['department_id']) && !empty($data['position_id'])) {
            $positionMatchesDepartment = \App\Models\Position::query()
                ->whereKey($data['position_id'])
                ->where('tenant_id', $tenantId)
                ->where('department_id', $data['department_id'])
                ->exists();

            if (!$positionMatchesDepartment) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Jabatan tidak sesuai dengan departemen tenant aktif.');
            }
        }

        return $data;
    }

    private function transformComponent(KpiComponent $component): array
    {
        return [
            'id' => $component->id,
            'jabatan' => $component->jabatan,
            'department_id' => $component->department_id,
            'department' => $component->department ? $component->department->only(['id', 'nama', 'kode']) : null,
            'position_id' => $component->position_id,
            'position' => $component->positionRef ? [
                'id' => $component->positionRef->id,
                'nama' => $component->positionRef->nama,
                'kode' => $component->positionRef->kode,
                'department_id' => $component->positionRef->department_id,
            ] : null,
            'objectives' => $component->objectives,
            'strategy' => $component->strategy,
            'bobot' => $component->bobot !== null ? (float) $component->bobot : null,
            'target' => $component->target !== null ? (float) $component->target : null,
            'satuan' => $component->satuan,
            'tipe' => $component->tipe,
            'kpi_type' => $component->kpi_type,
            'period' => $component->period,
            'catatan' => $component->catatan,
            'is_active' => (bool) $component->is_active,
            'created_at' => optional($component->created_at)->toISOString(),
            'updated_at' => optional($component->updated_at)->toISOString(),
        ];
    }

    private function ensureComponentAccessible(User $actor, KpiComponent $kpiComponent): void
    {
        if ((int) $kpiComponent->tenant_id !== $this->resolveScopedTenantId($actor)) {
            abort(Response::HTTP_FORBIDDEN, 'Komponen KPI ini berada di tenant lain.');
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
