<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kkprl_proposal_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('kkprl_proposals')->cascadeOnDelete();
            $table->string('chapter'); // bag-2, bag-3, bag-4, bag-5
            $table->json('messages')->nullable(); // Store the chat history JSON array
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kkprl_proposal_chats');
    }
};
