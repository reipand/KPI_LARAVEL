<?php

namespace Tests\Feature;

use App\Models\KpiIndicator;
use App\Models\KpiReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KpiReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pegawai_can_create_submitted_report_and_hr_can_find_it(): void
    {
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $hr = User::factory()->create([
            'role' => 'hr_manager',
        ]);

        $indicator = KpiIndicator::query()->create([
            'name' => 'Capai target bulanan',
            'description' => 'Monitoring harian',
            'weight' => 1,
            'default_target_value' => 100,
            'formula' => ['type' => 'percentage'],
        ]);

        Sanctum::actingAs($pegawai);

        $response = $this->postJson('/api/kpi-reports', [
            'kpi_indicator_id' => $indicator->id,
            'period_type' => 'monthly',
            'tanggal' => '2026-03-15',
            'period_label' => 'Maret 2026',
            'nilai_aktual' => 90,
            'catatan' => 'Laporan sudah dikirim.',
            'status' => 'submitted',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', 'submitted');

        $this->assertDatabaseHas('kpi_reports', [
            'user_id' => $pegawai->id,
            'kpi_indicator_id' => $indicator->id,
            'status' => 'submitted',
        ]);

        $reportId = $response->json('data.id');
        $this->assertNotNull(KpiReport::query()->find($reportId)?->submitted_at);

        Sanctum::actingAs($hr);

        $indexResponse = $this->getJson('/api/kpi-reports?status=submitted');

        $indexResponse
            ->assertOk()
            ->assertJsonFragment([
                'id' => $reportId,
                'status' => 'submitted',
            ]);
    }

    public function test_pegawai_can_submit_existing_draft_report(): void
    {
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $indicator = KpiIndicator::query()->create([
            'name' => 'Capai target mingguan',
            'description' => 'Koordinasi rutin',
            'weight' => 1,
            'default_target_value' => 50,
            'formula' => ['type' => 'percentage'],
        ]);

        $report = KpiReport::query()->create([
            'user_id' => $pegawai->id,
            'kpi_indicator_id' => $indicator->id,
            'period_type' => 'weekly',
            'tanggal' => '2026-04-10',
            'period_label' => 'Minggu 2 April 2026',
            'nilai_target' => 50,
            'nilai_aktual' => 40,
            'persentase' => 80,
            'score_label' => 'good',
            'catatan' => 'Masih draft.',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($pegawai);

        $response = $this->putJson("/api/kpi-reports/{$report->id}", [
            'kpi_indicator_id' => $indicator->id,
            'period_type' => 'weekly',
            'tanggal' => '2026-04-10',
            'period_label' => 'Minggu 2 April 2026',
            'nilai_target' => 50,
            'nilai_aktual' => 40,
            'catatan' => 'Draft sudah dikirim.',
            'status' => 'submitted',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', 'submitted');

        $report->refresh();

        $this->assertSame('submitted', $report->status);
        $this->assertNotNull($report->submitted_at);
        $this->assertSame($pegawai->id, $report->submitted_by);
    }
}
