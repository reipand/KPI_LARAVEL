<?php

namespace App\Services;

use App\Models\KpiScore;
use App\Models\User;
use App\Repositories\Contracts\KpiIndicatorRepositoryInterface;
use App\Repositories\Contracts\KpiRecordRepositoryInterface;
use App\Repositories\Contracts\KpiScoreRepositoryInterface;
use App\Repositories\Contracts\KpiTargetRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class KpiService
{
    public function __construct(
        private readonly KpiIndicatorRepositoryInterface $indicatorRepository,
        private readonly KpiTargetRepositoryInterface $targetRepository,
        private readonly KpiRecordRepositoryInterface $recordRepository,
        private readonly KpiScoreRepositoryInterface $scoreRepository,
    ) {
    }

    public function inputRecord(array $payload): KpiScore
    {
        return DB::transaction(function () use ($payload) {
            /** @var User $user */
            $user = User::query()->with('roleRef')->findOrFail($payload['user_id']);

            if (!$user->role_id) {
                throw new InvalidArgumentException('User belum memiliki role yang terhubung.');
            }

            $indicator = $this->indicatorRepository->findById((int) $payload['indicator_id']);

            if (!$indicator) {
                throw (new ModelNotFoundException())->setModel('App\\Models\\KpiIndicator', [$payload['indicator_id']]);
            }

            if ((int) $indicator->role_id !== (int) $user->role_id) {
                throw new InvalidArgumentException('Indicator KPI tidak sesuai dengan role user.');
            }

            $period = $this->resolvePeriod($payload['period_type'], $payload['period']);
            $targetValue = round((float) $payload['target_value'], 2);
            $actualValue = round((float) $payload['actual_value'], 2);
            $achievementRatio = $this->calculateAchievementRatio($actualValue, $targetValue);
            $recordScore = round($achievementRatio * (float) $indicator->weight, 2);

            $identity = [
                'user_id' => $user->id,
                'indicator_id' => $indicator->id,
                'period_type' => $period['type'],
                'period_start' => $period['start']->toDateString(),
            ];

            $values = [
                'period_end' => $period['end']->toDateString(),
                'target_value' => $targetValue,
                'actual_value' => $actualValue,
                'achievement_ratio' => $achievementRatio,
                'score' => $recordScore,
            ];

            $this->targetRepository->upsert(
                [
                    'indicator_id' => $indicator->id,
                    'period_type' => $period['type'],
                    'period_start' => $period['start']->toDateString(),
                ],
                [
                    'period_end' => $period['end']->toDateString(),
                    'target_value' => $targetValue,
                ]
            );

            $this->recordRepository->upsert($identity, $values);

            return $this->recalculateUserScore($user, $period['type'], $period['start']->toDateString());
        });
    }

    public function recalculateUserScore(User $user, string $periodType, string $periodStart): KpiScore
    {
        if (!$user->role_id) {
            throw new InvalidArgumentException('User belum memiliki role KPI.');
        }

        $indicators = $this->indicatorRepository->getByRole((int) $user->role_id);
        $records = $this->recordRepository->getUserRecordsForPeriod($user->id, $periodType, $periodStart);
        $targets = $this->targetRepository->getByIndicatorsAndPeriod(
            $indicators->pluck('id')->all(),
            $periodType,
            $periodStart
        );
        $period = $this->resolvePeriod($periodType, $periodStart);

        $breakdown = $indicators->map(function ($indicator) use ($records, $targets) {
            $record = $records->get($indicator->id);
            $target = $targets->get($indicator->id);
            $targetValue = (float) ($record?->target_value ?? $target?->target_value ?? 0);
            $actualValue = (float) ($record?->actual_value ?? 0);
            $achievementRatio = $this->calculateAchievementRatio($actualValue, $targetValue);
            $indicatorScore = round($achievementRatio * (float) $indicator->weight, 2);

            return [
                'indicator_id' => $indicator->id,
                'name' => $indicator->name,
                'description' => $indicator->description,
                'weight' => (float) $indicator->weight,
                'target_value' => round($targetValue, 2),
                'actual_value' => round($actualValue, 2),
                'achievement_ratio' => round($achievementRatio * 100, 2),
                'score' => $indicatorScore,
            ];
        })->values()->all();

        $rawScore = round(collect($breakdown)->sum('score'), 2);
        $normalizedScore = round(min($rawScore, 100), 2);

        return $this->scoreRepository->upsert(
            [
                'user_id' => $user->id,
                'period_type' => $periodType,
                'period_start' => $period['start']->toDateString(),
            ],
            [
                'role_id' => $user->role_id,
                'period_end' => $period['end']->toDateString(),
                'raw_score' => $rawScore,
                'normalized_score' => $normalizedScore,
                'grade' => $this->resolveGrade($normalizedScore),
                'breakdown' => $breakdown,
            ]
        )->loadMissing(['user.roleRef', 'role']);
    }

    public function getDashboard(array $filters): array
    {
        $period = $this->resolvePeriod($filters['period_type'], $filters['period']);
        $roleId = $filters['role_id'] ?? null;
        $scores = $this->scoreRepository->getLeaderboard(
            $period['type'],
            $period['start']->toDateString(),
            $roleId
        );

        $average = round((float) $scores->avg('normalized_score'), 2);
        $topPerformer = $scores->first();
        $lowPerformer = $scores->sortBy('normalized_score')->first();

        return [
            'filters' => [
                'period_type' => $period['type'],
                'period' => $period['start']->toDateString(),
                'role_id' => $roleId,
            ],
            'summary' => [
                'average_kpi' => $average,
                'top_performer' => $topPerformer,
                'low_performer' => $lowPerformer,
                'employee_count' => $scores->count(),
            ],
            'ranking' => $this->attachRank($scores),
        ];
    }

    public function getUserScore(int $userId, array $filters): KpiScore
    {
        $period = $this->resolvePeriod($filters['period_type'], $filters['period']);
        $score = $this->scoreRepository->findUserScore(
            $userId,
            $period['type'],
            $period['start']->toDateString()
        );

        if ($score) {
            return $score;
        }

        /** @var User $user */
        $user = User::query()->with('roleRef')->findOrFail($userId);

        return $this->recalculateUserScore($user, $period['type'], $period['start']->toDateString());
    }

    private function attachRank(Collection $scores): Collection
    {
        return $scores->values()->map(function (KpiScore $score, int $index) {
            $score->setAttribute('rank', $index + 1);

            return $score;
        });
    }

    private function calculateAchievementRatio(float $actualValue, float $targetValue): float
    {
        if ($targetValue <= 0) {
            return 0;
        }

        return round(min($actualValue / $targetValue, 1), 4);
    }

    private function resolveGrade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'E',
        };
    }

    private function resolvePeriod(string $periodType, string $period): array
    {
        $date = CarbonImmutable::parse($period);

        return match ($periodType) {
            'weekly' => [
                'type' => 'weekly',
                'start' => $date->startOfWeek(),
                'end' => $date->endOfWeek(),
            ],
            'monthly' => [
                'type' => 'monthly',
                'start' => $date->startOfMonth(),
                'end' => $date->endOfMonth(),
            ],
            default => throw new InvalidArgumentException('Period type tidak valid.'),
        };
    }
}
