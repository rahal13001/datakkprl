<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 9. Activity Logs
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('subject');
            $table->json('changes')->nullable();
            $table->timestamps();
        });

        // 10. Notification Logs
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('channel')->default('email'); // email
            $table->string('destination'); // email address
            $table->text('message_body');
            $table->string('status'); // sent, failed
            $table->timestamps();
        });

        // 11. AI Chat Logs
        Schema::create('ai_chat_logs', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->text('question');
            $table->text('response');
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_logs');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('activity_logs');
    }
};
