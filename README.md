# 🤖 AI Chatbot SaaS - شات بوت ذكي متعدد المستأجرين

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="200" alt="Laravel Logo">
  <br>
  <strong>نظام شات بوت ذكي مبني بـ Laravel 12 مع WebSocket وذكاء اصطناعي</strong>
</p>

<p align="center">
<img src="https://img.shields.io/badge/Laravel-12.x-red?style=flat-square&logo=laravel" alt="Laravel 12">
<img src="https://img.shields.io/badge/PHP-8.3+-blue?style=flat-square&logo=php" alt="PHP 8.3+">
<img src="https://img.shields.io/badge/WebSocket-Reverb-green?style=flat-square" alt="WebSocket Reverb">
<img src="https://img.shields.io/badge/AI-Ollama-purple?style=flat-square" alt="Ollama AI">
<img src="https://img.shields.io/badge/Frontend-JavaScript-yellow?style=flat-square&logo=javascript" alt="JavaScript">
<img src="https://img.shields.io/badge/Database-MySQL-orange?style=flat-square&logo=mysql" alt="MySQL">
</p>

---

## 🌟 نظرة عامة على المشروع

**AI Chatbot SaaS** هو نظام شات بوت ذكي متطور مبني بـ **Laravel 12** مع دعم الذكاء الاصطناعي والتفاعل الفوري عبر WebSocket. 

### 🎯 الهدف من المشروع
- إنشاء نظام شات بوت ذكي يدعم **تعدد المستأجرين (Multi-tenant)**
- تقديم تجربة محادثة فورية مع **الذكاء الاصطناعي**
- دعم **البث المباشر (Real-time Broadcasting)** عبر WebSocket
- واجهة سهلة الاستخدام باللغة **العربية والإنجليزية**
- نظام قابل للتوسع والتطوير

### ⭐ المميزات الرئيسية

#### 🤖 ذكاء اصطناعي متطور
- **تكامل مع Ollama AI** باستخدام نموذج `qwen2.5-coder:latest`
- **دعم المحادثات العربية** مع فهم السياق
- **ردود ذكية وطبيعية** مع دعم المحادثات الطويلة
- **نظام Prompts قابل للتخصيص**

#### 🌐 WebSocket وBroadcasting
- **Laravel Reverb** للبث المباشر
- **تحديثات فورية** بدون إعادة تحميل الصفحة
- **دعم القنوات المتعددة** لكل محادثة
- **اتصال موثوق** مع إعادة الاتصال التلقائي

#### 🏗️ Architecture متقدم
- **Multi-tenant System** - نظام متعدد المستأجرين
- **Queue Jobs** لمعالجة الرسائل في الخلفية
- **Event-driven Architecture** مع Events & Listeners
- **RESTful API** شامل ومرن

#### 📱 واجهة مستخدم حديثة
- **تصميم responsive** يعمل على جميع الأجهزة
- **دعم اللغة العربية** بالكامل
- **مؤشرات الكتابة والحالة**
- **تجربة مستخدم سلسة**

---

## 🚀 الخدمات والتقنيات المستخدمة

### Backend Framework
- **Laravel 12.x** - أحدث إصدار من Laravel
- **PHP 8.3+** - لغة البرمجة الأساسية
- **MySQL** - قاعدة البيانات

### AI & Machine Learning
- **Ollama AI Service** - خدمة الذكاء الاصطناعي المحلية
- **qwen2.5-coder:latest** - النموذج المستخدم
- **Custom Prompts** - نظام prompts مخصص

### Real-time Communication
- **Laravel Reverb** - WebSocket Server
- **Laravel Echo** - Frontend WebSocket Client
- **Pusher Protocol** - بروتوكول البث

### Queue System
- **Laravel Queues** - نظام المهام
- **ProcessChatMessage Job** - معالجة الرسائل
- **Event Broadcasting** - بث الأحداث

### Frontend
- **Vanilla JavaScript** - بدون frameworks إضافية
- **Bootstrap 5** - تصميم responsive
- **Laravel Echo** - WebSocket integration
- **Modern ES6+** - JavaScript حديث

---

## 📋 متطلبات التشغيل

### متطلبات النظام
- **PHP >= 8.3**
- **Composer** - مدير packages لـ PHP
- **Node.js >= 18.x** - لبناء Frontend assets
- **MySQL >= 8.0** - قاعدة البيانات
- **Git** - لإدارة النسخ

### خدمات إضافية مطلوبة
- **Ollama AI** - للذكاء الاصطناعي ([تحميل من هنا](https://ollama.ai/))
- **WebSocket Support** - Laravel Reverb (مدمج)

---

## ⚙️ التثبيت والتشغيل السريع

### 1️⃣ تحميل المشروع
```bash
git clone https://github.com/your-repo/ai-chatbot-saas.git
cd ai-chatbot-saas
```

### 2️⃣ تثبيت Dependencies
```bash
# تثبيت PHP packages
composer install

# تثبيت Node packages
npm install

# بناء Frontend assets
npm run build
```

### 3️⃣ إعداد البيئة
```bash
# نسخ ملف البيئة
cp .env.example .env

# توليد مفتاح التطبيق
php artisan key:generate

# إعداد قاعدة البيانات في .env
# DB_DATABASE=your_database_name
# DB_USERNAME=your_username
# DB_PASSWORD=your_password
```

### 4️⃣ إعداد قاعدة البيانات
```bash
# تشغيل Migrations
php artisan migrate

# تشغيل Seeders (بيانات تجريبية)
php artisan db:seed
```

### 5️⃣ تثبيت وتشغيل Ollama
```bash
# تحميل Ollama من https://ollama.ai/
# بعد التثبيت، قم بتحميل النموذج:
ollama pull qwen2.5-coder:latest

# تشغيل خدمة Ollama
ollama serve
```

### 6️⃣ تشغيل الخدمات
```bash
# Terminal 1: Laravel Server
php artisan serve --host=0.0.0.0 --port=8000

# Terminal 2: Reverb WebSocket Server  
php artisan reverb:start --host=0.0.0.0 --port=8080

# Terminal 3: Queue Worker
php artisan queue:work --verbose

# Terminal 4: Ollama AI Service (إذا لم يكن يعمل بالفعل)
ollama serve
```

### 7️⃣ الوصول للتطبيق
- **الموقع الرئيسي**: http://localhost:8000
- **صفحة العرض التوضيحي**: http://localhost:8000/demo
- **API**: http://localhost:8000/api/
- **WebSocket**: ws://localhost:8080

---

## 📖 توثيق شامل

### 📚 وثائق تقنية مفصلة

#### 🎯 [دليل العمل الكامل للديمو](DEMO_DOCUMENTATION.md)
**كيف يعمل النظام خطوة بخطوة**
- دورة حياة الرسالة من Frontend إلى AI
- شرح جميع الخدمات المطلوبة
- سيناريو كامل مع الأمثلة
- تشخيص المشاكل والحلول
- أدوات المراقبة والتحسين

#### 📡 [دليل Broadcasting والـ WebSocket](BROADCASTING_DOCUMENTATION.md) 
**شرح نظام البث المباشر والاتصال الفوري**
- Architecture العامة للـ Broadcasting
- إعداد Laravel Reverb WebSocket
- إنشاء وإدارة القنوات
- Frontend Integration مع Echo
- تشخيص مشاكل WebSocket
- تحسينات الأداء والأمان

#### 🎪 [دليل Events والأحداث](EVENTS_DOCUMENTATION.md)
**شرح نظام الأحداث ودورة الحياة**
- مفهوم Events في Laravel
- إنشاء أحداث مخصصة (MessageSent, ConversationStarted)
- Event Listeners معقدة
- Event Subscribers للأحداث المتقدمة
- تشخيص وDebug للأحداث
- تحسين الأداء مع Queue

#### 🧪 [دليل الاختبارات الشامل](TESTS_DOCUMENTATION.md)
**اختبارات شاملة لجميع أجزاء النظام**
- Feature Tests للـ API وWebSocket
- Unit Tests للـ Jobs والServices  
- Factory Tests للنماذج
- اختبارات البث المباشر
- أكثر من 25 اختبار شامل

### 🗂️ وثائق إضافية (قيد الإنشاء)
- **API Documentation** - وثائق شاملة لجميع endpoints
- **Deployment Guide** - دليل النشر على الخوادم
- **Performance Guide** - تحسين الأداء والسرعة
- **Security Guide** - حماية وأمان التطبيق

---

## 🏗️ هيكل المشروع

```
ai-chatbot-saas/
├── 📁 app/
│   ├── 📁 Console/Commands/          # Artisan Commands
│   ├── 📁 Events/                    # Laravel Events
│   │   └── MessageSent.php          # حدث إرسال الرسالة
│   ├── 📁 Http/Controllers/         # Controllers
│   │   ├── 📁 Api/                  # API Controllers
│   │   └── ChatbotDemoController.php # Demo Controller
│   ├── 📁 Jobs/                     # Background Jobs
│   │   └── ProcessChatMessage.php   # معالجة رسائل الذكاء الاصطناعي
│   ├── 📁 Models/                   # Eloquent Models
│   │   ├── Chatbot.php             # نموذج الشات بوت
│   │   ├── Conversation.php        # نموذج المحادثة  
│   │   ├── Message.php             # نموذج الرسالة
│   │   └── Tenant.php              # نموذج المستأجر
│   └── 📁 Services/                # Services
│       └── OllamaService.php       # خدمة الذكاء الاصطناعي
├── 📁 config/                      # إعدادات التطبيق
│   ├── broadcasting.php            # إعدادات البث
│   └── ollama.php                 # إعدادات الذكاء الاصطناعي
├── 📁 database/
│   ├── 📁 factories/               # Data Factories للاختبار
│   ├── 📁 migrations/              # جداول قاعدة البيانات
│   └── 📁 seeders/                 # بيانات تجريبية
├── 📁 resources/
│   ├── 📁 js/components/           # JavaScript Components
│   │   └── ChatbotWidget.js       # مكون الشات بوت الرئيسي
│   ├── 📁 views/                   # Laravel Views
│   └── 📁 css/                     # Stylesheets
├── 📁 routes/
│   ├── api.php                     # API Routes
│   ├── web.php                     # Web Routes  
│   └── channels.php                # Broadcasting Channels
├── 📁 tests/                       # اختبارات شاملة
│   ├── 📁 Feature/                 # Feature Tests
│   └── 📁 Unit/                    # Unit Tests
├── 📄 DEMO_DOCUMENTATION.md        # توثيق الديمو
├── 📄 BROADCASTING_DOCUMENTATION.md # توثيق البث المباشر
├── 📄 EVENTS_DOCUMENTATION.md      # توثيق الأحداث
├── 📄 TESTS_DOCUMENTATION.md       # توثيق الاختبارات
└── 📄 README.md                    # هذا الملف
```

---

---

## 🎮 دليل الاستخدام السريع

### 🚀 تشغيل النظام (للمرة الأولى)

1. **تأكد من تشغيل جميع الخدمات:**
   ```bash
   # في 4 terminals منفصلة:
   php artisan serve                    # Laravel Server
   php artisan reverb:start            # WebSocket Server
   php artisan queue:work               # Queue Worker
   ollama serve                         # AI Service
   ```

2. **افتح المتصفح واذهب إلى:**
   - http://localhost:8000/demo

3. **ابدأ المحادثة:**
   - اكتب "مرحبا" أو أي رسالة
   - انتظر رد الذكاء الاصطناعي (2-5 ثواني)
   - استمتع بالمحادثة!

### 🔧 أوامر مفيدة

```bash
# تشغيل الاختبارات
php artisan test

# مشاهدة الـ logs
tail -f storage/logs/laravel.log

# تنظيف Cache
php artisan config:cache
php artisan route:cache

# فحص حالة النظام
php artisan queue:work --once        # فحص الـ Queue
curl http://localhost:11434/api/tags  # فحص Ollama

# إعادة تشغيل كل شيء
php artisan config:clear && php artisan serve &
php artisan reverb:start &
php artisan queue:work &
```

---

## 🛠️ Development وتطوير

### 📝 إضافة ميزات جديدة

1. **إضافة أحداث جديدة:**
   ```bash
   php artisan make:event NewFeatureEvent
   php artisan make:listener HandleNewFeature
   ```

2. **إضافة API endpoints:**
   ```bash
   php artisan make:controller Api/NewFeatureController --api
   ```

3. **إضافة Jobs جديدة:**
   ```bash
   php artisan make:job ProcessNewFeature
   ```

### 🧪 تشغيل الاختبارات

```bash
# جميع الاختبارات
php artisan test

# اختبار محدد  
php artisan test --filter=ChatbotApiTest

# مع تفاصيل أكثر
php artisan test --verbose

# اختبار التغطية
php artisan test --coverage
```

### 🐛 تشخيص المشاكل

| المشكلة | الحل |
|---------|------|
| رسائل لا تظهر | تأكد من تشغيل Queue Worker |
| WebSocket لا يعمل | تأكد من تشغيل Reverb Server |
| AI لا يرد | تأكد من تشغيل Ollama وتحميل النموذج |
| أخطاء 500 | راجع `storage/logs/laravel.log` |

---

## 🚀 Production Deployment

### 📦 إعداد الإنتاج

```bash
# تحسين للإنتاج
composer install --optimize-autoloader --no-dev
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache

# إعداد Queue Worker كخدمة
# استخدم Supervisor أو systemd

# إعداد WebSocket Server
# استخدم PM2 أو Supervisor
```

### 🔒 الأمان

- تأكد من إعداد HTTPS في الإنتاج
- استخدم Redis أو Database للـ Queue في الإنتاج
- فعّل Rate Limiting للـ API
- راجع إعدادات CORS

---

## 🤝 المساهمة في المشروع

نرحب بمساهماتك! 

### خطوات المساهمة:
1. Fork المشروع
2. إنشاء branch جديد (`git checkout -b feature/amazing-feature`)
3. Commit التغييرات (`git commit -m 'Add amazing feature'`)
4. Push للـ branch (`git push origin feature/amazing-feature`)
5. إنشاء Pull Request

### 📋 معايير الكود:
- اتبع PSR-12 coding standards
- اكتب tests للميزات الجديدة
- وثّق الكود باللغة العربية
- اختبر جميع التغييرات قبل الـ commit

---

## 📞 الدعم والمساعدة

### 🆘 طرق الحصول على المساعدة:

1. **راجع التوثيق أولاً:**
   - [DEMO_DOCUMENTATION.md](DEMO_DOCUMENTATION.md) - شرح شامل للنظام
   - [BROADCASTING_DOCUMENTATION.md](BROADCASTING_DOCUMENTATION.md) - WebSocket والبث
   - [EVENTS_DOCUMENTATION.md](EVENTS_DOCUMENTATION.md) - نظام الأحداث
   - [TESTS_DOCUMENTATION.md](TESTS_DOCUMENTATION.md) - الاختبارات

2. **فحص الـ Logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **تشغيل التشخيص:**
   ```bash
   php artisan config:show
   php artisan route:list
   php artisan queue:work --once
   ```

### 🐞 الإبلاغ عن الأخطاء

إذا وجدت خطأ، يرجى:
1. فحص الـ logs
2. محاولة إعادة إنتاج الخطأ
3. إنشاء Issue مع التفاصيل الكاملة

---

## 📄 License والترخيص

هذا المشروع مرخص تحت **MIT License** - راجع ملف [LICENSE](LICENSE) للتفاصيل.

---

## 🙏 شكر وتقدير

### Built with ❤️ using:
- **[Laravel](https://laravel.com)** - The PHP Framework for Web Artisans
- **[Ollama AI](https://ollama.ai/)** - Local AI Models
- **[Laravel Reverb](https://laravel.com/docs/broadcasting)** - WebSocket Broadcasting
- **[Bootstrap](https://getbootstrap.com)** - Frontend Framework

### Special Thanks:
- **Laravel Community** - للإطار الرائع والدعم
- **Ollama Team** - لتقديم AI محلي مجاني
- **GitHub Copilot** - للمساعدة في التطوير والتوثيق

---

<p align="center">
  <strong>تم تطوير هذا المشروع بواسطة مطورين عرب لخدمة المجتمع العربي 🇸🇦</strong>
  <br>
  <em>Built with ❤️ for the Arab Developer Community</em>
</p>

---

*آخر تحديث: أغسطس 2025 | Laravel 12 | PHP 8.3*
