<?php

namespace Tests\Feature;

use App\Models\KpiComponent;
use App\Models\KpiReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KpiReportEvidenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_kpi_report_resource_returns_evidence_path_and_url(): void
    {
        $pegawai = User::factory()->create([
            'role' => 'pegawai',
        ]);

        $component = KpiComponent::query()->create([
            'jabatan' => $pegawai->jabatan,
            'objectives' => 'Upload evidence laporan',
            'strategy' => 'Dokumentasi rutin',
            'bobot' => 1,
            'target' => 100,
            'tipe' => 'achievement',
            'is_active' => true,
        ]);

        KpiReport::query()->create([
            'user_id' => $pegawai->id,
            'kpi_component_id' => $component->id,
            'period_type' => 'monthly',
            'tanggal' => '2026-04-14',
            'period_label' => 'April 2026',
            'nilai_target' => 100,
            'nilai_aktual' => 90,
            'persentase' => 90,
            'score_label' => 'good',
            'status' => 'submitted',
            'file_evidence' => 'kpi-evidence/2026/04/bukti.pdf',
        ]);

        Sanctum::actingAs($pegawai);

        $response = $this->getJson('/api/kpi-reports?tahun=2026&bulan=4');

        $response
            ->assertOk()
            ->assertJsonPath('data.items.0.file_evidence', 'kpi-evidence/2026/04/bukti.pdf')
            ->assertJsonPath('data.items.0.file_evidence_url', url('/storage/kpi-evidence/2026/04/bukti.pdf'));
    }
}
