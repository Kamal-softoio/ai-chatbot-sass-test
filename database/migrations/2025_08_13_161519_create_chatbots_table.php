<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('chatbots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('model_name')->default('llama2');
            $table->text('system_prompt')->nullable();
            $table->boolean('is_public')->default(false);
            $table->string('widget_id')->unique();
            $table->json('settings')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('total_conversations')->default(0);
            $table->integer('total_messages')->default(0);
            $table->timestamp('last_activity')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index('widget_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('chatbots');
    }
};