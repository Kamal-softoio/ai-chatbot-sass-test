<?php

namespace App\Jobs;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\OllamaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessChatMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Conversation $conversation;

    protected Message $userMessage;

    public function __construct(Conversation $conversation, Message $userMessage)
    {
        $this->conversation = $conversation;
        $this->userMessage = $userMessage;
    }

    public function handle(OllamaService $ollamaService)
    {
        try {
            // إعداد الرسائل للإرسال إلى Ollama
            $messages = $this->prepareMessages();

            $startTime = microtime(true);

            // إرسال الطلب إلى Ollama
            $response = $ollamaService->generateResponse(
                $this->conversation->chatbot->model_name ?? 'qwen2.5-coder:latest',
                $messages
            );

            $processingTime = microtime(true) - $startTime;

            if ($response && isset($response['message']['content'])) {
                // حفظ رد الروبوت
                $botMessage = Message::create([
                    'conversation_id' => $this->conversation->id,
                    'content' => $response['message']['content'],
                    'role' => 'assistant',
                    'metadata' => [
                        'model' => $this->conversation->chatbot->model_name ?? 'qwen2.5-coder:latest',
                        'tokens_used' => $response['eval_count'] ?? null,
                        'processing_time' => $processingTime,
                        'eval_duration' => $response['eval_duration'] ?? null,
                        'load_duration' => $response['load_duration'] ?? null,
                        'timestamp' => now()->toISOString(),
                    ],
                    'tokens_used' => $response['eval_count'] ?? null,
                    'processing_time' => $processingTime,
                ]);

                // تحديث إحصائيات المحادثة والروبوت
                $this->updateStatistics();

                // إرسال البث المباشر للرسالة
                Log::info('إرسال البث المباشر للرسالة', [
                    'session_id' => $this->conversation->session_id,
                    'channel' => 'conversation.'.$this->conversation->session_id,
                    'message_id' => $botMessage->id,
                    'message_role' => $botMessage->role,
                ]);
                broadcast(new MessageSent($botMessage, $this->conversation));

                Log::info('تمت معالجة رسالة الدردشة بنجاح', [
                    'conversation_id' => $this->conversation->id,
                    'session_id' => $this->conversation->session_id,
                    'processing_time' => $processingTime,
                ]);
            } else {
                throw new \Exception('لم يتم الحصول على رد صحيح من Ollama');
            }
        } catch (\Exception $e) {
            Log::error('خطأ في معالجة رسالة الدردشة', [
                'conversation_id' => $this->conversation->id,
                'session_id' => $this->conversation->session_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // إنشاء رسالة خطأ
            $errorMessage = Message::create([
                'conversation_id' => $this->conversation->id,
                'content' => 'عذراً، حدث خطأ في معالجة رسالتك. يرجى المحاولة مرة أخرى.',
                'role' => 'assistant',
                'metadata' => [
                    'error' => true,
                    'error_message' => $e->getMessage(),
                    'timestamp' => now()->toISOString(),
                ],
            ]);

            // إرسال رسالة الخطأ عبر البث المباشر
            broadcast(new MessageSent($errorMessage, $this->conversation))->toOthers();
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
            ->where('id', '<=', $this->userMessage->id)
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

        return $messages;
    }

    /**
     * تحديث الإحصائيات
     */
    private function updateStatistics(): void
    {
        // تحديث وقت آخر نشاط للمحادثة
        $this->conversation->update(['updated_at' => now()]);

        // تحديث إحصائيات الروبوت إذا كان متوفراً
        if ($this->conversation->chatbot) {
            $this->conversation->chatbot->increment('total_messages', 1);
        }
    }
}
