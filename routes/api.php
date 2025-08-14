<?php

use App\Http\Controllers\Api\ChatController;
use Illuminate\Support\Facades\Route;

// API للروبوتات العامة (بدون مصادقة)
Route::prefix('public/chat/{widget_id}')->group(function () {
    Route::post('/', [App\Http\Controllers\Api\PublicChatController::class, 'chat']);
    Route::get('/response/{session_id}', [App\Http\Controllers\Api\PublicChatController::class, 'getResponse']);
    Route::get('/history/{session_id}', [App\Http\Controllers\Api\PublicChatController::class, 'getHistory']);
    Route::post('/end/{session_id}', [App\Http\Controllers\Api\PublicChatController::class, 'endConversation']);
    Route::get('/info', [App\Http\Controllers\Api\PublicChatController::class, 'getBotInfo']);
    Route::get('/widget.js', [App\Http\Controllers\Api\PublicChatController::class, 'getWidget']);
});

// New WebSocket-powered chat API
Route::prefix('chat')->name('api.chat.')->group(function () {
    // Send message
    Route::post('/message', [ChatController::class, 'sendMessage'])->name('message');

    // Start new conversation
    Route::post('/start', [ChatController::class, 'startConversation'])->name('start');

    // Get conversation history
    Route::get('/history', [ChatController::class, 'getHistory'])->name('history');

    // Upload file
    Route::post('/upload', [ChatController::class, 'uploadFile'])->name('upload');

    // Get chatbot configuration
    Route::get('/config/{chatbotId}', [ChatController::class, 'getChatbotConfig'])->name('config');
});
