<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_material_access_id')->constrained()->cascadeOnDelete();
            $table->foreignId('learning_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('material_type', 100)->nullable();
            $table->unsignedBigInteger('material_id')->nullable();
            $table->string('material_key')->nullable()->index();
            $table->string('material_title')->nullable();
            $table->string('page_url', 2048)->nullable();
            $table->string('activity_type', 50)->index();
            $table->unsignedTinyInteger('progress_percent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_activity_logs');
    }
};
