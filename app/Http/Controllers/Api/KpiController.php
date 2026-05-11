<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\KpiScoreResource;
use App\Models\User;
use App\Services\KpiCalculatorService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KpiController extends ApiController
{
    public function __construct(private readonly KpiCalculatorService $calculator)
    {
    }

    public function me(Request $request)
    {
        $score = $this->calculator->calculateForUser(
            $request->user(),
            $request->integer('bulan') ?: null,
            $request->integer('tahun') ?: null,
        );

        return $this->resource(new KpiScoreResource($score));
    }

    public function show(Request $request, User $user)
    {
        if (!$request->user()->canManageAllData()) {
            return $this->error('Akses ditolak.', status: Response::HTTP_FORBIDDEN);
        }

        $tenantId = $this->resolveScopedTenantId($request);
        if ($tenantId && (int) $user->tenant_id !== $tenantId) {
            return $this->error('User KPI berada di tenant lain.', status: Response::HTTP_FORBIDDEN);
        }

        $score = $this->calculator->calculateForUser(
            $user,
            $request->integer('bulan') ?: null,
            $request->integer('tahun') ?: null,
        );

        return $this->resource(new KpiScoreResource($score));
    }

    public function ranking(Request $request)
    {
        $ranking = $this->calculator
            ->ranking($request->integer('bulan') ?: null, $request->integer('tahun') ?: null)
            ->values();

        return $this->success(KpiScoreResource::collection($ranking)->resolve(), 'Berhasil');
    }

    private function resolveScopedTenantId(Request $request): ?int
    {
        if (app()->bound('current_tenant_id')) {
            $tenantId = app('current_tenant_id');

            return $tenantId ? (int) $tenantId : null;
        }

        return $request->user()?->tenant_id ? (int) $request->user()->tenant_id : null;
    }
}
