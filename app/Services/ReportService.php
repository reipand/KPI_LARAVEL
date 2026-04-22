<?php

namespace App\Services;

use App\Models\EmployeeKpiAssignment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(private readonly KpiEngineService $engine) {}

    public function kpiSummary(int $tenantId, int $year, ?int $month = null): array
    {
        $query = EmployeeKpiAssignment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('period_year', $year)
            ->whereIn('status', ['approved', 'submitted'])
            ->with(['employee', 'results', 'template']);

        if ($month) {
            $query->where('period_month', $month);
        }

        $assignments = $query->get();
        $total       = $assignments->count();
        $approved    = $assignments->where('status', 'approved')->count();

        $scores = $assignments->map(fn ($a) => $this->engine->totalScore($a));

        return [
            'period'            => $month ? "Month {$month}/{$year}" : "Year {$year}",
            'total_assignments' => $total,
            'approved'          => $approved,
            'pending'           => $total - $approved,
            'avg_score'         => round($scores->average() ?? 0, 2),
            'highest_score'     => round($scores->max() ?? 0, 2),
            'lowest_score'      => round($scores->min() ?? 0, 2),
            'department_breakdown' => $this->engine->departmentSummary($tenantId, $year, $month),
        ];
    }

    public function employeePerformance(int $tenantId, int $employeeId, int $year): array
    {
        $employee = User::withoutGlobalScopes()->findOrFail($employeeId);

        $assignments = EmployeeKpiAssignment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('period_year', $year)
            ->with(['results.indicator', 'template'])
            ->orderBy('period_month')
            ->get();

        $monthly = $assignments->map(function ($a) {
            return [
                'period'     => $a->period_label,
                'status'     => $a->status,
                'total_score' => $this->engine->totalScore($a),
                'grade'      => $this->engine->grade($this->engine->totalScore($a)),
                'indicators' => $a->results->map(fn ($r) => [
                    'name'                => $r->indicator?->indicator_name,
                    'actual'              => $r->actual_value,
                    'target'              => $r->indicator?->target_value,
                    'achievement_percent' => $r->achievement_percent,
                    'weighted_score'      => $r->weighted_score,
                ]),
            ];
        });

        return [
            'employee'      => [
                'id'         => $employee->id,
                'name'       => $employee->nama,
                'nip'        => $employee->nip,
                'position'   => $employee->jabatan,
                'department' => $employee->departemen,
            ],
            'year'          => $year,
            'monthly_data'  => $monthly,
            'annual_avg'    => round($monthly->avg('total_score') ?? 0, 2),
            'final_grade'   => $this->engine->grade($monthly->avg('total_score') ?? 0),
        ];
    }

    public function exportKpiPdf(int $tenantId, int $employeeId, int $year): \Barryvdh\DomPDF\PDF
    {
        $data = $this->employeePerformance($tenantId, $employeeId, $year);

        return Pdf::loadView('reports.employee_kpi_pdf', $data)
            ->setPaper('a4', 'portrait');
    }

    public function exportKpiSummaryPdf(int $tenantId, int $year, ?int $month = null): \Barryvdh\DomPDF\PDF
    {
        $data = $this->kpiSummary($tenantId, $year, $month);

        return Pdf::loadView('reports.kpi_summary_pdf', $data)
            ->setPaper('a4', 'landscape');
    }
}
