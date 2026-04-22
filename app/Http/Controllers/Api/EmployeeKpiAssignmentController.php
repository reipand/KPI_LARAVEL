<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\EmployeeKpiAssignment;
use App\Models\EmployeeKpiResult;
use App\Models\KpiTemplateIndicator;
use App\Services\AuditService;
use App\Services\KpiEngineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeKpiAssignmentController extends Controller
{
    public function __construct(
        private readonly KpiEngineService $engine,
        private readonly AuditService $audit,
    ) {}

    #[OA\Get(
        path: '/api/v2/kpi/assignments',
        summary: 'List assignments',
        security: [['bearerAuth' => []]],
        tags: ['KPI Assignments'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function index(Request $request): JsonResponse
    {
        $assignments = EmployeeKpiAssignment::with(['employee', 'template', 'assigner'])
            ->when($request->employee_id, fn ($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->period_year, fn ($q) => $q->where('period_year', $request->period_year))
            ->when($request->period_month, fn ($q) => $q->where('period_month', $request->period_month))
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->paginate(20);

        return response()->json($assignments);
    }

    #[OA\Post(
        path: '/api/v2/kpi/assignments',
        summary: 'Create assignment',
        security: [['bearerAuth' => []]],
        tags: ['KPI Assignments'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'employee_id'     => 'required|exists:users,id',
            'kpi_template_id' => 'required|exists:kpi_templates,id',
            'period_month'    => 'nullable|integer|between:1,12',
            'period_year'     => 'required|integer|min:2020',
        ]);

        $tenantId = app('current_tenant_id');

        // Prevent duplicate
        $exists = EmployeeKpiAssignment::where([
            'tenant_id'       => $tenantId,
            'employee_id'     => $data['employee_id'],
            'kpi_template_id' => $data['kpi_template_id'],
            'period_year'     => $data['period_year'],
            'period_month'    => $data['period_month'] ?? null,
        ])->exists();

        if ($exists) {
            return response()->json(['message' => 'Assignment already exists for this period.'], 422);
        }

        $assignment = EmployeeKpiAssignment::create([
            'tenant_id'       => $tenantId,
            'employee_id'     => $data['employee_id'],
            'kpi_template_id' => $data['kpi_template_id'],
            'assigned_by'     => auth()->id(),
            'period_month'    => $data['period_month'] ?? null,
            'period_year'     => $data['period_year'],
            'status'          => 'assigned',
            'assigned_at'     => now(),
        ]);

        // Pre-create result rows for each indicator
        $indicators = $assignment->template->indicators;
        foreach ($indicators as $indicator) {
            EmployeeKpiResult::create([
                'tenant_id'     => $tenantId,
                'assignment_id' => $assignment->id,
                'indicator_id'  => $indicator->id,
                'status'        => 'pending',
            ]);
        }

        $this->audit->log('kpi_assignments', 'create', 'EmployeeKpiAssignment', $assignment->id, null, $data);

        return response()->json([
            'message' => 'KPI assigned.',
            'data'    => $assignment->load(['employee', 'template.indicators', 'results']),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v2/kpi/assignments/{id}',
        summary: 'Get assignment details',
        security: [['bearerAuth' => []]],
        tags: ['KPI Assignments'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function show(EmployeeKpiAssignment $assignment): JsonResponse
    {
        return response()->json([
            'data' => $assignment->load(['employee', 'template.indicators', 'results.indicator', 'assigner', 'reviewer']),
        ]);
    }

    /**
     * Employee submits their KPI values.
     */
    #[OA\Post(
        path: '/api/v2/kpi/assignments/{id}/submit',
        summary: 'Employee submits results',
        security: [['bearerAuth' => []]],
        tags: ['KPI Assignments'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function submit(Request $request, EmployeeKpiAssignment $assignment): JsonResponse
    {
        $this->authorizeEmployee($assignment);

        if (! in_array($assignment->status, ['assigned', 'rejected'])) {
            return response()->json(['message' => 'Assignment is not in a submittable state.'], 422);
        }

        $data = $request->validate([
            'results'                  => 'required|array',
            'results.*.indicator_id'   => 'required|exists:kpi_template_indicators,id',
            'results.*.actual_value'   => 'required|numeric|min:0',
            'results.*.notes'          => 'nullable|string',
        ]);

        $inputData = collect($data['results'])->keyBy('indicator_id')->toArray();

        $this->engine->processSubmission($assignment, $inputData);

        $assignment->update([
            'status'       => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->audit->log('kpi_assignments', 'submit', 'EmployeeKpiAssignment', $assignment->id);

        return response()->json([
            'message' => 'KPI submitted for review.',
            'data'    => $assignment->load('results.indicator'),
        ]);
    }

    /**
     * Reviewer approves the KPI submission.
     */
    #[OA\Post(
        path: '/api/v2/kpi/assignments/{id}/approve',
        summary: 'HR/Manager approves',
        security: [['bearerAuth' => []]],
        tags: ['KPI Assignments'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function approve(Request $request, EmployeeKpiAssignment $assignment): JsonResponse
    {
        if ($assignment->status !== 'submitted') {
            return response()->json(['message' => 'Only submitted KPIs can be approved.'], 422);
        }

        $data = $request->validate(['notes' => 'nullable|string']);

        $assignment->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'approved_at' => now(),
        ]);

        $assignment->results()->update([
            'status'         => 'approved',
            'reviewer_notes' => $data['notes'] ?? null,
        ]);

        $this->audit->log('kpi_assignments', 'approve', 'EmployeeKpiAssignment', $assignment->id);

        return response()->json(['message' => 'KPI approved.', 'data' => $assignment->fresh()]);
    }

    /**
     * Reviewer rejects the KPI submission.
     */
    #[OA\Post(
        path: '/api/v2/kpi/assignments/{id}/reject',
        summary: 'HR/Manager rejects',
        security: [['bearerAuth' => []]],
        tags: ['KPI Assignments'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function reject(Request $request, EmployeeKpiAssignment $assignment): JsonResponse
    {
        if ($assignment->status !== 'submitted') {
            return response()->json(['message' => 'Only submitted KPIs can be rejected.'], 422);
        }

        $data = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $assignment->update([
            'status'           => 'rejected',
            'reviewed_by'      => auth()->id(),
            'reviewed_at'      => now(),
            'rejection_reason' => $data['rejection_reason'],
        ]);

        $assignment->results()->update(['status' => 'rejected']);

        $this->audit->log('kpi_assignments', 'reject', 'EmployeeKpiAssignment', $assignment->id, null, [
            'reason' => $data['rejection_reason'],
        ]);

        return response()->json(['message' => 'KPI rejected.', 'data' => $assignment->fresh()]);
    }

    /**
     * My assignments — for currently authenticated employee.
     */
    #[OA\Get(
        path: '/api/v2/kpi/assignments/mine',
        summary: 'Get my own assignments',
        security: [['bearerAuth' => []]],
        tags: ['KPI Assignments'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function myAssignments(Request $request): JsonResponse
    {
        $assignments = EmployeeKpiAssignment::with(['template.indicators', 'results.indicator'])
            ->where('employee_id', auth()->id())
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->period_year, fn ($q) => $q->where('period_year', $request->period_year))
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->paginate(15);

        return response()->json($assignments);
    }

    private function authorizeEmployee(EmployeeKpiAssignment $assignment): void
    {
        $user = auth()->user();
        if (! $user->hasAnyRole(['super_admin', 'tenant_admin', 'hr_manager', 'dept_head', 'supervisor'])
            && (int) $assignment->employee_id !== (int) $user->id) {
            abort(403, 'You can only submit your own KPI.');
        }
    }
}
