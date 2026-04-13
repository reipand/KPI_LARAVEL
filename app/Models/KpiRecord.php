<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiRecord extends Model
{
    protected $fillable = [
        'user_id',
        'indicator_id',
        'period_type',
        'period_start',
        'period_end',
        'target_value',
        'actual_value',
        'achievement_ratio',
        'score',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'target_value' => 'decimal:2',
            'actual_value' => 'decimal:2',
            'achievement_ratio' => 'decimal:4',
            'score' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(KpiIndicator::class, 'indicator_id');
    }
}
