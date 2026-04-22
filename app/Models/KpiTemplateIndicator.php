<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiTemplateIndicator extends Model
{
    use BelongsToTenant;

    protected $table = 'kpi_template_indicators';

    protected $fillable = [
        'tenant_id',
        'kpi_template_id',
        'indicator_name',
        'weight',
        'target_type',
        'target_value',
        'scoring_method',
        'max_cap',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'weight'       => 'float',
        'target_value' => 'float',
        'max_cap'      => 'float',
        'sort_order'   => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(KpiTemplate::class, 'kpi_template_id');
    }

    public function results(): HasMany
    {
        return $this->hasMany(EmployeeKpiResult::class, 'indicator_id');
    }
}
