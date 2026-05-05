<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\KpiIndicator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Integrity tests verifying the refactor:
 *  - divisions table and columns are gone
 *  - custom role_id columns are gone from users / kpi_indicators / kpi_scores
 *  - IT department (ITD) and its KPI indicators work correctly
 *  - Spatie roles can be assigned to and queried from users
 */
class RefactorIntegrityTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────────
    // 1. Schema: divisions table no longer exists
    // ──────────────────────────────────────────────────────────────

    public function test_divisions_table_does_not_exist(): void
    {
        $this->assertFalse(
            Schema::hasTable('divisions'),
            'The divisions table should have been dropped by the cleanup migration.'
        );
    }

    public function test_users_has_no_division_id_column(): void
    {
        $this->assertFalse(
            Schema::hasColumn('users', 'division_id'),
            'users.division_id should have been removed.'
        );
    }

    public function test_kpi_indicators_has_no_role_id_column(): void
    {
        $this->assertFalse(
            Schema::hasColumn('kpi_indicators', 'role_id'),
            'kpi_indicators.role_id should have been removed.'
        );
    }

    public function test_kpi_scores_has_no_role_id_column(): void
    {
        $this->assertFalse(
            Schema::hasColumn('kpi_scores', 'role_id'),
            'kpi_scores.role_id should have been removed.'
        );
    }

    // ──────────────────────────────────────────────────────────────
    // 2. IT department (ITD) KPI indicators work end-to-end
    // ──────────────────────────────────────────────────────────────

    public function test_it_department_kpi_input_stores_and_calculates_correctly(): void
    {
        $hr   = User::factory()->create(['role' => 'hr_manager']);
        $dept = Department::factory()->create(['kode' => 'ITD', 'nama' => 'Information Technology']);

        $employee = User::factory()->create([
            'role'          => 'employee',
            'department_id' => $dept->id,
        ]);

        // Zero-penalty indicator (mimics "Zero Security Breach")
        $indicator = KpiIndicator::factory()->create([
            'name'          => 'Zero Security Breach',
            'weight'        => 25,
            'department_id' => $dept->id,
            'formula'       => ['type' => 'zero_penalty'],
        ]);

        Sanctum::actingAs($hr);

        // actual = 0 breaches → full score (zero_penalty: actual 0 → weight)
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

        $this->assertEquals(25, $response->json('data.breakdown.0.score'));
    }

    public function test_it_department_percentage_kpi_calculates_correctly(): void
    {
        $hr   = User::factory()->create(['role' => 'hr_manager']);
        $dept = Department::factory()->create(['kode' => 'ITD', 'nama' => 'Information Technology']);

        $employee = User::factory()->create([
            'role'          => 'employee',
            'department_id' => $dept->id,
        ]);

        // Percentage indicator (mimics "System Uptime")
        $indicator = KpiIndicator::factory()->create([
            'name'          => 'System Uptime',
            'weight'        => 40,
            'department_id' => $dept->id,
            'formula'       => ['type' => 'percentage'],
        ]);

        Sanctum::actingAs($hr);

        // 99/100 * 40 = 39.6
        $response = $this->postJson('/api/kpi/input', [
            'user_id'      => $employee->id,
            'indicator_id' => $indicator->id,
            'target_value' => 100,
            'actual_value' => 99,
            'period_type'  => 'monthly',
            'period'       => '2026-04-01',
        ]);

        $response->assertCreated()
                 ->assertJsonPath('success', true);

        $this->assertEquals(39.6, $response->json('data.breakdown.0.score'));
    }

    // ──────────────────────────────────────────────────────────────
    // 3. Spatie roles: assign and verify
    // ──────────────────────────────────────────────────────────────

    public function test_spatie_role_can_be_assigned_to_user(): void
    {
        $role = Role::firstOrCreate(['name' => 'IT', 'guard_name' => 'web']);
        $user = User::factory()->create(['role' => 'employee']);

        $user->assignRole($role);

        $this->assertTrue($user->hasRole('IT'));
        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => User::class,
            'model_id'   => $user->id,
            'role_id'    => $role->id,
        ]);
    }

    public function test_all_twelve_spatie_roles_can_be_seeded(): void
    {
        $roles = [
            'Direktur Utama', 'Direktur', 'Marketing & Sales', 'Digital Marketing',
            'Finance', 'Accounting', 'HR & GA Manager', 'Admin GA',
            'Driver', 'Office Boy', 'R&D Staff', 'IT',
        ];

        foreach ($roles as $name) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        foreach ($roles as $name) {
            $this->assertDatabaseHas('roles', ['name' => $name, 'guard_name' => 'web']);
        }

        $this->assertCount(12, Role::all());
    }

    public function test_user_spatie_roles_relationship_returns_assigned_role_names(): void
    {
        $role    = Role::firstOrCreate(['name' => 'IT', 'guard_name' => 'web']);
        $itStaff = User::factory()->create(['role' => 'employee']);
        $itStaff->assignRole($role);

        // Reload with Spatie roles relation to verify eager-load path
        $itStaff->load('roles');

        $roleNames = $itStaff->roles->pluck('name');

        $this->assertContains('IT', $roleNames);
        $this->assertCount(1, $roleNames);
    }

    // ──────────────────────────────────────────────────────────────
    // 4. Six departments exist and none reference a division
    // ──────────────────────────────────────────────────────────────

    public function test_six_departments_can_be_seeded_without_division(): void
    {
        $depts = [
            ['kode' => 'BOD', 'nama' => 'Board of Director'],
            ['kode' => 'FNA', 'nama' => 'Finance & Accounting'],
            ['kode' => 'HGA', 'nama' => 'HR & GA'],
            ['kode' => 'BDV', 'nama' => 'Business Development'],
            ['kode' => 'RND', 'nama' => 'Research & Development'],
            ['kode' => 'ITD', 'nama' => 'Information Technology'],
        ];

        foreach ($depts as $data) {
            Department::factory()->create($data);
        }

        $this->assertDatabaseCount('departments', 6);

        foreach ($depts as $data) {
            $this->assertDatabaseHas('departments', ['kode' => $data['kode']]);
        }
    }
}
