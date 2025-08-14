<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Tenant extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_name',
        'phone',
        'plan',
        'max_chatbots',
        'max_messages_per_month',
        'messages_used_this_month',
        'billing_cycle_start',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'billing_cycle_start' => 'date',
        'is_active' => 'boolean',
    ];

    // العلاقات
    public function chatbots()
    {
        return $this->hasMany(Chatbot::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    // التحقق من القدرة على إنشاء روبوت جديد
    public function canCreateChatbot(): bool
    {
        return $this->chatbots()->count() < $this->max_chatbots;
    }

    // التحقق من القدرة على إرسال رسائل
    public function canSendMessage(): bool
    {
        $this->resetMonthlyCounterIfNeeded();
        return $this->messages_used_this_month < $this->max_messages_per_month;
    }

    // إعادة تعيين العداد الشهري إذا لزم الأمر
    public function resetMonthlyCounterIfNeeded(): void
    {
        if ($this->billing_cycle_start->addMonth()->isPast()) {
            $this->update([
                'messages_used_this_month' => 0,
                'billing_cycle_start' => now(),
            ]);
        }
    }

    // زيادة عداد الرسائل
    public function incrementMessageCounter(): void
    {
        $this->increment('messages_used_this_month');
    }
}