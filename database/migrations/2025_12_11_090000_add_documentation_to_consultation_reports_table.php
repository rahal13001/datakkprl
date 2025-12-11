<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('consultation_reports', function (Blueprint $table) {
            $table->json('documentation')->nullable()->after('feedback');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultation_reports', function (Blueprint $table) {
            $table->dropColumn('documentation');
        });
    }
};
