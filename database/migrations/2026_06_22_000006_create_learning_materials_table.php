<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_group_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('type');
            $table->string('pdf_path')->nullable();
            $table->string('video_url')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(false);
            $table->integer('view_count')->default(0);
            $table->integer('download_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['learning_group_id', 'is_published', 'sort_order'], 'learning_materials_group_published_sort_idx');
            $table->index(['type', 'is_published'], 'learning_materials_type_published_idx');
            $table->index(['is_featured', 'is_published'], 'learning_materials_featured_published_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_materials');
    }
};
