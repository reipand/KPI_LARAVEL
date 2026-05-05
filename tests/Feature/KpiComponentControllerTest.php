<?php

namespace Tests\Feature;

use App\Models\KpiComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KpiComponentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_kpi_components(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        KpiComponent::query()->create([
            'jabatan' => 'Staff IT',
            'objectives' => 'Ketepatan Penyelesaian Ticket',
            'strategy' => 'Pantau SLA harian',
            'bobot' => 25,
            'target' => 100,
            'satuan' => '%',
            'tipe' => 'achievement',
            'kpi_type' => 'percentage',
            'period' => 'monthly',
            'catatan' => 'Komponen inti tim IT',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/kpi-components');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.jabatan', 'Staff IT')
            ->assertJsonPath('data.0.objectives', 'Ketepatan Penyelesaian Ticket');
    }
}
