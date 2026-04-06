<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Task extends Model
{
    protected $table = 'tasks';
    
    protected $fillable = [
        'employee_id', 'task_date', 'title', 'type', 'status',
        'start_time', 'end_time', 'has_delay', 'has_error', 
        'has_complaint', 'description'
    ];

    protected $casts = [
        'task_date' => 'date',
        'has_delay' => 'boolean',
        'has_error' => 'boolean',
        'has_complaint' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function kpiMapping(): HasOne
    {
        return $this->hasOne(TaskKpiMapping::class);
    }
}