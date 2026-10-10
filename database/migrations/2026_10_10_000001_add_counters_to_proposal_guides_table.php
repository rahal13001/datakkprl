<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposal_guides', function (Blueprint $table) {
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('download_count')->default(0);
        });

        // Restore the client's last known totals without discarding larger legacy totals.
        $legacySlug = 'panduan-perolehan-data-oseanografi-ekosistem-pesisir-batimetri-dan-pemetaan-dasar-bagi-sektor-perikanan-skala-kecil-serta-tingkat-resiko-rendah';
        $guideSlug = 'pedoman-perolehan-data-proposal-kkprl-skala-kecil';

        DB::transaction(function () use ($legacySlug, $guideSlug) {
            $guides = DB::table('proposal_guides as guides')
                ->leftJoin('learning_materials as materials', 'materials.id', '=', 'guides.legacy_material_id')
                ->select('guides.id', 'guides.title', 'materials.slug', 'materials.view_count as legacy_views', 'materials.download_count as legacy_downloads')
                ->get();

            foreach ($guides as $guide) {
                $isLegacyGuide = $guide->slug === $legacySlug || in_array(Str::slug($guide->title), [$legacySlug, $guideSlug], true);
                DB::table('proposal_guides')->where('id', $guide->id)->update([
                    'view_count' => max((int) $guide->legacy_views, $isLegacyGuide ? 96 : 0),
                    'download_count' => max((int) $guide->legacy_downloads, $isLegacyGuide ? 65 : 0),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('proposal_guides', fn (Blueprint $table) => $table->dropColumn(['view_count', 'download_count']));
    }
};
