<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'chatbot_id',
        'session_id',
        'user_identifier',
        'is_active',
        'last_activity',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_activity' => 'datetime',
        'metadata' => 'array',
    ];

    // العلاقات
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function chatbot()
    {
        return $this->belongsTo(Chatbot::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    // النطاقات
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}