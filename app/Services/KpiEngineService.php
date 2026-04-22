<?php

namespace App\Services;

use App\Models\EmployeeKpiAssignment;
use App\Models\EmployeeKpiResult;
use App\Models\KpiTemplate;
use App\Models\KpiTemplateIndicator;
use Illuminate\Support\Collection;

/**
 * Template-based KPI calculation engine.
 * All scoring logic lives here — never in controllers.
 */
class KpiEngineService
{
    /**
     * Calculate achievement % for a single indicator.
     * Result is capped at indicator's max_cap (default 120%).
     */
    public function calculateAchievement(KpiTemplateIndicator $indicator, float $actualValue): float
    {
        if ($indicator->target_value == 0) {
            return $actualValue > 0 ? $indicator->max_cap : 0.0;
        }

        $raw = match ($indicator->scoring_method) {
            'higher_is_better' => ($actualValue / $indicator->target_value) * 100,
            'lower_is_better'  => ($indicator->target_value / $actualValue) * 100,
            'exact_match'      => abs($actualValue - $indicator->target_value) < 0.0001 ? 100.0 : 0.0,
            default            => 0.0,
        };

        return min(round($raw, 2), $indicator->max_cap);
    }

    /**
     * Compute and persist all indicator results for an assignment.
     * Expects $inputData keyed by indicator_id => actual_value.
     */
    public function processSubmission(EmployeeKpiAssignment $assignment, array $inputData): void
    {
        $indicators = $assignment->template->indicators;

        foreach ($indicators as $indicator) {
            $actualValue = (float) ($inputData[$indicator->id]['actual_value'] ?? 0);
            $notes       = $inputData[$indicator->id]['notes'] ?? null;

            $achievementPercent = $this->calculateAchievement($indicator, $actualValue);
            $weightedScore      = round($achievementPercent * ($indicator->weight / 100), 4);

            EmployeeKpiResult::updateOrCreate(
                [
                    'assignment_id' => $assignment->id,
                    'indicator_id'  => $indicator->id,
                ],
                [
                    'tenant_id'           => $assignment->tenant_id,
                    'actual_value'        => $actualValue,
                    'achievement_percent' => $achievementPercent,
                    'weighted_score'      => $weightedScore,
                    'employee_notes'      => $notes,
                    'status'              => 'submitted',
                ]
            );
        }
    }

    /**
     * Compute total score for an assignment (sum of all weighted scores).
     */
    public function totalScore(EmployeeKpiAssignment $assignment): float
    {
        return (float) $assignment->results->sum('weighted_score');
    }

    /**
     * Return a human-readable grade from a total score (0–100 scale).
     */
    public function grade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default      => 'E',
        };
    }

    /**
     * Build a per-department performance summary for reporting.
     */
    public function departmentSummary(int $tenantId, int $year, ?int $month = null): Collection
    {
        $query = EmployeeKpiAssignment::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('period_year', $year)
            ->whereIn('status', ['approved'])
            ->with(['employee.department', 'results']);

        if ($month) {
            $query->where('period_month', $month);
        }

        return $query->get()
            ->groupBy(fn ($a) => $a->employee?->department?->name ?? 'Unknown')
            ->map(function (Collection $assignments, string $dept) {
                $scores = $assignments->map(fn ($a) => $this->totalScore($a));

                return [
                    'department'    => $dept,
                    'total_emp'     => $assignments->count(),
                    'avg_score'     => round($scores->average() ?? 0, 2),
                    'max_score'     => round($scores->max() ?? 0, 2),
                    'min_score'     => round($scores->min() ?? 0, 2),
                ];
            })
            ->values();
    }
}
