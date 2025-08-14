<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MockOllamaService extends OllamaService
{
    /**
     * محاكي لتوليد الردود للاختبار
     */
    public function generateResponse(string $model, array $data, array $options = []): array
    {
        // محاكاة التأخير الطبيعي
        sleep(rand(1, 3));

        Log::info('Mock Ollama Response', [
            'model' => $model,
            'messages' => $data['messages'] ?? [],
            'options' => $options,
        ]);

        // الحصول على آخر رسالة من المستخدم
        $messages = $data['messages'] ?? [];
        $lastMessage = end($messages);
        $userInput = $lastMessage['content'] ?? 'مرحبا';

        // ردود ذكية بناءً على الرسالة
        $responses = $this->generateSmartResponse($userInput);
        $selectedResponse = $responses[array_rand($responses)];

        return [
            'model' => $model,
            'created_at' => now()->toISOString(),
            'message' => [
                'role' => 'assistant',
                'content' => $selectedResponse,
            ],
            'done' => true,
            'total_duration' => rand(1000000000, 3000000000), // nanoseconds
            'load_duration' => rand(100000000, 500000000),
            'prompt_eval_count' => rand(20, 50),
            'prompt_eval_duration' => rand(500000000, 1000000000),
            'eval_count' => rand(50, 200),
            'eval_duration' => rand(1000000000, 2000000000),
        ];
    }

    /**
     * توليد ردود ذكية بناءً على رسالة المستخدم
     */
    private function generateSmartResponse(string $input): array
    {
        $input = strtolower(trim($input));

        // ردود الترحيب
        if (preg_match('/(أهلا|مرحبا|هلا|السلام|صباح|مساء)/', $input)) {
            return [
                'أهلاً وسهلاً بك! 😊 كيف يمكنني مساعدتك اليوم؟',
                'مرحباً بك! أنا هنا لمساعدتك. ما الذي تحتاج إليه؟',
                'السلام عليكم ورحمة الله وبركاته! يسعدني أن أكون في خدمتك',
                'أهلاً بك عزيزي! كيف حالك اليوم؟ وكيف يمكنني أن أساعدك؟',
            ];
        }

        // أسئلة حول الخدمات
        if (preg_match('/(خدما|منتج|سعر|تكلفة|شراء)/', $input)) {
            return [
                'نحن نقدم خدمات متنوعة في الذكاء الاصطناعي والشات بوت. هل تريد معرفة المزيد عن خدمة معينة؟',
                'لدينا باقات مختلفة تناسب جميع الاحتياجات. هل تريد أن أوضح لك الأسعار والميزات؟',
                'منتجاتنا تشمل حلول الذكاء الاصطناعي المخصصة للشركات. ما نوع العمل الذي تقوم به؟',
            ];
        }

        // أسئلة تقنية
        if (preg_match('/(تقني|برمج|كود|تطوير|websocket|laravel)/', $input)) {
            return [
                'أنا مطور بتقنيات حديثة مثل Laravel 12 و WebSocket! هل تريد معرفة المزيد عن التقنيات المستخدمة؟',
                'هذا الشات بوت مطور باستخدام Laravel Reverb للاتصال المباشر و Ollama للذكاء الاصطناعي. رائع، أليس كذلك؟',
                'التطوير بـ WebSocket يعطي تجربة فورية ممتازة! هل لديك مشروع تقني تريد المساعدة فيه؟',
            ];
        }

        // أسئلة عن المساعدة
        if (preg_match('/(مساعد|ساعد|يمكنك|قادر|تستطيع)/', $input)) {
            return [
                'يمكنني مساعدتك في الكثير من الأمور! أجيب على الأسئلة، أقدم المعلومات، وأساعد في حل المشاكل. ما الذي تحتاجه؟',
                'أنا مساعد ذكي قادر على فهم اللغة العربية والإنجليزية. أساعد في الاستشارات، المعلومات، والدعم التقني. كيف يمكنني خدمتك؟',
                'لدي قدرات متنوعة في المحادثة والمساعدة. هل تريد أن نتحدث عن موضوع معين أم لديك سؤال محدد؟',
            ];
        }

        // أسئلة شخصية
        if (preg_match('/(أنت|اسمك|من|كيف حالك)/', $input)) {
            return [
                'أنا أنيس، مساعدك الذكي! 🤖 أعمل بتقنية الذكاء الاصطناعي لمساعدتك في كل ما تحتاجه.',
                'اسمي أنيس وأنا بخير، شكراً لسؤالك! أنا هنا لأكون رفيقك في هذه المحادثة. كيف حالك أنت؟',
                'أنا مساعد ذكي تم تطويري خصيصاً لتقديم أفضل تجربة محادثة. كيف يمكنني أن أجعل يومك أفضل؟',
            ];
        }

        // أسئلة عن الوقت أو التاريخ
        if (preg_match('/(وقت|ساعة|تاريخ|يوم)/', $input)) {
            $currentTime = now()->format('Y-m-d H:i:s');
            $dayName = now()->locale('ar')->dayName;

            return [
                "الوقت الآن هو {$currentTime} - يوم {$dayName}. هل تحتاج مساعدة في شيء آخر؟",
                "اليوم هو {$dayName} والوقت {$currentTime}. كيف يمكنني مساعدتك؟",
                "نحن في يوم {$dayName} والساعة الآن ".now()->format('H:i').'. ما الذي يمكنني فعله من أجلك؟',
            ];
        }

        // ردود عامة للرسائل الأخرى
        return [
            'شكراً لك على رسالتك! أنا هنا لمساعدتك. هل يمكنك توضيح أكثر عن ما تحتاجه؟',
            'هذا سؤال جيد! دعني أفكر... 🤔 هل يمكنك إعطائي المزيد من التفاصيل؟',
            'أقدر تفاعلك معي! للحصول على أفضل مساعدة، حاول أن تكون أكثر تحديداً في سؤالك.',
            'رسالة مثيرة للاهتمام! أنا أتعلم باستمرار لأقدم لك أفضل إجابة. ما رأيك لو أعدت صياغة السؤال؟',
            'أنا سعيد بالحديث معك! إذا كان لديك سؤال محدد أو تحتاج مساعدة في شيء معين، فقط أخبرني.',
            'ممتاز! أحب التحديات. هل لديك المزيد من التفاصيل حول ما تبحث عنه؟',
        ];
    }

    /**
     * محاكي للتحقق من صحة الاتصال
     */
    public function isHealthy(): bool
    {
        return true; // المحاكي دائماً متاح
    }

    /**
     * محاكي لقائمة النماذج
     */
    public function getAvailableModels(): array
    {
        return [
            [
                'name' => 'qwen2.5-coder:latest',
                'modified_at' => now()->toISOString(),
                'size' => 4661838465,
                'digest' => 'mock-digest-123',
                'details' => [
                    'format' => 'gguf',
                    'family' => 'qwen2',
                    'families' => ['qwen2'],
                    'parameter_size' => '7.6B',
                    'quantization_level' => 'Q4_K_M',
                ],
            ],
            [
                'name' => 'llama3.1:latest',
                'modified_at' => now()->subHours(2)->toISOString(),
                'size' => 5630000000,
                'digest' => 'mock-digest-456',
                'details' => [
                    'format' => 'gguf',
                    'family' => 'llama',
                    'parameter_size' => '8B',
                    'quantization_level' => 'Q4_K_M',
                ],
            ],
        ];
    }
}
