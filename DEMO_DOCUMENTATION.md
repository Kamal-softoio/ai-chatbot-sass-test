# 🎯 دليل العمل الكامل للديمو - AI Chatbot SaaS

## 📋 نظرة عامة

هذا الدليل يشرح بالتفصيل الممل كيف يعمل الديمو خطوة بخطوة، من إرسال الرسالة لحد وصول الرد للمستخدم عبر WebSocket.

---

## 🚀 الخدمات المطلوبة للتشغيل

### 1. خدمة Laravel (Backend API)
```bash
php artisan serve --host=0.0.0.0 --port=8000
```
- **الدور**: استقبال طلبات API ومعالجة الرسائل
- **المنفذ**: 8000
- **الرابط**: http://localhost:8000

### 2. خدمة Laravel Reverb (WebSocket Server)
```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```
- **الدور**: إدارة اتصالات WebSocket والبث المباشر
- **المنفذ**: 8080
- **البروتوكول**: Pusher Protocol
- **الرابط**: ws://localhost:8080

### 3. خدمة Ollama AI
```bash
ollama serve
```
- **الدور**: توليد الردود الذكية
- **المنفذ**: 11434 (افتراضي)
- **النموذج**: qwen2.5-coder:latest
- **الرابط**: http://localhost:11434

### 4. Queue Worker (معالج المهام)
```bash
php artisan queue:work --verbose
```
- **الدور**: معالجة مهام الذكاء الاصطناعي في الخلفية
- **النوع**: Sync Queue (افتراضي)
- **المعالجة**: ProcessChatMessage Job

---

## 🔄 دورة الحياة الكاملة للرسالة

### المرحلة 1: إرسال الرسالة من المستخدم

#### 1.1 واجهة المستخدم (Frontend)
```javascript
// الملف: resources/js/components/ChatbotWidget.js
sendMessage() {
    const message = this.messageInput.value.trim();
    
    // 1. إضافة الرسالة للواجهة فوراً
    this.addMessage(message, 'user');
    
    // 2. إرسال طلب AJAX للـ API
    fetch('/api/public/chat', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            message: message,
            session_id: this.sessionId,
            chatbot_id: 1 // افتراضي
        })
    });
}
```

#### 1.2 الطريق (Route)
```php
// الملف: routes/api.php
Route::post('/public/chat', [PublicChatController::class, 'chat'])
    ->name('public.chat');
```

### المرحلة 2: معالجة الطلب في Backend

#### 2.1 Controller (المتحكم)
```php
// الملف: app/Http/Controllers/Api/PublicChatController.php
public function chat(Request $request)
{
    // 1. التحقق من صحة البيانات
    $validated = $request->validate([
        'message' => 'required|string|max:5000',
        'session_id' => 'required|string|max:255',
        'chatbot_id' => 'integer|exists:chatbots,id',
    ]);

    // 2. العثور على أو إنشاء Conversation
    $conversation = $this->findOrCreateConversation(
        $validated['session_id'], 
        $validated['chatbot_id'] ?? 1
    );

    // 3. حفظ رسالة المستخدم
    $userMessage = Message::create([
        'conversation_id' => $conversation->id,
        'content' => $validated['message'],
        'role' => 'user',
        'metadata' => ['ip' => $request->ip()]
    ]);

    // 4. إرسال المهمة للـ Queue
    ProcessChatMessage::dispatch($userMessage, $conversation);

    // 5. إرجاع الاستجابة للمستخدم
    return response()->json([
        'success' => true,
        'conversation_id' => $conversation->id,
        'session_id' => $conversation->session_id,
        'message' => 'تم استلام رسالتك وجاري المعالجة...'
    ]);
}
```

### المرحلة 3: معالجة في الخلفية (Queue Job)

#### 3.1 مهمة ProcessChatMessage
```php
// الملف: app/Jobs/ProcessChatMessage.php
public function handle(): void
{
    try {
        // 1. جلب تاريخ المحادثة
        $messages = Message::where('conversation_id', $this->conversation->id)
            ->orderBy('created_at', 'asc')
            ->get();

        // 2. تحضير الرسائل للـ AI
        $formattedMessages = $this->prepareMessagesForAI($messages);

        // 3. استدعاء Ollama AI Service
        $aiResponse = $this->ollamaService->chat($formattedMessages);

        // 4. حفظ رد الذكاء الاصطناعي
        $botMessage = Message::create([
            'conversation_id' => $this->conversation->id,
            'content' => $aiResponse['message']['content'],
            'role' => 'assistant',
            'metadata' => [
                'model' => $aiResponse['model'] ?? 'unknown',
                'processing_time' => microtime(true) - $startTime
            ]
        ]);

        // 5. بث الرسالة عبر WebSocket
        broadcast(new MessageSent($botMessage, $this->conversation));

    } catch (\Exception $e) {
        // معالجة الأخطاء...
    }
}
```

#### 3.2 خدمة Ollama AI
```php
// الملف: app/Services/OllamaService.php
public function chat(array $messages): array
{
    // 1. تحضير البيانات
    $data = [
        'model' => 'qwen2.5-coder:latest',
        'messages' => $messages,
        'stream' => false,
        'options' => [
            'temperature' => 1.0,
            'top_p' => 0.9,
            'num_predict' => 2048
        ]
    ];

    // 2. إرسال الطلب لـ Ollama
    $response = Http::timeout(30)->post(
        $this->baseUrl . '/api/chat', 
        $data
    );

    // 3. إرجاع الاستجابة
    return $response->json();
}
```

### المرحلة 4: بث الرسالة (Broadcasting)

#### 4.1 إنشاء الحدث (Event)
```php
// الملف: app/Events/MessageSent.php
class MessageSent implements ShouldBroadcast
{
    public function __construct(Message $message, Conversation $conversation)
    {
        $this->message = $message;
        $this->conversation = $conversation;
        
        Log::info('MessageSent Event Created', [
            'message_id' => $message->id,
            'session_id' => $conversation->session_id
        ]);
    }

    // القناة التي سيتم البث عليها
    public function broadcastOn(): array
    {
        $channel = 'conversation.' . $this->conversation->session_id;
        return [new Channel($channel)];
    }

    // البيانات التي سيتم بثها
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->content,
                'role' => $this->message->role,
                'timestamp' => $this->message->created_at->toISOString()
            ],
            'conversation' => [
                'id' => $this->conversation->id,
                'session_id' => $this->conversation->session_id
            ]
        ];
    }

    // اسم الحدث
    public function broadcastAs(): string
    {
        return 'MessageSent';
    }
}
```

#### 4.2 إرسال للـ WebSocket Server
```php
// عند استدعاء broadcast()
broadcast(new MessageSent($botMessage, $this->conversation));

// Laravel يقوم بـ:
// 1. إنشاء الحدث
// 2. تحديد القناة: conversation.{session_id}
// 3. إرسال للـ Reverb Server على المنفذ 8080
// 4. Reverb يبث للعملاء المتصلين
```

### المرحلة 5: استقبال في Frontend

#### 5.1 إعداد WebSocket Connection
```javascript
// الملف: resources/js/components/ChatbotWidget.js
initWebSocket() {
    // 1. إنشاء اتصال Echo
    this.echo = new Echo({
        broadcaster: 'reverb',
        key: 'ai-chatbot-key',
        wsHost: 'localhost',
        wsPort: 8080,
        wssPort: 8080,
        forceTLS: false,
        enabledTransports: ['ws', 'wss']
    });

    // 2. الاشتراك في قناة المحادثة
    const channelName = `conversation.${this.sessionId}`;
    this.channel = this.echo.channel(channelName);

    // 3. الاستماع لحدث MessageSent
    this.channel.listen('MessageSent', (event) => {
        console.log('Message received via WebSocket:', event);
        
        // 4. إضافة الرسالة للواجهة
        this.addMessage(event.message.content, 'bot');
        this.hideTypingIndicator();
    });
}
```

---

## 🔧 تكوين WebSocket Broadcasting

### إعداد Reverb
```php
// الملف: config/broadcasting.php
'reverb' => [
    'driver' => 'reverb',
    'key' => env('REVERB_APP_KEY', 'ai-chatbot-key'),
    'secret' => env('REVERB_APP_SECRET', 'ai-chatbot-secret'),
    'app_id' => env('REVERB_APP_ID', '1'),
    'options' => [
        'host' => env('REVERB_HOST', '0.0.0.0'),
        'port' => env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'http'),
    ],
]
```

### إعداد Frontend Echo
```javascript
// في resources/js/components/ChatbotWidget.js
const echoConfig = {
    broadcaster: 'reverb',
    key: 'ai-chatbot-key',           // مطابق لـ REVERB_APP_KEY
    wsHost: 'localhost',             // عنوان الخادم
    wsPort: 8080,                    // منفذ Reverb
    wssPort: 8080,                   // منفذ SSL (إذا كان مطلوب)
    forceTLS: false,                 // بدون SSL محلياً
    enabledTransports: ['ws', 'wss'] // أنواع الاتصال المدعومة
};
```

---

## 🎭 سيناريو كامل خطوة بخطوة

### الخطوة 1: المستخدم يكتب "مرحبا"
```
[Frontend] User types: "مرحبا"
[Frontend] sessionId: "session-abc123-1755184000000"
```

### الخطوة 2: إرسال للـ API
```
POST /api/public/chat
{
    "message": "مرحبا",
    "session_id": "session-abc123-1755184000000",
    "chatbot_id": 1
}
```

### الخطوة 3: معالجة في Controller
```
[Controller] Validate data ✓
[Controller] Find/Create Conversation ID: 15
[Controller] Create User Message ID: 47
[Controller] Dispatch ProcessChatMessage Job
[Controller] Return Response: {"success": true, "conversation_id": 15}
```

### الخطوة 4: معالجة في Queue Job
```
[Queue] Start ProcessChatMessage Job
[Queue] Load conversation history (0 previous messages)
[Queue] Prepare AI messages with system prompt
[Queue] Call Ollama API with: 
        - Model: qwen2.5-coder:latest
        - Messages: [{"role":"system"...}, {"role":"user","content":"مرحبا"}]
```

### الخطوة 5: Ollama AI Response
```
[Ollama] Processing request...
[Ollama] Generate response: "مرحبًا! كيف يمكنني مساعدتك اليوم؟"
[Ollama] Return response to Laravel
```

### الخطوة 6: حفظ وبث الرد
```
[Queue] Create Bot Message ID: 48
[Queue] Content: "مرحبًا! كيف يمكنني مساعدتك اليوم؟"
[Queue] Create MessageSent Event
[Queue] Broadcast to channel: "conversation.session-abc123-1755184000000"
```

### الخطوة 7: Reverb Broadcasting
```
[Reverb] Receive broadcast request
[Reverb] Channel: conversation.session-abc123-1755184000000
[Reverb] Event: MessageSent
[Reverb] Data: {
    "message": {
        "id": 48,
        "content": "مرحبًا! كيف يمكنني مساعدتك اليوم؟",
        "role": "assistant",
        "timestamp": "2025-08-14T15:30:00.000Z"
    },
    "conversation": {
        "id": 15,
        "session_id": "session-abc123-1755184000000"
    }
}
[Reverb] Send to connected WebSocket clients
```

### الخطوة 8: Frontend يستقبل الرد
```
[Frontend] WebSocket receives MessageSent event
[Frontend] Parse event data
[Frontend] Add message to chat: "مرحبًا! كيف يمكنني مساعدتك اليوم؟"
[Frontend] Hide typing indicator
[Frontend] Scroll to bottom
```

---

## 🐛 تشخيص المشاكل الشائعة

### مشكلة 1: الرسائل لا تظهر في الواجهة
**السبب المحتمل**: WebSocket غير متصل أو Channel خاطئ
**الحل**:
```javascript
// فحص الاتصال
console.log('Echo status:', this.echo);
console.log('Channel name:', `conversation.${this.sessionId}`);
console.log('Channel status:', this.channel);
```

### مشكلة 2: Ollama لا يرد
**السبب المحتمل**: Ollama service غير شغال أو النموذج غير موجود
**الحل**:
```bash
# فحص خدمة Ollama
curl http://localhost:11434/api/tags

# تحديث النموذج
ollama pull qwen2.5-coder:latest
```

### مشكلة 3: Queue Jobs لا تعمل
**السبب المحتمل**: Queue Worker غير شغال
**الحل**:
```bash
# تشغيل Queue Worker
php artisan queue:work --verbose

# فحص الـ Jobs
php artisan queue:failed
```

### مشكلة 4: Reverb غير متصل
**السبب المحتمل**: Reverb Server غير شغال أو إعدادات خاطئة
**الحل**:
```bash
# تشغيل Reverb
php artisan reverb:start --debug

# فحص الإعدادات
php artisan config:cache
```

---

## 📊 مراقبة الأداء

### Logs مهمة للمراقبة
```bash
# Laravel Logs
tail -f storage/logs/laravel.log

# Reverb Logs (في Terminal منفصل)
php artisan reverb:start --debug

# Queue Worker Logs
php artisan queue:work --verbose
```

### Browser Console للتشخيص
```javascript
// في Developer Console
// فحص Echo connection
window.Echo

// فحص الرسائل المستقبلة
// ستظهر في Console عند الاستقبال
```

---

## ⚡ تحسينات الأداء

### 1. تحسين Ollama
```php
// في OllamaService.php
'options' => [
    'temperature' => 0.7,    // أقل عشوائية = ردود أسرع
    'top_p' => 0.9,
    'num_predict' => 1024    // كلمات أقل = ردود أسرع
]
```

### 2. تحسين WebSocket
```javascript
// إعدادات Echo محسنة
{
    broadcaster: 'reverb',
    key: 'ai-chatbot-key',
    wsHost: 'localhost',
    wsPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws'], // WebSocket فقط
    auth: {
        headers: {}, // بدون authentication للأداء
    }
}
```

### 3. تحسين Database
```php
// في Migration
$table->index('conversation_id'); // فهرس للرسائل
$table->index('session_id');     // فهرس للجلسات
$table->index('created_at');     // فهرس للوقت
```

---

## 🔍 أدوات التشخيص المتقدم

### 1. WebSocket Connection Test
```javascript
// اختبار الاتصال
fetch('/api/public/chat', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        message: 'test connection',
        session_id: 'test-session-' + Date.now(),
        chatbot_id: 1
    })
}).then(r => r.json()).then(console.log);
```

### 2. Database Query للتحقق
```sql
-- فحص آخر الرسائل
SELECT * FROM messages ORDER BY created_at DESC LIMIT 10;

-- فحص المحادثات النشطة  
SELECT * FROM conversations ORDER BY updated_at DESC LIMIT 5;

-- فحص إحصائيات اليوم
SELECT COUNT(*) as total_messages FROM messages 
WHERE DATE(created_at) = CURDATE();
```

### 3. Ollama Health Check
```bash
# فحص صحة الخدمة
curl http://localhost:11434/api/version

# فحص النماذج المتاحة
curl http://localhost:11434/api/tags

# اختبار النموذج
curl http://localhost:11434/api/generate -d '{
    "model": "qwen2.5-coder:latest",
    "prompt": "Hello",
    "stream": false
}'
```

---

## 🎯 خلاصة التدفق الكامل

```
[User] يكتب رسالة
    ↓
[Frontend] يرسل POST لـ /api/public/chat  
    ↓
[Controller] يحفظ الرسالة + ينشئ Queue Job
    ↓
[Queue Job] يستدعي Ollama AI
    ↓
[Ollama] يولد الرد الذكي
    ↓
[Queue Job] يحفظ الرد + يبث MessageSent Event
    ↓
[Reverb] يستقبل البث ويرسله للعملاء
    ↓
[Frontend] يستقبل عبر WebSocket ويعرض الرد
    ↓
[User] يشاهد الرد في الواجهة
```

**وقت التنفيذ المتوقع**: 2-5 ثواني (حسب تعقيد الرد)
**المنافذ المستخدمة**: 8000 (Laravel), 8080 (Reverb), 11434 (Ollama)
**التقنيات**: Laravel 12, WebSocket, Queue Jobs, AI Integration

---

*تم إنشاء هذا التوثيق بواسطة GitHub Copilot - دليل شامل للديمو! 🚀*
