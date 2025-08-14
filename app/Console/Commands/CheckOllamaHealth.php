<?php
// php artisan make:command CheckOllamaHealth

// app/Console/Commands/CheckOllamaHealth.php

namespace App\Console\Commands;

use App\Services\OllamaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckOllamaHealth extends Command
{
    protected $signature = 'ollama:health-check';
    protected $description = 'فحص حالة خادم Ollama وتسجيل النتائج';

    private OllamaService $ollamaService;

    public function __construct(OllamaService $ollamaService)
    {
        parent::__construct();
        $this->ollamaService = $ollamaService;
    }

    public function handle()
    {
        $this->info('فحص حالة خادم Ollama...');

        $isHealthy = $this->ollamaService->isHealthy();
        
        if ($isHealthy) {
            $this->info('✅ خادم Ollama يعمل بشكل صحيح');
            
            // الحصول على قائمة النماذج المتاحة
            $models = $this->ollamaService->getAvailableModels();
            $this->info("📋 النماذج المتاحة: " . count($models));
            
            foreach ($models as $model) {
                $this->line(" - {$model['name']} (" . ($model['size'] ?? 'غير محدد') . ")");
            }
            
            // حفظ الحالة في الكاش
            Cache::put('ollama_health_status', [
                'status' => 'healthy',
                'models_count' => count($models),
                'last_check' => now()->toISOString(),
                'models' => $models,
            ], now()->addMinutes(5));

            Log::info('Ollama health check passed', [
                'models_count' => count($models)
            ]);

        } else {
            $this->error('❌ خادم Ollama غير متاح');
            
            Cache::put('ollama_health_status', [
                'status' => 'unhealthy',
                'last_check' => now()->toISOString(),
                'error' => 'Server not responding'
            ], now()->addMinutes(5));

            Log::error('Ollama health check failed - server not responding');
            
            // يمكن إرسال إشعار للإدارة هنا
            return 1; // إرجاع كود خطأ
        }

        return 0;
    }
}

// // إضافة الأمر إلى الجدولة
// // في app/Console/Kernel.php

// protected function schedule(Schedule $schedule)
// {
//     // فحص صحة Ollama كل 5 دقائق
//     $schedule->command('ollama:health-check')
//         ->everyFiveMinutes()
//         ->withoutOverlapping()
//         ->runInBackground();

//     // تنظيف المحادثات القديمة غير النشطة
//     $schedule->call(function () {
//         \App\Models\Conversation::where('is_active', true)
//             ->where('last_activity', '<', now()->subHours(24))
//             ->update(['is_active' => false]);
//     })->daily();

//     // تنظيف الكاش القديم
//     $schedule->call(function () {
//         Cache::forget('chat_response_*');
//         Cache::forget('conversation_status_*');
//     })->hourly();
// }