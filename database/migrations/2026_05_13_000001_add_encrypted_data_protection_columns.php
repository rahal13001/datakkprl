<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->text('access_token_encrypted')->nullable();
            $table->char('access_token_hash', 64)->nullable()->index();
            $table->text('name_encrypted')->nullable();
            $table->text('email_encrypted')->nullable();
            $table->char('email_hash', 64)->nullable()->index();
            $table->text('whatsapp_encrypted')->nullable();
            $table->char('whatsapp_hash', 64)->nullable()->index();
            $table->text('instance_encrypted')->nullable();
            $table->longText('address_encrypted')->nullable();
            $table->longText('metadata_encrypted')->nullable();
            $table->longText('supporting_documents_encrypted')->nullable();
            $table->longText('supporting_document_links_encrypted')->nullable();
            $table->text('coordinate_file_encrypted')->nullable();
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->text('meeting_link_encrypted')->nullable();
        });

        Schema::table('berita_acara', function (Blueprint $table) {
            $table->text('attendance_url_token_encrypted')->nullable();
            $table->char('attendance_url_token_hash', 64)->nullable()->unique();
            $table->longText('lokasi_permohonan_encrypted')->nullable();
            $table->longText('hasil_pendampingan_encrypted')->nullable();
            $table->text('tanda_tangan_pemohon_encrypted')->nullable();
            $table->text('lampiran_peta_encrypted')->nullable();
            $table->longText('lampiran_dokumentasi_encrypted')->nullable();
            $table->longText('lampiran_lainnya_encrypted')->nullable();
        });

        Schema::table('berita_acara_attendees', function (Blueprint $table) {
            $table->text('token_encrypted')->nullable();
            $table->char('token_hash', 64)->nullable()->unique();
            $table->text('nama_encrypted')->nullable();
            $table->text('jabatan_encrypted')->nullable();
            $table->text('instansi_encrypted')->nullable();
            $table->text('email_encrypted')->nullable();
            $table->char('email_hash', 64)->nullable()->index();
            $table->text('no_hp_encrypted')->nullable();
            $table->text('tanda_tangan_encrypted')->nullable();
        });

        Schema::table('consultation_reports', function (Blueprint $table) {
            $table->longText('content_encrypted')->nullable();
            $table->longText('feedback_encrypted')->nullable();
            $table->longText('documentation_encrypted')->nullable();
        });

        Schema::table('satisfaction_surveys', function (Blueprint $table) {
            $table->longText('criticism_encrypted')->nullable();
            $table->longText('suggestion_encrypted')->nullable();
        });

        Schema::table('notification_logs', function (Blueprint $table) {
            $table->text('destination_encrypted')->nullable();
            $table->longText('message_body_encrypted')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('notification_logs', function (Blueprint $table) {
            $table->dropColumn(['destination_encrypted', 'message_body_encrypted']);
        });

        Schema::table('satisfaction_surveys', function (Blueprint $table) {
            $table->dropColumn(['criticism_encrypted', 'suggestion_encrypted']);
        });

        Schema::table('consultation_reports', function (Blueprint $table) {
            $table->dropColumn(['content_encrypted', 'feedback_encrypted', 'documentation_encrypted']);
        });

        Schema::table('berita_acara_attendees', function (Blueprint $table) {
            $table->dropColumn([
                'token_encrypted', 'token_hash', 'nama_encrypted', 'jabatan_encrypted', 'instansi_encrypted',
                'email_encrypted', 'email_hash', 'no_hp_encrypted', 'tanda_tangan_encrypted',
            ]);
        });

        Schema::table('berita_acara', function (Blueprint $table) {
            $table->dropColumn([
                'attendance_url_token_encrypted', 'attendance_url_token_hash', 'lokasi_permohonan_encrypted',
                'hasil_pendampingan_encrypted', 'tanda_tangan_pemohon_encrypted', 'lampiran_peta_encrypted',
                'lampiran_dokumentasi_encrypted', 'lampiran_lainnya_encrypted',
            ]);
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('meeting_link_encrypted');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'access_token_encrypted', 'access_token_hash', 'name_encrypted', 'email_encrypted', 'email_hash',
                'whatsapp_encrypted', 'whatsapp_hash', 'instance_encrypted', 'address_encrypted',
                'metadata_encrypted', 'supporting_documents_encrypted', 'supporting_document_links_encrypted',
                'coordinate_file_encrypted',
            ]);
        });
    }
};
