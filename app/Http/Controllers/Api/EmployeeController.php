<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Resources\UserResource;
use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class EmployeeController extends ApiController
{
    public function index(Request $request)
    {
        $actor = $request->user();
        $scopedTenantId = $this->resolveScopedTenantId($actor);
        $requestedTenantId = $request->filled('tenant_id') ? (int) $request->integer('tenant_id') : null;

        if ($requestedTenantId && $requestedTenantId !== $scopedTenantId) {
            abort(Response::HTTP_FORBIDDEN, 'Daftar pegawai hanya dapat ditampilkan untuk tenant yang sedang aktif.');
        }

        $employees = User::query()
            ->with(['department', 'positionRef', 'primaryTenant'])
            ->when($scopedTenantId > 0, fn ($query) => $query->where('tenant_id', $scopedTenantId))
            ->when($request->filled('jabatan'), fn ($query) => $query->where('jabatan', $request->string('jabatan')))
            ->when($request->filled('departemen'), fn ($query) => $query->where('departemen', $request->string('departemen')))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy('nama')
            ->paginate((int) $request->input('per_page', 15));

        return $this->paginated(UserResource::collection($employees), $employees);
    }

    public function store(StoreEmployeeRequest $request)
    {
        $payload = $request->validated();
        $payload['tenant_id'] = $this->resolveTargetTenantId($request, $payload['tenant_id'] ?? null);
        $payload['is_active'] = (bool) ($payload['is_active'] ?? true);
        $payload['password'] = $this->buildIdentitySecret($payload['nip'], $payload['nama']);

        $employee = User::create($payload);
        $employee->syncRoles([$payload['role']]);

        ActivityLog::record(
            $request->user(),
            'employee.created',
            User::class,
            $employee->id,
            ['nip' => $employee->nip, 'nama' => $employee->nama],
            $request
        );

        return $this->resource(new UserResource($employee), 'Pegawai berhasil ditambahkan.', Response::HTTP_CREATED);
    }

    public function update(StoreEmployeeRequest $request, User $employee)
    {
        $this->ensureEmployeeAccessible($request->user(), $employee);

        $payload = $request->validated();
        $payload['tenant_id'] = $this->resolveTargetTenantId($request, $payload['tenant_id'] ?? $employee->tenant_id);
        $payload['is_active'] = (bool) ($payload['is_active'] ?? $employee->is_active);

        if (
            $employee->nip !== $payload['nip'] ||
            $employee->nama !== $payload['nama']
        ) {
            $payload['password'] = $this->buildIdentitySecret($payload['nip'], $payload['nama']);
        }

        $employee->syncRoles([$payload['role']]);
        $employee->update($payload);

        ActivityLog::record(
            $request->user(),
            'employee.updated',
            User::class,
            $employee->id,
            ['nip' => $employee->nip, 'nama' => $employee->nama],
            $request
        );

        return $this->resource(new UserResource($employee->refresh()), 'Pegawai berhasil diperbarui.');
    }

    public function destroy(Request $request, User $employee)
    {
        $this->ensureEmployeeAccessible($request->user(), $employee);

        $payload = ['nip' => $employee->nip, 'nama' => $employee->nama];
        $employee->tokens()->delete();
        $employee->delete();

        ActivityLog::record(
            $request->user(),
            'employee.deleted',
            User::class,
            $employee->id,
            $payload,
            $request
        );

        return $this->success(null, 'Pegawai berhasil dihapus.');
    }

    private function buildIdentitySecret(string $nip, string $nama): string
    {
        return Hash::make(strtolower(trim($nip).'|'.trim($nama)));
    }

    private function resolveTargetTenantId(Request $request, ?int $tenantId): int
    {
        $actor = $request->user();

        if (! $actor->canManageAllData()) {
            return $this->resolveScopedTenantId($actor);
        }

        $targetTenantId = $tenantId ?: $request->integer('tenant_id') ?: $this->resolveScopedTenantId($actor);

        if (! $this->canAccessTenant($actor, $targetTenantId)) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki akses ke perusahaan tersebut.');
        }

        return $targetTenantId;
    }

    private function ensureEmployeeAccessible(User $actor, User $employee): void
    {
        if ($actor->canManageAllData()) {
            if ($employee->tenant_id && ! $this->canAccessTenant($actor, (int) $employee->tenant_id)) {
                abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki akses ke pegawai dari perusahaan ini.');
            }

            return;
        }

        if ((int) $employee->tenant_id !== $this->resolveScopedTenantId($actor)) {
            abort(Response::HTTP_FORBIDDEN, 'Pegawai ini berada di perusahaan lain.');
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

    private function canAccessTenant(User $actor, int $tenantId): bool
    {
        if ($tenantId <= 0) {
            return false;
        }

        if ($actor->hasKpiRole('super_admin')) {
            return Tenant::withoutGlobalScopes()->whereKey($tenantId)->exists();
        }

        return $actor->hasAccessToTenant($tenantId);
    }
}
