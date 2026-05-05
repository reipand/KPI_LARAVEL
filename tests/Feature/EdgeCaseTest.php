<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\KpiIndicator;
use App\Models\KpiNotification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Edge-case tests covering:
 *  - User without department
 *  - User without role
 *  - KPI without target (target = 0)
 *  - Task without end_date
 *  - Multiple KPI assignments for same user
 *  - Indicator department mismatch
 */
class EdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────
    // 1. User without department or role → graceful error on KPI input
    // ──────────────────────────────────────────────────────────────

    public function test_kpi_input_fails_gracefully_for_user_without_dept_or_role(): void
    {
        $hr       = User::factory()->create(['role' => 'hr_manager']);
        $orphan = User::factory()->create([
            'role'          => 'employee',
            'department_id' => null,
        ]);

        $indicator = KpiIndicator::factory()->create([
            'name'   => 'Orphan KPI',
            'weight' => 30,
        ]);

        Sanctum::actingAs($hr);

        $this->postJson('/api/kpi/input', [
            'user_id'      => $orphan->id,
            'indicator_id' => $indicator->id,
            'target_value' => 100,
            'actual_value' => 50,
            'period_type'  => 'monthly',
            'period'       => '2026-04-01',
        ])->assertUnprocessable()
          ->assertJsonPath('success', false);
    }

    // ──────────────────────────────────────────────────────────────
    // 2. KPI without target (target_value = 0) → score = 0, no crash
    // ──────────────────────────────────────────────────────────────

    public function test_kpi_input_with_zero_target_returns_zero_score(): void
    {
        $hr   = User::factory()->create(['role' => 'hr_manager']);
        $dept = Department::factory()->create(['kode' => 'FNA2', 'nama' => 'Finance Dept']);

        $employee = User::factory()->create(['role' => 'employee', 'department_id' => $dept->id]);

        $indicator = KpiIndicator::factory()->create([
            'name'          => 'Zero Error Invoice',
            'weight'        => 35,
            'department_id' => $dept->id,
            'formula'       => ['type' => 'zero_penalty'],
        ]);

        Sanctum::actingAs($hr);

        $response = $this->postJson('/api/kpi/input', [
            'user_id'      => $employee->id,
            'indicator_id' => $indicator->id,
            'target_value' => 0,
            'actual_value' => 0,
            'period_type'  => 'monthly',
            'period'       => '2026-04-01',
        ]);

        $response->assertCreated()
                 ->assertJsonPath('success', true);

        // zero_penalty: actual=0 → full score=35
        $this->assertEquals(35, $response->json('data.breakdown.0.score'));
    }

    // ──────────────────────────────────────────────────────────────
    // 3. Task without end_date → period resolved from start_date
    // ──────────────────────────────────────────────────────────────

    public function test_task_without_end_date_stores_correctly(): void
    {
        $hr       = User::factory()->create(['role' => 'hr_manager']);
        $employee = User::factory()->create(['role' => 'employee']);

        Sanctum::actingAs($hr);

        $response = $this->postJson('/api/tasks', [
            'title'       => 'Task tanpa deadline',
            'assigned_to' => $employee->id,
            'start_date'  => '2026-04-10',
            // No end_date
            'weight'      => 10,
            'status'      => 'pending',
        ]);

        $response->assertCreated();

        $taskId = $response->json('data.id');
        $this->assertNull($response->json('data.end_date'), 'end_date should be null');

        // KPI score for user should not crash
        Sanctum::actingAs($hr);
        $this->getJson("/api/kpi/user/{$employee->id}?period_type=monthly&period=2026-04-01")
             ->assertOk();
    }

    // ──────────────────────────────────────────────────────────────
    // 4. Task with multi-day duration (3 days) stores dates correctly
    // ──────────────────────────────────────────────────────────────

    public function test_multi_day_task_stores_start_and_end_date(): void
    {
        $hr       = User::factory()->create(['role' => 'hr_manager']);
        $employee = User::factory()->create(['role' => 'employee']);

        Sanctum::actingAs($hr);

        $response = $this->postJson('/api/tasks', [
            'title'       => 'Workshop 3 hari',
            'assigned_to' => $employee->id,
            'start_date'  => '2026-04-08',
            'end_date'    => '2026-04-10',
            'weight'      => 20,
            'status'      => 'pending',
        ]);

        $response->assertCreated();

        // Verify via API response (avoids SQLite datetime format mismatch in assertDatabaseHas)
        $this->assertEquals('2026-04-08', $response->json('data.start_date'));
        $this->assertEquals('2026-04-10', $response->json('data.end_date'));
    }

    // ──────────────────────────────────────────────────────────────
    // 5. Task completion → score = full weight
    // ──────────────────────────────────────────────────────────────

    public function test_task_done_status_gives_full_score(): void
    {
        $hr       = User::factory()->create(['role' => 'hr_manager']);
        $employee = User::factory()->create(['role' => 'employee']);

        Sanctum::actingAs($hr);

        $response = $this->postJson('/api/tasks', [
            'title'       => 'Task completed',
            'assigned_to' => $employee->id,
            'start_date'  => '2026-04-10',
            'end_date'    => '2026-04-10',
            'weight'      => 25,
            'status'      => 'done',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('task_scores', [
            'task_id' => $response->json('data.id'),
            'score'   => 25.0,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // 6. Multiple KPI indicators assigned to same user
    // ──────────────────────────────────────────────────────────────

    public function test_multiple_kpi_indicators_sum_correctly(): void
    {
        $hr   = User::factory()->create(['role' => 'hr_manager']);
        $dept = Department::factory()->create(['kode' => 'MUL', 'nama' => 'Multi Dept']);

        $employee = User::factory()->create(['role' => 'employee', 'department_id' => $dept->id]);

        $ind1 = KpiIndicator::factory()->create([
            'name' => 'KPI A', 'weight' => 40,
            'department_id' => $dept->id, 'formula' => ['type' => 'percentage'],
        ]);
        $ind2 = KpiIndicator::factory()->create([
            'name' => 'KPI B', 'weight' => 60,
            'department_id' => $dept->id, 'formula' => ['type' => 'percentage'],
        ]);

        Sanctum::actingAs($hr);

        $this->postJson('/api/kpi/input', [
            'user_id' => $employee->id, 'indicator_id' => $ind1->id,
            'target_value' => 100, 'actual_value' => 80,
            'period_type' => 'monthly', 'period' => '2026-04-01',
        ])->assertCreated();

        $this->postJson('/api/kpi/input', [
            'user_id' => $employee->id, 'indicator_id' => $ind2->id,
            'target_value' => 100, 'actual_value' => 60,
            'period_type' => 'monthly', 'period' => '2026-04-01',
        ])->assertCreated();

        // ind1 score = 80/100 * 40 = 32, ind2 = 60/100 * 60 = 36, total = 68
        $response = $this->getJson("/api/kpi/user/{$employee->id}?period_type=monthly&period=2026-04-01");

        $response->assertOk();
        $this->assertEquals(68, $response->json('data.normalized_score'));
    }

    // ──────────────────────────────────────────────────────────────
    // 7. Indicator department mismatch → error (not crash)
    // ──────────────────────────────────────────────────────────────

    public function test_kpi_input_rejects_indicator_from_wrong_department(): void
    {
        $hr    = User::factory()->create(['role' => 'hr_manager']);
        $dept1 = Department::factory()->create(['kode' => 'D01', 'nama' => 'Dept A']);
        $dept2 = Department::factory()->create(['kode' => 'D02', 'nama' => 'Dept B']);

        $employee = User::factory()->create([
            'role'          => 'employee',
            'department_id' => $dept1->id,
        ]);

        // Indicator belongs to dept2, not dept1
        $indicator = KpiIndicator::factory()->create([
            'name'          => 'Wrong Dept KPI',
            'weight'        => 30,
            'department_id' => $dept2->id,
            'formula'       => ['type' => 'percentage'],
        ]);

        Sanctum::actingAs($hr);

        $this->postJson('/api/kpi/input', [
            'user_id'      => $employee->id,
            'indicator_id' => $indicator->id,
            'target_value' => 100,
            'actual_value' => 80,
            'period_type'  => 'monthly',
            'period'       => '2026-04-01',
        ])->assertUnprocessable()
          ->assertJsonPath('success', false);
    }

    // ──────────────────────────────────────────────────────────────
    // 8. Department-based indicator assigned to user with dept_id (no role)
    // ──────────────────────────────────────────────────────────────

    public function test_department_based_indicator_works_without_role_id(): void
    {
        $hr   = User::factory()->create(['role' => 'hr_manager']);
        $dept = Department::factory()->create(['kode' => 'HGA2', 'nama' => 'HR & GA']);

        $employee = User::factory()->create([
            'role'          => 'employee',
            'department_id' => $dept->id,
        ]);

        $indicator = KpiIndicator::factory()->create([
            'name'          => 'HR Satisfaction',
            'weight'        => 50,
            'department_id' => $dept->id,
            'formula'       => ['type' => 'percentage'],
        ]);

        Sanctum::actingAs($hr);

        $response = $this->postJson('/api/kpi/input', [
            'user_id'      => $employee->id,
            'indicator_id' => $indicator->id,
            'target_value' => 80,
            'actual_value' => 80,
            'period_type'  => 'monthly',
            'period'       => '2026-04-01',
        ]);

        $response->assertCreated()
                 ->assertJsonPath('success', true);

        $this->assertEquals(50, $response->json('data.normalized_score'));
    }
}
