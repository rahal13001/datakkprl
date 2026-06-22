<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('satisfaction_survey_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('image_path');
            $table->boolean('is_published')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['year', 'quarter']);
            $table->index(['is_published', 'year', 'quarter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satisfaction_survey_results');
    }
};
