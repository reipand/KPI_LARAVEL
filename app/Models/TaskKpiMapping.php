<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskKpiMapping extends Model
{
    protected $fillable = [
        'task_id', 'kpi_component_id', 'manual_score'
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function kpiComponent(): BelongsTo
    {
        return $this->belongsTo(KpiComponent::class);
    }
}