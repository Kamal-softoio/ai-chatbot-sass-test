<?php

namespace Tests\Feature;

use App\Models\Chatbot;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickDemoTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_access_demo_page()
    {
        $response = $this->get('/chatbot/demo');

        $response->assertStatus(200)
                ->assertSee('AI Chatbot Demo')
                ->assertSee('chatbot-widget');
    }

    /** @test */
    public function it_can_create_test_data()
    {
        // إنشاء بيانات اختبار سريعة
        $tenant = Tenant::factory()->create();
        $chatbot = Chatbot::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'روبوت الاختبار',
        ]);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('chatbots', [
            'id' => $chatbot->id,
            'tenant_id' => $tenant->id,
            'name' => 'روبوت الاختبار',
        ]);
    }

    /** @test */
    public function it_returns_json_error_for_invalid_chatbot()
    {
        $response = $this->postJson('/api/chat/send', [
            'chatbot_id' => 999,
            'message' => 'رسالة اختبار',
            'session_id' => 'test-session',
        ]);

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                ]);
    }
}
