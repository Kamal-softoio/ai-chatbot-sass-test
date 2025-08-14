<?php

namespace Database\Factories;

use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Conversation>
 */
class ConversationFactory extends Factory
{
    protected $model = Conversation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'chatbot_id' => Chatbot::factory(),
            'session_id' => 'session-' . fake()->uuid,
            'user_identifier' => fake()->optional()->ipv4,
            'is_active' => fake()->boolean(70), // 70% احتمال أن تكون نشطة
            'last_activity' => fake()->dateTimeBetween('-1 week', 'now'),
            'metadata' => [
                'browser' => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                'os' => fake()->randomElement(['Windows', 'macOS', 'Linux', 'iOS', 'Android']),
                'device_type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
                'referrer' => fake()->optional()->url,
                'utm_source' => fake()->optional()->word,
                'utm_medium' => fake()->optional()->word,
                'utm_campaign' => fake()->optional()->word,
                'user_agent' => fake()->userAgent,
            ],
        ];
    }

    /**
     * Indicate that the conversation should be active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'last_activity' => now()->subMinutes(rand(1, 30)),
        ]);
    }

    /**
     * Indicate that the conversation should be ended.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'last_activity' => fake()->dateTimeBetween('-3 days', 'now'),
        ]);
    }

    /**
     * Indicate that the conversation should have many messages.
     */
    public function withManyMessages(): static
    {
        return $this->state(fn (array $attributes) => [
            // This is just a state marker - actual messages would be created separately
        ]);
    }

    /**
     * Indicate that the conversation should be from mobile device.
     */
    public function mobile(): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'device_type' => 'mobile',
                'os' => fake()->randomElement(['iOS', 'Android']),
                'user_agent' => fake()->randomElement([
                    'Mozilla/5.0 (iPhone; CPU iPhone OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15',
                    'Mozilla/5.0 (Linux; Android 10; SM-G975F) AppleWebKit/537.36',
                    'Mozilla/5.0 (iPad; CPU OS 14_7_1 like Mac OS X) AppleWebKit/605.1.15',
                ]),
            ]),
        ]);
    }

    /**
     * Indicate that the conversation should be recent.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_activity' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }
}
