<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\OllamaService;

class DashboardController extends Controller
{
    private OllamaService $ollamaService;

    public function __construct(OllamaService $ollamaService)
    {
        $this->ollamaService = $ollamaService;
    }

    public function index()
    {
        $tenant = Auth::guard('tenant')->user();
        
        // إحصائيات عامة
        $stats = [
            'total_chatbots' => $tenant->chatbots()->count(),
            'active_chatbots' => $tenant->chatbots()->active()->count(),
            'total_conversations' => $tenant->conversations()->count(),
            'messages_used_this_month' => $tenant->messages_used_this_month,
            'messages_remaining' => $tenant->max_messages_per_month - $tenant->messages_used_this_month,
            'plan' => $tenant->plan,
        ];

        // آخر الروبوتات
        $recentChatbots = $tenant->chatbots()
            ->with(['conversations' => function($query) {
                $query->latest('last_activity')->take(1);
            }])
            ->latest()
            ->take(5)
            ->get();

        // آخر المحادثات
        $recentConversations = $tenant->conversations()
            ->with(['chatbot', 'messages' => function($query) {
                $query->latest()->take(1);
            }])
            ->latest('last_activity')
            ->take(10)
            ->get();

        // حالة Ollama
        $ollamaStatus = $this->ollamaService->isHealthy();
        $availableModels = $ollamaStatus ? $this->ollamaService->getAvailableModels() : [];

        return view('dashboard', compact(
            'tenant',
            'stats', 
            'recentChatbots', 
            'recentConversations',
            'ollamaStatus',
            'availableModels'
        ));
    }
}