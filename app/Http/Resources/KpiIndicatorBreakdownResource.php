<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KpiIndicatorBreakdownResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'indicator_id' => $this['indicator_id'],
            'name' => $this['name'],
            'description' => $this['description'],
            'weight' => $this['weight'],
            'target_value' => $this['target_value'],
            'actual_value' => $this['actual_value'],
            'achievement_ratio' => $this['achievement_ratio'],
            'score' => $this['score'],
        ];
    }
}
