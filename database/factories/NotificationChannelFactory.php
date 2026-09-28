<?php

namespace Database\Factories;

use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationChannel>
 */
class NotificationChannelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'Email',
            'target_destination' => fake()->safeEmail(),
            'is_active' => true,
        ];
    }

    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Email',
            'target_destination' => fake()->safeEmail(),
        ]);
    }

    public function discord(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Webhook_Discord',
            'target_destination' => 'https://discord.com/api/webhooks/'.fake()->numerify('##########/####################'),
        ]);
    }

    public function telegram(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Webhook_Telegram',
            'target_destination' => 'https://api.telegram.org/bot'.fake()->regexify('[0-9]{9}:[a-zA-Z0-9_-]{35}').'/sendMessage',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
