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

    public function whatsapp(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'WhatsApp',
            'target_destination' => '+573'.fake()->numerify('#########'),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
