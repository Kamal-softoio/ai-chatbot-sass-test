# 📡 دليل Broadcasting والـ WebSocket - AI Chatbot SaaS

## 🌐 نظرة عامة على Broadcasting

Broadcasting في Laravel هو نظام يسمح بإرسال الأحداث (Events) من الـ Backend للـ Frontend بشكل مباشر وفوري باستخدام WebSocket أو Server-Sent Events.

---

## 🏗️ Architecture العامة

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Laravel App   │    │   Reverb Server  │    │   Frontend JS   │
│   (Backend)     │────│   (WebSocket)    │────│   (Browser)     │
│   Port: 8000    │    │   Port: 8080     │    │   WebSocket     │
└─────────────────┘    └──────────────────┘    └─────────────────┘
        │                       │                       │
        │ 1. broadcast()        │ 2. Push to clients   │ 3. Listen & Handle
        │ Event + Data          │ via WebSocket         │ Update UI
        ▼                       ▼                       ▼
   ProcessChatMessage ────── MessageSent Event ────── ChatbotWidget.js
```

---

## 🔧 إعداد Broadcasting Driver

### 1. تكوين Broadcasting
```php
// config/broadcasting.php
'default' => env('BROADCAST_DRIVER', 'reverb'),

'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        'key' => env('REVERB_APP_KEY', 'ai-chatbot-key'),
        'secret' => env('REVERB_APP_SECRET', 'ai-chatbot-secret'),
        'app_id' => env('REVERB_APP_ID', '1'),
        'options' => [
            'host' => env('REVERB_HOST', '0.0.0.0'),
            'port' => env('REVERB_PORT', 8080),
            'scheme' => env('REVERB_SCHEME', 'http'),
            'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
        ],
        'client_options' => [
            // إعدادات إضافية للعميل
        ],
        'scaling' => [
            'enabled' => env('REVERB_SCALING_ENABLED', false),
            'channel' => env('REVERB_SCALING_CHANNEL', 'reverb'),
        ],
        'pulse' => [
            'ingest' => env('REVERB_PULSE_INGEST', 'enabled'),
        ],
    ],
],
```

### 2. متغيرات البيئة (.env)
```bash
# Broadcasting Settings
BROADCAST_DRIVER=reverb
REVERB_APP_KEY=ai-chatbot-key
REVERB_APP_SECRET=ai-chatbot-secret
REVERB_APP_ID=1
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http

# Queue Settings (مطلوب للـ Broadcasting)
QUEUE_CONNECTION=sync
```

---

## 📢 إنشاء الأحداث (Events)

### 1. Event Class Structure
```php
// app/Events/MessageSent.php
<?php

namespace App\Events;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;
    public Conversation $conversation;

    public function __construct(Message $message, Conversation $conversation)
    {
        $this->message = $message;
        $this->conversation = $conversation;
    }

    // تحديد القناة/القنوات للبث
    public function broadcastOn(): array
    {
        return [
            new Channel('conversation.' . $this->conversation->session_id)
        ];
    }

    // البيانات التي سيتم بثها
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->content,
                'role' => $this->message->role,
                'timestamp' => $this->message->created_at->toISOString(),
                'metadata' => $this->message->metadata,
            ],
            'conversation' => [
                'id' => $this->conversation->id,
                'session_id' => $this->conversation->session_id,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }

    // اسم الحدث (اختياري)
    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    // شروط البث (اختياري)
    public function broadcastWhen(): bool
    {
        return $this->message && $this->conversation;
    }
}
```

### 2. أنواع القنوات المختلفة

#### أ) Public Channels
```php
// قناة عامة - أي شخص يمكنه الاشتراك
public function broadcastOn(): array
{
    return [
        new Channel('public-chat')
    ];
}
```

#### ب) Private Channels
```php
// قناة خاصة - تحتاج authorization
public function broadcastOn(): array
{
    return [
        new PrivateChannel('private-chat.' . $this->userId)
    ];
}
```

#### ج) Presence Channels
```php
// قناة مع معلومات المستخدمين المتصلين
public function broadcastOn(): array
{
    return [
        new PresenceChannel('presence-chat-room.' . $this->roomId)
    ];
}
```

---

## 🚀 إطلاق الأحداث (Broadcasting Events)

### 1. من داخل Job
```php
// app/Jobs/ProcessChatMessage.php
public function handle(): void
{
    // ... معالجة الرسالة ...

    // إنشاء رد الـ AI
    $botMessage = Message::create([
        'conversation_id' => $this->conversation->id,
        'content' => $aiResponse['message']['content'],
        'role' => 'assistant',
        'metadata' => ['model' => $aiResponse['model']]
    ]);

    // بث الحدث
    broadcast(new MessageSent($botMessage, $this->conversation));

    // أو بث متأخر
    // broadcast(new MessageSent($botMessage, $this->conversation))->later(now()->addSeconds(1));

    // أو بث لأشخاص محددين فقط
    // broadcast(new MessageSent($botMessage, $this->conversation))->toOthers();
}
```

### 2. من داخل Controller
```php
public function sendMessage(Request $request)
{
    // ... حفظ الرسالة ...

    // بث فوري
    event(new MessageSent($message, $conversation));

    // أو
    broadcast(new MessageSent($message, $conversation));

    return response()->json(['success' => true]);
}
```

### 3. بث شرطي
```php
// بث فقط إذا تحقق شرط معين
if ($message->role === 'assistant' && $message->content) {
    broadcast(new MessageSent($message, $conversation));
}
```

---

## 🖥️ Frontend Integration

### 1. إعداد Laravel Echo
```javascript
// resources/js/components/ChatbotWidget.js

// إعداد Echo
initWebSocket() {
    this.echo = new Echo({
        broadcaster: 'reverb',
        key: 'ai-chatbot-key',           // نفس REVERB_APP_KEY
        wsHost: 'localhost',             // أو عنوان السيرفر
        wsPort: 8080,                    // نفس REVERB_PORT
        wssPort: 8080,                   // للـ HTTPS
        forceTLS: false,                 // true للـ production
        enabledTransports: ['ws', 'wss'],
        auth: {
            headers: {
                Authorization: `Bearer ${token}` // إذا كان مطلوب authentication
            }
        }
    });
}
```

### 2. الاشتراك في القنوات
```javascript
// قناة عامة
subscribeToPublicChannel() {
    this.echo.channel('public-notifications')
        .listen('NotificationSent', (event) => {
            console.log('Public notification:', event);
            this.showNotification(event.message);
        });
}

// قناة خاصة بالمحادثة
subscribeToConversation() {
    const channelName = `conversation.${this.sessionId}`;
    
    this.channel = this.echo.channel(channelName);
    
    this.channel.listen('MessageSent', (event) => {
        console.log('Message received:', event);
        this.handleNewMessage(event.message);
    });

    // معالجة أخطاء الاتصال
    this.channel.error((error) => {
        console.error('WebSocket error:', error);
        this.handleConnectionError(error);
    });
}

// قناة خاصة (تحتاج authorization)
subscribeToPrivateChannel() {
    this.echo.private(`private-chat.${this.userId}`)
        .listen('PrivateMessageSent', (event) => {
            console.log('Private message:', event);
        });
}

// قناة Presence (مع معلومات المستخدمين)
subscribeToPresenceChannel() {
    this.echo.join(`presence-chat-room.${this.roomId}`)
        .here((users) => {
            console.log('Users currently online:', users);
        })
        .joining((user) => {
            console.log('User joined:', user);
        })
        .leaving((user) => {
            console.log('User left:', user);
        })
        .listen('MessageSent', (event) => {
            console.log('Room message:', event);
        });
}
```

### 3. إدارة دورة حياة الاتصال
```javascript
class ChatbotWidget {
    constructor() {
        this.echo = null;
        this.channel = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectInterval = 3000;
    }

    // بدء الاتصال
    connect() {
        try {
            this.initWebSocket();
            this.subscribeToEvents();
            console.log('WebSocket connected successfully');
        } catch (error) {
            console.error('WebSocket connection failed:', error);
            this.scheduleReconnect();
        }
    }

    // إعادة الاتصال التلقائي
    scheduleReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            
            setTimeout(() => {
                console.log(`Reconnecting... Attempt ${this.reconnectAttempts}`);
                this.connect();
            }, this.reconnectInterval * this.reconnectAttempts);
        } else {
            console.error('Max reconnection attempts reached');
            this.showConnectionError();
        }
    }

    // قطع الاتصال بشكل نظيف
    disconnect() {
        if (this.channel) {
            this.echo.leaveChannel(`conversation.${this.sessionId}`);
        }
        
        if (this.echo) {
            this.echo.disconnect();
        }
        
        console.log('WebSocket disconnected');
    }

    // معالجة الأحداث
    subscribeToEvents() {
        this.channel = this.echo.channel(`conversation.${this.sessionId}`);
        
        // رسالة جديدة
        this.channel.listen('MessageSent', (event) => {
            this.handleMessageReceived(event);
        });

        // حالة الكتابة
        this.channel.listen('UserTyping', (event) => {
            this.showTypingIndicator(event.user);
        });

        // أحداث أخرى...
    }

    handleMessageReceived(event) {
        // إضافة الرسالة للواجهة
        this.addMessage(event.message.content, event.message.role);
        
        // تحديث آخر نشاط
        this.updateLastActivity();
        
        // إشعار صوتي (اختياري)
        if (this.soundEnabled) {
            this.playNotificationSound();
        }
        
        // إشعار المتصفح (اختياري)
        if (!document.hasFocus()) {
            this.showBrowserNotification(event.message.content);
        }
    }
}
```

---

## 🔍 تشخيص مشاكل Broadcasting

### 1. مشاكل شائعة وحلولها

#### مشكلة: الأحداث لا تصل للـ Frontend
```php
// التحقق من إعدادات Broadcasting
php artisan config:cache
php artisan config:show broadcasting

// التحقق من أن الحدث يتم بثه
Log::info('Broadcasting event', ['event' => class_basename($event)]);
broadcast(new MessageSent($message, $conversation));
```

#### مشكلة: WebSocket لا يتصل
```javascript
// تفحص الاتصال
console.log('Echo config:', {
    broadcaster: 'reverb',
    key: 'ai-chatbot-key',
    wsHost: 'localhost',
    wsPort: 8080
});

// اختبار الاتصال مباشرة
const ws = new WebSocket('ws://localhost:8080/app/ai-chatbot-key');
ws.onopen = () => console.log('Direct WebSocket connected');
ws.onerror = (error) => console.error('Direct WebSocket error:', error);
```

#### مشكلة: القنوات الخاطئة
```php
// التأكد من أسماء القنوات
public function broadcastOn(): array
{
    $channelName = 'conversation.' . $this->conversation->session_id;
    Log::info('Broadcasting on channel', ['channel' => $channelName]);
    
    return [new Channel($channelName)];
}
```

### 2. أدوات التشخيص

#### Reverb Debug Mode
```bash
# تشغيل Reverb مع تفاصيل التشخيص
php artisan reverb:start --debug --host=0.0.0.0 --port=8080
```

#### Browser Network Tab
```
1. افتح Developer Tools
2. اذهب لتاب Network  
3. فلتر على WebSocket (WS)
4. راقب الرسائل المرسلة والمستقبلة
```

#### Laravel Telescope (للتطوير)
```bash
# تثبيت Telescope
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate

# مراقبة الأحداث في /telescope/broadcasting
```

---

## ⚡ تحسين الأداء

### 1. Queue Broadcasting
```php
// بث الأحداث عبر Queue للأداء الأفضل
class MessageSent implements ShouldBroadcast, ShouldQueue
{
    use Queueable;

    public $queue = 'broadcasting'; // queue منفصل للبث

    // تأخير البث (اختياري)
    public $delay = 1; // ثانية واحدة

    // إعادة المحاولة
    public $tries = 3;
}
```

### 2. تحسين حجم البيانات
```php
public function broadcastWith(): array
{
    return [
        // بث البيانات المطلوبة فقط
        'message' => $this->message->only(['id', 'content', 'role', 'created_at']),
        'conversation_id' => $this->conversation->id,
        // تجنب بث البيانات الكبيرة أو الحساسة
    ];
}
```

### 3. Connection Pooling
```php
// config/broadcasting.php
'reverb' => [
    'driver' => 'reverb',
    // ... الإعدادات الأخرى
    'client_options' => [
        'max_frame_size' => 2000000, // 2MB
        'max_message_size' => 2000000,
    ],
    'scaling' => [
        'enabled' => true, // للتوسع
        'channel' => 'reverb-scaling',
    ],
],
```

---

## 🔐 الأمان والحماية

### 1. Authorization للقنوات الخاصة
```php
// routes/channels.php
Broadcast::channel('private-chat.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('conversation.{sessionId}', function ($user, $sessionId) {
    // التحقق من أن المستخدم يملك هذه الجلسة
    return $user->sessions()->where('session_id', $sessionId)->exists();
});
```

### 2. تشفير البيانات الحساسة
```php
public function broadcastWith(): array
{
    return [
        'message' => [
            'id' => $this->message->id,
            'content' => $this->shouldEncrypt() ? encrypt($this->message->content) : $this->message->content,
            'role' => $this->message->role,
        ],
    ];
}

private function shouldEncrypt(): bool
{
    return $this->message->metadata['sensitive'] ?? false;
}
```

### 3. Rate Limiting
```php
// config/broadcasting.php
'reverb' => [
    // ... إعدادات أخرى
    'options' => [
        'max_connections' => 1000,        // حد أقصى للاتصالات
        'max_frame_size' => 1000000,      // حد أقصى لحجم الرسالة
        'heartbeat_interval' => 60,       // فحص الاتصال كل 60 ثانية
    ],
],
```

---

## 📊 مراقبة ومتابعة النظام

### 1. Logging الأحداث
```php
// في Event class
public function __construct(Message $message, Conversation $conversation)
{
    $this->message = $message;
    $this->conversation = $conversation;
    
    // تسجيل إنشاء الحدث
    Log::info('MessageSent Event Created', [
        'message_id' => $message->id,
        'conversation_id' => $conversation->id,
        'session_id' => $conversation->session_id,
        'content_length' => strlen($message->content),
    ]);
}

public function broadcastOn(): array
{
    $channel = 'conversation.' . $this->conversation->session_id;
    
    // تسجيل القناة
    Log::info('Broadcasting MessageSent', [
        'channel' => $channel,
        'event' => 'MessageSent',
        'timestamp' => now()->toISOString(),
    ]);
    
    return [new Channel($channel)];
}
```

### 2. Metrics وإحصائيات
```php
// في ProcessChatMessage Job
public function handle(): void
{
    $startTime = microtime(true);
    
    // ... معالجة الرسالة
    
    // بث الحدث
    broadcast(new MessageSent($botMessage, $this->conversation));
    
    $processingTime = microtime(true) - $startTime;
    
    // تسجيل إحصائيات الأداء
    Log::info('Message processing completed', [
        'processing_time' => $processingTime,
        'conversation_id' => $this->conversation->id,
        'message_length' => strlen($botMessage->content),
        'broadcast_sent' => true,
    ]);
}
```

### 3. Health Check للـ WebSocket
```php
// في Controller أو Command
public function checkWebSocketHealth()
{
    try {
        // اختبار بث تجريبي
        broadcast(new TestEvent('Health check at ' . now()));
        
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now(),
            'reverb_host' => config('broadcasting.connections.reverb.options.host'),
            'reverb_port' => config('broadcasting.connections.reverb.options.port'),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'unhealthy',
            'error' => $e->getMessage(),
            'timestamp' => now(),
        ], 500);
    }
}
```

---

## 🧪 اختبار Broadcasting

### 1. اختبار الأحداث
```php
// tests/Feature/BroadcastingTest.php
use Illuminate\Support\Facades\Event;

public function test_message_sent_event_is_broadcasted()
{
    Event::fake([MessageSent::class]);
    
    $message = Message::factory()->create();
    $conversation = $message->conversation;
    
    broadcast(new MessageSent($message, $conversation));
    
    Event::assertDispatched(MessageSent::class, function ($event) use ($message) {
        return $event->message->id === $message->id;
    });
}

public function test_broadcast_data_structure()
{
    $message = Message::factory()->create();
    $conversation = $message->conversation;
    
    $event = new MessageSent($message, $conversation);
    $data = $event->broadcastWith();
    
    $this->assertArrayHasKey('message', $data);
    $this->assertArrayHasKey('conversation', $data);
    $this->assertEquals($message->id, $data['message']['id']);
}
```

### 2. اختبار Frontend
```javascript
// في تطبيق الاختبار
describe('WebSocket Broadcasting', () => {
    let echo, channel;
    
    beforeEach(() => {
        echo = new Echo({
            broadcaster: 'reverb',
            key: 'test-key',
            wsHost: 'localhost',
            wsPort: 8080,
            forceTLS: false
        });
    });
    
    afterEach(() => {
        if (echo) echo.disconnect();
    });
    
    test('can connect to WebSocket', (done) => {
        echo.connector.socket.on('connect', () => {
            expect(echo.connector.socket.connected).toBe(true);
            done();
        });
    });
    
    test('can receive MessageSent event', (done) => {
        channel = echo.channel('test-conversation');
        
        channel.listen('MessageSent', (event) => {
            expect(event.message).toBeDefined();
            expect(event.message.content).toBeDefined();
            done();
        });
        
        // محاكاة إرسال الحدث من Backend
        // يجب أن يكون لديك endpoint للاختبار
    });
});
```

---

## 🎯 أفضل الممارسات

### 1. تنظيم الأحداث
```php
// تجميع الأحداث المتشابهة في مجلدات
app/Events/
├── Chat/
│   ├── MessageSent.php
│   ├── UserTyping.php
│   └── ConversationEnded.php
├── Notifications/
│   ├── SystemAlert.php
│   └── UserMention.php
└── System/
    ├── ServerStatus.php
    └── MaintenanceMode.php
```

### 2. استخدام Event Subscribers
```php
// app/Listeners/ChatEventSubscriber.php
class ChatEventSubscriber
{
    public function handleMessageSent(MessageSent $event): void
    {
        // معالجة إضافية للرسالة
        $this->updateUserActivity($event->conversation);
        $this->checkForMentions($event->message);
    }
    
    public function subscribe($events): void
    {
        $events->listen(MessageSent::class, [ChatEventSubscriber::class, 'handleMessageSent']);
    }
}
```

### 3. Error Handling شامل
```php
public function handle(): void
{
    try {
        // ... معالجة الرسالة
        
        broadcast(new MessageSent($botMessage, $this->conversation));
        
    } catch (BroadcastException $e) {
        Log::error('Broadcasting failed', [
            'error' => $e->getMessage(),
            'message_id' => $this->message->id,
        ]);
        
        // محاولة إعادة البث
        $this->retryBroadcast();
        
    } catch (\Exception $e) {
        Log::error('Unexpected error in message processing', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        throw $e; // إعادة إثارة الخطأ للـ Queue system
    }
}
```

---

*تم إنشاء هذا التوثيق بواسطة GitHub Copilot - دليل شامل للـ Broadcasting! 📡*
