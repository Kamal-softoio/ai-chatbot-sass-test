<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class WebSocketConnectionTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Chatbot $chatbot;
    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        $this->chatbot = Chatbot::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->conversation = Conversation::factory()->create([
            'chatbot_id' => $this->chatbot->id,
            'tenant_id' => $this->tenant->id,
        ]);
    }

    /** @test */
    public function it_broadcasts_message_sent_event()
    {
        Event::fake();

        $message = Message::factory()->create([
            'conversation_id' => $this->conversation->id,
            'content' => 'رسالة اختبار البث',
            'role' => 'assistant',
        ]);

        // إطلاق الحدث
        event(new MessageSent($message, $this->conversation));

        // التحقق من إطلاق الحدث
        Event::assertDispatched(MessageSent::class, function ($event) use ($message) {
            return $event->message->id === $message->id
                && $event->conversation->id === $this->conversation->id;
        });
    }

    /** @test */
    public function it_can_access_websocket_demo_page()
    {
        $response = $this->get('/chatbot/demo');

        $response->assertStatus(200)
                ->assertSee('AI Chatbot Demo')
                ->assertSee('chatbot-widget');
    }

    /** @test */
    public function message_sent_event_has_correct_structure()
    {
        $message = Message::factory()->create([
            'conversation_id' => $this->conversation->id,
            'content' => 'اختبار هيكل الرسالة',
            'role' => 'user',
            'metadata' => ['test' => true],
        ]);

        $event = new MessageSent($message, $this->conversation);

        // التحقق من خصائص الحدث
        $this->assertEquals($message->id, $event->message->id);
        $this->assertEquals($this->conversation->id, $event->conversation->id);
        $this->assertEquals('اختبار هيكل الرسالة', $event->message->content);
        $this->assertEquals('user', $event->message->role);
    }

    /** @test */
    public function it_broadcasts_on_correct_channel()
    {
        $message = Message::factory()->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $event = new MessageSent($message, $this->conversation);

        // التحقق من اسم القناة
        $this->assertEquals(
            "chat.{$this->conversation->session_id}",
            $event->broadcastOn()->name
        );
    }

    /** @test */
    public function it_includes_message_data_in_broadcast()
    {
        $message = Message::factory()->create([
            'conversation_id' => $this->conversation->id,
            'content' => 'محتوى الرسالة للبث',
            'role' => 'assistant',
            'metadata' => [
                'model' => 'qwen2.5-coder:latest',
                'processing_time' => 2.5,
            ],
        ]);

        $event = new MessageSent($message, $this->conversation);
        $broadcastData = $event->broadcastWith();

        // التحقق من بيانات البث
        $this->assertArrayHasKey('message', $broadcastData);
        $this->assertArrayHasKey('conversation', $broadcastData);
        
        $this->assertEquals($message->id, $broadcastData['message']['id']);
        $this->assertEquals('محتوى الرسالة للبث', $broadcastData['message']['content']);
        $this->assertEquals('assistant', $broadcastData['message']['role']);
        
        $this->assertEquals($this->conversation->id, $broadcastData['conversation']['id']);
        $this->assertEquals($this->conversation->session_id, $broadcastData['conversation']['session_id']);
    }

    /** @test */
    public function it_can_handle_arabic_content_in_broadcasts()
    {
        $arabicContent = 'مرحباً بكم في نظام الذكاء الاصطناعي المتطور';
        
        $message = Message::factory()->create([
            'conversation_id' => $this->conversation->id,
            'content' => $arabicContent,
            'role' => 'assistant',
        ]);

        $event = new MessageSent($message, $this->conversation);
        $broadcastData = $event->broadcastWith();

        $this->assertEquals($arabicContent, $broadcastData['message']['content']);
    }

    /** @test */
    public function it_includes_timestamp_in_broadcast_data()
    {
        $message = Message::factory()->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $event = new MessageSent($message, $this->conversation);
        $broadcastData = $event->broadcastWith();

        $this->assertArrayHasKey('created_at', $broadcastData['message']);
        $this->assertNotEmpty($broadcastData['message']['created_at']);
    }

    /** @test */
    public function it_can_test_reverb_configuration()
    {
        // التحقق من إعدادات البث
        $this->assertEquals('reverb', config('broadcasting.default'));
        
        $reverbConfig = config('broadcasting.connections.reverb');
        $this->assertEquals('0.0.0.0', $reverbConfig['host']);
        $this->assertEquals('8080', $reverbConfig['port']);
        $this->assertEquals('app-id', $reverbConfig['app_id']);
        $this->assertEquals('app-key', $reverbConfig['key']);
        $this->assertEquals('app-secret', $reverbConfig['secret']);
    }
}
