<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add is_chunked column to regulations table
        Schema::table('regulations', function (Blueprint $table) {
            $table->boolean('is_chunked')->default(false)->after('extracted_text');
        });

        // Create regulation_chunks table
        Schema::create('regulation_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regulation_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->text('chunk_text');
            $table->unsignedInteger('word_count')->nullable();
            $table->timestamps();

            $table->index(['regulation_id', 'chunk_index']);
        });

        // Add FULLTEXT index for MySQL
        DB::statement('ALTER TABLE regulation_chunks ADD FULLTEXT ft_chunk_text (chunk_text)');
    }

    public function down(): void
    {
        Schema::dropIfExists('regulation_chunks');

        Schema::table('regulations', function (Blueprint $table) {
            $table->dropColumn('is_chunked');
        });
    }
};
