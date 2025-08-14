<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Message>
 */
class MessageFactory extends Factory
{
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $role = fake()->randomElement(['user', 'assistant']);
        
        return [
            'conversation_id' => Conversation::factory(),
            'content' => $this->generateContentByRole($role),
            'role' => $role,
            'metadata' => $this->generateMetadataByRole($role),
            'tokens_used' => $role === 'assistant' ? fake()->numberBetween(20, 200) : null,
            'processing_time' => $role === 'assistant' ? fake()->randomFloat(3, 0.5, 5.0) : null,
        ];
    }

    /**
     * Generate content based on message role.
     */
    private function generateContentByRole(string $role): string
    {
        if ($role === 'user') {
            return fake()->randomElement([
                'مرحباً، كيف حالك؟',
                'أريد معلومات عن منتجاتكم',
                'هل يمكنك مساعدتي في حل هذه المشكلة؟',
                'ما هي أوقات العمل لديكم؟',
                'كيف يمكنني التواصل مع الدعم التقني؟',
                'أحتاج إلى معرفة الأسعار',
                'هل لديكم خدمة توصيل؟',
                'ما هي طرق الدفع المتاحة؟',
                'شكراً لك على المساعدة',
                'هل يمكنك توضيح هذا النقطة أكثر؟',
            ]);
        }

        return fake()->randomElement([
            'أهلاً وسهلاً بك! يسعدني أن أساعدك اليوم.',
            'بالطبع! يمكنني تقديم معلومات شاملة عن منتجاتنا.',
            'أفهم مشكلتك تماماً. دعني أقدم لك الحل المناسب.',
            'أوقات العمل لدينا من الأحد إلى الخميس من 9 صباحاً حتى 6 مساءً.',
            'يمكنك التواصل مع الدعم التقني عبر الهاتف أو البريد الإلكتروني.',
            'سأقدم لك جدولاً مفصلاً بالأسعار الحالية.',
            'نعم، لدينا خدمة توصيل سريعة تغطي جميع المناطق.',
            'نقبل جميع طرق الدفع: نقدي، فيزا، ماستركارد، والدفع الإلكتروني.',
            'على الرحب والسعة! أنا هنا دائماً للمساعدة.',
            'بالتأكيد! دعني أوضح لك هذه النقطة بالتفصيل.',
        ]);
    }

    /**
     * Generate metadata based on message role.
     */
    private function generateMetadataByRole(string $role): array
    {
        if ($role === 'user') {
            return [
                'timestamp' => now()->toISOString(),
                'source' => 'web_widget',
                'device_info' => [
                    'type' => fake()->randomElement(['desktop', 'mobile', 'tablet']),
                    'browser' => fake()->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                ],
            ];
        }

        return [
            'model' => fake()->randomElement([
                'qwen2.5-coder:latest',
                'llama3.1:latest',
                'mistral:latest',
            ]),
            'tokens_used' => fake()->numberBetween(20, 200),
            'processing_time' => fake()->randomFloat(3, 0.5, 5.0),
            'eval_duration' => fake()->numberBetween(1000000000, 5000000000),
            'load_duration' => fake()->numberBetween(100000000, 1000000000),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Indicate that the message should be from a user.
     */
    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'user',
            'content' => fake()->randomElement([
                'مرحباً',
                'أحتاج مساعدة',
                'ما هي خدماتكم؟',
                'كم السعر؟',
                'شكراً لك',
            ]),
            'metadata' => [
                'timestamp' => now()->toISOString(),
                'source' => 'web_widget',
            ],
            'tokens_used' => null,
            'processing_time' => null,
        ]);
    }

    /**
     * Indicate that the message should be from an assistant.
     */
    public function assistant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'assistant',
            'content' => fake()->randomElement([
                'مرحباً بك! كيف يمكنني مساعدتك؟',
                'سأكون سعيداً لمساعدتك.',
                'لدينا خدمات متنوعة، أيها يهمك؟',
                'أسعارنا تنافسية جداً.',
                'على الرحب والسعة!',
            ]),
            'metadata' => [
                'model' => 'qwen2.5-coder:latest',
                'tokens_used' => fake()->numberBetween(20, 100),
                'processing_time' => fake()->randomFloat(3, 0.5, 3.0),
                'timestamp' => now()->toISOString(),
            ],
            'tokens_used' => fake()->numberBetween(20, 100),
            'processing_time' => fake()->randomFloat(3, 0.5, 3.0),
        ]);
    }

    /**
     * Indicate that the message should have high rating.
     */
    public function highRated(): static
    {
        return $this->state(fn (array $attributes) => [
            // Note: rating and feedback columns don't exist in the database
            // This is just a state marker for testing purposes
        ]);
    }

    /**
     * Indicate that the message should be recent.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'updated_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }
}
