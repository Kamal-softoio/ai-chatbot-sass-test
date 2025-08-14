<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Chatbot;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

         // إنشاء مستأجر تجريبي
        $tenant = Tenant::create([
            'name' => 'مستأجر تجريبي',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'company_name' => 'شركة تجريبية',
            'plan' => 'premium',
            'max_chatbots' => 10,
            'max_messages_per_month' => 10000,
        ]);

        // إنشاء شات بوت تجريبي
        Chatbot::create([
            'tenant_id' => $tenant->id,
            'name' => 'المساعد الذكي التجريبي',
            'description' => 'روبوت محادثة ذكي للاختبار',
            'model_name' => 'llama2',
            'system_prompt' => 'أنت مساعد ذكي مفيد وودود. أجب على الأسئلة بوضوح ودقة باللغة العربية.',
            'is_public' => true,
            'settings' => [
                'temperature' => 0.7,
                'max_tokens' => 2048,
                'top_p' => 0.9,
            ],
            'status' => 'active',
        ]);

        $this->command->info('تم إنشاء البيانات التجريبية بنجاح!');
        $this->command->info('Email: test@example.com');
        $this->command->info('Password: password');

    }
}
