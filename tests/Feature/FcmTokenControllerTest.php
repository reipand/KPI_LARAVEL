<?php

namespace Tests\Feature;

use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FcmTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_register_fcm_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/fcm/token', [
            'token' => 'test-fcm-token-abc123',
            'device_type' => 'web',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('fcm_tokens', [
            'user_id' => $user->id,
            'token' => 'test-fcm-token-abc123',
        ]);
    }

    public function test_registering_same_token_twice_does_not_duplicate(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/fcm/token', ['token' => 'dup-token', 'device_type' => 'web']);
        $this->postJson('/api/fcm/token', ['token' => 'dup-token', 'device_type' => 'web']);

        $this->assertDatabaseCount('fcm_tokens', 1);
    }

    public function test_user_can_delete_their_fcm_token(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        FcmToken::create(['user_id' => $user->id, 'token' => 'del-token', 'device_type' => 'web']);

        $response = $this->deleteJson('/api/fcm/token', ['token' => 'del-token']);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('fcm_tokens', ['token' => 'del-token']);
    }
}
