<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Chatbot;
use App\Models\Conversation;
use App\Models\Message;

class TenantServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // تطبيق Global Scope تلقائياً لضمان عزل البيانات
        $this->registerGlobalScopes();
    }

    private function registerGlobalScopes()
    {
        // تطبيق Tenant Scope على النماذج الحساسة
        Chatbot::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->has('current_tenant')) {
                $builder->where('tenant_id', app('current_tenant')->id);
            }
        });

        Conversation::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->has('current_tenant')) {
                $builder->where('tenant_id', app('current_tenant')->id);
            }
        });

        Message::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->has('current_tenant')) {
                $builder->where('tenant_id', app('current_tenant')->id);
            }
        });
    }

    public function register()
    {
        //
    }
}