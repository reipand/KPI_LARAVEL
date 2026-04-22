<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskManagementController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    #[OA\Get(
        path: '/api/v2/tasks/v2',
        summary: 'List tasks',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function index(Request $request): JsonResponse
    {
        $tasks = Task::with(['assignee', 'assigner', 'department'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->priority, fn ($q) => $q->where('priority', $request->priority))
            ->when($request->assignee_employee_id, fn ($q) => $q->where('assigned_to', $request->assignee_employee_id))
            ->when($request->department_id, fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->search, fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($tasks);
    }

    #[OA\Post(
        path: '/api/v2/tasks/v2',
        summary: 'Create task',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'                 => 'required|string|max:255',
            'description'           => 'nullable|string',
            'assignee_employee_id'  => 'required|exists:users,id',
            'department_id'         => 'nullable|exists:departments,id',
            'priority'              => 'required|in:low,medium,high,critical',
            'start_date'            => 'nullable|date',
            'due_date'              => 'nullable|date|after_or_equal:start_date',
        ]);

        $task = Task::create([
            'tenant_id'   => app('current_tenant_id'),
            'assigned_to' => $data['assignee_employee_id'],
            'assigned_by' => auth()->id(),
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'priority'    => $data['priority'],
            'status'      => 'open',
            'start_date'  => $data['start_date'] ?? null,
            'due_date'    => $data['due_date'] ?? null,
        ]);

        $this->audit->log('tasks', 'create', 'Task', $task->id, null, $data);

        return response()->json(['message' => 'Task created.', 'data' => $task->load('assignee')], 201);
    }

    #[OA\Get(
        path: '/api/v2/tasks/v2/{id}',
        summary: 'Get task',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function show(Task $task): JsonResponse
    {
        return response()->json([
            'data' => $task->load(['assignee', 'assigner', 'department']),
        ]);
    }

    #[OA\Put(
        path: '/api/v2/tasks/v2/{id}',
        summary: 'Update task',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function update(Request $request, Task $task): JsonResponse
    {
        $this->checkOwnership($task);

        $data = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'priority'    => 'in:low,medium,high,critical',
            'status'      => 'in:open,in_progress,completed,overdue,cancelled',
            'due_date'    => 'nullable|date',
            'progress'    => 'nullable|integer|between:0,100',
        ]);

        $old = $task->toArray();

        if (isset($data['status']) && $data['status'] === 'completed' && ! $task->completed_at) {
            $data['completed_at'] = now();
        }

        $task->update($data);

        $this->audit->log('tasks', 'update', 'Task', $task->id, $old, $task->fresh()->toArray());

        return response()->json(['message' => 'Task updated.', 'data' => $task->fresh()]);
    }

    #[OA\Delete(
        path: '/api/v2/tasks/v2/{id}',
        summary: 'Delete task',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function destroy(Task $task): JsonResponse
    {
        $this->checkOwnership($task);

        $old = $task->toArray();
        $task->delete();

        $this->audit->log('tasks', 'delete', 'Task', $task->id, $old, null);

        return response()->json(['message' => 'Task deleted.']);
    }

    #[OA\Get(
        path: '/api/v2/tasks/v2/mine',
        summary: 'Get my tasks',
        security: [['bearerAuth' => []]],
        tags: ['Tasks'],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
        public function myTasks(Request $request): JsonResponse
    {
        $tasks = Task::with(['assigner', 'department'])
            ->where('assigned_to', auth()->id())
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('due_date')
            ->paginate(15);

        return response()->json($tasks);
    }

    private function checkOwnership(Task $task): void
    {
        $tenantId = app()->bound('current_tenant_id') ? app('current_tenant_id') : null;

        if ($tenantId !== null && (int) $task->tenant_id !== (int) $tenantId) {
            abort(403, 'Access denied.');
        }
    }
}
