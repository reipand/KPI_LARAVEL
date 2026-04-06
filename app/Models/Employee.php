<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'nip', 'name', 'position', 'department', 'status', 
        'join_date', 'phone', 'email', 'role'
    ];

    protected $casts = [
        'join_date' => 'date',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function isHR(): bool
    {
        return $this->role === 'hr_manager' || $this->position === 'Direktur Utama' || $this->position === 'Direktur';
    }

    public function calculateKpiScore($month = null, $year = null)
    {
        $kpiComponents = KpiComponent::where('position', $this->position)->get();
        
        if ($kpiComponents->isEmpty()) {
            return (object)['total' => 0, 'predikat' => 'N/A', 'components' => []];
        }

        $tasksQuery = $this->tasks();
        
        if ($month && $year) {
            $tasksQuery->whereMonth('task_date', $month)->whereYear('task_date', $year);
        }
        
        $tasks = $tasksQuery->get();
        $totalScore = 0;
        $componentResults = [];

        foreach ($kpiComponents as $component) {
            $mappedTasks = $tasks->filter(function($task) use ($component) {
                return $task->kpiMapping && $task->kpiMapping->kpi_component_id === $component->id;
            });

            $skor = $this->calculateComponentScore($component, $mappedTasks);
            $bobotSkor = $component->weight * $skor;
            $totalScore += $bobotSkor;
            
            $componentResults[] = (object)[
                'component' => $component,
                'skor' => $skor,
                'bobotSkor' => $bobotSkor,
                'linkedCount' => $mappedTasks->count()
            ];
        }

        $predikat = $this->getPredikat($totalScore);

        return (object)[
            'total' => round($totalScore, 2),
            'predikat' => $predikat,
            'components' => $componentResults
        ];
    }

    private function calculateComponentScore($component, $tasks)
    {
        if ($component->type === 'zero_delay') {
            $hasDelay = $tasks->contains('has_delay', true);
            return $hasDelay ? 0 : 5;
        }
        
        if ($component->type === 'zero_error') {
            $hasError = $tasks->contains('has_error', true);
            return $hasError ? 0 : 5;
        }
        
        if ($component->type === 'zero_complaint') {
            $hasComplaint = $tasks->contains('has_complaint', true);
            return $hasComplaint ? 0 : 5;
        }
        
        if (in_array($component->type, ['achievement', 'csi'])) {
            if ($tasks->isEmpty()) return 0;
            
            $achieved = $tasks->sum(function($task) {
                return $task->kpiMapping->manual_score ?? 0;
            });
            
            $percentage = $component->target > 0 ? ($achieved / $component->target) * 100 : 100;
            
            if ($percentage > 99.9) return 5;
            if ($percentage >= 90) return 4;
            if ($percentage >= 70) return 3;
            if ($percentage >= 60) return 2;
            if ($percentage >= 50) return 1;
            return 0;
        }
        
        return 0;
    }

    private function getPredikat($score)
    {
        if ($score >= 5) return 'Baik Sekali';
        if ($score >= 4) return 'Baik';
        if ($score >= 3) return 'Cukup';
        if ($score >= 2) return 'Kurang';
        return 'Buruk';
    }
}