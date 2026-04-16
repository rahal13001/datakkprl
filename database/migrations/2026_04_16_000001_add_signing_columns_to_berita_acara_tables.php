<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('berita_acara', function (Blueprint $table) {
            $table->timestamp('signing_deadline')->nullable()->after('status');
        });

        Schema::table('berita_acara_attendees', function (Blueprint $table) {
            $table->string('token')->unique()->nullable()->after('id');
            $table->boolean('is_signatory')->default(true)->after('is_officer');
            $table->timestamp('confirmed_at')->nullable()->after('is_signatory');
            $table->string('email')->nullable()->after('instansi');
            $table->string('no_hp')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('berita_acara_attendees', function (Blueprint $table) {
            $table->dropColumn(['token', 'is_signatory', 'confirmed_at', 'email', 'no_hp']);
        });

        Schema::table('berita_acara', function (Blueprint $table) {
            $table->dropColumn('signing_deadline');
        });
    }
};
