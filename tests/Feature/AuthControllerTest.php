<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'nip' => 'EMP-001',
            'nama' => 'Budi Santoso',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'nip' => $user->nip,
            'nama' => $user->nama,
            'device_name' => 'PHPUnit',
        ]);

        $response
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'message' => 'Akun Anda sedang nonaktif. Hubungi HR untuk aktivasi kembali.',
            ]);

        $this->assertCount(0, $user->fresh()->tokens);
    }
}
