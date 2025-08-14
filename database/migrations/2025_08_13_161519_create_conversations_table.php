<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('chatbot_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->index();
            $table->string('user_identifier')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_activity');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['chatbot_id', 'is_active']);
            $table->index(['session_id', 'chatbot_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('conversations');
    }
};