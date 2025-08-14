# 🔍 دليل التشخيص والـ Debugging - AI Chatbot SaaS

## 📋 نظرة عامة

هذا الدليل يشرح بالتفصيل كيفية تشخيص المشاكل وإجراء debugging للديمو خطوة بخطوة. يغطي جميع الخدمات والمكونات المختلفة.

---

## 🚀 التحقق من الخدمات الأساسية أولاً

### 1️⃣ فحص خدمة Laravel الأساسية

```bash
# التأكد من تشغيل Laravel
curl -I http://localhost:8000

# النتيجة المتوقعة:
# HTTP/1.1 200 OK
# Server: PHP Development Server
```

**إذا لم تعمل:**
```bash
# تشغيل Laravel Server
cd i:\projects\ai-chatbot-saas
php artisan serve --host=0.0.0.0 --port=8000

# فحص الأخطاء في الـ logs
tail -f storage/logs/laravel.log
```

### 2️⃣ فحص خدمة Ollama AI

```bash
# فحص حالة Ollama
curl http://localhost:11434/api/version

# النتيجة المتوقعة:
# {"version":"0.x.x"}

# فحص النماذج المتاحة
curl http://localhost:11434/api/tags

# يجب أن تجد qwen2.5-coder:latest
```

**إذا لم تعمل:**
```bash
# تشغيل Ollama
ollama serve

# في terminal آخر، تحميل النموذج
ollama pull qwen2.5-coder:latest

# اختبار النموذج
ollama run qwen2.5-coder:latest "مرحبا"
```

### 3️⃣ فحص خدمة Reverb WebSocket

```bash
# فحص اتصال Reverb
telnet localhost 8080

# أو باستخدام curl (إذا كان متاح)
curl -I http://localhost:8080

# النتيجة المتوقعة: اتصال ناجح أو رد من الخادم
```

**إذا لم تعمل:**
```bash
# تشغيل Reverb مع debug mode
php artisan reverb:start --host=0.0.0.0 --port=8080 --debug

# مراقبة الـ output للأخطاء
```

### 4️⃣ فحص Queue Worker

```bash
# اختبار Queue Worker
php artisan queue:work --once

# النتيجة المتوقعة:
# [INFO] Processing jobs from the [default] queue.
# (ثم إما job تم تنفيذه أو لا توجد jobs)
```

**إذا لم تعمل:**
```bash
# فحص الـ Queue configuration
php artisan config:show queue

# تشغيل Queue Worker مع verbose mode
php artisan queue:work --verbose

# فحص Jobs فاشلة
php artisan queue:failed
```

---

## 🔧 خطوات التشخيص التفصيلية

### المرحلة 1: فحص الأساسيات

#### أ) فحص الملفات والإعدادات

```bash
# 1. فحص ملف البيئة
cat .env | grep -E "DB_|BROADCAST_|REVERB_|OLLAMA_"

# يجب أن تجد:
# DB_CONNECTION=mysql (أو sqlite)
# BROADCAST_DRIVER=reverb
# REVERB_APP_KEY=ai-chatbot-key
# REVERB_PORT=8080
```

```bash
# 2. فحص إعدادات قاعدة البيانات
php artisan tinker
# ثم داخل tinker:
DB::connection()->getPdo();
# يجب ألا يظهر خطأ
exit
```

```bash
# 3. فحص الجداول في قاعدة البيانات
php artisan tinker
# ثم:
Schema::hasTable('messages');
Schema::hasTable('conversations');
Schema::hasTable('chatbots');
Schema::hasTable('tenants');
# يجب أن يرجع true للجميع
exit
```

#### ب) فحص الـ Routes

```bash
# فحص جميع الـ routes
php artisan route:list | grep -E "api/public|demo"

# يجب أن تجد:
# POST   api/public/chat
# GET    demo
```

#### ج) فحص الـ Permissions

```bash
# فحص أذونات الكتابة
ls -la storage/logs/
ls -la storage/app/
ls -la storage/framework/

# يجب أن تكون قابلة للكتابة (writable)
```

### المرحلة 2: اختبار كل خدمة منفصلة

#### 🎯 اختبار API مباشرة

```bash
# اختبار API endpoint بدون Frontend
curl -X POST http://localhost:8000/api/public/chat \
  -H "Content-Type: application/json" \
  -d '{
    "message": "test debug message",
    "session_id": "debug-session-' $(date +%s) '",
    "chatbot_id": 1
  }'

# النتيجة المتوقعة:
# {
#   "success": true,
#   "conversation_id": X,
#   "session_id": "debug-session-XXXXX",
#   "message": "تم استلام رسالتك وجاري المعالجة..."
# }
```

#### 🤖 اختبار Ollama مباشرة

```bash
# اختبار الـ AI Service مباشرة
curl -X POST http://localhost:11434/api/chat \
  -H "Content-Type: application/json" \
  -d '{
    "model": "qwen2.5-coder:latest",
    "messages": [
      {"role": "system", "content": "أنت مساعد ذكي مفيد."},
      {"role": "user", "content": "مرحبا"}
    ],
    "stream": false
  }'

# النتيجة المتوقعة:
# {
#   "model": "qwen2.5-coder:latest",
#   "created_at": "...",
#   "message": {
#     "role": "assistant",
#     "content": "مرحبًا! كيف يمكنني مساعدتك اليوم؟"
#   }
# }
```

#### 📡 اختبار WebSocket من Browser

افتح **Developer Console** في المتصفح واكتب:

```javascript
// اختبار اتصال WebSocket مباشر
const ws = new WebSocket('ws://localhost:8080/app/ai-chatbot-key');

ws.onopen = function() {
    console.log('✅ WebSocket connected successfully');
};

ws.onmessage = function(event) {
    console.log('📨 Message received:', event.data);
};

ws.onerror = function(error) {
    console.error('❌ WebSocket error:', error);
};

ws.onclose = function(event) {
    console.log('🔌 WebSocket closed:', event.code, event.reason);
};
```

---

## 🔍 سيناريو التشخيص المتكامل

### الخطوة 1: تجهيز بيئة Debug

```bash
# 1. افتح 4 terminals منفصلة

# Terminal 1: Laravel مع logs
cd i:\projects\ai-chatbot-saas
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 2: Reverb مع debug
php artisan reverb:start --host=0.0.0.0 --port=8080 --debug

# Terminal 3: Queue Worker مع verbose
php artisan queue:work --verbose

# Terminal 4: مراقبة Logs
tail -f storage/logs/laravel.log
```

### الخطوة 2: اختبار من المتصفح

1. **افتح المتصفح:**
   ```
   http://localhost:8000/demo
   ```

2. **افتح Developer Tools (F12):**
   - اذهب لتاب **Console**
   - اذهب لتاب **Network**
   - اذهب لتاب **WebSocket** (في Network)

### الخطوة 3: إرسال رسالة تجريبية

1. **اكتب في الشات:** "test debug"
2. **راقب في Console:** يجب أن تظهر رسائل مثل:
   ```javascript
   WebSocket connected to: ws://localhost:8080
   Channel subscribed: conversation.session-xxxxx
   Message sent via API
   API Response: {success: true, conversation_id: X}
   ```

### الخطوة 4: تتبع الرسالة خلال النظام

#### في Terminal 4 (Logs) يجب أن تشاهد:

```
[2025-08-14 XX:XX:XX] local.INFO: رسالة مستخدم جديدة {"message":"test debug","session_id":"session-xxxxx"}

[2025-08-14 XX:XX:XX] local.INFO: تم إنشاء محادثة جديدة {"conversation_id":X,"session_id":"session-xxxxx"}

[2025-08-14 XX:XX:XX] local.INFO: تم إرسال المهمة للـ Queue {"job":"ProcessChatMessage","message_id":X}
```

#### في Terminal 3 (Queue Worker) يجب أن تشاهد:

```
[INFO] Processing: App\Jobs\ProcessChatMessage

[2025-08-14 XX:XX:XX] local.INFO: بدء معالجة رسالة الدردشة {"message_id":X,"conversation_id":Y}

[2025-08-14 XX:XX:XX] local.INFO: الداتا [{"role":"system","content":"..."},{"role":"user","content":"test debug"}]

[2025-08-14 XX:XX:XX] local.INFO: إرسال البث المباشر للرسالة {"session_id":"session-xxxxx","channel":"conversation.session-xxxxx"}

[2025-08-14 XX:XX:XX] local.INFO: تمت معالجة رسالة الدردشة بنجاح {"conversation_id":Y,"processing_time":X.XX}

[INFO] Processed: App\Jobs\ProcessChatMessage
```

#### في Terminal 2 (Reverb) يجب أن تشاهد:

```
[DEBUG] Broadcasting message to channel: conversation.session-xxxxx
[DEBUG] Event: MessageSent
[DEBUG] Clients connected to channel: 1
[DEBUG] Message sent to 1 clients
```

#### في Browser Console يجب أن تشاهد:

```javascript
Message received via WebSocket: {
  message: {
    id: X,
    content: "مرحبًا! كيف يمكنني مساعدتك اليوم؟",
    role: "assistant",
    timestamp: "2025-08-14T..."
  },
  conversation: {
    id: Y,
    session_id: "session-xxxxx"
  }
}
```

---

## 🚨 تشخيص المشاكل الشائعة

### مشكلة 1: الرسائل لا تظهر في الواجهة

#### الأعراض:
- المستخدم يكتب رسالة
- تظهر رسالة المستخدم
- لا يظهر رد الـ AI

#### التشخيص:

```bash
# 1. فحص الـ Queue Worker
ps aux | grep "queue:work"
# يجب أن تجد process يعمل

# 2. فحص آخر الرسائل في قاعدة البيانات
php artisan tinker
Message::latest()->take(5)->get(['id', 'role', 'content', 'created_at']);
exit

# 3. فحص الـ Jobs المعلقة أو الفاشلة
php artisan queue:failed
php artisan horizon:status  # إذا كنت تستخدم Horizon
```

#### الحلول المحتملة:

```bash
# حل 1: إعادة تشغيل Queue Worker
php artisan queue:restart
php artisan queue:work --verbose

# حل 2: معالجة Jobs المعلقة يدوياً
php artisan queue:work --once

# حل 3: مسح Cache والإعدادات
php artisan config:clear
php artisan cache:clear
```

### مشكلة 2: WebSocket لا يتصل

#### الأعراض:
- خطأ في Console: "WebSocket connection failed"
- لا توجد رسائل في Network tab للـ WebSocket

#### التشخيص:

```bash
# 1. فحص منفذ Reverb
netstat -an | findstr :8080

# 2. فحص إعدادات Reverb
php artisan config:show broadcasting.connections.reverb

# 3. اختبار اتصال مباشر
telnet localhost 8080
```

#### الحلول المحتملة:

```bash
# حل 1: إعادة تشغيل Reverb
php artisan reverb:restart
php artisan reverb:start --debug

# حل 2: فحص Firewall
# تأكد أن المنفذ 8080 غير محجوب

# حل 3: تغيير المنفذ
# في .env:
REVERB_PORT=8081
# ثم في JavaScript:
wsPort: 8081
```

### مشكلة 3: Ollama AI لا يرد

#### الأعراض:
- رسائل في الـ logs تقول "AI Service Error"
- timeout errors في Queue Worker

#### التشخيص:

```bash
# 1. فحص حالة Ollama
curl http://localhost:11434/api/version

# 2. فحص النموذج
ollama list

# 3. اختبار النموذج
ollama run qwen2.5-coder:latest "test"
```

#### الحلول المحتملة:

```bash
# حل 1: إعادة تشغيل Ollama
taskkill /f /im ollama.exe  # Windows
ollama serve

# حل 2: إعادة تحميل النموذج
ollama pull qwen2.5-coder:latest

# حل 3: تغيير النموذج مؤقتاً
# في config/ollama.php:
'default_model' => 'llama2',  # بدلاً من qwen2.5-coder:latest
```

### مشكلة 4: خطأ 500 في API

#### التشخيص:

```bash
# 1. فحص Laravel logs
tail -20 storage/logs/laravel.log

# 2. فحص PHP errors
# في .env:
APP_DEBUG=true
LOG_LEVEL=debug

# 3. اختبار API مباشرة
curl -X POST http://localhost:8000/api/public/chat \
  -H "Content-Type: application/json" \
  -d '{"message":"test","session_id":"debug"}'
```

#### الحلول الشائعة:

```bash
# حل 1: مسح Cache
php artisan config:clear
php artisan route:clear
php artisan view:clear

# حل 2: إعادة تثبيت Dependencies
composer install --no-dev
npm install

# حل 3: فحص أذونات الملفات
chmod -R 775 storage bootstrap/cache
```

---

## 🧪 أدوات Debug المتقدمة

### 1️⃣ إنشاء Debug Command مخصص

```bash
# إنشاء Command للتشخيص
php artisan make:command DebugChatbot
```

```php
// app/Console/Commands/DebugChatbot.php
<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Models\Conversation;
use App\Events\MessageSent;
use App\Services\OllamaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugChatbot extends Command
{
    protected $signature = 'chatbot:debug {--component=all}';
    protected $description = 'Debug chatbot components';

    public function handle()
    {
        $component = $this->option('component');
        
        $this->info('🔍 Starting Chatbot Debug...');
        
        if ($component === 'all' || $component === 'database') {
            $this->debugDatabase();
        }
        
        if ($component === 'all' || $component === 'ollama') {
            $this->debugOllama();
        }
        
        if ($component === 'all' || $component === 'websocket') {
            $this->debugWebSocket();
        }
        
        if ($component === 'all' || $component === 'queue') {
            $this->debugQueue();
        }
        
        $this->info('✅ Debug completed!');
    }
    
    private function debugDatabase()
    {
        $this->info('📊 Checking Database...');
        
        try {
            $messagesCount = Message::count();
            $conversationsCount = Conversation::count();
            
            $this->line("Messages: {$messagesCount}");
            $this->line("Conversations: {$conversationsCount}");
            
            $latestMessage = Message::latest()->first();
            if ($latestMessage) {
                $this->line("Latest message: {$latestMessage->id} - {$latestMessage->role}");
            }
            
            $this->info('✅ Database OK');
        } catch (\Exception $e) {
            $this->error('❌ Database Error: ' . $e->getMessage());
        }
    }
    
    private function debugOllama()
    {
        $this->info('🤖 Checking Ollama...');
        
        try {
            $response = Http::timeout(5)->get('http://localhost:11434/api/version');
            
            if ($response->successful()) {
                $version = $response->json()['version'] ?? 'unknown';
                $this->line("Ollama Version: {$version}");
                
                // Test AI response
                $ollamaService = new OllamaService();
                $testResponse = $ollamaService->chat([
                    ['role' => 'user', 'content' => 'test']
                ]);
                
                $this->line("AI Response: " . substr($testResponse['message']['content'], 0, 50) . "...");
                $this->info('✅ Ollama OK');
            } else {
                $this->error('❌ Ollama not responding');
            }
        } catch (\Exception $e) {
            $this->error('❌ Ollama Error: ' . $e->getMessage());
        }
    }
    
    private function debugWebSocket()
    {
        $this->info('📡 Checking WebSocket...');
        
        try {
            // Test if Reverb port is open
            $connection = @fsockopen('localhost', 8080, $errno, $errstr, 1);
            
            if ($connection) {
                $this->info('✅ WebSocket port is open');
                fclose($connection);
                
                // Test broadcasting
                $this->line('Testing broadcast...');
                $conversation = Conversation::factory()->create();
                $message = Message::factory()->create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => 'Debug test message'
                ]);
                
                broadcast(new MessageSent($message, $conversation));
                $this->info('✅ Broadcast sent');
            } else {
                $this->error('❌ WebSocket port closed');
            }
        } catch (\Exception $e) {
            $this->error('❌ WebSocket Error: ' . $e->getMessage());
        }
    }
    
    private function debugQueue()
    {
        $this->info('⚙️ Checking Queue...');
        
        try {
            // Check failed jobs
            $failedJobs = \DB::table('failed_jobs')->count();
            $this->line("Failed jobs: {$failedJobs}");
            
            if ($failedJobs > 0) {
                $this->warn("You have {$failedJobs} failed jobs. Run 'php artisan queue:failed' to see them.");
            }
            
            $this->info('✅ Queue checked');
        } catch (\Exception $e) {
            $this->error('❌ Queue Error: ' . $e->getMessage());
        }
    }
}
```

### 2️⃣ استخدام التشخيص المخصص

```bash
# تشخيص كامل
php artisan chatbot:debug

# تشخيص مكون محدد
php artisan chatbot:debug --component=database
php artisan chatbot:debug --component=ollama
php artisan chatbot:debug --component=websocket
php artisan chatbot:debug --component=queue
```

### 3️⃣ إنشاء Health Check Route

```php
// routes/web.php
Route::get('/health', function () {
    $status = [
        'database' => 'ok',
        'ollama' => 'checking',
        'websocket' => 'checking',
        'queue' => 'ok',
    ];
    
    try {
        // Test database
        \DB::connection()->getPdo();
    } catch (\Exception $e) {
        $status['database'] = 'error: ' . $e->getMessage();
    }
    
    try {
        // Test Ollama
        $response = \Http::timeout(3)->get('http://localhost:11434/api/version');
        $status['ollama'] = $response->successful() ? 'ok' : 'not responding';
    } catch (\Exception $e) {
        $status['ollama'] = 'error: ' . $e->getMessage();
    }
    
    try {
        // Test WebSocket port
        $connection = @fsockopen('localhost', 8080, $errno, $errstr, 1);
        $status['websocket'] = $connection ? 'ok' : 'port closed';
        if ($connection) fclose($connection);
    } catch (\Exception $e) {
        $status['websocket'] = 'error: ' . $e->getMessage();
    }
    
    return response()->json($status);
});
```

**الاستخدام:**
```bash
curl http://localhost:8000/health
```

---

## 📊 مراقبة الأداء والإحصائيات

### إنشاء Debug Dashboard

```bash
# إنشاء route للـ dashboard
# routes/web.php
Route::get('/debug-dashboard', function () {
    return view('debug-dashboard');
});
```

```html
<!-- resources/views/debug-dashboard.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Debug Dashboard</title>
    <meta charset="utf-8">
    <meta http-equiv="refresh" content="5">
    <style>
        body { font-family: Arial; margin: 20px; }
        .status-ok { color: green; }
        .status-error { color: red; }
        .metric { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>🔍 Debug Dashboard</h1>
    
    <div class="metric">
        <h3>Database Status</h3>
        @php
            try {
                DB::connection()->getPdo();
                echo '<span class="status-ok">✅ Connected</span>';
                $messagesCount = App\Models\Message::count();
                $conversationsCount = App\Models\Conversation::count();
                echo "<br>Messages: {$messagesCount}";
                echo "<br>Conversations: {$conversationsCount}";
            } catch (Exception $e) {
                echo '<span class="status-error">❌ Error: ' . $e->getMessage() . '</span>';
            }
        @endphp
    </div>
    
    <div class="metric">
        <h3>Latest Messages</h3>
        @php
            $latestMessages = App\Models\Message::with('conversation')
                ->latest()->take(5)->get();
        @endphp
        @foreach($latestMessages as $message)
            <div>
                <strong>{{ $message->role }}</strong>: 
                {{ Str::limit($message->content, 50) }}
                <em>({{ $message->created_at->diffForHumans() }})</em>
            </div>
        @endforeach
    </div>
    
    <div class="metric">
        <h3>Queue Status</h3>
        @php
            $failedJobs = DB::table('failed_jobs')->count();
            echo $failedJobs > 0 
                ? '<span class="status-error">❌ ' . $failedJobs . ' failed jobs</span>'
                : '<span class="status-ok">✅ No failed jobs</span>';
        @endphp
    </div>
    
    <div class="metric">
        <h3>Server Info</h3>
        <div>Laravel Version: {{ app()->version() }}</div>
        <div>PHP Version: {{ phpversion() }}</div>
        <div>Memory Usage: {{ round(memory_get_usage(true)/1024/1024, 2) }} MB</div>
        <div>Current Time: {{ now()->format('Y-m-d H:i:s') }}</div>
    </div>
    
    <div class="metric">
        <h3>Quick Actions</h3>
        <button onclick="window.location.reload()">🔄 Refresh</button>
        <button onclick="testAPI()">🧪 Test API</button>
        <button onclick="testWebSocket()">📡 Test WebSocket</button>
    </div>
    
    <script>
        function testAPI() {
            fetch('/api/public/chat', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    message: 'debug test',
                    session_id: 'debug-' + Date.now(),
                    chatbot_id: 1
                })
            })
            .then(r => r.json())
            .then(data => {
                alert('API Response: ' + JSON.stringify(data, null, 2));
            })
            .catch(error => {
                alert('API Error: ' + error.message);
            });
        }
        
        function testWebSocket() {
            const ws = new WebSocket('ws://localhost:8080/app/ai-chatbot-key');
            ws.onopen = () => alert('✅ WebSocket connected successfully');
            ws.onerror = (error) => alert('❌ WebSocket error: ' + error);
            ws.onclose = (event) => alert('🔌 WebSocket closed: ' + event.code);
        }
    </script>
</body>
</html>
```

**الوصول للـ dashboard:**
```
http://localhost:8000/debug-dashboard
```

---

## 🎯 Debug Checklist سريع

### ✅ قائمة تحقق سريعة (5 دقائق)

```bash
# 1. فحص الخدمات الأساسية
curl -I http://localhost:8000                     # Laravel
curl -I http://localhost:11434/api/version        # Ollama  
telnet localhost 8080                             # WebSocket (Ctrl+C للخروج)

# 2. فحص آخر النشاطات
tail -10 storage/logs/laravel.log                 # Logs
php artisan tinker --execute="Message::latest()->take(3)->get(['role','content','created_at'])"

# 3. اختبار سريع للAPI
curl -X POST http://localhost:8000/api/public/chat -H "Content-Type: application/json" -d '{"message":"test","session_id":"debug"}'

# 4. فحص Queue
php artisan queue:work --once

# 5. فحص إعدادات
php artisan config:show broadcasting.default
```

### 🚨 علامات المشكلة

| العرض | السبب المحتمل | الحل السريع |
|-------|-------------|------------|
| لا توجد ردود AI | Queue Worker معطل | `php artisan queue:work` |
| خطأ WebSocket | Reverb معطل | `php artisan reverb:start` |
| خطأ 500 | Laravel خطأ | فحص `storage/logs/laravel.log` |
| AI timeout | Ollama معطل | `ollama serve` |
| Database خطأ | اتصال DB | فحص `.env` والـ migration |

---

## 📞 الحصول على المساعدة

### عندما تحتاج مساعدة:

1. **اجمع المعلومات:**
   ```bash
   # احفظ هذه المعلومات
   php artisan --version
   php --version  
   curl http://localhost:11434/api/version
   tail -20 storage/logs/laravel.log
   ```

2. **ادخل على Debug Dashboard:**
   ```
   http://localhost:8000/debug-dashboard
   ```

3. **اختبر بسيناريو بسيط:**
   - اذهب لـ http://localhost:8000/demo
   - اكتب "test"
   - انتظر 30 ثانية
   - سجل النتيجة

4. **راجع التوثيق:**
   - [DEMO_DOCUMENTATION.md](DEMO_DOCUMENTATION.md)
   - [BROADCASTING_DOCUMENTATION.md](BROADCASTING_DOCUMENTATION.md)
   - [EVENTS_DOCUMENTATION.md](EVENTS_DOCUMENTATION.md)

---

*تم إنشاء هذا الدليل بواسطة GitHub Copilot - تشخيص شامل ومفصل! 🔍*
