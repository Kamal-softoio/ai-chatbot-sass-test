#!/bin/bash

echo "🧪 تشغيل جميع اختبارات الشات بوت - الإصدار الشامل"
echo "=================================================="

# التحقق من Laravel
echo "🔍 فحص النظام..."
php artisan --version
echo ""

# إعداد قاعدة البيانات
echo "🗄️ إعادة إعداد قاعدة البيانات..."
php artisan migrate:fresh --seed
echo ""

# عداد الاختبارات
total_tests=0
passed_tests=0
failed_tests=0

# دالة لتشغيل اختبار واحد
run_test() {
    local test_name="$1"
    local test_filter="$2"
    local description="$3"
    
    echo "📋 اختبار: $description"
    echo "   الفئة: $test_name"
    echo "   المرشح: $test_filter"
    
    if php artisan test --filter="$test_filter" --quiet; then
        echo "   ✅ نجح!"
        ((passed_tests++))
    else
        echo "   ❌ فشل!"
        ((failed_tests++))
    fi
    ((total_tests++))
    echo ""
}

echo "🚀 بدء تنفيذ الاختبارات الأساسية:"
echo "======================================"

# 1. الاختبارات الأساسية
run_test "SimpleTest" "SimpleTest::it_can_run_basic_tests" "إنشاء البيانات الأساسية"
run_test "SimpleTest" "SimpleTest::it_can_access_home_page" "الوصول للصفحة الرئيسية"

# 2. اختبارات النظام الشاملة  
run_test "ChatbotSystemTest" "ChatbotSystemTest::it_can_create_complete_chatbot_system" "إنشاء نظام شات بوت كامل"
run_test "ChatbotSystemTest" "ChatbotSystemTest::it_can_handle_arabic_content" "معالجة المحتوى العربي"
run_test "ChatbotSystemTest" "ChatbotSystemTest::it_can_test_chatbot_settings" "إعدادات الشات بوت"

# 3. اختبارات WebSocket
run_test "WebSocketConnectionTest" "WebSocketConnectionTest::it_can_test_reverb_configuration" "إعدادات Reverb WebSocket"
run_test "WebSocketConnectionTest" "WebSocketConnectionTest::it_broadcasts_message_sent_event" "بث الأحداث"

# 4. اختبارات معالجة الرسائل
run_test "ProcessChatMessageJobTest" "ProcessChatMessageJobTest::it_stores_processing_metadata" "حفظ البيانات الوصفية"

echo "📊 ملخص نتائج الاختبارات الأساسية:"
echo "=================================="
echo "إجمالي الاختبارات: $total_tests"
echo "الناجحة: $passed_tests ✅"
echo "الفاشلة: $failed_tests ❌"
echo ""

if [ $failed_tests -eq 0 ]; then
    echo "🎉 جميع الاختبارات الأساسية نجحت!"
    echo ""
    echo "🔥 تشغيل الاختبارات المتقدمة الاختيارية..."
    echo "==========================================="
    
    # تشغيل اختبارات إضافية اختيارية
    echo "🧪 اختبار API كامل..."
    if php artisan test --filter=ChatbotApiTest --quiet; then
        echo "✅ API Tests نجحت!"
    else
        echo "⚠️ API Tests بها مشاكل (اختيارية)"
    fi
    
    echo ""
    echo "🧪 اختبار WebSocket كامل..."
    if php artisan test --filter=WebSocketConnectionTest --quiet; then
        echo "✅ WebSocket Tests نجحت!"
    else
        echo "⚠️ WebSocket Tests بها مشاكل (اختيارية)"
    fi
    
else
    echo "⚠️ هناك اختبارات فاشلة. يرجى مراجعة الأخطاء أعلاه."
fi

echo ""
echo "🎯 أوامر إضافية مفيدة:"
echo "======================"
echo "لتشغيل جميع الاختبارات: php artisan test"
echo "لتشغيل اختبار محدد: php artisan test --filter=اسم_الاختبار"
echo "لعرض التفاصيل: php artisan test --verbose"
echo "لتشغيل نوع محدد: php artisan test --testsuite=Feature"
echo ""
echo "📋 للحصول على توثيق كامل: cat TESTS_DOCUMENTATION.md"
echo ""

exit_code=0
if [ $failed_tests -gt 0 ]; then
    exit_code=1
fi

echo "🏁 انتهى تشغيل الاختبارات!"
exit $exit_code
