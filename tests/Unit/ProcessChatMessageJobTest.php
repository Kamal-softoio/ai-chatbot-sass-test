<?php

namespace Tests\Unit;

use App\Events\MessageSent;
use App\Jobs\ProcessChatMessage;
use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Tenant;
use App\Services\OllamaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class ProcessChatMessageJobTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Chatbot $chatbot;
    protected Conversation $conversation;
    protected Message $userMessage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->chatbot = Chatbot::factory()->create([
            'tenant_id' => $this->tenant->id,
            'model_name' => 'qwen2.5-coder:latest',
            'system_prompt' => 'أنت مساعد ذكي مفيد',
        ]);
        $this->conversation = Conversation::factory()->create([
            'chatbot_id' => $this->chatbot->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $this->userMessage = Message::factory()->create([
            'conversation_id' => $this->conversation->id,
            'content' => 'مرحبا، كيف حالك؟',
            'role' => 'user',
        ]);
    }

    /** @test */
    public function it_processes_chat_message_successfully()
    {
        Event::fake();

        // محاكاة استجابة Ollama ناجحة
        $mockOllamaService = Mockery::mock(OllamaService::class);
        $mockOllamaService->shouldReceive('generateResponse')
            ->once()
            ->andReturn([
                'message' => [
                    'content' => 'مرحبا! أنا بخير، شكراً لك. كيف يمكنني مساعدتك اليوم؟',
                ],
                'eval_count' => 45,
                'eval_duration' => 2500000000,
                'load_duration' => 500000000,
            ]);

        $this->app->instance(OllamaService::class, $mockOllamaService);

        // تنفيذ المهمة
        $job = new ProcessChatMessage($this->conversation, $this->userMessage);
        $job->handle($mockOllamaService);

        // التحقق من إنشاء رسالة المساعد
        $assistantMessage = Message::where('conversation_id', $this->conversation->id)
            ->where('role', 'assistant')
            ->first();

        $this->assertNotNull($assistantMessage);
        $this->assertEquals('مرحبا! أنا بخير، شكراً لك. كيف يمكنني مساعدتك اليوم؟', $assistantMessage->content);
        $this->assertEquals(45, $assistantMessage->tokens_used);

        // التحقق من إطلاق حدث البث
        Event::assertDispatched(MessageSent::class);
    }

    /** @test */
    public function it_handles_ollama_service_failure()
    {
        Event::fake();

        // محاكاة فشل خدمة Ollama
        $mockOllamaService = Mockery::mock(OllamaService::class);
        $mockOllamaService->shouldReceive('generateResponse')
            ->once()
            ->andReturn(null);

        $this->app->instance(OllamaService::class, $mockOllamaService);

        // تنفيذ المهمة
        $job = new ProcessChatMessage($this->conversation, $this->userMessage);
        $job->handle($mockOllamaService);

        // التحقق من إنشاء رسالة خطأ
        $errorMessage = Message::where('conversation_id', $this->conversation->id)
            ->where('role', 'assistant')
            ->first();

        $this->assertNotNull($errorMessage);
        $this->assertStringContainsString('عذراً، حدث خطأ', $errorMessage->content);
        
        // التحقق من بيانات الخطأ الوصفية
        $this->assertTrue($errorMessage->metadata['error']);
        $this->assertArrayHasKey('error_message', $errorMessage->metadata);

        // التحقق من إطلاق حدث البث للرسالة
        Event::assertDispatched(MessageSent::class);
    }

    /** @test */
    public function it_prepares_messages_with_system_prompt()
    {
        $mockOllamaService = Mockery::mock(OllamaService::class);
        
        // التحقق من الرسائل المرسلة
        $mockOllamaService->shouldReceive('generateResponse')
            ->once()
            ->with(
                'qwen2.5-coder:latest',
                Mockery::on(function ($messages) {
                    // التحقق من وجود System Prompt
                    $this->assertEquals('system', $messages[0]['role']);
                    $this->assertEquals('أنت مساعد ذكي مفيد', $messages[0]['content']);
                    
                    // التحقق من رسالة المستخدم
                    $this->assertEquals('user', $messages[1]['role']);
                    $this->assertEquals('مرحبا، كيف حالك؟', $messages[1]['content']);
                    
                    return true;
                })
            )
            ->andReturn([
                'message' => ['content' => 'رد اختبار'],
            ]);

        $this->app->instance(OllamaService::class, $mockOllamaService);

        $job = new ProcessChatMessage($this->conversation, $this->userMessage);
        $job->handle($mockOllamaService);
    }

    /** @test */
    public function it_includes_recent_conversation_history()
    {
        // إنشاء رسائل سابقة
        $oldMessages = Message::factory()->count(5)->create([
            'conversation_id' => $this->conversation->id,
            'role' => 'user',
        ]);

        $mockOllamaService = Mockery::mock(OllamaService::class);
        
        $mockOllamaService->shouldReceive('generateResponse')
            ->once()
            ->with(
                'qwen2.5-coder:latest',
                Mockery::on(function ($messages) {
                    // التحقق من وجود الرسائل السابقة (محدود بـ 10)
                    $this->assertCount(7, $messages); // system + 5 old + 1 new
                    return true;
                })
            )
            ->andReturn([
                'message' => ['content' => 'رد مع التاريخ'],
            ]);

        $this->app->instance(OllamaService::class, $mockOllamaService);

        $job = new ProcessChatMessage($this->conversation, $this->userMessage);
        $job->handle($mockOllamaService);
    }

    /** @test */
    public function it_updates_conversation_statistics()
    {
        $mockOllamaService = Mockery::mock(OllamaService::class);
        $mockOllamaService->shouldReceive('generateResponse')
            ->andReturn([
                'message' => ['content' => 'رد للإحصائيات'],
            ]);

        $this->app->instance(OllamaService::class, $mockOllamaService);

        $initialMessageCount = $this->chatbot->total_messages ?? 0;

        $job = new ProcessChatMessage($this->conversation, $this->userMessage);
        $job->handle($mockOllamaService);

        // التحقق من تحديث الإحصائيات
        $this->chatbot->refresh();
        $this->assertEquals($initialMessageCount + 1, $this->chatbot->total_messages);
        
        // التحقق من تحديث وقت المحادثة
        $this->conversation->refresh();
        $this->assertTrue($this->conversation->updated_at->isAfter($this->conversation->created_at));
    }

    /** @test */
    public function it_stores_processing_metadata()
    {
        $mockOllamaService = Mockery::mock(OllamaService::class);
        $mockOllamaService->shouldReceive('generateResponse')
            ->andReturn([
                'message' => ['content' => 'رد مع بيانات وصفية'],
                'eval_count' => 75,
                'eval_duration' => 1500000000,
                'load_duration' => 300000000,
            ]);

        $this->app->instance(OllamaService::class, $mockOllamaService);

        $job = new ProcessChatMessage($this->conversation, $this->userMessage);
        $job->handle($mockOllamaService);

        $assistantMessage = Message::where('conversation_id', $this->conversation->id)
            ->where('role', 'assistant')
            ->first();

        // التحقق من البيانات الوصفية
        $this->assertEquals('qwen2.5-coder:latest', $assistantMessage->metadata['model']);
        $this->assertEquals(75, $assistantMessage->metadata['tokens_used']);
        $this->assertArrayHasKey('processing_time', $assistantMessage->metadata);
        $this->assertArrayHasKey('timestamp', $assistantMessage->metadata);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
