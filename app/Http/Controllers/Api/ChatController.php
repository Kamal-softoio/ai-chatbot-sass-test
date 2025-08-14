<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessChatMessage;
use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * Send a message to the chatbot and get a response
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
            'chatbot_id' => 'required|integer|exists:chatbots,id',
            'conversation_id' => 'nullable|integer|exists:conversations,id',
            'session_id' => 'required|string|max:255',
        ]);

        try {
            // Find or create conversation
            $conversation = $this->findOrCreateConversation(
                $request->chatbot_id,
                $request->conversation_id,
                $request->session_id
            );

            // Create user message
            $userMessage = Message::create([
                'conversation_id' => $conversation->id,
                'content' => $request->message,
                'role' => 'user',
                'metadata' => [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp' => now()->toISOString(),
                ],
            ]);

            // Broadcast that we received the message (this will show typing indicator)
            $this->broadcastMessageReceived($conversation, $userMessage);

            // Dispatch job to process the message asynchronously
            ProcessChatMessage::dispatch($conversation, $userMessage);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'conversation_id' => $conversation->id,
                'message_id' => $userMessage->id,
                'data' => [
                    'conversation_id' => $conversation->id,
                    'message_id' => $userMessage->id,
                    'session_id' => $conversation->session_id,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Chat message error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get conversation history
     */
    public function getHistory(Request $request): JsonResponse
    {
        $request->validate([
            'conversation_id' => 'required|integer|exists:conversations,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $conversation = Conversation::findOrFail($request->conversation_id);

            $messages = $conversation->messages()
                ->orderBy('created_at', 'asc')
                ->limit($request->get('limit', 50))
                ->get()
                ->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'content' => $message->content,
                        'role' => $message->role,
                        'timestamp' => $message->created_at->toISOString(),
                        'metadata' => $message->metadata,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation_id' => $conversation->id,
                    'messages' => $messages,
                    'total_messages' => $conversation->messages()->count(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load conversation history.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Create a new conversation or find existing one
     */
    public function startConversation(Request $request): JsonResponse
    {
        $request->validate([
            'chatbot_id' => 'required|integer|exists:chatbots,id',
            'session_id' => 'required|string|max:255',
        ]);

        try {
            $conversation = $this->findOrCreateConversation(
                $request->chatbot_id,
                null,
                $request->session_id
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'conversation_id' => $conversation->id,
                    'session_id' => $conversation->session_id,
                    'chatbot' => [
                        'id' => $conversation->chatbot->id,
                        'name' => $conversation->chatbot->name,
                        'description' => $conversation->chatbot->description,
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start conversation.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Upload file for chat context
     */
    public function uploadFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'conversation_id' => 'required|integer|exists:conversations,id',
        ]);

        try {
            $conversation = Conversation::findOrFail($request->conversation_id);

            $file = $request->file('file');
            $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('chat-files', $filename, 'public');

            // Create message with file attachment
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'content' => '📎 تم إرفاق ملف: '.$file->getClientOriginalName(),
                'role' => 'user',
                'metadata' => [
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'file_type' => $file->getClientMimeType(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'timestamp' => now()->toISOString(),
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'message_id' => $message->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_url' => asset('storage/'.$path),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get chatbot configuration for public embedding
     */
    public function getChatbotConfig(Request $request, int $chatbotId): JsonResponse
    {
        try {
            $chatbot = Chatbot::findOrFail($chatbotId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $chatbot->id,
                    'name' => $chatbot->name,
                    'description' => $chatbot->description,
                    'welcome_message' => $chatbot->configuration['welcome_message'] ?? null,
                    'language' => $chatbot->configuration['language'] ?? 'ar',
                    'theme' => $chatbot->configuration['theme'] ?? 'modern',
                    'features' => [
                        'voice_input' => $chatbot->configuration['enable_voice'] ?? true,
                        'file_upload' => $chatbot->configuration['enable_files'] ?? true,
                        'quick_actions' => $chatbot->configuration['quick_actions'] ?? [],
                    ],
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Chatbot not found or not accessible.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 404);
        }
    }

    /**
     * Find or create conversation
     */
    private function findOrCreateConversation(int $chatbotId, ?int $conversationId, string $sessionId): Conversation
    {
        // Get the chatbot to access tenant_id
        $chatbot = Chatbot::findOrFail($chatbotId);

        // If conversation ID is provided, find it
        if ($conversationId) {
            $conversation = Conversation::where('id', $conversationId)
                ->where('chatbot_id', $chatbotId)
                ->where('session_id', $sessionId)
                ->first();

            if ($conversation) {
                return $conversation;
            }
        }

        // Find existing conversation by session ID
        $conversation = Conversation::where('chatbot_id', $chatbotId)
            ->where('session_id', $sessionId)
            ->first();

        if ($conversation) {
            return $conversation;
        }

        // Create new conversation
        return Conversation::create([
            'tenant_id' => $chatbot->tenant_id,
            'chatbot_id' => $chatbotId,
            'session_id' => $sessionId,
            'is_active' => true,
            'last_activity' => now(),
            'metadata' => [
                'created_at' => now()->toISOString(),
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
            ],
        ]);
    }

    /**
     * Broadcast message received event (will show typing indicator)
     */
    private function broadcastMessageReceived(Conversation $conversation, Message $message): void
    {
        try {
            Broadcast::private('conversation.'.$conversation->session_id)
                ->send([
                    'event' => 'MessageReceived',
                    'data' => [
                        'conversation_id' => $conversation->id,
                        'message_id' => $message->id,
                        'timestamp' => now()->toISOString(),
                    ],
                ]);
        } catch (\Exception $e) {
            \Log::warning('Failed to broadcast message received event', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversation->id,
            ]);
        }
    }
}
