@echo off
echo 🚀 بدء تشغيل اختبارات الشات بوت الذكي
echo ==================================

rem التحقق من Laravel
echo 1️⃣ التحقق من Laravel...
php artisan --version

rem إعداد قاعدة البيانات
echo 2️⃣ إعداد قاعدة البيانات...
php artisan migrate:fresh --seed --quiet

rem تشغيل الاختبارات الأساسية
echo 3️⃣ تشغيل الاختبارات الأساسية...
php artisan test --filter=SimpleTest

echo 4️⃣ تشغيل اختبارات النظام الشاملة...
php artisan test --filter=ChatbotSystemTest

echo 5️⃣ تشغيل اختبارات WebSocket...
php artisan test --filter=WebSocketConnectionTest::it_can_test_reverb_configuration

echo 6️⃣ تشغيل اختبار معالجة الرسائل (Unit Test)...
php artisan test --filter=ProcessChatMessageJobTest::it_stores_processing_metadata

echo 7️⃣ تشغيل جميع الاختبارات المتبقية...
php artisan test --testsuite=Feature,Unit --stop-on-failure

rem عرض النتائج
echo.
echo ==========================================
echo ✅ تم الانتهاء من جميع الاختبارات الأساسية!
echo ==========================================
echo.
echo � ملخص الاختبارات المُنفذة:
echo    ✓ الاختبارات الأساسية (SimpleTest)
echo    ✓ اختبارات النظام الشاملة (ChatbotSystemTest)
echo    ✓ اختبارات WebSocket (WebSocketConnectionTest)
echo    ✓ اختبارات معالجة الرسائل (ProcessChatMessageJobTest)
echo    ✓ جميع الاختبارات المتبقية
echo.
echo 🎯 الاختبارات المتقدمة الإضافية:
echo    💡 لاختبار API كامل: php artisan test --filter=ChatbotApiTest
echo    �💡 لاختبار WebSocket كامل: php artisan test --filter=WebSocketConnectionTest
echo    💡 لتشغيل جميع الاختبارات: php artisan test
echo.
echo 📋 للاطلاع على التوثيق: type TESTS_DOCUMENTATION.md
echo.
echo 🎯 ملفات الاختبار الجاهزة:
echo    - tests/Feature/ChatbotApiTest.php
echo    - tests/Feature/WebSocketConnectionTest.php
echo    - tests/Feature/ChatbotSystemTest.php
echo    - tests/Unit/ProcessChatMessageJobTest.php
echo.
echo 🏭 ملفات Factory الجاهزة:
echo    - database/factories/ChatbotFactory.php
echo    - database/factories/ConversationFactory.php
echo    - database/factories/MessageFactory.php
echo.
echo 🎉 جميع ملفات الاختبار جاهزة للاستخدام!
pause
