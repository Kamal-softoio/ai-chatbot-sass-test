<?php

// routes/web.php

use App\Http\Controllers\Auth\TenantAuthController;
use App\Http\Controllers\ChatbotDemoController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// Demo route for WebSocket chatbot
Route::get('/demo', [ChatbotDemoController::class, 'index'])->name('chatbot.demo');
Route::get('/embed/{chatbotId}', [ChatbotDemoController::class, 'embed'])->name('chatbot.embed');

// مسارات المصادقة للمستأجرين
Route::prefix('auth')->group(function () {
    Route::get('/login', [TenantAuthController::class, 'showLoginForm'])->name('tenant.login');
    Route::post('/login', [TenantAuthController::class, 'login']);
    Route::get('/register', [TenantAuthController::class, 'showRegistrationForm'])->name('tenant.register');
    Route::post('/register', [TenantAuthController::class, 'register']);
    Route::post('/logout', [TenantAuthController::class, 'logout'])->name('tenant.logout');
});

// المسارات المحمية بـ Tenant Middleware
Route::middleware(['tenant'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // مسارات إدارة الروبوتات
    Route::prefix('chatbots')->name('chatbots.')->group(function () {
        Route::get('/', [App\Http\Controllers\ChatbotController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\ChatbotController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\ChatbotController::class, 'store'])->name('store');
        Route::get('/{chatbot}', [App\Http\Controllers\ChatbotController::class, 'show'])->name('show');
        Route::get('/{chatbot}/edit', [App\Http\Controllers\ChatbotController::class, 'edit'])->name('edit');
        Route::put('/{chatbot}', [App\Http\Controllers\ChatbotController::class, 'update'])->name('update');
        Route::delete('/{chatbot}', [App\Http\Controllers\ChatbotController::class, 'destroy'])->name('destroy');

        // مسارات إضافية
        Route::post('/{chatbot}/test', [App\Http\Controllers\ChatbotController::class, 'test'])->name('test');
        Route::get('/{chatbot}/embed-code', [App\Http\Controllers\ChatbotController::class, 'embedCode'])->name('embed-code');
    });
});
