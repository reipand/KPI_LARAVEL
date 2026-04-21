<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KpiIndicatorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'weight' => $this->weight !== null ? (float) $this->weight : null,
            'default_target_value' => $this->default_target_value !== null ? (float) $this->default_target_value : null,
            'formula' => $this->formula,
            'formula_type_label' => $this->getFormulaTypeLabel(),
            'department_id' => $this->department_id,
            'position_id' => $this->position_id,
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department?->id,
                'nama' => $this->department?->nama,
                'kode' => $this->department?->kode,
            ]),
            'position' => $this->whenLoaded('position', fn () => [
                'id' => $this->position?->id,
                'nama' => $this->position?->nama,
                'level' => $this->position?->level,
            ]),
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
