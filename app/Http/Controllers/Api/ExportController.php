<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\KpiDashboardRequest;
use App\Models\Department;
use App\Models\KpiReport;
use App\Models\User;
use App\Services\KpiCalculatorService;
use App\Services\KpiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ExportController extends ApiController
{
    public function __construct(
        private KpiCalculatorService $kpiCalculator,
        private KpiService $kpiService,
    ) {}

    /**
     * Export individual KPI report to PDF.
     */
    public function kpiPdf(Request $request, User $user): Response
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        $tenantId = $this->resolveScopedTenantId($request);

        $this->ensureUserAccessible($request, $user, $tenantId);

        $kpiData = $this->kpiCalculator->calculateForUser($user, $bulan, $tahun);

        $reports = KpiReport::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->with('kpiIndicator')
            ->get();

        $bulanLabel = \DateTime::createFromFormat('!m', $bulan)->format('F');

        $pdf = Pdf::loadView('exports.kpi_report', [
            'user' => $user,
            'kpiData' => $kpiData,
            'reports' => $reports,
            'bulan' => $bulanLabel,
            'tahun' => $tahun,
            'generatedAt' => now()->format('d M Y H:i'),
        ]);

        $filename = "KPI_{$user->nip}_{$bulan}_{$tahun}.pdf";

        return $pdf->download($filename);
    }

    public function export(KpiDashboardRequest $request): Response|JsonResponse
    {
        $filters = $request->filters();
        $employeeId = $filters['employee_id'];

        if ($employeeId) {
            try {
                $score = $this->kpiService->getUserScore($employeeId, $filters, $request->user());
            } catch (\InvalidArgumentException $exception) {
                return $this->error($exception->getMessage(), status: 403);
            }

            $pdf = Pdf::loadView('exports.kpi_advanced_user_report', [
                'company' => config('kpi.company_name'),
                'generatedAt' => now(),
                'score' => $score,
            ]);

            return $pdf->download(sprintf(
                'KPI_User_%s_%s.pdf',
                $score->user?->nip ?? $score->user_id,
                optional($score->period_start)->format('Y_m')
            ));
        }

        $payload = $this->kpiService->buildTeamPdfData($filters);
        $pdf = Pdf::loadView('exports.kpi_advanced_team_report', $payload);

        return $pdf->download('KPI_Team_' . now()->format('Y_m_d_His') . '.pdf');
    }

    /**
     * Export ranking to simple CSV (Excel-compatible, no ext-gd required).
     */
    public function rankingCsv(Request $request): Response
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        $departmentId = $request->input('department_id');
        $tenantId = $this->resolveScopedTenantId($request);

        $ranking = $this->kpiCalculator
            ->ranking($bulan, $tahun)
            ->when($departmentId, fn ($items) => $items->filter(
                fn ($item) => (string) $item['user']->department_id === (string) $departmentId
            ))
            ->when($tenantId, fn ($items) => $items->filter(
                fn ($item) => (int) $item['user']->tenant_id === (int) $tenantId
            ))
            ->values();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"Ranking_KPI_{$bulan}_{$tahun}.csv\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($ranking) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Rank', 'NIP', 'Nama', 'Jabatan', 'Departemen', 'Skor KPI', 'Predikat']);

            foreach ($ranking as $index => $item) {
                fputcsv($handle, [
                    $index + 1,
                    $item['user']->nip,
                    $item['user']->nama,
                    $item['user']->jabatan,
                    $item['user']->departemen,
                    $item['total'],
                    $item['predikat'],
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export Analytics KPI dashboard summary to PDF.
     */
    public function analyticsPdf(Request $request): Response
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = $request->integer('bulan') ?: null;
        $departmentId = $request->input('department_id');
        $tenantId = $this->resolveScopedTenantId($request);
        $department = $departmentId
            ? Department::query()->where('tenant_id', $tenantId)->find($departmentId)
            : null;
        $months = range(1, 12);

        $monthlyReports = KpiReport::query()
            ->where('tenant_id', $tenantId)
            ->whereYear('tanggal', $tahun)
            ->when($departmentId, fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('department_id', $departmentId)))
            ->selectRaw('MONTH(tanggal) as bulan, AVG(persentase) as avg_persentase, COUNT(*) as jumlah')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->keyBy('bulan');

        $monthlyTrend = collect($months)->map(function (int $month) use ($monthlyReports) {
            $row = $monthlyReports->get($month);

            return [
                'month' => date('M', mktime(0, 0, 0, $month, 1)),
                'avg_percentage' => $row ? round((float) $row->avg_persentase, 1) : null,
                'total_reports' => $row ? (int) $row->jumlah : 0,
            ];
        });

        $reportStats = KpiReport::query()
            ->where('tenant_id', $tenantId)
            ->whereYear('tanggal', $tahun)
            ->when($bulan, fn ($q) => $q->whereMonth('tanggal', $bulan))
            ->when($departmentId, fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('department_id', $departmentId)))
            ->selectRaw('AVG(persentase) as avg_persen, COUNT(*) as total_reports,
                SUM(CASE WHEN score_label = "excellent" THEN 1 ELSE 0 END) as excellent,
                SUM(CASE WHEN score_label = "good" THEN 1 ELSE 0 END) as good,
                SUM(CASE WHEN score_label = "average" THEN 1 ELSE 0 END) as average_count,
                SUM(CASE WHEN score_label = "bad" THEN 1 ELSE 0 END) as bad')
            ->first();

        $reportData = KpiReport::query()
            ->where('tenant_id', $tenantId)
            ->whereYear('tanggal', $tahun)
            ->when($bulan, fn ($q) => $q->whereMonth('tanggal', $bulan))
            ->when($departmentId, fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('department_id', $departmentId)))
            ->selectRaw('user_id, AVG(persentase) as avg_persen')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $departments = Department::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->when($departmentId, fn ($q) => $q->whereKey($departmentId))
            ->with('users:id,nama,department_id,role')
            ->orderBy('nama')
            ->get();

        $departmentRows = $departments->map(function (Department $department) use ($reportData, $bulan, $tahun) {
            $employees = $department->users->where('role', 'employee');
            $percentages = $employees
                ->map(fn (User $user) => $reportData->has($user->id) ? (float) $reportData[$user->id]->avg_persen : null)
                ->filter(fn ($score) => $score !== null);

            $taskScores = $employees
                ->map(fn (User $user) => $this->kpiCalculator->calculateForUser($user, $bulan, $tahun)['total'])
                ->filter(fn ($score) => $score > 0);

            return [
                'name' => $department->nama,
                'employee_count' => $employees->count(),
                'avg_percentage' => $percentages->isNotEmpty() ? round($percentages->average(), 1) : null,
                'avg_task_score' => $taskScores->isNotEmpty() ? round($taskScores->average(), 2) : null,
            ];
        });

        $reportDistribution = [
            'Excellent (>100%)' => (int) ($reportStats->excellent ?? 0),
            'Good (80-100%)' => (int) ($reportStats->good ?? 0),
            'Average (50-80%)' => (int) ($reportStats->average_count ?? 0),
            'Bad (<50%)' => (int) ($reportStats->bad ?? 0),
        ];

        $taskDistribution = ['Baik Sekali' => 0, 'Baik' => 0, 'Cukup' => 0, 'Kurang' => 0, 'Buruk' => 0];
        User::query()
            ->where('tenant_id', $tenantId)
            ->where('role', 'employee')
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->get()
            ->each(function (User $user) use (&$taskDistribution, $bulan, $tahun) {
                $predikat = $this->kpiCalculator->calculateForUser($user, $bulan, $tahun)['predikat'];

                if (isset($taskDistribution[$predikat])) {
                    $taskDistribution[$predikat]++;
                }
            });

        $topReports = KpiReport::query()
            ->where('tenant_id', $tenantId)
            ->whereYear('tanggal', $tahun)
            ->when($bulan, fn ($q) => $q->whereMonth('tanggal', $bulan))
            ->when($departmentId, fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('department_id', $departmentId)))
            ->with(['user.department', 'kpiIndicator'])
            ->orderByDesc('persentase')
            ->limit(10)
            ->get();

        $pdf = Pdf::loadView('exports.analytics_kpi_report', [
            'company' => config('kpi.company_name'),
            'tahun' => $tahun,
            'bulan' => $bulan,
            'periodLabel' => $bulan
                ? \DateTime::createFromFormat('!m', $bulan)->format('F') . " {$tahun}"
                : "Tahun {$tahun}",
            'departmentLabel' => $department?->nama ?? ($departmentId ? "Departemen #{$departmentId}" : 'Semua Departemen'),
            'generatedAt' => now(),
            'summary' => [
                'total_employees' => User::query()
                    ->where('tenant_id', $tenantId)
                    ->where('role', 'employee')
                    ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
                    ->count(),
                'total_departments' => Department::query()
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->when($departmentId, fn ($q) => $q->whereKey($departmentId))
                    ->count(),
                'total_reports' => (int) ($reportStats->total_reports ?? 0),
                'avg_achievement' => round((float) ($reportStats->avg_persen ?? 0), 1),
            ],
            'monthlyTrend' => $monthlyTrend,
            'departmentRows' => $departmentRows,
            'reportDistribution' => $reportDistribution,
            'taskDistribution' => $taskDistribution,
            'topReports' => $topReports,
        ]);

        $monthSuffix = $bulan ? '_' . str_pad((string) $bulan, 2, '0', STR_PAD_LEFT) : '';
        $departmentSuffix = $departmentId ? "_Dept_{$departmentId}" : '';

        return $pdf->download("Analytics_KPI_{$tahun}{$monthSuffix}{$departmentSuffix}.pdf");
    }

    /**
     * Export KPI reports to CSV.
     */
    public function reportsCsv(Request $request): Response
    {
        $bulan = (int) $request->input('bulan', now()->month);
        $tahun = (int) $request->input('tahun', now()->year);
        $departmentId = $request->input('department_id');
        $tenantId = $this->resolveScopedTenantId($request);

        $reports = \App\Models\KpiReport::query()
            ->where('tenant_id', $tenantId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->when($departmentId, fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('department_id', $departmentId)))
            ->with(['user.department', 'kpiIndicator'])
            ->orderByDesc('persentase')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"Laporan_KPI_{$bulan}_{$tahun}.csv\"",
        ];

        $callback = function () use ($reports) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['NIP', 'Nama', 'Departemen', 'Indikator KPI', 'Target', 'Aktual', 'Persentase (%)', 'Predikat', 'Tanggal', 'Status']);

            foreach ($reports as $r) {
                fputcsv($handle, [
                    $r->user?->nip ?? '-',
                    $r->user?->nama ?? '-',
                    $r->user?->department?->nama ?? '-',
                    $r->kpiIndicator?->name ?? '-',
                    $r->nilai_target ?? '-',
                    $r->nilai_aktual ?? '-',
                    $r->persentase ?? '-',
                    match ($r->score_label) {
                        'excellent' => 'Excellent (>100%)',
                        'good' => 'Good (80-100%)',
                        'average' => 'Average (50-80%)',
                        'bad' => 'Bad (<50%)',
                        default => '-',
                    },
                    $r->tanggal?->toDateString() ?? '-',
                    $r->status,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function ensureUserAccessible(Request $request, User $user, int $tenantId): void
    {
        if ((int) $user->tenant_id !== $tenantId) {
            abort(Response::HTTP_FORBIDDEN, 'User berada di tenant lain.');
        }

        if (! $request->user()->canManageAllData() && (int) $request->user()->id !== (int) $user->id) {
            abort(Response::HTTP_FORBIDDEN, 'Akses ditolak.');
        }
    }

    private function resolveScopedTenantId(Request $request): int
    {
        $tenantId = app()->bound('current_tenant_id') ? (int) app('current_tenant_id') : 0;

        if ($tenantId > 0) {
            return $tenantId;
        }

        if ($request->user()?->tenant_id) {
            return (int) $request->user()->tenant_id;
        }

        abort(Response::HTTP_FORBIDDEN, 'Tenant aktif tidak ditemukan.');
    }
}
