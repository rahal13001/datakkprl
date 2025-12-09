<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. App Settings
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Services
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Holidays
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. FAQs
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->string('slug')->unique();
            $table->longText('answer');
            $table->boolean('is_published')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // 5. Regulations
        Schema::create('regulations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('document_number')->nullable();
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->integer('download_count')->default(0);
            $table->boolean('is_published')->default(true);
            $table->longText('extracted_text')->nullable(); // JSON or Text for AI
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regulations');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('services');
        Schema::dropIfExists('app_settings');
    }
};
