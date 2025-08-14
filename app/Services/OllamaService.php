<?php
// app/Services/OllamaService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaService
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('ollama.host', 'http://localhost:11434');
        $this->timeout = config('ollama.timeout', 120);
    }

    /**
     * الحصول على قائمة النماذج المتاحة
     */
    public function getAvailableModels(): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->baseUrl . '/api/tags');

            if ($response->successful()) {
                return $response->json('models', []);
            }

            Log::error('فشل في الحصول على قائمة النماذج', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('خطأ في الاتصال بـ Ollama', [
                'message' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * توليد رد من النموذج
     */
    public function generateResponse(string $model, array $messages, array $options = []): ?array
    {
        try {
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'stream' => false,
                'options' => array_merge([
                    'temperature' => 0.7,
                    'top_p' => 0.9,
                    'num_predict' => 2048,
                ], $options)
            ];

            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/api/chat', $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('فشل في توليد الرد من Ollama', [
                'model' => $model,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('خطأ في توليد الرد من Ollama', [
                'model' => $model,
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * إنشاء تضمينات نصية (للاستخدام في RAG لاحقاً)
     */
    public function generateEmbeddings(string $model, string $text): ?array
    {
        try {
            $payload = [
                'model' => $model,
                'prompt' => $text,
            ];

            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/api/embeddings', $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('فشل في إنشاء التضمينات', [
                'model' => $model,
                'status' => $response->status()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('خطأ في إنشاء التضمينات', [
                'model' => $model,
                'message' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * سحب نموذج جديد
     */
    public function pullModel(string $modelName): bool
    {
        try {
            $response = Http::timeout(300) // وقت أطول لسحب النماذج
                ->post($this->baseUrl . '/api/pull', [
                    'name' => $modelName
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('خطأ في سحب النموذج', [
                'model' => $modelName,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * إنشاء نموذج مخصص باستخدام Modelfile
     */
    public function createCustomModel(string $modelName, string $modelfile): bool
    {
        try {
            $payload = [
                'name' => $modelName,
                'modelfile' => $modelfile
            ];

            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/api/create', $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('خطأ في إنشاء النموذج المخصص', [
                'model' => $modelName,
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * التحقق من حالة الخادم
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)
                ->get($this->baseUrl . '/api/tags');

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}