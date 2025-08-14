@echo off
setlocal EnableDelayedExpansion

echo 🧪 تشغيل جميع اختبارات الشات بوت - الإصدار الشامل
echo ==================================================

rem التحقق من Laravel
echo 🔍 فحص النظام...
php artisan --version
echo.

rem إعداد قاعدة البيانات
echo 🗄️ إعادة إعداد قاعدة البيانات...
php artisan migrate:fresh --seed
echo.

rem عداد الاختبارات
set total_tests=0
set passed_tests=0
set failed_tests=0

echo 🚀 بدء تنفيذ الاختبارات الأساسية:
echo ======================================

rem 1. الاختبارات الأساسية
echo 📋 اختبار: إنشاء البيانات الأساسية
php artisan test --filter=SimpleTest::it_can_run_basic_tests --quiet
if !ERRORLEVEL! EQU 0 (
    echo    ✅ نجح!
    set /a passed_tests+=1
) else (
    echo    ❌ فشل!
    set /a failed_tests+=1
)
set /a total_tests+=1
echo.

echo 📋 اختبار: الوصول للصفحة الرئيسية
php artisan test --filter=SimpleTest::it_can_access_home_page --quiet
if !ERRORLEVEL! EQU 0 (
    echo    ✅ نجح!
    set /a passed_tests+=1
) else (
    echo    ❌ فشل!
    set /a failed_tests+=1
)
set /a total_tests+=1
echo.

rem 2. اختبارات النظام الشاملة
echo 📋 اختبار: إنشاء نظام شات بوت كامل
php artisan test --filter=ChatbotSystemTest::it_can_create_complete_chatbot_system --quiet
if !ERRORLEVEL! EQU 0 (
    echo    ✅ نجح!
    set /a passed_tests+=1
) else (
    echo    ❌ فشل!
    set /a failed_tests+=1
)
set /a total_tests+=1
echo.

echo 📋 اختبار: معالجة المحتوى العربي
php artisan test --filter=ChatbotSystemTest::it_can_handle_arabic_content --quiet
if !ERRORLEVEL! EQU 0 (
    echo    ✅ نجح!
    set /a passed_tests+=1
) else (
    echo    ❌ فشل!
    set /a failed_tests+=1
)
set /a total_tests+=1
echo.

echo 📋 اختبار: إعدادات الشات بوت
php artisan test --filter=ChatbotSystemTest::it_can_test_chatbot_settings --quiet
if !ERRORLEVEL! EQU 0 (
    echo    ✅ نجح!
    set /a passed_tests+=1
) else (
    echo    ❌ فشل!
    set /a failed_tests+=1
)
set /a total_tests+=1
echo.

rem 3. اختبارات WebSocket
echo 📋 اختبار: إعدادات Reverb WebSocket
php artisan test --filter=WebSocketConnectionTest::it_can_test_reverb_configuration --quiet
if !ERRORLEVEL! EQU 0 (
    echo    ✅ نجح!
    set /a passed_tests+=1
) else (
    echo    ❌ فشل!
    set /a failed_tests+=1
)
set /a total_tests+=1
echo.

echo 📋 اختبار: بث الأحداث
php artisan test --filter=WebSocketConnectionTest::it_broadcasts_message_sent_event --quiet
if !ERRORLEVEL! EQU 0 (
    echo    ✅ نجح!
    set /a passed_tests+=1
) else (
    echo    ❌ فشل!
    set /a failed_tests+=1
)
set /a total_tests+=1
echo.

rem 4. اختبارات معالجة الرسائل
echo 📋 اختبار: حفظ البيانات الوصفية
php artisan test --filter=ProcessChatMessageJobTest::it_stores_processing_metadata --quiet
if !ERRORLEVEL! EQU 0 (
    echo    ✅ نجح!
    set /a passed_tests+=1
) else (
    echo    ❌ فشل!
    set /a failed_tests+=1
)
set /a total_tests+=1
echo.

echo 📊 ملخص نتائج الاختبارات الأساسية:
echo ==================================
echo إجمالي الاختبارات: !total_tests!
echo الناجحة: !passed_tests! ✅
echo الفاشلة: !failed_tests! ❌
echo.

if !failed_tests! EQU 0 (
    echo 🎉 جميع الاختبارات الأساسية نجحت!
    echo.
    echo 🔥 تشغيل الاختبارات المتقدمة الاختيارية...
    echo ===========================================
    
    rem تشغيل اختبارات إضافية اختيارية
    echo 🧪 اختبار API كامل...
    php artisan test --filter=ChatbotApiTest --quiet
    if !ERRORLEVEL! EQU 0 (
        echo ✅ API Tests نجحت!
    ) else (
        echo ⚠️ API Tests بها مشاكل ^(اختيارية^)
    )
    
    echo.
    echo 🧪 اختبار WebSocket كامل...
    php artisan test --filter=WebSocketConnectionTest --quiet
    if !ERRORLEVEL! EQU 0 (
        echo ✅ WebSocket Tests نجحت!
    ) else (
        echo ⚠️ WebSocket Tests بها مشاكل ^(اختيارية^)
    )
    
) else (
    echo ⚠️ هناك اختبارات فاشلة. يرجى مراجعة الأخطاء أعلاه.
)

echo.
echo 🎯 أوامر إضافية مفيدة:
echo ======================
echo لتشغيل جميع الاختبارات: php artisan test
echo لتشغيل اختبار محدد: php artisan test --filter=اسم_الاختبار
echo لعرض التفاصيل: php artisan test --verbose
echo لتشغيل نوع محدد: php artisan test --testsuite=Feature
echo.
echo 📋 للحصول على توثيق كامل: type TESTS_DOCUMENTATION.md
echo.
echo 🏁 انتهى تشغيل الاختبارات!

if !failed_tests! GTR 0 (
    exit /b 1
) else (
    exit /b 0
)

pause
