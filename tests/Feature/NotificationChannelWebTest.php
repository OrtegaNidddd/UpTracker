<?php

namespace Tests\Feature;

use App\Mail\ServiceAlertMail;
use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationChannelWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_notification_channels(): void
    {
        $user = User::factory()->create();

        NotificationChannel::create([
            'user_id' => $user->id,
            'type' => 'Email',
            'target_destination' => 'admin@empresa.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('notification-channels.index'));

        $response->assertStatus(200);
        $response->assertSee('admin@empresa.com');
        $response->assertSee('Canales de Alerta');
    }

    public function test_user_can_create_email_notification_channel(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('notification-channels.store'), [
            'type' => 'Email',
            'target_destination' => 'soc@empresa.com',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('notification-channels.index'));
        $this->assertDatabaseHas('notification_channels', [
            'user_id' => $user->id,
            'type' => 'Email',
            'target_destination' => 'soc@empresa.com',
            'is_active' => true,
        ]);
    }

    public function test_user_can_create_discord_webhook_channel(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('notification-channels.store'), [
            'type' => 'Webhook_Discord',
            'target_destination' => 'https://discord.com/api/webhooks/12345/abcdef',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('notification-channels.index'));
        $this->assertDatabaseHas('notification_channels', [
            'user_id' => $user->id,
            'type' => 'Webhook_Discord',
            'target_destination' => 'https://discord.com/api/webhooks/12345/abcdef',
        ]);
    }

    public function test_user_can_send_live_test_to_channel(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $channel = NotificationChannel::create([
            'user_id' => $user->id,
            'type' => 'Email',
            'target_destination' => 'alerts@empresa.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('notification-channels.test', $channel));

        $response->assertRedirect(route('notification-channels.index'));
        $response->assertSessionHas('success');

        Mail::assertSent(ServiceAlertMail::class, function ($mail) {
            return $mail->hasTo('alerts@empresa.com');
        });
    }

    public function test_user_can_delete_notification_channel(): void
    {
        $user = User::factory()->create();

        $channel = NotificationChannel::create([
            'user_id' => $user->id,
            'type' => 'Webhook_Telegram',
            'target_destination' => 'https://api.telegram.org/bot123/sendMessage',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->delete(route('notification-channels.destroy', $channel));

        $response->assertRedirect(route('notification-channels.index'));
        $this->assertDatabaseMissing('notification_channels', [
            'id' => $channel->id,
        ]);
    }
}
