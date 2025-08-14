# 🧪 ملفات الاختبار - AI Chatbot SaaS

## 📋 نظرة عامة

تم إنشاء مجموعة شاملة من ملفات الاختبار لضمان جودة وموثوقية نظام الشات بوت المبني بـ Laravel WebSocket.

## 📂 هيكل ملفات الاختبار

```
tests/
├── Feature/                     # اختبارات التكامل والـ Feature
│   ├── ChatbotApiTest.php      # اختبارات API الشات بوت
│   ├── ChatbotSystemTest.php   # اختبارات النظام الشاملة
│   ├── QuickDemoTest.php       # اختبارات سريعة للعرض التوضيحي
│   ├── SimpleTest.php          # اختبارات أساسية
│   └── WebSocketConnectionTest.php # اختبارات WebSocket
├── Unit/                       # اختبارات الوحدة
│   └── ProcessChatMessageJobTest.php # اختبارات معالجة الرسائل
└── TestCase.php               # الفئة الأساسية للاختبارات
```

## 🔧 ملفات الـ Factory

```
database/factories/
├── ChatbotFactory.php         # مولد بيانات الشات بوت
├── ConversationFactory.php    # مولد بيانات المحادثات
├── MessageFactory.php         # مولد بيانات الرسائل
├── TenantFactory.php          # مولد بيانات المستأجرين
└── UserFactory.php            # مولد بيانات المستخدمين
```

---

## 📝 وصف تفصيلي للاختبارات

### 1️⃣ ChatbotApiTest.php
**الغرض**: اختبار جميع endpoints الخاصة بـ API الشات بوت

#### 🎯 الاختبارات المشمولة:
- ✅ **إرسال رسالة جديدة** - `it_can_send_a_chat_message()`
- ✅ **إنشاء محادثة جديدة** - `it_creates_conversation_if_not_exists()`
- ✅ **استخدام محادثة موجودة** - `it_uses_existing_conversation_for_same_session()`
- ✅ **التحقق من صحة ID الشات بوت** - `it_requires_valid_chatbot_id()`
- ✅ **التحقق من الحقول المطلوبة** - `it_validates_required_fields()`
- ✅ **جلب تاريخ المحادثة** - `it_can_get_conversation_history()`
- ✅ **معالجة المحادثات غير الموجودة** - `it_returns_404_for_non_existent_conversation()`
- ✅ **مسح تاريخ المحادثة** - `it_can_clear_conversation_history()`
- ✅ **تقييم الرسائل** - `it_can_rate_a_message()`

#### 🔍 مثال الاستخدام:
```bash
php artisan test --filter=ChatbotApiTest
```

---

### 2️⃣ WebSocketConnectionTest.php
**الغرض**: اختبار اتصالات WebSocket والبث المباشر

#### 🎯 الاختبارات المشمولة:
- ✅ **بث الأحداث** - `it_broadcasts_message_sent_event()`
- ✅ **الوصول لصفحة العرض التوضيحي** - `it_can_access_websocket_demo_page()`
- ✅ **هيكل الأحداث** - `message_sent_event_has_correct_structure()`
- ✅ **قنوات البث الصحيحة** - `it_broadcasts_on_correct_channel()`
- ✅ **بيانات البث** - `it_includes_message_data_in_broadcast()`
- ✅ **المحتوى العربي** - `it_can_handle_arabic_content_in_broadcasts()`
- ✅ **الطوابع الزمنية** - `it_includes_timestamp_in_broadcast_data()`
- ✅ **إعدادات Reverb** - `it_can_test_reverb_configuration()`

---

### 3️⃣ ProcessChatMessageJobTest.php
**الغرض**: اختبار معالجة الرسائل بواسطة Ollama AI

#### 🎯 الاختبارات المشمولة:
- ✅ **معالجة ناجحة** - `it_processes_chat_message_successfully()`
- ✅ **معالجة الأخطاء** - `it_handles_ollama_service_failure()`
- ✅ **System Prompts** - `it_prepares_messages_with_system_prompt()`
- ✅ **تاريخ المحادثة** - `it_includes_recent_conversation_history()`
- ✅ **الإحصائيات** - `it_updates_conversation_statistics()`
- ✅ **البيانات الوصفية** - `it_stores_processing_metadata()`

---

### 4️⃣ ChatbotSystemTest.php
**الغرض**: اختبارات شاملة للنظام بأكمله

#### 🎯 الاختبارات المشمولة:
- ✅ **النظام الكامل** - `it_can_create_complete_chatbot_system()`
- ✅ **المحتوى العربي** - `it_can_handle_arabic_content()`
- ✅ **تقييم الرسائل** - `it_can_test_message_ratings()`
- ✅ **إعدادات الشات بوت** - `it_can_test_chatbot_settings()`
- ✅ **المحادثات المتعددة** - `it_can_create_multiple_conversations()`

---

## 🎭 مولدات البيانات (Factories)

### ChatbotFactory
```php
// إنشاء شات بوت عادي
$chatbot = Chatbot::factory()->create();

// إنشاء شات بوت عالي الأداء
$chatbot = Chatbot::factory()->highPerformance()->create();

// إنشاء شات بوت عربي
$chatbot = Chatbot::factory()->arabicFocused()->create();

// إنشاء شات بوت غير نشط
$chatbot = Chatbot::factory()->inactive()->create();
```

### MessageFactory
```php
// إنشاء رسالة مستخدم
$message = Message::factory()->user()->create();

// إنشاء رسالة مساعد
$message = Message::factory()->assistant()->create();

// إنشاء رسالة عالية التقييم
$message = Message::factory()->highRated()->create();

// إنشاء رسالة حديثة
$message = Message::factory()->recent()->create();
```

### ConversationFactory
```php
// إنشاء محادثة نشطة
$conversation = Conversation::factory()->active()->create();

// إنشاء محادثة منتهية
$conversation = Conversation::factory()->ended()->create();

// إنشاء محادثة من جهاز محمول
$conversation = Conversation::factory()->mobile()->create();

// إنشاء محادثة حديثة
$conversation = Conversation::factory()->recent()->create();
```

---

## 🚀 تشغيل الاختبارات

### تشغيل جميع الاختبارات
```bash
php artisan test
```

### تشغيل اختبارات محددة
```bash
# اختبار API فقط
php artisan test --filter=ChatbotApiTest

# اختبار WebSocket فقط  
php artisan test --filter=WebSocketConnectionTest

# اختبار النظام فقط
php artisan test --filter=ChatbotSystemTest

# اختبار سريع
php artisan test --filter=SimpleTest
```

### تشغيل اختبار واحد
```bash
php artisan test --filter=it_can_send_a_chat_message
```

### تشغيل مع تفاصيل أكثر
```bash
php artisan test --verbose
```

---

## 📊 الإحصائيات والتغطية

### 🎯 إجمالي الاختبارات المنشأة: **25+ اختبار**

- **Feature Tests**: 20+ اختبار
- **Unit Tests**: 6+ اختبار
- **Factory Tests**: مدمجة في الاختبارات

### 🔍 المناطق المُغطاة:
- ✅ API Endpoints
- ✅ WebSocket Broadcasting  
- ✅ Database Models
- ✅ Queue Jobs
- ✅ Event Broadcasting
- ✅ Arabic Content Support
- ✅ Error Handling
- ✅ Data Validation

---

## 🛠️ إعداد بيئة الاختبار

### قاعدة البيانات
```bash
# إعداد قاعدة بيانات الاختبار
php artisan migrate:fresh --env=testing --seed
```

### متطلبات إضافية
```bash
# تأكد من تشغيل Queue Worker
php artisan queue:work

# تأكد من تشغيل Reverb Server
php artisan reverb:start
```

---

## 📋 قائمة التحقق للاختبارات

- [x] **إنشاء ملفات الاختبار الأساسية**
- [x] **إنشاء ملفات Factory للبيانات**
- [x] **اختبار API Endpoints**
- [x] **اختبار WebSocket Broadcasting**
- [x] **اختبار معالجة الرسائل**
- [x] **اختبار المحتوى العربي**
- [x] **اختبار معالجة الأخطاء**
- [x] **اختبار التحقق من البيانات**
- [x] **توثيق جميع الاختبارات**

---

## 🎉 النتائج المتوقعة

عند تشغيل الاختبارات بنجاح، ستحصل على:

```
✓ Tests passed successfully
✓ All API endpoints working
✓ WebSocket broadcasting functional
✓ Database operations verified
✓ Arabic content support confirmed
✓ Error handling tested
✓ Full system integration verified
```

---

## 📞 الدعم والمساعدة

للحصول على المساعدة في الاختبارات:

1. **تحقق من تشغيل الخدمات المطلوبة**
2. **راجع سجل الأخطاء في logs/laravel.log**
3. **تأكد من إعداد قاعدة البيانات بشكل صحيح**
4. **تأكد من تشغيل Ollama AI Service**

---

*تم إنشاء ملفات الاختبار بواسطة GitHub Copilot - نظام ذكي وشامل للاختبار! 🤖*
