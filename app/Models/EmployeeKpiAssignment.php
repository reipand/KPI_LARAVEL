<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeKpiAssignment extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $table = 'employee_kpi_assignments';

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'kpi_template_id',
        'assigned_by',
        'period_month',
        'period_year',
        'status',
        'rejection_reason',
        'assigned_at',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'reviewed_by',
    ];

    protected $casts = [
        'assigned_at'  => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at'  => 'datetime',
        'approved_at'  => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTemplate::class, 'kpi_template_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function results(): HasMany
    {
        return $this->hasMany(EmployeeKpiResult::class, 'assignment_id');
    }

    public function getTotalScoreAttribute(): float
    {
        return (float) $this->results->sum('weighted_score');
    }

    public function getPeriodLabelAttribute(): string
    {
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];

        if ($this->period_month) {
            return ($months[$this->period_month] ?? '?').' '.$this->period_year;
        }

        return (string) $this->period_year;
    }
}
