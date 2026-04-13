<?php

namespace Tests\Feature;

use App\Models\KpiIndicator;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KpiManagementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_kpi_input_auto_calculates_user_score(): void
    {
        $actor = User::factory()->create(['role' => 'hr_manager']);
        $employeeRole = Role::query()->create([
            'name' => 'Sales',
            'slug' => 'sales',
        ]);

        $employee = User::factory()->create([
            'role' => 'pegawai',
            'role_id' => $employeeRole->id,
        ]);

        $indicator = KpiIndicator::query()->create([
            'name' => 'Closing Deal',
            'description' => 'Jumlah closing deal per bulan',
            'weight' => 40,
            'role_id' => $employeeRole->id,
        ]);

        Sanctum::actingAs($actor);

        $response = $this->postJson('/api/kpi/input', [
            'user_id' => $employee->id,
            'indicator_id' => $indicator->id,
            'target_value' => 20,
            'actual_value' => 10,
            'period_type' => 'monthly',
            'period' => '2026-04-10',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.normalized_score', 20)
            ->assertJsonPath('data.breakdown.0.score', 20)
            ->assertJsonPath('data.breakdown.0.achievement_ratio', 50);
    }

    public function test_dashboard_returns_summary_and_ranking(): void
    {
        $actor = User::factory()->create(['role' => 'direktur']);
        $role = Role::query()->create([
            'name' => 'Engineering',
            'slug' => 'engineering',
        ]);

        $topUser = User::factory()->create(['role' => 'pegawai', 'role_id' => $role->id]);
        $lowUser = User::factory()->create(['role' => 'pegawai', 'role_id' => $role->id]);

        $indicator = KpiIndicator::query()->create([
            'name' => 'Bug Fix',
            'description' => 'Penyelesaian bug',
            'weight' => 100,
            'role_id' => $role->id,
        ]);

        Sanctum::actingAs($actor);

        $this->postJson('/api/kpi/input', [
            'user_id' => $topUser->id,
            'indicator_id' => $indicator->id,
            'target_value' => 10,
            'actual_value' => 10,
            'period_type' => 'monthly',
            'period' => '2026-04-13',
        ])->assertCreated();

        $this->postJson('/api/kpi/input', [
            'user_id' => $lowUser->id,
            'indicator_id' => $indicator->id,
            'target_value' => 10,
            'actual_value' => 4,
            'period_type' => 'monthly',
            'period' => '2026-04-13',
        ])->assertCreated();

        $response = $this->getJson('/api/kpi/dashboard?period_type=monthly&period=2026-04-13&role_id=' . $role->id);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.average_kpi', 70)
            ->assertJsonPath('data.summary.top_performer.user.id', $topUser->id)
            ->assertJsonPath('data.summary.low_performer.user.id', $lowUser->id)
            ->assertJsonPath('data.ranking.0.rank', 1);
    }
}
