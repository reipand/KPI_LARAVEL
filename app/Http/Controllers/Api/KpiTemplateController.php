<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\KpiTemplate;
use App\Models\KpiTemplateIndicator;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KpiTemplateController extends Controller
{
    public function __construct(private readonly AuditService $auditService) {}

    #[OA\Get(
        path: '/api/v2/kpi/templates',
        summary: 'List KPI templates',
        security: [['bearerAuth' => []]],
        tags: ['KPI Templates'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function index(Request $request): JsonResponse
    {
        $templates = KpiTemplate::with(['department', 'position', 'indicators'])
            ->when($request->search, fn ($q) => $q->where('template_name', 'like', "%{$request->search}%"))
            ->when($request->department_id, fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->is_active !== null, fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($templates);
    }

    #[OA\Post(
        path: '/api/v2/kpi/templates',
        summary: 'Create KPI template',
        security: [['bearerAuth' => []]],
        tags: ['KPI Templates'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'template_name' => 'required|string|max:255',
            'description'   => 'nullable|string',
            'period_type'   => 'required|in:monthly,quarterly,yearly',
            'department_id' => 'nullable|exists:departments,id',
            'position_id'   => 'nullable|exists:positions,id',
            'is_active'     => 'boolean',
            'indicators'    => 'required|array|min:1',
            'indicators.*.indicator_name' => 'required|string|max:255',
            'indicators.*.weight'         => 'required|numeric|min:0|max:100',
            'indicators.*.target_type'    => 'required|in:number,percent,boolean,checklist,rating',
            'indicators.*.target_value'   => 'required|numeric',
            'indicators.*.scoring_method' => 'required|in:higher_is_better,lower_is_better,exact_match',
            'indicators.*.max_cap'        => 'nullable|numeric|min:100',
            'indicators.*.notes'          => 'nullable|string',
        ]);

        $totalWeight = collect($data['indicators'])->sum('weight');
        if (abs($totalWeight - 100) > 0.01) {
            return response()->json([
                'message' => 'Indicator weights must sum to 100%. Current total: '.$totalWeight.'%',
            ], 422);
        }

        $template = KpiTemplate::create([
            'tenant_id'     => app('current_tenant_id'),
            'template_name' => $data['template_name'],
            'description'   => $data['description'] ?? null,
            'period_type'   => $data['period_type'],
            'department_id' => $data['department_id'] ?? null,
            'position_id'   => $data['position_id'] ?? null,
            'is_active'     => $data['is_active'] ?? true,
            'created_by'    => auth()->id(),
        ]);

        foreach ($data['indicators'] as $idx => $ind) {
            KpiTemplateIndicator::create([
                'tenant_id'        => $template->tenant_id,
                'kpi_template_id'  => $template->id,
                'indicator_name'   => $ind['indicator_name'],
                'weight'           => $ind['weight'],
                'target_type'      => $ind['target_type'],
                'target_value'     => $ind['target_value'],
                'scoring_method'   => $ind['scoring_method'],
                'max_cap'          => $ind['max_cap'] ?? 120.00,
                'notes'            => $ind['notes'] ?? null,
                'sort_order'       => $idx,
            ]);
        }

        $this->auditService->log('kpi_templates', 'create', 'KpiTemplate', $template->id, null, [
            'template_name' => $template->template_name,
        ]);

        return response()->json([
            'message' => 'KPI Template created.',
            'data'    => $template->load('indicators'),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v2/kpi/templates/{id}',
        summary: 'Get template with indicators',
        security: [['bearerAuth' => []]],
        tags: ['KPI Templates'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function show(KpiTemplate $kpiTemplate): JsonResponse
    {
        return response()->json([
            'data' => $kpiTemplate->load(['indicators', 'department', 'position']),
        ]);
    }

    #[OA\Put(
        path: '/api/v2/kpi/templates/{id}',
        summary: 'Update KPI template',
        security: [['bearerAuth' => []]],
        tags: ['KPI Templates'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function update(Request $request, KpiTemplate $kpiTemplate): JsonResponse
    {
        $data = $request->validate([
            'template_name' => 'sometimes|string|max:255',
            'description'   => 'nullable|string',
            'period_type'   => 'in:monthly,quarterly,yearly',
            'department_id' => 'nullable|exists:departments,id',
            'position_id'   => 'nullable|exists:positions,id',
            'is_active'     => 'boolean',
        ]);

        $old = $kpiTemplate->toArray();
        $kpiTemplate->update($data);

        $this->auditService->log('kpi_templates', 'update', 'KpiTemplate', $kpiTemplate->id, $old, $kpiTemplate->fresh()->toArray());

        return response()->json(['message' => 'Template updated.', 'data' => $kpiTemplate->load('indicators')]);
    }

    #[OA\Delete(
        path: '/api/v2/kpi/templates/{id}',
        summary: 'Soft-delete KPI template',
        security: [['bearerAuth' => []]],
        tags: ['KPI Templates'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function destroy(KpiTemplate $kpiTemplate): JsonResponse
    {
        $old = $kpiTemplate->toArray();
        $kpiTemplate->delete();

        $this->auditService->log('kpi_templates', 'delete', 'KpiTemplate', $kpiTemplate->id, $old, null);

        return response()->json(['message' => 'Template deleted.']);
    }

    // --- Indicator sub-resources ---

    #[OA\Post(
        path: '/api/v2/kpi/templates/{id}/indicators',
        summary: 'Add indicator to template',
        security: [['bearerAuth' => []]],
        tags: ['KPI Templates'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function storeIndicator(Request $request, KpiTemplate $kpiTemplate): JsonResponse
    {
        $data = $request->validate([
            'indicator_name' => 'required|string|max:255',
            'weight'         => 'required|numeric|min:0|max:100',
            'target_type'    => 'required|in:number,percent,boolean,checklist,rating',
            'target_value'   => 'required|numeric',
            'scoring_method' => 'required|in:higher_is_better,lower_is_better,exact_match',
            'max_cap'        => 'nullable|numeric|min:100',
            'notes'          => 'nullable|string',
        ]);

        $indicator = $kpiTemplate->indicators()->create([
            ...$data,
            'tenant_id'  => $kpiTemplate->tenant_id,
            'sort_order' => $kpiTemplate->indicators()->max('sort_order') + 1,
        ]);

        return response()->json(['message' => 'Indicator added.', 'data' => $indicator], 201);
    }

    #[OA\Put(
        path: '/api/v2/kpi/templates/{templateId}/indicators/{id}',
        summary: 'Update indicator',
        security: [['bearerAuth' => []]],
        tags: ['KPI Templates'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function updateIndicator(Request $request, KpiTemplateIndicator $indicator): JsonResponse
    {
        $data = $request->validate([
            'indicator_name' => 'sometimes|string|max:255',
            'weight'         => 'sometimes|numeric|min:0|max:100',
            'target_type'    => 'in:number,percent,boolean,checklist,rating',
            'target_value'   => 'sometimes|numeric',
            'scoring_method' => 'in:higher_is_better,lower_is_better,exact_match',
            'max_cap'        => 'nullable|numeric|min:100',
            'notes'          => 'nullable|string',
        ]);

        $indicator->update($data);

        return response()->json(['message' => 'Indicator updated.', 'data' => $indicator]);
    }

    #[OA\Delete(
        path: '/api/v2/kpi/templates/{templateId}/indicators/{id}',
        summary: 'Remove indicator',
        security: [['bearerAuth' => []]],
        tags: ['KPI Templates'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function destroyIndicator(KpiTemplateIndicator $indicator): JsonResponse
    {
        $indicator->delete();

        return response()->json(['message' => 'Indicator removed.']);
    }
}
