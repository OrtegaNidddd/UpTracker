<?php

namespace Tests\Feature;

use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationChannelWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_notification_channels(): void
    {
        $response = $this->get('/notification-channels');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_notification_channels_index(): void
    {
        $user = User::factory()->create();
        NotificationChannel::factory()->create([
            'user_id' => $user->id,
            'type' => 'Email',
            'target_destination' => 'admin@example.com',
        ]);

        $response = $this->actingAs($user)->get('/notification-channels');

        $response->assertStatus(200);
        $response->assertSee('Canales de Alerta y Notificación');
        $response->assertSee('admin@example.com');
    }

    public function test_authenticated_user_can_create_email_notification_channel(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/notification-channels', [
            'type' => 'Email',
            'target_destination' => 'devops@example.com',
            'is_active' => true,
        ]);

        $response->assertRedirect('/notification-channels');
        $this->assertDatabaseHas('notification_channels', [
            'user_id' => $user->id,
            'type' => 'Email',
            'target_destination' => 'devops@example.com',
            'is_active' => true,
        ]);
    }

    public function test_authenticated_user_can_create_discord_webhook_channel(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/notification-channels', [
            'type' => 'Webhook_Discord',
            'target_destination' => 'https://discord.com/api/webhooks/123/token',
            'is_active' => true,
        ]);

        $response->assertRedirect('/notification-channels');
        $this->assertDatabaseHas('notification_channels', [
            'user_id' => $user->id,
            'type' => 'Webhook_Discord',
            'target_destination' => 'https://discord.com/api/webhooks/123/token',
        ]);
    }

    public function test_authenticated_user_can_toggle_channel_status(): void
    {
        $user = User::factory()->create();
        $channel = NotificationChannel::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->patch("/notification-channels/{$channel->id}/toggle");

        $response->assertRedirect('/notification-channels');
        $this->assertDatabaseHas('notification_channels', [
            'id' => $channel->id,
            'is_active' => false,
        ]);
    }

    public function test_authenticated_user_can_delete_notification_channel(): void
    {
        $user = User::factory()->create();
        $channel = NotificationChannel::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete("/notification-channels/{$channel->id}");

        $response->assertRedirect('/notification-channels');
        $this->assertDatabaseMissing('notification_channels', ['id' => $channel->id]);
    }

    public function test_user_cannot_modify_or_delete_another_users_channel(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $channel = NotificationChannel::factory()->create(['user_id' => $user1->id]);

        $responseToggle = $this->actingAs($user2)->patch("/notification-channels/{$channel->id}/toggle");
        $responseToggle->assertStatus(403);

        $responseDelete = $this->actingAs($user2)->delete("/notification-channels/{$channel->id}");
        $responseDelete->assertStatus(403);
    }
}
