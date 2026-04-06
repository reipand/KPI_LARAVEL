<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiComponent extends Model
{
    protected $fillable = [
        'position', 'objective', 'strategy', 'weight', 
        'target', 'type', 'note'
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'target' => 'decimal:2',
    ];

    public function taskMappings(): HasMany
    {
        return $this->hasMany(TaskKpiMapping::class);
    }
}