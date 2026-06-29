<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_material_accesses', function (Blueprint $table) {
            $table->id();
            $table->uuid('access_uuid')->unique();
            $table->uuid('browser_uuid')->nullable()->index();
            $table->string('material_type', 100)->nullable();
            $table->unsignedBigInteger('material_id')->nullable();
            $table->string('material_key')->index();
            $table->string('material_title')->nullable();
            $table->string('name', 150);
            $table->string('institution', 200);
            $table->string('access_purpose', 255);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->unsignedInteger('visit_count')->default(0);
            $table->timestamps();

            $table->index(['material_id', 'created_at']);
            $table->index(['material_key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_material_accesses');
    }
};
