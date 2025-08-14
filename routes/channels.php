<?php

use Illuminate\Support\Facades\Broadcast;

// User-specific channel (existing)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Conversation channel for WebSocket chat - PUBLIC for demo
Broadcast::channel('conversation.{sessionId}', function ($user, $sessionId) {
    // Allow public access for demo purposes
    // In production, you might want to add authentication
    return true;
});

// Private chatbot channels for authenticated users (tenant-specific)
Broadcast::channel('chatbot.{chatbotId}', function ($user, $chatbotId) {
    // Only allow tenant users to access their chatbot channels
    if (auth()->guard('tenant')->check()) {
        $tenant = auth()->guard('tenant')->user();
        // Check if the chatbot belongs to this tenant
        $chatbot = \App\Models\Chatbot::where('id', $chatbotId)
            ->where('tenant_id', $tenant->id)
            ->first();

        if ($chatbot) {
            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'tenant_id' => $tenant->id,
            ];
        }
    }

    return false;
});
