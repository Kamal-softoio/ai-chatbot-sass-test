<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Jobs\ProcessChatMessage;
use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ChatbotApiTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Chatbot $chatbot;

    protected function setUp(): void
    {
        parent::setUp();
        
        // إنشاء بيانات اختبار
        $this->tenant = Tenant::factory()->create();
        $this->chatbot = Chatbot::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /** @test */
    public function it_can_send_a_chat_message()
    {
        // تفعيل Queue للاختبار
        Queue::fake();
        
        $response = $this->postJson('/api/chat/message', [
            'chatbot_id' => $this->chatbot->id,
            'message' => 'مرحبا، كيف حالك؟',
            'session_id' => 'test-session-123',
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'message' => [
                            'id',
                            'content',
                            'sender',
                            'created_at',
                        ],
                        'conversation_id',
                    ],
                ]);

        // التأكد من حفظ الرسالة في قاعدة البيانات
        $this->assertDatabaseHas('messages', [
            'content' => 'مرحبا، كيف حالك؟',
            'sender' => 'user',
        ]);

        // التأكد من تشغيل Job
        Queue::assertPushed(ProcessChatMessage::class);
    }

    /** @test */
    public function it_creates_conversation_if_not_exists()
    {
        Queue::fake();
        
        $response = $this->postJson('/api/chat/message', [
            'chatbot_id' => $this->chatbot->id,
            'message' => 'رسالة جديدة',
            'session_id' => 'new-session-456',
        ]);

        $response->assertStatus(201);
        
        // التأكد من إنشاء محادثة جديدة
        $this->assertDatabaseHas('conversations', [
            'session_id' => 'new-session-456',
            'chatbot_id' => $this->chatbot->id,
        ]);
    }

    /** @test */
    public function it_uses_existing_conversation_for_same_session()
    {
        Queue::fake();

        // إنشاء محادثة موجودة
        $conversation = Conversation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'chatbot_id' => $this->chatbot->id,
            'session_id' => 'existing-session',
        ]);

        $response = $this->postJson('/api/chat/message', [
            'chatbot_id' => $this->chatbot->id,
            'message' => 'رسالة في محادثة موجودة',
            'session_id' => 'existing-session',
        ]);

        $response->assertStatus(201);
        
        // التأكد من استخدام المحادثة الموجودة
        $this->assertEquals(1, Conversation::where('session_id', 'existing-session')->count());
    }

    /** @test */
    public function it_requires_valid_chatbot_id()
    {
        $response = $this->postJson('/api/chat/message', [
            'chatbot_id' => 999999, // ID غير موجود
            'message' => 'رسالة اختبار',
            'session_id' => 'test-session',
        ]);

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Chatbot not found',
                ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->postJson('/api/chat/message', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['chatbot_id', 'message', 'session_id']);
    }

    /** @test */
    public function it_can_get_conversation_history()
    {
        // إنشاء محادثة مع رسائل
        $conversation = Conversation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'chatbot_id' => $this->chatbot->id,
            'session_id' => 'history-test',
        ]);

        Message::factory()->count(3)->create([
            'conversation_id' => $conversation->id,
        ]);

        $response = $this->getJson('/api/chat/history?session_id=history-test&chatbot_id=' . $this->chatbot->id);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'conversation_id',
                        'messages' => [
                            '*' => [
                                'id',
                                'content',
                                'sender',
                                'created_at',
                            ]
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_conversation()
    {
        $chatbot = Chatbot::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->getJson('/api/chat/history?session_id=non-existent&chatbot_id=' . $chatbot->id);

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Conversation not found',
                ]);
    }

    /** @test */
    public function it_can_clear_conversation_history()
    {
        $chatbot = Chatbot::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $conversation = Conversation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'chatbot_id' => $chatbot->id,
            'session_id' => 'clear-test',
        ]);

        Message::factory()->count(5)->create([
            'conversation_id' => $conversation->id,
        ]);

        $response = $this->deleteJson('/api/chat/history', [
            'session_id' => 'clear-test',
            'chatbot_id' => $chatbot->id,
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Conversation history cleared',
                ]);

        // التأكد من حذف الرسائل
        $this->assertEquals(0, Message::where('conversation_id', $conversation->id)->count());
    }

    /** @test */
    public function it_can_rate_a_message()
    {
        $chatbot = Chatbot::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $conversation = Conversation::factory()->create([
            'tenant_id' => $this->tenant->id,
            'chatbot_id' => $chatbot->id,
        ]);

        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'sender' => 'assistant',
        ]);

        $response = $this->postJson('/api/chat/rate', [
            'message_id' => $message->id,
            'rating' => 'thumbs_up',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Rating saved successfully',
                ]);

        // التأكد من حفظ التقييم
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'rating' => 'thumbs_up',
        ]);
    }
}
