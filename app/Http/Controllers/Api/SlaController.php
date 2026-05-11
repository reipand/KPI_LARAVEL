<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreSlaRequest;
use App\Http\Resources\SlaResource;
use App\Models\ActivityLog;
use App\Models\Position;
use App\Models\Sla;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SlaController extends ApiController
{
    public function index(Request $request)
    {
        $scopedTenantId = $this->resolveScopedTenantId($request->user());

        $sla = Sla::query()
            ->where('tenant_id', $scopedTenantId)
            ->when($request->filled('jabatan'), fn ($query) => $query->where('jabatan', $request->string('jabatan')))
            ->orderBy('jabatan')
            ->paginate((int) $request->input('per_page', 15));

        return $this->paginated(SlaResource::collection($sla), $sla);
    }

    public function store(StoreSlaRequest $request)
    {
        $payload = $this->normalizePayload($request->validated(), $this->resolveScopedTenantId($request->user()));
        $sla = Sla::create($payload);

        ActivityLog::record(
            $request->user(),
            'sla.created',
            Sla::class,
            $sla->id,
            ['nama_pekerjaan' => $sla->nama_pekerjaan],
            $request
        );

        return $this->resource(new SlaResource($sla), 'SLA berhasil ditambahkan.', Response::HTTP_CREATED);
    }

    public function update(StoreSlaRequest $request, Sla $sla)
    {
        $this->ensureSlaAccessible($request->user(), $sla);
        $payload = $this->normalizePayload($request->validated(), $this->resolveScopedTenantId($request->user()));
        $sla->update($payload);

        ActivityLog::record(
            $request->user(),
            'sla.updated',
            Sla::class,
            $sla->id,
            ['nama_pekerjaan' => $sla->nama_pekerjaan],
            $request
        );

        return $this->resource(new SlaResource($sla->refresh()), 'SLA berhasil diperbarui.');
    }

    public function destroy(Request $request, Sla $sla)
    {
        $this->ensureSlaAccessible($request->user(), $sla);
        $payload = ['nama_pekerjaan' => $sla->nama_pekerjaan];
        $sla->delete();

        ActivityLog::record(
            $request->user(),
            'sla.deleted',
            Sla::class,
            $sla->id,
            $payload,
            $request
        );

        return $this->success(null, 'SLA berhasil dihapus.');
    }

    private function normalizePayload(array $data, int $tenantId): array
    {
        $payload = array_merge($data, ['tenant_id' => $tenantId]);

        if (! empty($payload['position_id'])) {
            $position = Position::query()->where('tenant_id', $tenantId)->find($payload['position_id']);
            if ($position) {
                $payload['jabatan'] = $position->nama;
            }
        }

        return $payload;
    }

    private function ensureSlaAccessible(User $actor, Sla $sla): void
    {
        if ((int) $sla->tenant_id !== $this->resolveScopedTenantId($actor)) {
            abort(Response::HTTP_FORBIDDEN, 'SLA ini berada di tenant lain.');
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
