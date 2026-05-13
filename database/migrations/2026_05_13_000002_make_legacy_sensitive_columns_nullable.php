<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->uuid('access_token')->nullable()->change();
            $table->json('contact_details')->nullable()->change();
        });

        Schema::table('berita_acara_attendees', function (Blueprint $table) {
            $table->string('nama')->nullable()->change();
        });

        Schema::table('notification_logs', function (Blueprint $table) {
            $table->string('destination')->nullable()->change();
            $table->text('message_body')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Intentionally not reverted: legacy sensitive columns may be redacted to null.
    }
};
