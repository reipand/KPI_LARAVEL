<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\KpiIndicator;
use App\Models\Position;
use App\Models\Task;
use App\Models\TaskScore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskAssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_assign_manual_task_and_task_score_is_created(): void
    {
        $hr   = User::factory()->create(['role' => 'hr_manager']);
        $dept = Department::factory()->create(['kode' => 'OPS', 'nama' => 'Operations']);
        $employee = User::factory()->create(['role' => 'employee', 'department_id' => $dept->id]);

        KpiIndicator::query()->create([
            'name'          => 'Delivery',
            'description'   => 'Delivery KPI',
            'weight'        => 40,
            'department_id' => $dept->id,
        ]);

        Sanctum::actingAs($hr);

        $response = $this->postJson('/api/tasks', [
            'title' => 'Support event training batch April',
            'description' => 'Task manual dari HR di luar indikator KPI utama.',
            'assigned_to' => $employee->id,
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-12',
            'weight' => 20,
            'status' => 'done',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.task_type', Task::TYPE_MANUAL_ASSIGNMENT)
            ->assertJsonPath('data.assigned_to', $employee->id)
            ->assertJsonPath('data.status_code', Task::STATUS_DONE);

        $taskId = $response->json('data.id');

        $this->assertDatabaseHas('task_scores', [
            'task_id' => $taskId,
            'user_id' => $employee->id,
            'period' => '2026-04',
            'score' => 20,
        ]);

        $dashboard = $this->getJson('/api/kpi/user/'.$employee->id.'?period_type=monthly&period=2026-04-12');

        $dashboard
            ->assertOk()
            ->assertJsonPath('data.raw_score', 20)
            ->assertJsonPath('data.breakdown.1.type', 'task')
            ->assertJsonPath('data.breakdown.1.task_id', $taskId)
            ->assertJsonPath('data.breakdown.1.score', 20);
    }

    public function test_assignee_can_update_status_and_score_recalculates(): void
    {
        $hr = User::factory()->create(['role' => 'hr_manager']);
        $employee = User::factory()->create(['role' => 'employee']);

        $task = Task::query()->create([
            'task_type' => Task::TYPE_MANUAL_ASSIGNMENT,
            'user_id' => $employee->id,
            'assigned_by' => $hr->id,
            'assigned_to' => $employee->id,
            'tanggal' => '2026-04-10',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-15',
            'judul' => 'Follow up lead lama',
            'jenis_pekerjaan' => 'Task KPI',
            'status' => Task::statusForStorage('pending'),
            'weight' => 30,
        ]);

        TaskScore::query()->create([
            'task_id' => $task->id,
            'user_id' => $employee->id,
            'score' => 0,
            'period' => '2026-04',
        ]);

        Sanctum::actingAs($employee);

        $response = $this->putJson('/api/tasks/'.$task->id.'/update-status', [
            'status' => 'on_progress',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status_code', Task::STATUS_ON_PROGRESS);

        $this->assertDatabaseHas('task_scores', [
            'task_id' => $task->id,
            'score' => 15,
            'period' => '2026-04',
        ]);
    }

    public function test_assignee_must_upload_evidence_when_marking_manual_assignment_done_via_status_endpoint(): void
    {
        $hr = User::factory()->create(['role' => 'hr_manager']);
        $employee = User::factory()->create(['role' => 'employee']);

        $task = Task::query()->create([
            'task_type' => Task::TYPE_MANUAL_ASSIGNMENT,
            'user_id' => $employee->id,
            'assigned_by' => $hr->id,
            'assigned_to' => $employee->id,
            'tanggal' => '2026-04-10',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-15',
            'judul' => 'Closing task tanpa bukti',
            'jenis_pekerjaan' => 'Task KPI',
            'status' => Task::statusForStorage('pending'),
            'weight' => 30,
        ]);

        Sanctum::actingAs($employee);

        $this->putJson('/api/tasks/'.$task->id.'/update-status', [
            'status' => 'done',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file_evidence']);

        $file = UploadedFile::fake()->create('evidence.pdf', 128, 'application/pdf');

        $this->post('/api/tasks/'.$task->id.'/update-status', [
            '_method' => 'PUT',
            'status' => 'done',
            'file_evidence' => $file,
        ])
            ->assertOk()
            ->assertJsonPath('data.status_code', Task::STATUS_DONE);
    }

    public function test_my_tasks_returns_only_manual_assigned_tasks_for_user(): void
    {
        $hr = User::factory()->create(['role' => 'hr_manager']);
        $employee = User::factory()->create(['role' => 'employee']);
        $other = User::factory()->create(['role' => 'employee']);

        Task::query()->create([
            'task_type' => Task::TYPE_MANUAL_ASSIGNMENT,
            'user_id' => $employee->id,
            'assigned_by' => $hr->id,
            'assigned_to' => $employee->id,
            'tanggal' => '2026-04-10',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-10',
            'judul' => 'Task saya',
            'jenis_pekerjaan' => 'Task KPI',
            'status' => Task::statusForStorage('pending'),
            'weight' => 10,
        ]);

        Task::query()->create([
            'task_type' => Task::TYPE_MANUAL_ASSIGNMENT,
            'user_id' => $other->id,
            'assigned_by' => $hr->id,
            'assigned_to' => $other->id,
            'tanggal' => '2026-04-10',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-10',
            'judul' => 'Task orang lain',
            'jenis_pekerjaan' => 'Task KPI',
            'status' => Task::statusForStorage('pending'),
            'weight' => 10,
        ]);

        Sanctum::actingAs($employee);

        $response = $this->getJson('/api/my-tasks');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.assigned_to', $employee->id)
            ->assertJsonPath('data.items.0.title', 'Task saya');
    }

    public function test_assignee_can_update_manual_assignment_progress_via_regular_update_endpoint(): void
    {
        $hr = User::factory()->create(['role' => 'hr_manager']);
        $employee = User::factory()->create(['role' => 'employee']);

        $task = Task::query()->create([
            'task_type' => Task::TYPE_MANUAL_ASSIGNMENT,
            'user_id' => $employee->id,
            'assigned_by' => $hr->id,
            'assigned_to' => $employee->id,
            'tanggal' => '2026-04-10',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-15',
            'judul' => 'Follow up lead lama',
            'jenis_pekerjaan' => 'Task KPI',
            'status' => Task::statusForStorage('pending'),
            'weight' => 30,
        ]);

        TaskScore::query()->create([
            'task_id' => $task->id,
            'user_id' => $employee->id,
            'score' => 0,
            'period' => '2026-04',
        ]);

        Sanctum::actingAs($employee);

        $response = $this->putJson('/api/tasks/'.$task->id, [
            'status' => 'Dalam Proses',
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '10:30',
            'ada_delay' => true,
            'ada_error' => false,
            'ada_komplain' => false,
            'deskripsi' => 'Progress diupdate oleh pegawai.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status_code', Task::STATUS_ON_PROGRESS)
            ->assertJsonPath('data.waktu_mulai', '08:00')
            ->assertJsonPath('data.waktu_selesai', '10:30')
            ->assertJsonPath('data.ada_delay', true)
            ->assertJsonPath('data.deskripsi', 'Progress diupdate oleh pegawai.');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'Dalam Proses',
            'waktu_mulai' => '08:00',
            'waktu_selesai' => '10:30',
            'ada_delay' => true,
            'deskripsi' => 'Progress diupdate oleh pegawai.',
        ]);

        $this->assertDatabaseHas('task_scores', [
            'task_id' => $task->id,
            'score' => 15,
            'period' => '2026-04',
        ]);
    }

    public function test_assignee_must_upload_evidence_when_marking_manual_assignment_done_via_regular_update_endpoint(): void
    {
        $hr = User::factory()->create(['role' => 'hr_manager']);
        $employee = User::factory()->create(['role' => 'employee']);

        $task = Task::query()->create([
            'task_type' => Task::TYPE_MANUAL_ASSIGNMENT,
            'user_id' => $employee->id,
            'assigned_by' => $hr->id,
            'assigned_to' => $employee->id,
            'tanggal' => '2026-04-10',
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-15',
            'judul' => 'Update progress final',
            'jenis_pekerjaan' => 'Task KPI',
            'status' => Task::statusForStorage('pending'),
            'weight' => 30,
        ]);

        Sanctum::actingAs($employee);

        $this->putJson('/api/tasks/'.$task->id, [
            'status' => 'Selesai',
            'deskripsi' => 'Selesai tanpa bukti.',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file_evidence']);

        $file = UploadedFile::fake()->create('progress-proof.pdf', 128, 'application/pdf');

        $this->post('/api/tasks/'.$task->id, [
            '_method' => 'PUT',
            'status' => 'Selesai',
            'deskripsi' => 'Selesai dengan bukti.',
            'file_evidence' => $file,
        ])
            ->assertOk()
            ->assertJsonPath('data.status_code', Task::STATUS_DONE)
            ->assertJsonPath('data.file_evidence_url', fn ($value) => is_string($value) && $value !== '');
    }

    public function test_hr_cannot_assign_indicator_from_different_department(): void
    {
        $hr = User::factory()->create(['role' => 'hr_manager']);
        $itDepartment = Department::factory()->create(['kode' => 'IT', 'nama' => 'IT']);
        $financeDepartment = Department::factory()->create(['kode' => 'FIN', 'nama' => 'Finance']);
        $employee = User::factory()->create(['role' => 'employee', 'department_id' => $itDepartment->id]);

        $financeIndicator = KpiIndicator::factory()->create([
            'name' => 'Finance Accuracy',
            'department_id' => $financeDepartment->id,
        ]);

        Sanctum::actingAs($hr);

        $this->postJson('/api/tasks', [
            'title' => 'Task IT dengan indikator finance',
            'assigned_to' => $employee->id,
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-12',
            'weight' => 20,
            'status' => 'pending',
            'kpi_indicator_id' => $financeIndicator->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['kpi_indicator_id']);
    }

    public function test_kpi_indicator_index_scopes_results_to_selected_employee_department(): void
    {
        $hr = User::factory()->create(['role' => 'hr_manager']);
        $itDepartment = Department::factory()->create(['kode' => 'IT', 'nama' => 'IT']);
        $financeDepartment = Department::factory()->create(['kode' => 'FIN', 'nama' => 'Finance']);
        $employee = User::factory()->create(['role' => 'employee', 'department_id' => $itDepartment->id]);
        $itPosition = Position::query()->create([
            'nama' => 'IT Support',
            'kode' => 'ITS',
            'department_id' => $itDepartment->id,
            'level' => 1,
            'is_active' => true,
        ]);

        $directItIndicator = KpiIndicator::factory()->create([
            'name' => 'IT Uptime',
            'department_id' => $itDepartment->id,
        ]);
        $positionItIndicator = KpiIndicator::factory()->create([
            'name' => 'IT Ticket SLA',
            'department_id' => null,
            'position_id' => $itPosition->id,
        ]);
        $financeIndicator = KpiIndicator::factory()->create([
            'name' => 'Finance Accuracy',
            'department_id' => $financeDepartment->id,
        ]);

        Sanctum::actingAs($hr);

        $response = $this->getJson('/api/kpi-indicators?assigned_to='.$employee->id);

        $response->assertOk();
        $names = collect($response->json('data.items'))->pluck('name');

        $this->assertTrue($names->contains($directItIndicator->name));
        $this->assertTrue($names->contains($positionItIndicator->name));
        $this->assertFalse($names->contains($financeIndicator->name));
    }
}
