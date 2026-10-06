<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_guides', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('pdf_path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->foreignId('legacy_material_id')->nullable()->unique()->constrained('learning_materials')->nullOnDelete();
            $table->timestamps();
        });

        DB::transaction(function () {
            $material = DB::table('learning_materials')
                ->where('slug', 'panduan-perolehan-data-oseanografi-ekosistem-pesisir-batimetri-dan-pemetaan-dasar-bagi-sektor-perikanan-skala-kecil-serta-tingkat-resiko-rendah')
                ->where('type', 'pdf')->whereNotNull('pdf_path')->whereNull('deleted_at')->first();

            if (! $material || blank($material->pdf_path)) {
                return;
            }

            $visibleGroup = DB::table('learning_groups as groups')
                ->join('learning_categories as categories', 'categories.id', '=', 'groups.learning_category_id')
                ->where('groups.id', $material->learning_group_id)
                ->where('groups.is_published', true)->whereNull('groups.deleted_at')
                ->where('categories.is_active', true)->whereNull('categories.deleted_at')->exists();

            DB::table('proposal_guides')->insert([
                'title' => $material->title,
                'description' => $material->description,
                'pdf_path' => $material->pdf_path,
                'sort_order' => $material->sort_order,
                'is_published' => $material->is_published && $visibleGroup,
                'legacy_material_id' => $material->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('learning_materials')->where('id', $material->id)->update(['deleted_at' => now()]);
        });
    }

    public function down(): void
    {
        DB::table('learning_materials')->whereIn('id', DB::table('proposal_guides')->whereNotNull('legacy_material_id')->select('legacy_material_id'))
            ->update(['deleted_at' => null]);
        Schema::dropIfExists('proposal_guides');
    }
};
