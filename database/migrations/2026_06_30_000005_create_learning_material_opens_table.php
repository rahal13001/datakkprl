<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_material_opens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_material_access_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('material_id');
            $table->string('material_type', 100)->nullable();
            $table->string('material_key');
            $table->string('material_title');
            $table->timestamp('first_opened_at');
            $table->timestamp('last_opened_at');
            $table->unsignedInteger('open_count')->default(1);
            $table->timestamps();

            $table->unique(['learning_material_access_id', 'material_id'], 'learning_material_opens_access_material_unique');
            $table->index(['material_id', 'last_opened_at']);
        });

        DB::table('learning_activity_logs')
            ->where('activity_type', 'open_material')
            ->whereNotNull('material_id')
            ->selectRaw('learning_material_access_id, material_id, MAX(material_type) as material_type, MAX(material_key) as material_key, MAX(material_title) as material_title, MIN(occurred_at) as first_opened_at, MAX(occurred_at) as last_opened_at, COUNT(*) as open_count')
            ->groupBy('learning_material_access_id', 'material_id')
            ->orderBy('learning_material_access_id')
            ->each(function (object $activity): void {
                DB::table('learning_material_opens')->insert([
                    'learning_material_access_id' => $activity->learning_material_access_id,
                    'material_id' => $activity->material_id,
                    'material_type' => $activity->material_type,
                    'material_key' => $activity->material_key,
                    'material_title' => $activity->material_title,
                    'first_opened_at' => $activity->first_opened_at,
                    'last_opened_at' => $activity->last_opened_at,
                    'open_count' => $activity->open_count,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_material_opens');
    }
};
