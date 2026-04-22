<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeKpiResult extends Model
{
    use BelongsToTenant;

    protected $table = 'employee_kpi_results';

    protected $fillable = [
        'tenant_id',
        'assignment_id',
        'indicator_id',
        'actual_value',
        'achievement_percent',
        'weighted_score',
        'employee_notes',
        'reviewer_notes',
        'status',
    ];

    protected $casts = [
        'actual_value'        => 'float',
        'achievement_percent' => 'float',
        'weighted_score'      => 'float',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(EmployeeKpiAssignment::class, 'assignment_id');
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(KpiTemplateIndicator::class, 'indicator_id');
    }
}
