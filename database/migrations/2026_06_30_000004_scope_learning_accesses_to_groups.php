<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_material_accesses', function (Blueprint $table) {
            $table->unsignedBigInteger('learning_group_id')->nullable()->after('browser_uuid');
            $table->string('group_key')->nullable()->after('learning_group_id');
            $table->string('group_title')->nullable()->after('group_key');

            $table->index(['learning_group_id', 'created_at'], 'learning_accesses_group_created_idx');
            $table->index(['group_key', 'created_at'], 'learning_accesses_group_key_created_idx');
        });

        DB::table('learning_material_accesses')
            ->whereNotNull('material_id')
            ->orderBy('id')
            ->each(function (object $access): void {
                $group = DB::table('learning_materials')
                    ->join('learning_groups', 'learning_groups.id', '=', 'learning_materials.learning_group_id')
                    ->where('learning_materials.id', $access->material_id)
                    ->select('learning_groups.id', 'learning_groups.title')
                    ->first();

                if ($group) {
                    DB::table('learning_material_accesses')->where('id', $access->id)->update([
                        'learning_group_id' => $group->id,
                        'group_key' => 'learning-group:'.$group->id,
                        'group_title' => $group->title,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('learning_material_accesses', function (Blueprint $table) {
            $table->dropIndex('learning_accesses_group_created_idx');
            $table->dropIndex('learning_accesses_group_key_created_idx');
            $table->dropColumn(['learning_group_id', 'group_key', 'group_title']);
        });
    }
};
