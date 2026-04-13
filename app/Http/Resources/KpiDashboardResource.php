<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KpiDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'filters' => $this['filters'],
            'summary' => [
                'average_kpi' => $this['summary']['average_kpi'],
                'employee_count' => $this['summary']['employee_count'],
                'top_performer' => $this['summary']['top_performer']
                    ? (new KpiScoreDetailResource($this['summary']['top_performer']))->resolve()
                    : null,
                'low_performer' => $this['summary']['low_performer']
                    ? (new KpiScoreDetailResource($this['summary']['low_performer']))->resolve()
                    : null,
            ],
            'ranking' => KpiScoreDetailResource::collection($this['ranking'])->resolve(),
        ];
    }
}
