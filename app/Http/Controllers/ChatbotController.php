<?php

namespace App\Http\Controllers;

use App\Models\Chatbot;
use App\Services\OllamaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ChatbotController extends Controller
{
    private OllamaService $ollamaService;

    public function __construct(OllamaService $ollamaService)
    {
        $this->ollamaService = $ollamaService;
    }

    /**
     * عرض قائمة الروبوتات
     */
    public function index()
    {
        $tenant = Auth::guard('tenant')->user();
        
        $chatbots = Chatbot::with('conversations')
            ->withCount(['conversations', 'conversations as messages_count' => function ($query) {
                $query->withCount('messages');
            }])
            ->paginate(10);

        return view('chatbots.index', compact('chatbots', 'tenant'));
    }

    /**
     * عرض نموذج إنشاء روبوت جديد
     */
    public function create()
    {
        $tenant = Auth::guard('tenant')->user();

        // التحقق من الحد المسموح
        if (!$tenant->canCreateChatbot()) {
            return redirect()->route('chatbots.index')
                ->with('error', 'لقد وصلت إلى الحد الأقصى من الروبوتات المسموح بها في باقتك الحالية.');
        }

        // الحصول على النماذج المتاحة
        $availableModels = $this->ollamaService->getAvailableModels();
        $defaultModels = config('ollama.default_models', []);

        return view('chatbots.create', compact('availableModels', 'defaultModels'));
    }

    /**
     * حفظ روبوت جديد
     */
    public function store(Request $request)
    {
        $tenant = Auth::guard('tenant')->user();

        if (!$tenant->canCreateChatbot()) {
            return redirect()->route('chatbots.index')
                ->with('error', 'لقد وصلت إلى الحد الأقصى من الروبوتات.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'model_name' => 'required|string',
            'system_prompt' => 'nullable|string|max:5000',
            'is_public' => 'boolean',
            'settings.temperature' => 'nullable|numeric|between:0,1',
            'settings.max_tokens' => 'nullable|integer|between:100,4000',
        ]);

        $chatbot = new Chatbot([
            'tenant_id' => $tenant->id,
            'name' => $request->name,
            'description' => $request->description,
            'model_name' => $request->model_name,
            'system_prompt' => $request->system_prompt ?? 'أنت مساعد ذكي مفيد وودود. أجب على الأسئلة بوضوح ودقة.',
            'is_public' => $request->boolean('is_public'),
            'settings' => [
                'temperature' => $request->input('settings.temperature', 0.7),
                'max_tokens' => $request->input('settings.max_tokens', 2048),
                'top_p' => $request->input('settings.top_p', 0.9),
            ],
            'status' => 'active',
        ]);

        $chatbot->save();

        return redirect()->route('chatbots.show', $chatbot)
            ->with('success', 'تم إنشاء الروبوت بنجاح!');
    }

    /**
     * عرض تفاصيل روبوت معين
     */
    public function show(Chatbot $chatbot)
    {
        $chatbot->load(['conversations' => function ($query) {
            $query->latest()->with('messages')->take(10);
        }]);

        // إحصائيات الروبوت
        $stats = [
            'total_conversations' => $chatbot->conversations()->count(),
            'total_messages' => 1,
            'avg_messages_per_conversation' =>1,
            'last_activity' => $chatbot->conversations()->latest('last_activity')->first()?->last_activity,
        ];

        return view('chatbots.show', compact('chatbot', 'stats'));
    }

    /**
     * عرض نموذج تعديل الروبوت
     */
    public function edit(Chatbot $chatbot)
    {
        $availableModels = $this->ollamaService->getAvailableModels();
        $defaultModels = config('ollama.default_models', []);

        return view('chatbots.edit', compact('chatbot', 'availableModels', 'defaultModels'));
    }

    /**
     * تحديث بيانات الروبوت
     */
    public function update(Request $request, Chatbot $chatbot)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'model_name' => 'required|string',
            'system_prompt' => 'nullable|string|max:5000',
            'is_public' => 'boolean',
            'status' => Rule::in(['active', 'inactive']),
            'settings.temperature' => 'nullable|numeric|between:0,1',
            'settings.max_tokens' => 'nullable|integer|between:100,4000',
        ]);

        $chatbot->update([
            'name' => $request->name,
            'description' => $request->description,
            'model_name' => $request->model_name,
            'system_prompt' => $request->system_prompt,
            'is_public' => $request->boolean('is_public'),
            'status' => $request->status ?? 'active',
            'settings' => [
                'temperature' => $request->input('settings.temperature', 0.7),
                'max_tokens' => $request->input('settings.max_tokens', 2048),
                'top_p' => $request->input('settings.top_p', 0.9),
            ],
        ]);

        return redirect()->route('chatbots.show', $chatbot)
            ->with('success', 'تم تحديث الروبوت بنجاح!');
    }

    /**
     * حذف الروبوت
     */
    public function destroy(Chatbot $chatbot)
    {
        $name = $chatbot->name;
        $chatbot->delete();

        return redirect()->route('chatbots.index')
            ->with('success', "تم حذف الروبوت '{$name}' بنجاح.");
    }

    /**
     * اختبار الروبوت
     */
    public function test(Request $request, Chatbot $chatbot)
    {
        $request->validate([
            'message' => 'required|string|max:1000'
        ]);

        // إنشاء رسائل الاختبار
        $messages = [];
        
        if ($chatbot->system_prompt) {
            $messages[] = [
                'role' => 'system',
                'content' => $chatbot->system_prompt
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $request->message
        ];

        // إرسال الطلب إلى Ollama
        $response = $this->ollamaService->generateResponse(
            $chatbot->model_name,
            $messages,
            $chatbot->settings ?? []
        );

        if ($response) {
            return response()->json([
                'success' => true,
                'message' => $response['message']['content'] ?? 'لا يوجد رد',
                'processing_time' => $response['eval_duration'] ?? 0,
                'tokens_used' => $response['eval_count'] ?? 0,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'فشل في الحصول على رد من الروبوت. تأكد من أن خادم Ollama يعمل بشكل صحيح.'
        ], 500);
    }

    /**
     * نسخ كود التضمين للموقع
     */
    public function embedCode(Chatbot $chatbot)
    {
        if (!$chatbot->is_public) {
            return redirect()->route('chatbots.show', $chatbot)
                ->with('error', 'يجب تفعيل الروبوت كعام أولاً للحصول على كود التضمين.');
        }

        $embedCode = $this->generateEmbedCode($chatbot);
        
        return view('chatbots.embed-code', compact('chatbot', 'embedCode'));
    }

    /**
     * توليد كود التضمين
     */
    private function generateEmbedCode(Chatbot $chatbot): string
    {
        $widgetUrl = url("/api/public/chat/{$chatbot->widget_id}");
        
        return <<<HTML
<!-- كود تضمين الروبوت: {$chatbot->name} -->
<div id="chatbot-{$chatbot->widget_id}"></div>
<script>
  (function() {
    var script = document.createElement('script');
    script.src = '{$widgetUrl}/widget.js';
    script.setAttribute('data-widget-id', '{$chatbot->widget_id}');
    script.setAttribute('data-chatbot-name', '{$chatbot->name}');
    document.head.appendChild(script);
  })();
</script>
HTML;
    }
}
