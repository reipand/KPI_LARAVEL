<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Requests\UpdateTaskMappingRequest;
use App\Http\Resources\TaskResource;
use App\Models\ActivityLog;
use App\Models\KpiIndicator;
use App\Models\Tenant;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends ApiController
{
    public function __construct(
        private readonly TaskAssignmentService $taskAssignmentService,
        private readonly \App\Services\NotificationService $notificationService,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $tenantIds = $this->accessibleTenantIds($user);

        $tasks = Task::query()
            ->with([
                'user.primaryTenant',
                'assignee.primaryTenant',
                'assigner.primaryTenant',
                'kpiIndicator.position',
                'mapper.primaryTenant',
                'taskScores',
            ])
            ->when($user->isTenantAdmin(), fn ($query) => $query->where('tenant_id', $this->resolveScopedTenantId($user)))
            ->when($user->canManageAllData() && $tenantIds !== null, fn ($query) => $query->whereIn('tenant_id', $tenantIds))
            ->when(
                $request->filled('tenant_id') && $user->canManageAllData() && $this->canAccessTenant($user, $request->integer('tenant_id')),
                fn ($query) => $query->where('tenant_id', $request->integer('tenant_id'))
            )
            ->when($user->isEmployee(), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('user_id', $user->id)
                ->orWhere('assigned_to', $user->id)))
            ->when($request->filled('user_id') && !$user->isEmployee(), fn ($query) => $query->where('user_id', $request->integer('user_id')))
            ->when($request->filled('assigned_to') && !$user->isEmployee(), fn ($query) => $query->where('assigned_to', $request->integer('assigned_to')))
            ->when($request->filled('task_type') || $request->filled('type'), function ($query) use ($request) {
                $query->where('task_type', (string) ($request->input('task_type') ?? $request->input('type')));
            })
            ->when($request->filled('bulan'), fn ($query) => $query->whereMonth(DB::raw('COALESCE(end_date, tanggal)'), $request->integer('bulan')))
            ->when($request->filled('tahun'), fn ($query) => $query->whereYear(DB::raw('COALESCE(end_date, tanggal)'), $request->integer('tahun')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', Task::statusForStorage((string) $request->input('status'))))
            ->latest(DB::raw('COALESCE(end_date, tanggal)'))
            ->paginate((int) $request->input('per_page', 15));

        return $this->paginated(TaskResource::collection($tasks), $tasks);
    }

    public function store(StoreTaskRequest $request)
    {
        if ($request->isManualAssignmentPayload()) {
            if (!$request->user()->canManageCompanyData()) {
                return $this->error('Akses ditolak.', status: Response::HTTP_FORBIDDEN);
            }

            $payload = $request->validated();
            $payload['tenant_id'] = $this->resolveAssignableTenantId($request->user(), (int) $payload['assigned_to']);

            $task = $this->taskAssignmentService->create($payload, $request->user());

            ActivityLog::record(
                $request->user(),
                'task.assigned',
                Task::class,
                $task->id,
                ['judul' => $task->judul, 'assigned_to' => $task->assigned_to_user_id],
                $request
            );

            return $this->resource(new TaskResource($task), 'Task KPI berhasil di-assign.', Response::HTTP_CREATED);
        }

        $data = collect($request->validated())->except('file_evidence')->toArray();
        $this->ensureIndicatorMatchesUserDepartment($data['kpi_indicator_id'] ?? null, $request->user());
        $data['tenant_id'] = $this->resolveScopedTenantId($request->user());

        if ($request->hasFile('file_evidence')) {
            $data['file_evidence'] = $request->file('file_evidence')->store('task-evidence', 'public');
        }

        $task = $request->user()->tasks()->create($data);

        ActivityLog::record(
            $request->user(),
            'task.created',
            Task::class,
            $task->id,
            ['judul' => $task->judul],
            $request
        );

        return $this->resource(new TaskResource($task->load(['user.primaryTenant', 'kpiIndicator.position', 'mapper.primaryTenant'])), 'Pekerjaan berhasil ditambahkan.', Response::HTTP_CREATED);
    }

    public function update(StoreTaskRequest $request, Task $task)
    {
        $this->ensureTaskAccessible($task, $request->user());

        if ($task->isManualAssignment()) {
            if ($request->user()->canManageCompanyData()) {
                $payload = $request->validated();
                $payload['tenant_id'] = $this->resolveAssignableTenantId(
                    $request->user(),
                    (int) ($payload['assigned_to'] ?? $task->assigned_to_user_id)
                );

                $task = $this->taskAssignmentService->update($task, $payload, $request->user());

                ActivityLog::record(
                    $request->user(),
                    'task.assignment_updated',
                    Task::class,
                    $task->id,
                    ['judul' => $task->judul, 'assigned_to' => $task->assigned_to_user_id],
                    $request
                );

                return $this->resource(new TaskResource($task), 'Task KPI berhasil diperbarui.');
            }

            if ((int) $task->assigned_to_user_id !== (int) $request->user()->id) {
                return $this->error('Akses ditolak.', status: Response::HTTP_FORBIDDEN);
            }

            $payload = collect($request->validated())->except('file_evidence')->toArray();

            if ($request->hasFile('file_evidence')) {
                if ($task->file_evidence) {
                    Storage::disk('public')->delete($task->file_evidence);
                }
                $payload['file_evidence'] = $request->file('file_evidence')->store('task-evidence', 'public');
            }

            $task = $this->taskAssignmentService->updateAssigneeProgress($task, $payload);

            ActivityLog::record(
                $request->user(),
                'task.assignment_progress_updated',
                Task::class,
                $task->id,
                $payload,
                $request
            );

            return $this->resource(new TaskResource($task), 'Progress task berhasil diperbarui.');
        }

        if ($request->isManualAssignmentPayload()) {
            if (!$request->user()->canManageCompanyData()) {
                return $this->error('Akses ditolak.', status: Response::HTTP_FORBIDDEN);
            }

            $payload = $request->validated();
            $payload['tenant_id'] = $this->resolveAssignableTenantId(
                $request->user(),
                (int) ($payload['assigned_to'] ?? $task->assigned_to_user_id)
            );

            $task = $this->taskAssignmentService->update($task, $payload, $request->user());

            ActivityLog::record(
                $request->user(),
                'task.assignment_updated',
                Task::class,
                $task->id,
                ['judul' => $task->judul, 'assigned_to' => $task->assigned_to_user_id],
                $request
            );

            return $this->resource(new TaskResource($task), 'Task KPI berhasil diperbarui.');
        }

        $this->authorize('delete', $task);

        $data = collect($request->validated())->except('file_evidence')->toArray();
        $this->ensureIndicatorMatchesUserDepartment($data['kpi_indicator_id'] ?? $task->kpi_indicator_id, $request->user());

        if ($request->hasFile('file_evidence')) {
            if ($task->file_evidence) {
                Storage::disk('public')->delete($task->file_evidence);
            }
            $data['file_evidence'] = $request->file('file_evidence')->store('task-evidence', 'public');
        }

        $task->update($data);

        ActivityLog::record(
            $request->user(),
            'task.updated',
            Task::class,
            $task->id,
            ['judul' => $task->judul],
            $request
        );

        return $this->resource(new TaskResource($task->load(['user.primaryTenant', 'kpiIndicator.position', 'mapper.primaryTenant'])), 'Pekerjaan berhasil diperbarui.');
    }

    public function destroy(Request $request, Task $task)
    {
        $this->ensureTaskAccessible($task, $request->user());

        if ($task->isManualAssignment()) {
            if (!$request->user()->canManageCompanyData()) {
                return $this->error('Akses ditolak.', status: Response::HTTP_FORBIDDEN);
            }

            $payload = ['judul' => $task->judul, 'assigned_to' => $task->assigned_to_user_id];
            $this->taskAssignmentService->delete($task);

            ActivityLog::record(
                $request->user(),
                'task.assignment_deleted',
                Task::class,
                $task->id,
                $payload,
                $request
            );

            return $this->success(null, 'Task KPI berhasil dihapus.');
        }

        $this->authorize('delete', $task);

        $payload = ['judul' => $task->judul, 'user_id' => $task->user_id];
        $task->delete();

        ActivityLog::record(
            $request->user(),
            'task.deleted',
            Task::class,
            $task->id,
            $payload,
            $request
        );

        return $this->success(null, 'Pekerjaan berhasil dihapus.');
    }

    public function mapping(UpdateTaskMappingRequest $request, Task $task)
    {
        $this->ensureTaskAccessible($task, $request->user());

        if (!$request->user()->canManageAllData()) {
            return $this->error('Akses ditolak.', status: Response::HTTP_FORBIDDEN);
        }

        $taskOwner = $task->assignee ?: $task->user;
        $this->ensureIndicatorMatchesUserDepartment($request->integer('kpi_indicator_id'), $taskOwner);

        $task->update([
            'kpi_indicator_id' => $request->integer('kpi_indicator_id'),
            'manual_score' => $request->input('manual_score'),
            'mapped_by' => $request->user()->id,
            'mapped_at' => now(),
        ]);

        ActivityLog::record(
            $request->user(),
            'task.mapped',
            Task::class,
            $task->id,
            $request->validated(),
            $request
        );

        return $this->resource(new TaskResource($task->load(['user.primaryTenant', 'assignee.primaryTenant', 'kpiIndicator.position', 'mapper.primaryTenant'])), 'Mapping KPI berhasil diperbarui.');
    }

    public function myTasks(Request $request)
    {
        $tasks = Task::query()
            ->with(['assignee', 'assigner', 'kpiIndicator.position', 'taskScores'])
            ->with(['assignee.primaryTenant', 'assigner.primaryTenant'])
            ->where('task_type', Task::TYPE_MANUAL_ASSIGNMENT)
            ->where('assigned_to', $request->user()->id)
            ->when($request->user()->tenant_id, fn ($query) => $query->where('tenant_id', $this->resolveScopedTenantId($request->user())))
            ->latest('end_date')
            ->paginate((int) $request->input('per_page', 15));

        return $this->paginated(TaskResource::collection($tasks), $tasks);
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task)
    {
        $this->ensureTaskAccessible($task, $request->user());

        if (!$task->isManualAssignment()) {
            return $this->error('Endpoint ini hanya untuk task assignment HR.', status: Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ((int) $task->assigned_to_user_id !== (int) $request->user()->id && !$request->user()->canManageAllData()) {
            return $this->error('Akses ditolak.', status: Response::HTTP_FORBIDDEN);
        }

        $payload = collect($request->validated())->except('file_evidence')->toArray();

        if ($request->hasFile('file_evidence')) {
            if ($task->file_evidence) {
                Storage::disk('public')->delete($task->file_evidence);
            }
            $payload['file_evidence'] = $request->file('file_evidence')->store('task-evidence', 'public');
        }

        $task = $this->taskAssignmentService->updateStatus($task, $payload);

        ActivityLog::record(
            $request->user(),
            'task.status_updated',
            Task::class,
            $task->id,
            ['status' => $task->status_code, 'actual_value' => $task->actual_value],
            $request
        );

        return $this->resource(new TaskResource($task), 'Status task berhasil diperbarui.');
    }

    private function ensureIndicatorMatchesUserDepartment(?int $indicatorId, ?User $user): void
    {
        if (! $indicatorId || ! $user) {
            return;
        }

        $indicator = KpiIndicator::query()
            ->with('position:id,department_id')
            ->find($indicatorId);

        if (! $indicator) {
            return;
        }

        $indicatorDepartmentId = $indicator->department_id ?: $indicator->position?->department_id;

        if (! $user->department_id || ! $indicatorDepartmentId || (int) $user->department_id !== (int) $indicatorDepartmentId) {
            throw ValidationException::withMessages([
                'kpi_indicator_id' => 'Indikator KPI harus sesuai dengan divisi pegawai terkait.',
            ]);
        }
    }

    private function ensureTaskAccessible(Task $task, User $user): void
    {
        if ($user->canManageAllData()) {
            if ($task->tenant_id && ! $this->canAccessTenant($user, (int) $task->tenant_id)) {
                abort(Response::HTTP_FORBIDDEN, 'Task ini berada di perusahaan lain.');
            }

            return;
        }

        if ($user->isTenantAdmin() && (int) $task->tenant_id !== $this->resolveScopedTenantId($user)) {
            abort(Response::HTTP_FORBIDDEN, 'Task ini berada di perusahaan lain.');
        }
    }

    private function resolveAssignableTenantId(User $actor, int $assigneeId): int
    {
        $tenantId = (int) User::query()->whereKey($assigneeId)->value('tenant_id');

        if ($tenantId <= 0) {
            throw ValidationException::withMessages([
                'assigned_to' => 'Pegawai belum terhubung ke perusahaan mana pun.',
            ]);
        }

        if (! $actor->canManageAllData() && $tenantId !== $this->resolveScopedTenantId($actor)) {
            throw ValidationException::withMessages([
                'assigned_to' => 'HR perusahaan hanya bisa memberi tugas ke pegawai di perusahaan yang sama.',
            ]);
        }

        if ($actor->canManageAllData() && ! $this->canAccessTenant($actor, $tenantId)) {
            throw ValidationException::withMessages([
                'assigned_to' => 'Anda tidak memiliki akses ke perusahaan pegawai tersebut.',
            ]);
        }

        return $tenantId;
    }

    private function resolveScopedTenantId(User $actor): int
    {
        $tenantId = app()->bound('current_tenant_id') ? (int) app('current_tenant_id') : 0;

        if ($tenantId > 0) {
            return $tenantId;
        }

        return (int) $actor->tenant_id;
    }

    private function accessibleTenantIds(User $actor): ?array
    {
        if ($actor->hasKpiRole('super_admin')) {
            return null;
        }

        return collect([$actor->tenant_id])
            ->merge($actor->tenants()->pluck('tenants.id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function canAccessTenant(User $actor, int $tenantId): bool
    {
        if ($tenantId <= 0) {
            return false;
        }

        if ($actor->hasKpiRole('super_admin')) {
            return Tenant::withoutGlobalScopes()->whereKey($tenantId)->exists();
        }

        return $actor->hasAccessToTenant($tenantId);
    }
}
