<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;

    public Conversation $conversation;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message, Conversation $conversation = null)
    {
        Log::info('MessageSent Event Created', [
            'message_id' => $message->id,
            'message_content' => $message->content,
            'message_role' => $message->role,
            'conversation_id' => $conversation ? $conversation->id : 'null',
            'session_id' => $conversation ? $conversation->session_id : 'null',
        ]);
        
        $this->message = $message;
        $this->conversation = $conversation;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channel = 'conversation.'.$this->conversation->session_id;
        Log::info('MessageSent Broadcasting On Channel', ['channel' => $channel]);
        return [
            new Channel($channel),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $data = [
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->content,
                'role' => $this->message->role,
                'timestamp' => $this->message->created_at->toISOString(),
                'metadata' => $this->message->metadata,
            ],
            'conversation' => [
                'id' => $this->conversation->id,
                'session_id' => $this->conversation->session_id,
            ],
            'timestamp' => now()->toISOString(),
        ];
        
        Log::info('MessageSent Broadcasting Data', ['data' => $data]);
        return $data;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        Log::info('MessageSent Broadcasting As', ['event_name' => 'MessageSent']);
        return 'MessageSent';
    }
}
