<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('personal_access_token_id')->nullable()
                ->constrained('personal_access_tokens')->nullOnDelete();
            $table->enum('platform', ['android', 'ios']);
            $table->string('installation_id');
            $table->string('device_name');
            $table->string('app_version', 50);
            $table->unsignedBigInteger('build_number')->default(1);
            $table->longText('push_registration_encrypted')->nullable();
            $table->string('push_registration_hash', 64)->nullable()->unique();
            $table->string('notification_permission', 30)->default('prompt');
            $table->timestamp('last_registered_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'installation_id']);
            $table->index(['user_id', 'disabled_at']);
            $table->index('last_seen_at');
        });

        Schema::create('push_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('notification_id')->nullable()->index();
            $table->uuid('user_device_id')->nullable();
            $table->foreign('user_device_id')->references('id')->on('user_devices')->nullOnDelete();
            $table->string('event_type');
            $table->string('provider_message_id')->nullable();
            $table->string('status', 30);
            $table->string('error_code')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamps();

            $table->unique(['notification_id', 'user_device_id']);
            $table->index(['status', 'attempted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_delivery_logs');
        Schema::dropIfExists('user_devices');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('personal_access_tokens');
    }
};
