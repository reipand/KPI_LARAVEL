<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportManagementController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly AuditService $audit,
    ) {}

    #[OA\Get(
        path: '/api/v2/reports/kpi-summary',
        summary: 'KPI summary aggregated by period',
        security: [['bearerAuth' => []]],
        tags: ['Reports'],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
        ]
    )]
        public function kpiSummary(Request $request): JsonResponse
    {
        $request->validate([
            'year'  => 'required|integer',
            'month' => 'nullable|integer|between:1,12',
        ]);

        $tenantId = $this->getTenantId($request);

        $data = $this->reportService->kpiSummary(
            tenantId: $tenantId,
            year: (int) $request->year,
            month: $request->month ? (int) $request->month : null,
        );

        return response()->json(['data' => $data]);
    }

    #[OA\Get(
        path: '/api/v2/reports/employee-performance',
        summary: 'Employee performance list',
        security: [['bearerAuth' => []]],
        tags: ['Reports'],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
        ]
    )]
        public function employeePerformance(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'year'        => 'required|integer',
        ]);

        $tenantId = $this->getTenantId($request);

        $data = $this->reportService->employeePerformance(
            tenantId: $tenantId,
            employeeId: (int) $request->employee_id,
            year: (int) $request->year,
        );

        return response()->json(['data' => $data]);
    }

    #[OA\Get(
        path: '/api/v2/reports/department-performance',
        summary: 'Department performance summary',
        security: [['bearerAuth' => []]],
        tags: ['Reports'],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
        ]
    )]
        public function departmentPerformance(Request $request): JsonResponse
    {
        $request->validate([
            'year'  => 'required|integer',
            'month' => 'nullable|integer|between:1,12',
        ]);

        $tenantId = $this->getTenantId($request);

        $summary = app(\App\Services\KpiEngineService::class)->departmentSummary(
            $tenantId,
            (int) $request->year,
            $request->month ? (int) $request->month : null,
        );

        return response()->json(['data' => $summary]);
    }

    #[OA\Get(
        path: '/api/v2/reports/export/employee-pdf',
        summary: 'Export employee KPI as PDF',
        security: [['bearerAuth' => []]],
        tags: ['Reports'],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
        ]
    )]
        public function exportEmployeePdf(Request $request): Response
    {
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'year'        => 'required|integer',
        ]);

        $tenantId = $this->getTenantId($request);

        $pdf = $this->reportService->exportKpiPdf(
            tenantId: $tenantId,
            employeeId: (int) $request->employee_id,
            year: (int) $request->year,
        );

        $this->audit->log('reports', 'export_pdf', 'EmployeeKpiReport', $request->employee_id);

        return $pdf->download("kpi_employee_{$request->employee_id}_{$request->year}.pdf");
    }

    #[OA\Get(
        path: '/api/v2/reports/export/summary-pdf',
        summary: 'Export KPI summary as PDF',
        security: [['bearerAuth' => []]],
        tags: ['Reports'],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
        ]
    )]
        public function exportSummaryPdf(Request $request): Response
    {
        $request->validate([
            'year'  => 'required|integer',
            'month' => 'nullable|integer|between:1,12',
        ]);

        $tenantId = $this->getTenantId($request);

        $pdf = $this->reportService->exportKpiSummaryPdf(
            tenantId: $tenantId,
            year: (int) $request->year,
            month: $request->month ? (int) $request->month : null,
        );

        $this->audit->log('reports', 'export_summary_pdf', 'KpiSummaryReport', null);

        return $pdf->download("kpi_summary_{$request->year}.pdf");
    }

    private function getTenantId(Request $request): int
    {
        // Super admin can specify tenant_id; others use their own
        if (app()->bound('bypass_tenant_scope') && app('bypass_tenant_scope')) {
            return (int) ($request->tenant_id ?? app('current_tenant_id'));
        }

        return (int) app('current_tenant_id');
    }
}
