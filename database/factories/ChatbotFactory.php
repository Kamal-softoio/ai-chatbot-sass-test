<?php

namespace Database\Factories;

use App\Models\Chatbot;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Chatbot>
 */
class ChatbotFactory extends Factory
{
    protected $model = Chatbot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->randomElement([
                'مساعد الدعم الفني',
                'روبوت المبيعات',
                'مستشار العملاء',
                'مساعد المعلومات',
                'خبير المنتجات',
            ]),
            'description' => fake()->randomElement([
                'مساعد ذكي لخدمة العملاء والدعم التقني',
                'روبوت متخصص في المبيعات والاستشارات',
                'مساعد ذكي يقدم المعلومات والإرشادات',
                'خبير في المنتجات والخدمات',
                'مستشار تقني متطور',
            ]),
            'model_name' => fake()->randomElement([
                'qwen2.5-coder:latest',
                'llama3.1:latest',
                'mistral:latest',
                'phi3:latest',
            ]),
            'system_prompt' => fake()->randomElement([
                'أنت مساعد ذكي مفيد ومهذب. تجيب على الأسئلة بوضوح وبطريقة مفهومة.',
                'أنت خبير في خدمة العملاء. تساعد العملاء بحل مشاكلهم بطريقة فعالة ومهنية.',
                'أنت مستشار مبيعات ماهر. تساعد العملاء في اتخاذ قرارات الشراء المناسبة.',
                'أنت مساعد تقني متخصص. تقدم الدعم التقني والحلول للمشاكل التقنية.',
            ]),
            'is_public' => fake()->boolean(20), // 20% احتمال أن يكون عاماً
            'widget_id' => 'widget_' . fake()->lexify('????????????????'),
            'settings' => [
                'temperature' => fake()->randomFloat(2, 0.1, 1.5),
                'max_tokens' => fake()->randomElement([1000, 2000, 4000]),
                'top_p' => fake()->randomFloat(2, 0.5, 1.0),
                'frequency_penalty' => fake()->randomFloat(2, 0, 1),
                'presence_penalty' => fake()->randomFloat(2, 0, 1),
            ],
            'status' => fake()->randomElement(['active', 'inactive']),
            'total_conversations' => fake()->numberBetween(0, 1000),
            'total_messages' => fake()->numberBetween(0, 5000),
            'last_activity' => fake()->optional(0.8)->dateTimeBetween('-1 week', 'now'),
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'updated_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the chatbot should be inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the chatbot should have high performance settings.
     */
    public function highPerformance(): static
    {
        return $this->state(fn (array $attributes) => [
            'model_name' => 'qwen2.5-coder:latest',
            'settings' => [
                'temperature' => 0.7,
                'max_tokens' => 4000,
                'top_p' => 0.9,
                'frequency_penalty' => 0.1,
                'presence_penalty' => 0.1,
            ],
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the chatbot should have Arabic-focused settings.
     */
    public function arabicFocused(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->randomElement([
                'المساعد العربي الذكي',
                'روبوت الدعم العربي',
                'المستشار العربي',
            ]),
            'description' => 'مساعد ذكي متخصص في اللغة العربية',
            'system_prompt' => 'أنت مساعد ذكي يتحدث العربية بطلاقة. تجيب على جميع الأسئلة باللغة العربية الفصحى وبطريقة مهذبة ومفيدة.',
        ]);
    }
}
