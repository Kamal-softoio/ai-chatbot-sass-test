<?php
// routes/web.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\TenantAuthController;
use App\Http\Controllers\DashboardController;

// API للروبوتات العامة (بدون مصادقة)
Route::prefix('public/chat/{widget_id}')->group(function () {
    Route::post('/', [App\Http\Controllers\Api\PublicChatController::class, 'chat']);
    Route::get('/response/{session_id}', [App\Http\Controllers\Api\PublicChatController::class, 'getResponse']);
    Route::get('/history/{session_id}', [App\Http\Controllers\Api\PublicChatController::class, 'getHistory']);
    Route::post('/end/{session_id}', [App\Http\Controllers\Api\PublicChatController::class, 'endConversation']);
    Route::get('/info', [App\Http\Controllers\Api\PublicChatController::class, 'getBotInfo']);
    Route::get('/widget.js', [App\Http\Controllers\Api\PublicChatController::class, 'getWidget']);
});