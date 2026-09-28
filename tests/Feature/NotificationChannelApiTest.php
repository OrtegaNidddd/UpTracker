<?php

namespace Tests\Feature;

use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationChannelApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/notification-channels');
        $response->assertStatus(401);
    }

    public function test_user_can_list_only_their_own_notification_channels(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $ch1 = NotificationChannel::factory()->create([
            'user_id' => $user1->id,
            'type' => 'Email',
            'target_destination' => 'alerts@user1.com',
        ]);
        $ch2 = NotificationChannel::factory()->create([
            'user_id' => $user2->id,
            'type' => 'Webhook_Discord',
            'target_destination' => 'https://discord.com/api/webhooks/123/abc',
        ]);

        Sanctum::actingAs($user1);

        $response = $this->getJson('/api/notification-channels');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['target_destination' => 'alerts@user1.com'])
            ->assertJsonMissing(['target_destination' => 'https://discord.com/api/webhooks/123/abc']);
    }

    public function test_user_can_create_email_and_webhook_channels_with_validation(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Invalid email
        $invalidEmail = $this->postJson('/api/notification-channels', [
            'type' => 'Email',
            'target_destination' => 'not-an-email',
        ]);
        $invalidEmail->assertStatus(422)
            ->assertJsonValidationErrors(['target_destination']);

        // Invalid webhook url
        $invalidWebhook = $this->postJson('/api/notification-channels', [
            'type' => 'Webhook_Discord',
            'target_destination' => 'ftp://bad-url',
        ]);
        $invalidWebhook->assertStatus(422)
            ->assertJsonValidationErrors(['target_destination']);

        // Valid Email
        $validEmail = $this->postJson('/api/notification-channels', [
            'type' => 'Email',
            'target_destination' => 'ops@example.com',
        ]);
        $validEmail->assertStatus(201)
            ->assertJsonPath('data.type', 'Email')
            ->assertJsonPath('data.target_destination', 'ops@example.com');

        // Valid Webhook Discord
        $validDiscord = $this->postJson('/api/notification-channels', [
            'type' => 'Webhook_Discord',
            'target_destination' => 'https://discord.com/api/webhooks/999/xyz',
        ]);
        $validDiscord->assertStatus(201)
            ->assertJsonPath('data.type', 'Webhook_Discord');

        // Valid Webhook Telegram
        $validTelegram = $this->postJson('/api/notification-channels', [
            'type' => 'Webhook_Telegram',
            'target_destination' => 'https://api.telegram.org/bot123/sendMessage',
        ]);
        $validTelegram->assertStatus(201)
            ->assertJsonPath('data.type', 'Webhook_Telegram');
    }

    public function test_user_cannot_access_or_modify_other_users_channel(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $channel2 = NotificationChannel::factory()->create(['user_id' => $user2->id]);

        Sanctum::actingAs($user1);

        $this->getJson("/api/notification-channels/{$channel2->id}")->assertStatus(403);
        $this->putJson("/api/notification-channels/{$channel2->id}", ['is_active' => false])->assertStatus(403);
        $this->deleteJson("/api/notification-channels/{$channel2->id}")->assertStatus(403);
    }

    public function test_user_can_update_and_delete_notification_channel(): void
    {
        $user = User::factory()->create();
        $channel = NotificationChannel::factory()->create([
            'user_id' => $user->id,
            'type' => 'Email',
            'target_destination' => 'old@example.com',
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $updateResponse = $this->putJson("/api/notification-channels/{$channel->id}", [
            'target_destination' => 'new@example.com',
            'is_active' => false,
        ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.target_destination', 'new@example.com')
            ->assertJsonPath('data.is_active', false);

        $deleteResponse = $this->deleteJson("/api/notification-channels/{$channel->id}");
        $deleteResponse->assertStatus(200);

        $this->assertDatabaseMissing('notification_channels', ['id' => $channel->id]);
    }
}
