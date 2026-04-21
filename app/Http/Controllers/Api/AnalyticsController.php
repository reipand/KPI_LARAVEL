<?php

namespace App\Http\Controllers\Api;

use App\Models\Department;
use App\Models\KpiReport;
use App\Models\User;
use App\Services\KpiCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends ApiController
{
    public function __construct(private KpiCalculatorService $kpiCalculator) {}

    /**
     * KPI trend per month for all employees or a specific department.
     * Returns data suitable for a line chart.
     */
    public function trend(Request $request): JsonResponse
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $departmentId = $request->input('department_id');

        $months = range(1, 12);
        $labels = collect($months)->map(fn ($m) => date('M', mktime(0, 0, 0, $m, 1)));

        $users = User::query()
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->where('role', 'pegawai')
            ->get();

        // Monthly averages from kpi_reports
        $reportData = KpiReport::query()
            ->whereYear('tanggal', $tahun)
            ->when($departmentId, fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('department_id', $departmentId)))
            ->selectRaw('MONTH(tanggal) as bulan, AVG(persentase) as avg_persentase, COUNT(*) as jumlah')
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->keyBy('bulan');

        $datasets = [];

        // Dataset 1: Report-based percentage average
        $reportPercentages = collect($months)->map(
            fn ($m) => $reportData->has($m) ? round((float) $reportData[$m]->avg_persentase, 1) : null
        );

        if ($reportPercentages->filter()->isNotEmpty()) {
            $datasets[] = [
                'label' => 'Rata-rata Pencapaian (%)',
                'data' => $reportPercentages->values()->all(),
                'type' => 'percentage',
            ];
        }

        // Dataset 2: Task-based KPI score average (0-5 converted to percentage)
        $taskScores = collect($months)->map(function ($m) use ($users, $tahun) {
            if ($users->isEmpty()) {
                return null;
            }

            $scores = $users->map(
                fn ($user) => $this->kpiCalculator->calculateForUser($user, $m, $tahun)['total']
            )->filter(fn ($s) => $s > 0);

            return $scores->isNotEmpty() ? round($scores->average(), 2) : null;
        });

        $datasets[] = [
            'label' => 'Rata-rata Skor KPI (0-5)',
            'data' => $taskScores->values()->all(),
            'type' => 'score',
        ];

        return $this->success([
            'labels' => $labels->values()->all(),
            'datasets' => $datasets,
            'tahun' => $tahun,
        ], 'Data trend berhasil dimuat');
    }

    /**
     * Average KPI achievement per department.
     * Returns data suitable for a bar chart.
     */
    public function perDepartment(Request $request): JsonResponse
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = $request->input('bulan');
        $departmentId = $request->input('department_id');

        $departments = Department::query()
            ->where('is_active', true)
            ->when($departmentId, fn ($q) => $q->whereKey($departmentId))
            ->get();

        $labels = $departments->pluck('nama')->values()->all();

        // Percentage-based scores from kpi_reports
        $reportData = KpiReport::query()
            ->whereYear('tanggal', $tahun)
            ->when($bulan, fn ($q) => $q->whereMonth('tanggal', $bulan))
            ->when($departmentId, fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('department_id', $departmentId)))
            ->with('user:id,department_id')
            ->selectRaw('kpi_reports.user_id, AVG(persentase) as avg_persen')
            ->groupBy('kpi_reports.user_id')
            ->get()
            ->keyBy('user_id');

        $avgPercentages = $departments->map(function ($department) use ($reportData) {
            $userIds = $department->users()->pluck('id');
            $scores = $userIds->map(fn ($uid) => $reportData->has($uid) ? (float) $reportData[$uid]->avg_persen : null)->filter();

            return $scores->isNotEmpty() ? round($scores->average(), 1) : 0;
        })->values()->all();

        // Task-based KPI scores
        $users = User::query()
            ->where('role', 'pegawai')
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->get()
            ->groupBy('department_id');

        $avgTaskScores = $departments->map(function ($department) use ($users, $tahun, $bulan) {
            $deptUsers = $users->get($department->id, collect());

            if ($deptUsers->isEmpty()) {
                return 0;
            }

            $scores = $deptUsers->map(
                fn ($user) => $this->kpiCalculator->calculateForUser($user, $bulan ? (int) $bulan : null, $tahun)['total']
            )->filter(fn ($s) => $s > 0);

            return $scores->isNotEmpty() ? round($scores->average(), 2) : 0;
        })->values()->all();

        return $this->success([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Pencapaian KPI (%)',
                    'data' => $avgPercentages,
                    'type' => 'percentage',
                ],
                [
                    'label' => 'Skor KPI (0-5)',
                    'data' => $avgTaskScores,
                    'type' => 'score',
                ],
            ],
            'tahun' => $tahun,
            'bulan' => $bulan,
            'department_id' => $departmentId,
        ], 'Data per departemen berhasil dimuat');
    }

    /**
     * Score distribution (Bad/Average/Good/Excellent).
     * Returns data suitable for a pie/doughnut chart.
     */
    public function distribution(Request $request): JsonResponse
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = $request->input('bulan');
        $departmentId = $request->input('department_id');

        // Report-based distribution
        $reportCounts = KpiReport::query()
            ->whereYear('tanggal', $tahun)
            ->when($bulan, fn ($q) => $q->whereMonth('tanggal', $bulan))
            ->when($departmentId, fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('department_id', $departmentId)))
            ->whereNotNull('score_label')
            ->selectRaw('score_label, COUNT(*) as jumlah')
            ->groupBy('score_label')
            ->pluck('jumlah', 'score_label')
            ->toArray();

        // Task-based distribution
        $userQuery = User::where('role', 'pegawai')
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId));

        $taskDistribution = ['Baik Sekali' => 0, 'Baik' => 0, 'Cukup' => 0, 'Kurang' => 0, 'Buruk' => 0];

        foreach ($userQuery->get() as $user) {
            $result = $this->kpiCalculator->calculateForUser($user, $bulan ? (int) $bulan : null, $tahun);
            $predikat = $result['predikat'];
            if (isset($taskDistribution[$predikat])) {
                $taskDistribution[$predikat]++;
            }
        }

        return $this->success([
            'report_based' => [
                'labels' => ['Excellent (>100%)', 'Good (80-100%)', 'Average (50-80%)', 'Bad (<50%)'],
                'data' => [
                    $reportCounts['excellent'] ?? 0,
                    $reportCounts['good'] ?? 0,
                    $reportCounts['average'] ?? 0,
                    $reportCounts['bad'] ?? 0,
                ],
            ],
            'task_based' => [
                'labels' => array_keys($taskDistribution),
                'data' => array_values($taskDistribution),
            ],
            'tahun' => $tahun,
            'bulan' => $bulan,
        ], 'Data distribusi berhasil dimuat');
    }

    /**
     * Overview stats for a quick summary.
     */
    public function overview(Request $request): JsonResponse
    {
        $tahun = (int) $request->input('tahun', now()->year);
        $bulan = $request->integer('bulan') ?: null;

        $departmentId = $request->input('department_id');

        $totalEmployees = User::query()
            ->where('role', 'pegawai')
            ->when($departmentId, fn ($q) => $q->where('department_id', $departmentId))
            ->count();

        $reportStats = KpiReport::query()
            ->whereYear('tanggal', $tahun)
            ->when($bulan, fn ($q) => $q->whereMonth('tanggal', $bulan))
            ->when($departmentId, fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('department_id', $departmentId)))
            ->selectRaw('AVG(persentase) as avg_persen, COUNT(*) as total_reports,
                SUM(CASE WHEN score_label = "excellent" THEN 1 ELSE 0 END) as excellent,
                SUM(CASE WHEN score_label = "good" THEN 1 ELSE 0 END) as good,
                SUM(CASE WHEN score_label = "average" THEN 1 ELSE 0 END) as average_count,
                SUM(CASE WHEN score_label = "bad" THEN 1 ELSE 0 END) as bad')
            ->first();

        $totalDepartments = Department::query()
            ->where('is_active', true)
            ->when($departmentId, fn ($q) => $q->whereKey($departmentId))
            ->count();

        return $this->success([
            'total_employees' => $totalEmployees,
            'total_departments' => $totalDepartments,
            'total_reports' => (int) ($reportStats->total_reports ?? 0),
            'avg_achievement' => round((float) ($reportStats->avg_persen ?? 0), 1),
            'excellent_count' => (int) ($reportStats->excellent ?? 0),
            'good_count' => (int) ($reportStats->good ?? 0),
            'average_count' => (int) ($reportStats->average_count ?? 0),
            'bad_count' => (int) ($reportStats->bad ?? 0),
            'bulan' => $bulan,
            'tahun' => $tahun,
            'department_id' => $departmentId,
        ], 'Ringkasan analytics berhasil dimuat');
    }
}
