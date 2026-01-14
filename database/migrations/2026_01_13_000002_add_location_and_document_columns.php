<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add requires_documents to services table
        Schema::table('services', function (Blueprint $table) {
            $table->boolean('requires_documents')->default(false)->after('is_active');
        });

        // Add location and document columns to clients table
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('consultation_location_id')
                ->nullable()
                ->after('service_id')
                ->constrained('consultation_locations')
                ->nullOnDelete();
            $table->json('supporting_documents')->nullable()->after('metadata');
            $table->string('coordinate_file')->nullable()->after('supporting_documents');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['consultation_location_id']);
            $table->dropColumn(['consultation_location_id', 'supporting_documents', 'coordinate_file']);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('requires_documents');
        });
    }
};
