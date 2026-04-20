<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiIndicator extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'weight',
        'default_target_value',
        'formula',
        'department_id',
        'position_id',
    ];

    protected function casts(): array
    {
        return [
            'weight'               => 'decimal:2',
            'default_target_value' => 'decimal:2',
            'formula'              => 'array',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function targets(): HasMany
    {
        return $this->hasMany(KpiTarget::class, 'indicator_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(KpiRecord::class, 'indicator_id');
    }

    /** Human-readable formula type label. */
    public function getFormulaTtypeLabel(): string
    {
        return match ($this->formula['type'] ?? 'percentage') {
            'conditional'  => 'Kondisional',
            'threshold'    => 'Bertahap',
            'zero_penalty' => 'Zero Penalty',
            'flat'         => 'Tetap',
            default        => 'Persentase',
        };
    }
}
