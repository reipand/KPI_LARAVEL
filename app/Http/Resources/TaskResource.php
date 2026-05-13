<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_type' => $this->task_type,
            'title' => $this->judul,
            'description' => $this->deskripsi,
            'tanggal' => optional($this->tanggal)->toDateString(),
            'start_date' => optional($this->start_date)->toDateString(),
            'end_date' => optional($this->end_date)->toDateString(),
            'judul' => $this->judul,
            'jenis_pekerjaan' => $this->jenis_pekerjaan,
            'status' => $this->status,
            'status_code' => $this->status_code,
            'status_label' => $this->status_label,
            'waktu_mulai' => $this->formatTime($this->waktu_mulai),
            'waktu_selesai' => $this->formatTime($this->waktu_selesai),
            'ada_delay' => (bool) $this->ada_delay,
            'ada_error' => (bool) $this->ada_error,
            'ada_komplain' => (bool) $this->ada_komplain,
            'deskripsi' => $this->deskripsi,
            'assigned_by' => $this->assigned_by,
            'assigned_to' => $this->assigned_to_user_id,
            'weight' => $this->weight !== null ? (float) $this->weight : null,
            'target_value' => $this->target_value !== null ? (float) $this->target_value : null,
            'actual_value' => $this->actual_value !== null ? (float) $this->actual_value : null,
            'file_evidence' => $this->file_evidence,
            'file_evidence_url' => $this->file_evidence_url,
            'kpi_indicator_id' => $this->kpi_indicator_id,
            'is_kpi' => (bool) $this->is_kpi,
            'non_kpi_category' => $this->non_kpi_category,
            'review_quality' => $this->review_quality,
            'review_timeliness' => $this->review_timeliness,
            'review_initiative' => $this->review_initiative,
            'review_contribution' => $this->review_contribution,
            'review_note' => $this->review_note,
            'manual_score' => $this->manual_score !== null ? (float) $this->manual_score : null,
            'mapped_at' => optional($this->mapped_at)->toISOString(),
            'user' => new UserResource($this->whenLoaded('user')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'assigner' => new UserResource($this->whenLoaded('assigner')),
            'kpi_indicator' => new KpiIndicatorResource($this->whenLoaded('kpiIndicator')),
            'mapped_by_user' => new UserResource($this->whenLoaded('mapper')),
            'task_scores' => $this->whenLoaded('taskScores', fn () => $this->taskScores->map(fn ($score) => [
                'id' => $score->id,
                'score' => (float) $score->score,
                'period' => $score->period,
            ])->values()),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }

    private function formatTime(?string $value): ?string
    {
        return $value ? substr($value, 0, 5) : null;
    }
}
