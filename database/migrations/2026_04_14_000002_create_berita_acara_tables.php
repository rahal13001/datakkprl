<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('berita_acara', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('nomor_berita_acara')->nullable();
            $table->string('kbli')->nullable();
            $table->date('tanggal_pelaksanaan');
            $table->text('lokasi_permohonan')->nullable();

            // 6 structured result fields matching the legal template
            $table->text('hasil_deskripsi_rencana')->nullable();     // Point 1
            $table->text('hasil_lokasi_koordinat')->nullable();       // Point 2
            $table->text('hasil_info_pemanfaat')->nullable();         // Point 3
            $table->text('hasil_kondisi_ekosistem')->nullable();      // Point 4
            $table->text('hasil_perizinan')->nullable();              // Point 5
            $table->text('hasil_lainnya')->nullable();                // Point 6

            // Attachments
            $table->string('lampiran_peta')->nullable();              // Lampiran I: Peta Hasil Plotting
            $table->json('lampiran_dokumentasi')->nullable();         // Lampiran II: Dokumentasi
            $table->json('lampiran_lainnya')->nullable();             // Lampiran III: Hal lainnya

            // Applicant signature (encrypted file path on private disk)
            $table->string('tanda_tangan_pemohon')->nullable();

            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('berita_acara_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berita_acara_id')->constrained('berita_acara')->cascadeOnDelete();
            $table->string('nama');
            $table->string('jabatan')->nullable();
            $table->string('instansi')->nullable();
            $table->string('tanda_tangan')->nullable(); // encrypted file path on private disk
            $table->boolean('is_officer')->default(false); // true = internal officer, false = external attendee
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('berita_acara_attendees');
        Schema::dropIfExists('berita_acara');
    }
};
