<?php
// app/Http/Controllers/Api/PublicChatController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chatbot;
use App\Models\Conversation;
use App\Jobs\ProcessChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PublicChatController extends Controller
{
    /**
     * إرسال رسالة للروبوت
     */
    public function chat(Request $request, string $widgetId)
    {
        // التحقق من صحة البيانات
        $request->validate([
            'message' => 'required|string|max:2000',
            'session_id' => 'nullable|string|max:100',
        ]);

        // البحث عن الروبوت
        $chatbot = Chatbot::where('widget_id', $widgetId)
            // ->where('is_public', true)
            // ->where('status', 'active')
            ->first();

        if (!$chatbot) {
            return response()->json([
                'error' => 'الروبوت غير متاح أو غير مفعل'
            ], 404);
        }

        // التحقق من حدود المستأجر
        if (!$chatbot->tenant->canSendMessage()) {
            return response()->json([
                'error' => 'تم تجاوز الحد المسموح من الرسائل هذا الشهر'
            ], 429);
        }

        // تحديد معرف الجلسة
        $sessionId = $request->session_id ?: Str::uuid();
        $userIdentifier = $request->ip();

        // تطبيق Rate Limiting (5 رسائل في الدقيقة لكل IP)
        $rateLimitKey = "chat_rate_limit:{$userIdentifier}";
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return response()->json([
                'error' => "تم تجاوز الحد المسموح. حاول مرة أخرى خلال {$seconds} ثانية"
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 60); // دقيقة واحدة

        // العثور على المحادثة أو إنشاء واحدة جديدة
        $conversation = Conversation::where('session_id', $sessionId)
            ->where('chatbot_id', $chatbot->id)
            ->where('is_active', true)
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'tenant_id' => $chatbot->tenant_id,
                'chatbot_id' => $chatbot->id,
                'session_id' => $sessionId,
                'user_identifier' => $userIdentifier,
                'last_activity' => now(),
                'is_active' => true,
            ]);
        }

        // إنشاء رسالة المستخدم
        $userMessage = $conversation->messages()->create([
            'content' => $request->message,
            'role' => 'user',
            'metadata' => [
                'ip_address' => $userIdentifier,
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toISOString(),
            ],
        ]);

        // وضع الرسالة في الطابور للمعالجة
        ProcessChatMessage::dispatch($conversation, $userMessage);

        // تحديث حالة المحادثة لإظهار أن الروبوت "يكتب"
        Cache::put("conversation_status_{$sessionId}", 'processing', 300);

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'session_id' => $sessionId,
            'message' => 'تم إرسال رسالتك. سيرد الروبوت قريباً...',
            'status' => 'processing'
        ]);
    }

    /**
     * الحصول على حالة المحادثة والرد الجديد
     */
    public function getResponse(Request $request, string $widgetId, string $sessionId)
    {
        // التحقق من وجود الروبوت
        $chatbot = Chatbot::where('widget_id', $widgetId)
            // ->where('is_public', true)
            // ->where('status', 'active')
            ->first();

        if (!$chatbot) {
            return response()->json(['error' => 'الروبوت غير متاح'], 404);
        }

        // الحصول على حالة المحادثة
        $status = Cache::get("conversation_status_{$sessionId}", 'unknown');

        // الحصول على الرد إذا كان جاهزاً
        $response = Cache::get("chat_response_{$sessionId}");

        if ($response) {
            // حذف الرد من الكاش بعد إرساله
            Cache::forget("chat_response_{$sessionId}");
            Cache::forget("conversation_status_{$sessionId}");
            
            return response()->json([
                'success' => true,
                'status' => 'completed',
                'response' => $response
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => $status,
            'message' => $this->getStatusMessage($status)
        ]);
    }

    /**
     * الحصول على تاريخ المحادثة
     */
    public function getHistory(string $widgetId, string $sessionId)
    {
        $chatbot = Chatbot::where('widget_id', $widgetId)
            // ->where('is_public', true)
            // ->where('status', 'active')
            ->first();

        if (!$chatbot) {
            return response()->json(['error' => 'الروبوت غير متاح'], 404);
        }

        $conversation = Conversation::where('session_id', $sessionId)
            ->where('chatbot_id', $chatbot->id)
            ->first();

        if (!$conversation) {
            return response()->json([
                'messages' => [],
                'chatbot_name' => $chatbot->name,
                'session_id' => $sessionId
            ]);
        }

        $messages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at', 'asc')
            ->get(['role', 'content', 'created_at']);

        return response()->json([
            'messages' => $messages,
            'chatbot_name' => $chatbot->name,
            'session_id' => $sessionId,
            'last_activity' => $conversation->last_activity
        ]);
    }

    /**
     * إنهاء المحادثة
     */
    public function endConversation(string $widgetId, string $sessionId)
    {
        $conversation = Conversation::join('chatbots', 'conversations.chatbot_id', '=', 'chatbots.id')
            ->where('chatbots.widget_id', $widgetId)
            ->where('conversations.session_id', $sessionId)
            ->where('chatbots.is_public', true)
            ->select('conversations.*')
            ->first();

        if ($conversation) {
            $conversation->update(['is_active' => false]);
        }

        // تنظيف الكاش
        Cache::forget("conversation_status_{$sessionId}");
        Cache::forget("chat_response_{$sessionId}");

        return response()->json(['success' => true]);
    }

    /**
     * الحصول على معلومات الروبوت
     */
    public function getBotInfo(string $widgetId)
    {
        $chatbot = Chatbot::where('widget_id', $widgetId)
        // ->where('is_public', true)
        // ->where('status', 'active')
        ->first(['name', 'description', 'widget_id']);

        if (!$chatbot) {
            return response()->json(['error' => 'الروبوت غير متاح'], 404);
        }

        return response()->json([
            'name' => $chatbot->name,
            'description' => $chatbot->description,
            'widget_id' => $chatbot->widget_id,
            'welcome_message' => 'مرحباً! كيف يمكنني مساعدتك اليوم؟'
        ]);
    }

    /**
     * توليد ملف JavaScript للتضمين
     */
    public function getWidget(string $widgetId)
    {
        $chatbot = Chatbot::where('widget_id', $widgetId)
            // ->where('is_public', true)
            // ->where('status', 'active')
            ->first();

        if (!$chatbot) {
            return response('// الروبوت غير متاح', 404)
                ->header('Content-Type', 'application/javascript');
        }

          return response('// الروبوت غير متاح', 404)
                ->header('Content-Type', 'application/javascript');
        // $widgetJs = $this->generateWidgetJS($chatbot);
        
        // return response($widgetJs)
        //     ->header('Content-Type', 'application/javascript')
        //     ->header('Cache-Control', 'public, max-age=300'); // كاش لمدة 5 دقائق
    }

    /**
     * رسالة الحالة بناءً على status
     */
    private function getStatusMessage(string $status): string
    {
        return match ($status) {
            'processing' => 'الروبوت يفكر...',
            'completed' => 'تم الانتهاء',
            'error' => 'حدث خطأ في المعالجة',
            'failed' => 'فشل في معالجة الرسالة',
            default => 'حالة غير معروفة'
        };
    }
}

//     /**
//      * توليد كود JavaScript للتضمين
//      */
//     private function generateWidgetJS(Chatbot $chatbot): string
//     {
//         $apiUrl = url("/api/public/chat/{$chatbot->widget_id}");
//         $chatbotName = $chatbot->name;
        
//         return <<<JS
// (function() {
//     // إنشاء واجهة الدردشة
//     const widgetId = '{$chatbot->widget_id}';
//     const apiUrl = '{$apiUrl}';
//     const chatbotName = '{$chatbotName}';
    
//     // إنشاء الواجهة
//     function createChatWidget() {
//         const widget = document.createElement('div');
//         widget.id = 'chatbot-widget-' + widgetId;
//         widget.innerHTML = \`
//             <div style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
//                 <div id="chat-button" style="
//                     background: #007bff;
//                     color: white;
//                     border-radius: 50px;
//                     padding: 15px 20px;
//                     cursor: pointer;
//                     box-shadow: 0 2px 10px rgba(0,0,0,0.2);
//                     font-family: Arial, sans-serif;
//                 ">
//                     💬 \${chatbotName}
//                 </div>
//                 <div id="chat-window" style="
//                     display: none;
//                     position: absolute;
//                     bottom: 70px;
//                     right: 0;
//                     width: 350px;
//                     height: 500px;
//                     background: white;
//                     border-radius: 10px;
//                     box-shadow: 0 5px 25px rgba(0,0,0,0.3);
//                     overflow: hidden;
//                     font-family: Arial, sans-serif;
//                 ">
//                     <div style="background: #007bff; color: white; padding: 15px; font-weight: bold;">
//                         \${chatbotName}
//                         <span id="close-chat" style="float: right; cursor: pointer;">✕</span>
//                     </div>
//                     <div id="messages" style="height: 380px; overflow-y: auto; padding: 10px;"></div>
//                     <div style="padding: 10px; border-top: 1px solid #eee;">
//                         <input type="text" id="message-input" placeholder="اكتب رسالتك..." style="
//                             width: 70%;
//                             padding: 8px;
//                             border: 1px solid #ddd;
//                             border-radius: 20px;
//                             outline: none;
//                         ">
//                         <button id="send-btn" style="
//                             width: 25%;
//                             padding: 8px;
//                             background: #007bff;
//                             color: white;
//                             border: none;
//                             border-radius: 20px;
//                             cursor: pointer;
//                             margin-right: 5px;
//                         ">إرسال</button>
//                     </div>
//                 </div>
//             </div>
//         \`;
        
//         document.body.appendChild(widget);
        
//         // إضافة المنطق
//         setupChatLogic();
//     }
    
//     function setupChatLogic() {
//         const chatButton = document.getElementById('chat-button');
//         const chatWindow = document.getElementById('chat-window');
//         const closeBtn = document.getElementById('close-chat');
//         const messageInput = document.getElementById('message-input');
//         const sendBtn = document.getElementById('send-btn');
//         const messagesDiv = document.getElementById('messages');
        
//         let sessionId = localStorage.getItem('chatbot_session_' + widgetId) || null;
        
//         chatButton.onclick = () => {
//             chatWindow.style.display = chatWindow.style.display === 'none' ? 'block' : 'none';
//             if (chatWindow.style.display === 'block' && !sessionId) {
//                 addMessage('مرحباً! كيف يمكنني مساعدتك اليوم؟', 'bot');
//             }
//         };
        
//         closeBtn.onclick = () => {
//             chatWindow.style.display = 'none';
//         };
        
//         sendBtn.onclick = sendMessage;
//         messageInput.onkeypress = (e) => {
//             if (e.key === 'Enter') sendMessage();
//         };
        
//         function sendMessage() {
//             const message = messageInput.value.trim();
//             if (!message) return;
            
//             addMessage(message, 'user');
//             messageInput.value = '';
            
//             // إرسال الرسالة للخادم
//             fetch(apiUrl, {
//                 method: 'POST',
//                 headers: {
//                     'Content-Type': 'application/json',
//                     'Accept': 'application/json'
//                 },
//                 body: JSON.stringify({
//                     message: message,
//                     session_id: sessionId
//                 })
//             })
//             .then(response => response.json())
//             .then(data => {
//                 if (data.success) {
//                     sessionId = data.session_id;
//                     localStorage.setItem('chatbot_session_' + widgetId, sessionId);
//                     checkForResponse();
//                 } else {
//                     addMessage('عذراً، حدث خطأ: ' + (data.error || 'خطأ غير معروف'), 'error');
//                 }
//             })
//             .catch(error => {
//                 addMessage('عذراً، حدث خطأ في الاتصال', 'error');
//             });
//         }
        
//         function checkForResponse() {
//             if (!sessionId) return;
            
//             addMessage('...', 'typing');
            
//             const interval = setInterval(() => {
//                 fetch(apiUrl + '/response/' + sessionId)
//                 .then(response => response.json())
//                 .then(data => {
//                     if (data.status === 'completed' && data.response) {
//                         removeTypingIndicator();
//                         addMessage(data.response.message.content, 'bot');
//                         clearInterval(interval);
//                     } else if (data.status === 'error' || data.status === 'failed') {
//                         removeTypingIndicator();
//                         addMessage('عذراً، حدث خطأ في معالجة رسالتك', 'error');
//                         clearInterval(interval);
//                     }
//                 })
//                 .catch(error => {
//                     removeTypingIndicator();
//                     clearInterval(interval);
//                 });
//             }, 2000);
//         }
        
//         function addMessage(content, type) {
//             const messageDiv = document.createElement('div');
//             messageDiv.className = 'message-' + type;
//             messageDiv.style.cssText = \`
//                 margin: 10px 0;
//                 padding: 8px 12px;
//                 border-radius: 18px;
//                 max-width: 80%;
//                 word-wrap: break-word;
//                 \${type === 'user' ? 
//                     'background: #007bff; color: white; margin-right: auto; text-align: left;' : 
//                     type === 'error' ? 
//                     'background: #ff6b6b; color: white; margin-right: auto;' :
//                     'background: #f1f1f1; color: #333; margin-left: auto;'
//                 }
//             \`;
//             messageDiv.textContent = content;
//             messagesDiv.appendChild(messageDiv);
//             messagesDiv.scrollTop = messagesDiv.scrollHeight;
//         }
        
//         function removeTypingIndicator() {
//             const typingMsg = messagesDiv.querySelector('.message-typing');
//             if (typingMsg) {
//                 typingMsg.remove();
//             }
//         }
//     }
    
//     // تشغيل الويدجت عند تحميل الصفحة
//     if (document.readyState === 'loading') {
//         document.addEventListener('DOMContentLoaded', createChatWidget);
//     } else {
//         createChatWidget();
//     }
// })();
// JS;
//     }
// }