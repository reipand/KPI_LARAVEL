<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class KpiReport extends Model
{
    protected $fillable = [
        'user_id',
        'kpi_indicator_id',
        'period_type',
        'tanggal',
        'period_label',
        'nilai_target',
        'nilai_aktual',
        'persentase',
        'score_label',
        'catatan',
        'review_note',
        'file_evidence',
        'status',
        'submitted_by',
        'reviewed_by',
        'submitted_at',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nilai_target' => 'decimal:4',
            'nilai_aktual' => 'decimal:4',
            'persentase' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kpiIndicator(): BelongsTo
    {
        return $this->belongsTo(KpiIndicator::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getFileEvidenceUrlAttribute(): ?string
    {
        if (! $this->file_evidence) {
            return null;
        }

        return Storage::disk('public')->url($this->file_evidence);
    }

    public function calculatePercentage(): float
    {
        if (! $this->nilai_target || $this->nilai_target == 0) {
            return $this->nilai_aktual > 0 ? 100.0 : 0.0;
        }

        return round(((float) $this->nilai_aktual / (float) $this->nilai_target) * 100, 2);
    }

    public static function resolveScoreLabel(float $percentage): string
    {
        if ($percentage > 100) {
            return 'excellent';
        }

        if ($percentage >= 80) {
            return 'good';
        }

        if ($percentage >= 50) {
            return 'average';
        }

        return 'bad';
    }
}
