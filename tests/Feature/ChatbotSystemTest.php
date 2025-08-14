<?php

namespace Tests\Feature;

use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotSystemTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_complete_chatbot_system()
    {
        // إنشاء tenant
        $tenant = Tenant::factory()->create([
            'name' => 'شركة الاختبار التقني',
            'email' => 'admin@test.com',
        ]);

        // إنشاء chatbot
        $chatbot = Chatbot::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'مساعد الدعم التقني',
            'model_name' => 'qwen2.5-coder:latest',
        ]);

        // إنشاء محادثة
        $conversation = Conversation::factory()->create([
            'tenant_id' => $tenant->id,
            'chatbot_id' => $chatbot->id,
            'session_id' => 'test-session-123',
        ]);

        // إنشاء رسائل
        $userMessage = Message::factory()->user()->create([
            'conversation_id' => $conversation->id,
            'content' => 'مرحباً، أحتاج مساعدة',
        ]);

        $assistantMessage = Message::factory()->assistant()->create([
            'conversation_id' => $conversation->id,
            'content' => 'أهلاً وسهلاً! كيف يمكنني مساعدتك؟',
        ]);

        // التحقق من إنشاء البيانات
        $this->assertDatabaseHas('tenants', [
            'name' => 'شركة الاختبار التقني',
        ]);

        $this->assertDatabaseHas('chatbots', [
            'name' => 'مساعد الدعم التقني',
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('conversations', [
            'session_id' => 'test-session-123',
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('messages', [
            'content' => 'مرحباً، أحتاج مساعدة',
            'role' => 'user',
        ]);

        $this->assertDatabaseHas('messages', [
            'content' => 'أهلاً وسهلاً! كيف يمكنني مساعدتك؟',
            'role' => 'assistant',
        ]);

        // التحقق من العلاقات
        $this->assertEquals(2, $conversation->messages()->count());
        $this->assertEquals($tenant->id, $chatbot->tenant_id);
        $this->assertEquals($chatbot->id, $conversation->chatbot_id);
    }

    /** @test */
    public function it_can_handle_arabic_content()
    {
        $tenant = Tenant::factory()->create();
        $chatbot = Chatbot::factory()->arabicFocused()->create([
            'tenant_id' => $tenant->id,
        ]);

        $conversation = Conversation::factory()->create([
            'tenant_id' => $tenant->id,
            'chatbot_id' => $chatbot->id,
        ]);

        $arabicMessage = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'content' => 'مرحباً، كيف يمكنني الحصول على معلومات حول منتجاتكم؟',
            'role' => 'user',
        ]);

        $arabicResponse = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'content' => 'أهلاً بك! سأكون سعيداً لتقديم معلومات شاملة عن جميع منتجاتنا. أي منتج يهمك بشكل خاص؟',
            'role' => 'assistant',
        ]);

        $this->assertDatabaseHas('messages', [
            'content' => 'مرحباً، كيف يمكنني الحصول على معلومات حول منتجاتكم؟',
        ]);

        $this->assertDatabaseHas('messages', [
            'content' => 'أهلاً بك! سأكون سعيداً لتقديم معلومات شاملة عن جميع منتجاتنا. أي منتج يهمك بشكل خاص؟',
        ]);
    }

    /** @test */
    public function it_can_test_message_ratings()
    {
        $conversation = Conversation::factory()->create();
        
        $message = Message::factory()->assistant()->create([
            'conversation_id' => $conversation->id,
            'rating' => 5,
            'feedback' => 'رد ممتاز ومفيد جداً!',
        ]);

        $this->assertEquals(5, $message->rating);
        $this->assertEquals('رد ممتاز ومفيد جداً!', $message->feedback);
    }

    /** @test */
    public function it_can_test_chatbot_settings()
    {
        $chatbot = Chatbot::factory()->highPerformance()->create();

        $this->assertEquals('qwen2.5-coder:latest', $chatbot->model_name);
        $this->assertEquals('active', $chatbot->status);
        $this->assertArrayHasKey('temperature', $chatbot->settings);
        $this->assertEquals(0.7, $chatbot->settings['temperature']);
    }

    /** @test */
    public function it_can_create_multiple_conversations()
    {
        $tenant = Tenant::factory()->create();
        $chatbot = Chatbot::factory()->create(['tenant_id' => $tenant->id]);

        // إنشاء محادثات متعددة
        $conversations = Conversation::factory()->count(5)->create([
            'tenant_id' => $tenant->id,
            'chatbot_id' => $chatbot->id,
        ]);

        // إضافة رسائل لكل محادثة
        foreach ($conversations as $conversation) {
            Message::factory()->count(rand(3, 10))->create([
                'conversation_id' => $conversation->id,
            ]);
        }

        $this->assertEquals(5, $chatbot->conversations()->count());
        $this->assertTrue(Message::count() >= 15); // على الأقل 3 رسائل × 5 محادثات
    }
}
