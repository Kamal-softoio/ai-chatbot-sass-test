<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\OllamaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessChatMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $conversation;
    protected $message;
    protected $sessionId;

    public function __construct(Conversation $conversation, string $message, string $sessionId)
    {
        $this->conversation = $conversation;
        $this->message = $message;
        $this->sessionId = $sessionId;
    }

    public function handle(OllamaService $ollamaService)
    {
        try {
            // تحديث حالة المحادثة
            Cache::put("conversation_status_{$this->sessionId}", 'processing', 300);
            
            // حفظ رسالة المستخدم
            $userMessage = Message::create([
                'conversation_id' => $this->conversation->id,
                'role' => 'user',
                'content' => $this->message,
            ]);

            // إعداد الرسائل للإرسال إلى Ollama
            $messages = $this->prepareMessages();

            $startTime = microtime(true);
            
            // إرسال الطلب إلى Ollama
            $response = $ollamaService->generateResponse(
                $this->conversation->chatbot->model_name,
                $messages,
                $this->conversation->chatbot->settings ?? []
            );

            $processingTime = microtime(true) - $startTime;

            if ($response && isset($response['message']['content'])) {
                // حفظ رد الروبوت
                $botMessage = Message::create([
                    'conversation_id' => $this->conversation->id,
                    'role' => 'assistant',
                    'content' => $response['message']['content'],
                    'tokens_used' => $response['eval_count'] ?? null,
                    'processing_time' => $processingTime,
                    'metadata' => [
                        'model' => $this->conversation->chatbot->model_name,
                        'eval_duration' => $response['eval_duration'] ?? null,
                        'load_duration' => $response['load_duration'] ?? null,
                    ],
                ]);

                // تحديث إحصائيات المحادثة والروبوت
                $this->updateStatistics();

                // زيادة عداد رسائل المستأجر
                $this->conversation->tenant->incrementMessageCounter();

                // حفظ الرد في الكاش
                Cache::put("chat_response_{$this->sessionId}", [
                    'message' => [
                        'content' => $response['message']['content']
                    ],
                    'processing_time' => $processingTime,
                    'tokens_used' => $response['eval_count'] ?? 0,
                ], 300);

                Cache::put("conversation_status_{$this->sessionId}", 'completed', 300);

                Log::info('تمت معالجة رسالة الدردشة بنجاح', [
                    'conversation_id' => $this->conversation->id,
                    'session_id' => $this->sessionId,
                    'processing_time' => $processingTime,
                ]);
            } else {
                throw new \Exception('لم يتم الحصول على رد صحيح من Ollama');
            }
        } catch (\Exception $e) {
            Log::error('خطأ في معالجة رسالة الدردشة', [
                'conversation_id' => $this->conversation->id,
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
            ]);

            Cache::put("conversation_status_{$this->sessionId}", 'failed', 300);
            Cache::put("chat_response_{$this->sessionId}", [
                'message' => [
                    'content' => 'عذراً، حدث خطأ في معالجة رسالتك. يرجى المحاولة مرة أخرى.'
                ]
            ], 300);
        }
    }

    /**
     * إعداد الرسائل للإرسال إلى Ollama
     */
    private function prepareMessages(): array
    {
        $messages = [];

        // إضافة System Prompt إذا كان متوفراً
        if ($this->conversation->chatbot->system_prompt) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->conversation->chatbot->system_prompt,
            ];
        }

        // الحصول على آخر 10 رسائل من المحادثة للسياق
        $recentMessages = $this->conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->reverse();

        foreach ($recentMessages as $message) {
            $messages[] = [
                'role' => $message->role,
                'content' => $message->content,
            ];
        }

        // إضافة الرسالة الحالية
        $messages[] = [
            'role' => 'user',
            'content' => $this->message,
        ];

        return $messages;
    }

    /**
     * تحديث الإحصائيات
     */
    private function updateStatistics(): void
    {
        // تحديث وقت آخر نشاط للمحادثة
        $this->conversation->update(['last_activity' => now()]);

        // تحديث إحصائيات الروبوت
        $this->conversation->chatbot->increment('total_messages', 2); // رسالة المستخدم + رد الروبوت
        $this->conversation->chatbot->update(['last_activity' => now()]);
    }
}