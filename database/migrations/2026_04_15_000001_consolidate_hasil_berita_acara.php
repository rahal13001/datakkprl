<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add the new single column
        Schema::table('berita_acara', function (Blueprint $table) {
            $table->longText('hasil_pendampingan')->nullable()->after('lokasi_permohonan');
        });

        // Migrate existing data: merge 6 fields into 1
        $records = DB::table('berita_acara')->get();
        foreach ($records as $record) {
            $parts = [];
            if ($record->hasil_deskripsi_rencana) $parts[] = $record->hasil_deskripsi_rencana;
            if ($record->hasil_lokasi_koordinat) $parts[] = $record->hasil_lokasi_koordinat;
            if ($record->hasil_info_pemanfaat) $parts[] = $record->hasil_info_pemanfaat;
            if ($record->hasil_kondisi_ekosistem) $parts[] = $record->hasil_kondisi_ekosistem;
            if ($record->hasil_perizinan) $parts[] = $record->hasil_perizinan;
            if ($record->hasil_lainnya) $parts[] = $record->hasil_lainnya;

            if (!empty($parts)) {
                DB::table('berita_acara')
                    ->where('id', $record->id)
                    ->update(['hasil_pendampingan' => implode("\n", $parts)]);
            }
        }

        // Drop the old 6 columns
        Schema::table('berita_acara', function (Blueprint $table) {
            $table->dropColumn([
                'hasil_deskripsi_rencana',
                'hasil_lokasi_koordinat',
                'hasil_info_pemanfaat',
                'hasil_kondisi_ekosistem',
                'hasil_perizinan',
                'hasil_lainnya',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('berita_acara', function (Blueprint $table) {
            $table->text('hasil_deskripsi_rencana')->nullable();
            $table->text('hasil_lokasi_koordinat')->nullable();
            $table->text('hasil_info_pemanfaat')->nullable();
            $table->text('hasil_kondisi_ekosistem')->nullable();
            $table->text('hasil_perizinan')->nullable();
            $table->text('hasil_lainnya')->nullable();
        });

        Schema::table('berita_acara', function (Blueprint $table) {
            $table->dropColumn('hasil_pendampingan');
        });
    }
};
