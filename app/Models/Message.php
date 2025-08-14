<?php

// app/Models/Message.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'metadata',
        'tokens_used',
        'processing_time',
    ];

    protected $casts = [
        'metadata' => 'array',
        'tokens_used' => 'integer',
        'processing_time' => 'float',
    ];

    // العلاقات
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}