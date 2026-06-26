<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_feedback', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_anonymous')->default(true);
            $table->string('submitter_name')->nullable();
            $table->text('feedback')->nullable();
            $table->text('suggestion')->nullable();
            $table->longText('submitter_name_encrypted')->nullable();
            $table->longText('feedback_encrypted')->nullable();
            $table->longText('suggestion_encrypted')->nullable();
            $table->timestamps();
        });

        Schema::create('public_feedback_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('public_feedback_id')->constrained('public_feedback')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['public_feedback_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_feedback_user');
        Schema::dropIfExists('public_feedback');
    }
};
