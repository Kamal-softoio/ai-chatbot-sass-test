<?php
// app/Models/Chatbot.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Chatbot extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'model_name',
        'system_prompt',
        'is_public',
        'widget_id',
        'settings',
        'status',
        'total_conversations',
        'total_messages',
        'last_activity',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'settings' => 'array',
        'last_activity' => 'datetime',
    ];

    // توليد widget_id تلقائياً
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($chatbot) {
            if (empty($chatbot->widget_id)) {
                $chatbot->widget_id = 'widget_' . Str::random(16);
            }
        });
    }

    // العلاقات
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    // النطاقات (Scopes)
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }
}