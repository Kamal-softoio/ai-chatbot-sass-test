<?php

namespace App\Http\Controllers;

use App\Models\Chatbot;
use Illuminate\Http\Request;

class ChatbotDemoController extends Controller
{
    /**
     * Show the chatbot demo page
     */
    public function index()
    {
        // Get a sample chatbot for demo
        $chatbot = Chatbot::first();

        if (! $chatbot) {
            // Create demo tenant first
            $tenant = \App\Models\Tenant::first();
            if (!$tenant) {
                $tenant = \App\Models\Tenant::create([
                    'name' => 'شركة التجارب التقنية',
                    'domain' => 'demo.example.com',
                    'database' => 'demo_db',
                    'settings' => [
                        'theme' => 'modern',
                        'language' => 'ar',
                    ]
                ]);
            }

            // Create a demo chatbot if none exists
                    $chatbot = Chatbot::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Demo Chatbot'],
            [
                'model_name' => 'qwen2.5',
                'system_prompt' => 'أنت مساعد ذكي مفيد وودود. أجب على الأسئلة بوضوح ودقة باللغة العربية.',
                'is_public' => true
            ]
        );
        }

        return view('demo.chatbot', compact('chatbot'));
    }

    /**
     * Show embedded chatbot widget
     */
    public function embed(Request $request, int $chatbotId)
    {
        $chatbot = Chatbot::findOrFail($chatbotId);

        return view('demo.embed', compact('chatbot'));
    }
}
