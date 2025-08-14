# 🎪 دليل Events والأحداث - AI Chatbot SaaS

## 🌟 مفهوم Events في Laravel

Events في Laravel هي طريقة لتنظيم وفصل الأكواد المختلفة في التطبيق. بدلاً من كتابة كل الكود في مكان واحد، يمكنك "إطلاق" حدث معين، وتسمح لأجزاء أخرى من التطبيق "بالاستماع" لهذا الحدث والتفاعل معه.

---

## 🎯 دورة حياة الحدث (Event Lifecycle)

```
1. إنشاء Event Class
         ↓
2. إطلاق الحدث (fire/dispatch)
         ↓  
3. Laravel يجد جميع Listeners
         ↓
4. تنفيذ جميع Listeners بالتتابع
         ↓
5. Broadcasting (إذا كان مُعرّف)
         ↓
6. Frontend يستقبل الحدث عبر WebSocket
```

---

## 🏗️ هيكل Events في المشروع

```
app/Events/
├── MessageSent.php          # حدث إرسال الرسالة
├── ConversationStarted.php  # حدث بداية المحادثة
├── ConversationEnded.php    # حدث انتهاء المحادثة
├── UserTyping.php          # حدث كتابة المستخدم
├── SystemAlert.php         # تنبيه النظام
└── ErrorOccurred.php       # حدث حدوث خطأ

app/Listeners/
├── SendMessageNotification.php
├── UpdateConversationStats.php
├── LogChatActivity.php
└── ProcessAIResponse.php
```

---

## 📝 إنشاء Events

### 1. MessageSent Event (الحدث الرئيسي)

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
use Illuminate\Support\Facades\Log;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // البيانات الأساسية للحدث
    public Message $message;
    public Conversation $conversation;
    public array $metadata;

    /**
     * إنشاء instance جديد من الحدث
     */
    public function __construct(Message $message, Conversation $conversation, array $metadata = [])
    {
        $this->message = $message;
        $this->conversation = $conversation;
        $this->metadata = array_merge([
            'timestamp' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $metadata);

        // تسجيل إنشاء الحدث
        Log::info('MessageSent Event Created', [
            'event_id' => $this->generateEventId(),
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'session_id' => $conversation->session_id,
            'message_role' => $message->role,
            'content_length' => strlen($message->content),
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * تحديد القنوات للبث
     */
    public function broadcastOn(): array
    {
        $channels = [];
        
        // القناة الأساسية للمحادثة
        $mainChannel = 'conversation.' . $this->conversation->session_id;
        $channels[] = new Channel($mainChannel);
        
        // قناة إضافية لإحصائيات الشات بوت (إذا كانت مطلوبة)
        if ($this->conversation->chatbot) {
            $channels[] = new Channel('chatbot.' . $this->conversation->chatbot->id . '.stats');
        }
        
        // قناة عامة للإحصائيات (إذا كانت مطلوبة)
        if ($this->shouldBroadcastToGlobalStats()) {
            $channels[] = new Channel('global.chat.stats');
        }

        Log::info('Broadcasting MessageSent Event', [
            'channels' => array_map(fn($ch) => $ch->name, $channels),
            'event_name' => $this->broadcastAs(),
            'message_id' => $this->message->id,
        ]);

        return $channels;
    }

    /**
     * البيانات التي سيتم بثها
     */
    public function broadcastWith(): array
    {
        $data = [
            // بيانات الرسالة
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->content,
                'role' => $this->message->role,
                'timestamp' => $this->message->created_at->toISOString(),
                'metadata' => $this->message->metadata,
            ],
            
            // بيانات المحادثة
            'conversation' => [
                'id' => $this->conversation->id,
                'session_id' => $this->conversation->session_id,
                'chatbot_id' => $this->conversation->chatbot_id,
                'status' => $this->conversation->status,
            ],
            
            // بيانات إضافية للحدث
            'event_metadata' => $this->metadata,
            
            // إحصائيات سريعة
            'stats' => [
                'total_messages' => $this->conversation->messages()->count(),
                'response_time' => $this->calculateResponseTime(),
            ],
            
            // طابع زمني للحدث نفسه
            'event_timestamp' => now()->toISOString(),
        ];

        Log::info('Broadcasting MessageSent Data', [
            'data_size' => strlen(json_encode($data)),
            'message_id' => $this->message->id,
            'includes_stats' => isset($data['stats']),
        ]);

        return $data;
    }

    /**
     * اسم الحدث المخصص
     */
    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    /**
     * شروط البث
     */
    public function broadcastWhen(): bool
    {
        // بث الحدث فقط إذا:
        return $this->message &&                           // الرسالة موجودة
               $this->conversation &&                      // المحادثة موجودة
               !empty($this->message->content) &&          // المحتوى غير فارغ
               $this->message->role === 'assistant';       // رسالة من الـ AI فقط
    }

    /**
     * Queue للبث (لتحسين الأداء)
     */
    public function broadcastQueue(): string
    {
        return 'broadcasting';
    }

    /**
     * تأخير البث (اختياري)
     */
    public function broadcastDelay(): ?\DateTimeInterface
    {
        // تأخير لثانية واحدة لضمان حفظ البيانات في DB
        return now()->addSecond();
    }

    // === Helper Methods ===

    private function generateEventId(): string
    {
        return 'msg_sent_' . $this->message->id . '_' . time();
    }

    private function shouldBroadcastToGlobalStats(): bool
    {
        // بث للإحصائيات العامة كل 10 رسائل مثلاً
        return $this->message->id % 10 === 0;
    }

    private function calculateResponseTime(): ?float
    {
        $lastUserMessage = $this->conversation->messages()
            ->where('role', 'user')
            ->orderBy('created_at', 'desc')
            ->first();
            
        if ($lastUserMessage && $this->message->role === 'assistant') {
            return $this->message->created_at->diffInSeconds($lastUserMessage->created_at);
        }
        
        return null;
    }
}
```

### 2. ConversationStarted Event

```php
// app/Events/ConversationStarted.php
<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\Chatbot;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationStarted implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public Conversation $conversation;
    public array $context;

    public function __construct(Conversation $conversation, array $context = [])
    {
        $this->conversation = $conversation;
        $this->context = $context;

        Log::info('New Conversation Started', [
            'conversation_id' => $conversation->id,
            'session_id' => $conversation->session_id,
            'chatbot_id' => $conversation->chatbot_id,
            'context' => $context,
        ]);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('chatbot.' . $this->conversation->chatbot_id . '.conversations'),
            new Channel('global.conversations'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'conversation' => [
                'id' => $this->conversation->id,
                'session_id' => $this->conversation->session_id,
                'chatbot_id' => $this->conversation->chatbot_id,
                'started_at' => $this->conversation->created_at->toISOString(),
            ],
            'chatbot' => [
                'id' => $this->conversation->chatbot->id,
                'name' => $this->conversation->chatbot->name,
                'settings' => $this->conversation->chatbot->settings,
            ],
            'context' => $this->context,
            'timestamp' => now()->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ConversationStarted';
    }
}
```

### 3. UserTyping Event (للمؤشرات المتقدمة)

```php
// app/Events/UserTyping.php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable;

    public string $sessionId;
    public bool $isTyping;
    public array $userInfo;

    public function __construct(string $sessionId, bool $isTyping, array $userInfo = [])
    {
        $this->sessionId = $sessionId;
        $this->isTyping = $isTyping;
        $this->userInfo = $userInfo;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('conversation.' . $this->sessionId)
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->sessionId,
            'is_typing' => $this->isTyping,
            'user_info' => $this->userInfo,
            'timestamp' => now()->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'UserTyping';
    }

    // لا نريد تأخير هذا الحدث - يجب أن يكون فوري
    public function broadcastDelay(): ?\DateTimeInterface
    {
        return null;
    }
}
```

---

## 🎧 إنشاء Listeners

### 1. Event Listener أساسي

```php
// app/Listeners/LogChatActivity.php
<?php

namespace App\Listeners;

use App\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class LogChatActivity
{
    /**
     * معالجة الحدث
     */
    public function handle(MessageSent $event): void
    {
        // تسجيل النشاط
        Log::info('Chat Activity Logged', [
            'event_type' => 'message_sent',
            'message_id' => $event->message->id,
            'conversation_id' => $event->conversation->id,
            'message_role' => $event->message->role,
            'content_length' => strlen($event->message->content),
            'session_id' => $event->conversation->session_id,
            'timestamp' => now()->toISOString(),
        ]);

        // تحديث إحصائيات في Cache
        $this->updateChatStatistics($event);

        // تحديث آخر نشاط للمحادثة
        $this->updateLastActivity($event->conversation);
    }

    private function updateChatStatistics(MessageSent $event): void
    {
        $date = now()->format('Y-m-d');
        
        // عدد الرسائل اليوم
        Cache::increment("daily_messages:{$date}");
        
        // عدد رسائل الـ AI اليوم
        if ($event->message->role === 'assistant') {
            Cache::increment("daily_ai_responses:{$date}");
        }
        
        // عدد رسائل لكل شات بوت
        Cache::increment("chatbot_messages:{$event->conversation->chatbot_id}:{$date}");
    }

    private function updateLastActivity($conversation): void
    {
        $conversation->update([
            'last_activity_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
```

### 2. Listener للإشعارات

```php
// app/Listeners/SendMessageNotification.php
<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Notifications\NewMessageNotification;
use Illuminate\Support\Facades\Log;

class SendMessageNotification
{
    public function handle(MessageSent $event): void
    {
        // إرسال إشعار فقط لرسائل المستخدم
        if ($event->message->role === 'user') {
            $this->notifyAdmins($event);
        }
        
        // إرسال إشعار للمستخدم عند رد الـ AI (إذا كان خارج الصفحة)
        if ($event->message->role === 'assistant') {
            $this->notifyUserIfAway($event);
        }
    }

    private function notifyAdmins(MessageSent $event): void
    {
        // إشعار المديرين برسائل مهمة
        if ($this->isImportantMessage($event->message)) {
            // هنا يمكن إرسال بريد إلكتروني أو إشعار أداري
            Log::info('Important message detected', [
                'message_id' => $event->message->id,
                'content_preview' => substr($event->message->content, 0, 100),
            ]);
        }
    }

    private function notifyUserIfAway(MessageSent $event): void
    {
        // هنا يمكن إرسال إشعار push للمتصفح أو الجوال
        // إذا كان المستخدم غير نشط
    }

    private function isImportantMessage($message): bool
    {
        $keywords = ['مساعدة', 'مشكلة', 'خطأ', 'شكوى'];
        
        foreach ($keywords as $keyword) {
            if (str_contains($message->content, $keyword)) {
                return true;
            }
        }
        
        return false;
    }
}
```

---

## 🔗 تسجيل Events مع Listeners

### 1. في EventServiceProvider

```php
// app/Providers/EventServiceProvider.php
<?php

namespace App\Providers;

use App\Events\MessageSent;
use App\Events\ConversationStarted;
use App\Events\ConversationEnded;
use App\Events\UserTyping;
use App\Listeners\LogChatActivity;
use App\Listeners\SendMessageNotification;
use App\Listeners\UpdateConversationStats;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Event listener mappings
     */
    protected $listen = [
        // حدث إرسال الرسالة
        MessageSent::class => [
            LogChatActivity::class,
            SendMessageNotification::class,
            UpdateConversationStats::class,
        ],
        
        // حدث بداية المحادثة
        ConversationStarted::class => [
            \App\Listeners\WelcomeNewConversation::class,
            \App\Listeners\InitializeChatbotSettings::class,
        ],
        
        // حدث انتهاء المحادثة
        ConversationEnded::class => [
            \App\Listeners\SaveConversationSummary::class,
            \App\Listeners\CleanupTempData::class,
        ],
        
        // حدث الكتابة
        UserTyping::class => [
            \App\Listeners\UpdateTypingStatus::class,
        ],
    ];

    /**
     * Event subscribers (للأحداث المعقدة)
     */
    protected $subscribers = [
        \App\Listeners\ChatEventSubscriber::class,
    ];

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        parent::boot();
    }
}
```

### 2. Event Subscriber (للأحداث المعقدة)

```php
// app/Listeners/ChatEventSubscriber.php
<?php

namespace App\Listeners;

use App\Events\MessageSent;
use App\Events\ConversationStarted;
use App\Events\ConversationEnded;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class ChatEventSubscriber
{
    /**
     * معالجة أحداث الرسائل المرسلة
     */
    public function handleMessageSent(MessageSent $event): void
    {
        Log::info('ChatEventSubscriber handling MessageSent', [
            'message_id' => $event->message->id,
        ]);
        
        // تحليل المحتوى
        $this->analyzeMessageContent($event->message);
        
        // تحديث إحصائيات المحادثة
        $this->updateConversationMetrics($event->conversation);
        
        // فحص جودة الرد (إذا كان من الـ AI)
        if ($event->message->role === 'assistant') {
            $this->analyzeResponseQuality($event->message);
        }
    }

    /**
     * معالجة بداية المحادثة
     */
    public function handleConversationStarted(ConversationStarted $event): void
    {
        Log::info('New conversation started', [
            'conversation_id' => $event->conversation->id,
        ]);
        
        // إعداد إعدادات المحادثة
        $this->initializeConversationSettings($event->conversation);
        
        // إرسال رسالة ترحيب (إذا كانت مُعدة)
        $this->sendWelcomeMessage($event->conversation);
    }

    /**
     * تسجيل جميع الأحداث
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            MessageSent::class,
            [ChatEventSubscriber::class, 'handleMessageSent']
        );
        
        $events->listen(
            ConversationStarted::class,
            [ChatEventSubscriber::class, 'handleConversationStarted']
        );
        
        $events->listen(
            ConversationEnded::class,
            [ChatEventSubscriber::class, 'handleConversationEnded']
        );
    }

    // === Helper Methods ===

    private function analyzeMessageContent($message): void
    {
        // تحليل المشاعر، الكلمات المفتاحية، إلخ
        $analysis = [
            'length' => strlen($message->content),
            'word_count' => str_word_count($message->content),
            'contains_question' => str_contains($message->content, '؟'),
            'language' => $this->detectLanguage($message->content),
        ];
        
        // حفظ التحليل في metadata
        $message->update([
            'metadata' => array_merge($message->metadata ?? [], [
                'analysis' => $analysis
            ])
        ]);
    }

    private function updateConversationMetrics($conversation): void
    {
        $stats = [
            'total_messages' => $conversation->messages()->count(),
            'user_messages' => $conversation->messages()->where('role', 'user')->count(),
            'ai_messages' => $conversation->messages()->where('role', 'assistant')->count(),
            'last_activity' => now(),
        ];
        
        $conversation->update([
            'metadata' => array_merge($conversation->metadata ?? [], ['stats' => $stats])
        ]);
    }

    private function analyzeResponseQuality($message): void
    {
        // فحص جودة رد الـ AI
        $quality = [
            'length_appropriate' => strlen($message->content) > 10 && strlen($message->content) < 2000,
            'contains_arabic' => preg_match('/[\x{0600}-\x{06FF}]/u', $message->content),
            'response_time' => $this->calculateResponseTime($message),
        ];
        
        Log::info('AI Response Quality Analysis', [
            'message_id' => $message->id,
            'quality_metrics' => $quality,
        ]);
    }

    private function detectLanguage($text): string
    {
        // كشف اللغة بطريقة بسيطة
        $arabicChars = preg_match_all('/[\x{0600}-\x{06FF}]/u', $text);
        $englishChars = preg_match_all('/[a-zA-Z]/', $text);
        
        return $arabicChars > $englishChars ? 'ar' : 'en';
    }

    private function calculateResponseTime($message)
    {
        $conversation = $message->conversation;
        $previousMessage = $conversation->messages()
            ->where('id', '<', $message->id)
            ->orderBy('id', 'desc')
            ->first();
            
        if ($previousMessage) {
            return $message->created_at->diffInSeconds($previousMessage->created_at);
        }
        
        return null;
    }
}
```

---

## 🚀 إطلاق الأحداث (Dispatching Events)

### 1. إطلاق مباشر
```php
// في Controller أو Job
use App\Events\MessageSent;

// طريقة Laravel التقليدية
event(new MessageSent($message, $conversation));

// أو باستخدام broadcast helper
broadcast(new MessageSent($message, $conversation));

// أو باستخدام Event facade
Event::dispatch(new MessageSent($message, $conversation));
```

### 2. إطلاق مشروط
```php
// في ProcessChatMessage Job
public function handle(): void
{
    // ... معالجة الرسالة
    
    // بث الحدث فقط إذا كانت الرسالة صالحة
    if ($this->shouldBroadcastMessage($botMessage)) {
        broadcast(new MessageSent($botMessage, $this->conversation, [
            'processing_time' => microtime(true) - $startTime,
            'ai_model' => 'qwen2.5-coder:latest',
            'confidence' => $this->calculateConfidence($botMessage),
        ]));
    }
}

private function shouldBroadcastMessage($message): bool
{
    return !empty($message->content) && 
           strlen($message->content) > 5 &&
           !str_contains($message->content, 'خطأ');
}

private function calculateConfidence($message): float
{
    // حساب مستوى الثقة في الرد (مثال بسيط)
    $length = strlen($message->content);
    
    if ($length < 10) return 0.3;
    if ($length < 50) return 0.6;
    if ($length < 200) return 0.8;
    
    return 0.9;
}
```

### 3. إطلاق متأخر
```php
// إطلاق الحدث بعد 5 ثواني
broadcast(new MessageSent($message, $conversation))->later(now()->addSeconds(5));

// أو تحديد وقت محدد
$scheduledTime = now()->addMinutes(1);
broadcast(new MessageSent($message, $conversation))->later($scheduledTime);
```

### 4. إطلاق لمجموعة محددة
```php
// بث لجميع المتصلين ما عدا المرسل
broadcast(new MessageSent($message, $conversation))->toOthers();

// بث لقناة خاصة
broadcast(new MessageSent($message, $conversation))->toChannel('private-admin');
```

---

## 🧪 اختبار Events

### 1. اختبار إطلاق الأحداث

```php
// tests/Feature/EventsTest.php
<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EventsTest extends TestCase
{
    public function test_message_sent_event_is_fired()
    {
        Event::fake([MessageSent::class]);
        
        $conversation = Conversation::factory()->create();
        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
        ]);
        
        // إطلاق الحدث
        broadcast(new MessageSent($message, $conversation));
        
        // التحقق من إطلاق الحدث
        Event::assertDispatched(MessageSent::class, function ($event) use ($message) {
            return $event->message->id === $message->id;
        });
    }
    
    public function test_message_sent_event_has_correct_data()
    {
        $conversation = Conversation::factory()->create();
        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'content' => 'مرحبًا بك!',
            'role' => 'assistant',
        ]);
        
        $event = new MessageSent($message, $conversation);
        
        // فحص القنوات
        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertEquals(
            'conversation.' . $conversation->session_id,
            $channels[0]->name
        );
        
        // فحص البيانات
        $data = $event->broadcastWith();
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('conversation', $data);
        $this->assertEquals($message->content, $data['message']['content']);
        $this->assertEquals($message->role, $data['message']['role']);
    }
    
    public function test_event_broadcast_conditions()
    {
        $conversation = Conversation::factory()->create();
        
        // رسالة صحيحة - يجب البث
        $validMessage = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'content' => 'رد صحيح من الذكاء الاصطناعي',
            'role' => 'assistant',
        ]);
        
        $event = new MessageSent($validMessage, $conversation);
        $this->assertTrue($event->broadcastWhen());
        
        // رسالة فارغة - لا يجب البث
        $emptyMessage = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'content' => '',
            'role' => 'assistant',
        ]);
        
        $eventEmpty = new MessageSent($emptyMessage, $conversation);
        $this->assertFalse($eventEmpty->broadcastWhen());
        
        // رسالة مستخدم - لا يجب البث
        $userMessage = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'content' => 'رسالة من المستخدم',
            'role' => 'user',
        ]);
        
        $eventUser = new MessageSent($userMessage, $conversation);
        $this->assertFalse($eventUser->broadcastWhen());
    }
}
```

### 2. اختبار Listeners

```php
// tests/Feature/ListenersTest.php
<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Listeners\LogChatActivity;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ListenersTest extends TestCase
{
    public function test_log_chat_activity_listener()
    {
        Log::spy();
        
        $conversation = Conversation::factory()->create();
        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
        ]);
        
        $event = new MessageSent($message, $conversation);
        $listener = new LogChatActivity();
        
        // تنفيذ الـ listener
        $listener->handle($event);
        
        // التحقق من تسجيل النشاط
        Log::shouldHaveReceived('info')
            ->with('Chat Activity Logged', \Mockery::type('array'))
            ->once();
    }
}
```

---

## 🔍 Debug وتشخيص Events

### 1. تسجيل الأحداث في Log

```php
// في Event class
public function __construct(Message $message, Conversation $conversation)
{
    $this->message = $message;
    $this->conversation = $conversation;
    
    // تسجيل تفصيلي للحدث
    Log::debug('Event Created: MessageSent', [
        'event_class' => self::class,
        'message_id' => $message->id,
        'message_content' => substr($message->content, 0, 100),
        'message_role' => $message->role,
        'conversation_id' => $conversation->id,
        'session_id' => $conversation->session_id,
        'chatbot_id' => $conversation->chatbot_id,
        'created_at' => now()->toISOString(),
        'memory_usage' => memory_get_usage(true),
        'execution_time' => microtime(true),
    ]);
}
```

### 2. مراقبة الأداء

```php
// في Event class
public function broadcastWith(): array
{
    $startTime = microtime(true);
    $memoryBefore = memory_get_usage(true);
    
    $data = [
        // ... البيانات الأساسية
    ];
    
    $executionTime = microtime(true) - $startTime;
    $memoryUsed = memory_get_usage(true) - $memoryBefore;
    
    Log::info('Event Broadcasting Performance', [
        'event' => self::class,
        'execution_time' => $executionTime,
        'memory_used' => $memoryUsed,
        'data_size' => strlen(json_encode($data)),
        'message_id' => $this->message->id,
    ]);
    
    return $data;
}
```

### 3. Event Debugging Command

```php
// app/Console/Commands/DebugEventsCommand.php
<?php

namespace App\Console\Commands;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;

class DebugEventsCommand extends Command
{
    protected $signature = 'events:debug {--test-broadcast}';
    protected $description = 'Debug Events and Broadcasting';

    public function handle()
    {
        $this->info('🔍 Event Debugging Started');
        
        if ($this->option('test-broadcast')) {
            $this->testBroadcast();
        }
        
        $this->listRegisteredEvents();
        $this->testEventCreation();
        
        $this->info('✅ Event Debugging Completed');
    }
    
    private function testBroadcast()
    {
        $this->info('📡 Testing Broadcast...');
        
        $conversation = Conversation::factory()->create();
        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Test broadcast message at ' . now(),
        ]);
        
        broadcast(new MessageSent($message, $conversation));
        
        $this->info("✅ Broadcast sent for message ID: {$message->id}");
        $this->info("📺 Channel: conversation.{$conversation->session_id}");
    }
    
    private function listRegisteredEvents()
    {
        $this->info('📋 Registered Events:');
        
        $events = app('events')->getListeners();
        
        foreach ($events as $eventName => $listeners) {
            if (str_contains($eventName, 'App\Events')) {
                $this->line("  🎪 {$eventName}");
                foreach ($listeners as $listener) {
                    $listenerName = is_string($listener) ? $listener : get_class($listener);
                    $this->line("    🎧 {$listenerName}");
                }
            }
        }
    }
    
    private function testEventCreation()
    {
        $this->info('🧪 Testing Event Creation...');
        
        $conversation = Conversation::factory()->create();
        $message = Message::factory()->create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Test event creation',
        ]);
        
        $event = new MessageSent($message, $conversation);
        
        $this->info("✅ Event created successfully");
        $this->info("📺 Channels: " . implode(', ', array_map(fn($ch) => $ch->name, $event->broadcastOn())));
        $this->info("🎭 Event Name: " . $event->broadcastAs());
        $this->info("✅ Broadcast When: " . ($event->broadcastWhen() ? 'Yes' : 'No'));
    }
}
```

---

## ⚡ تحسين الأداء

### 1. Queue Events

```php
// في Event class
class MessageSent implements ShouldBroadcast, ShouldQueue
{
    use Queueable;
    
    public $queue = 'events';        // Queue منفصل للأحداث
    public $delay = 1;               // تأخير ثانية واحدة
    public $tries = 3;               // إعادة المحاولة 3 مرات
    public $backoff = [1, 5, 10];    // فترات إعادة المحاولة
    
    // معالجة فشل الـ Queue
    public function failed(\Throwable $exception): void
    {
        Log::error('Event broadcasting failed', [
            'event' => self::class,
            'message_id' => $this->message->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
```

### 2. تحسين البيانات المُرسلة

```php
public function broadcastWith(): array
{
    // إرسال البيانات المطلوبة فقط
    return [
        'message' => $this->message->only(['id', 'content', 'role', 'created_at']),
        'conversation' => $this->conversation->only(['id', 'session_id']),
        'timestamp' => now()->toISOString(),
        // تجنب إرسال البيانات الكبيرة أو الحساسة
    ];
}
```

### 3. Event Caching

```php
use Illuminate\Support\Facades\Cache;

public function broadcastWith(): array
{
    $cacheKey = "message_broadcast_data:{$this->message->id}";
    
    return Cache::remember($cacheKey, 300, function () {
        return [
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->content,
                'role' => $this->message->role,
                'timestamp' => $this->message->created_at->toISOString(),
            ],
            'conversation' => [
                'id' => $this->conversation->id,
                'session_id' => $this->conversation->session_id,
            ],
            'cached_at' => now()->toISOString(),
        ];
    });
}
```

---

## 🎯 أفضل الممارسات

### 1. تنظيم Events
- **اجعل Events بسيطة**: فقط البيانات الضرورية
- **استخدم أسماء واضحة**: `MessageSent` أفضل من `MessageEvent`
- **وثق الـ Events**: اكتب تعليقات واضحة
- **فصل المسؤوليات**: لا تضع منطق معقد في Event

### 2. Broadcasting
- **قلل حجم البيانات**: أرسل ما تحتاجه فقط
- **استخدم الـ Queue**: للأداء الأفضل
- **اختبر الاتصالات**: تأكد من WebSocket يعمل
- **معالجة الأخطاء**: دائماً اكتب معالجة للأخطاء

### 3. Listeners
- **اجعلها سريعة**: لا تؤخر العملية الأساسية
- **معالجة الأخطاء**: لا تدع Listener يفشل العملية كلها
- **استخدم Queue**: للعمليات الطويلة
- **اختبرها**: اكتب tests للـ Listeners

---

*تم إنشاء هذا التوثيق بواسطة GitHub Copilot - دليل شامل للـ Events! 🎪*
