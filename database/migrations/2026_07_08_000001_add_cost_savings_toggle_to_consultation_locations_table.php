<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_locations', function (Blueprint $table): void {
            $table->boolean('requires_cost_savings_estimate')
                ->default(false)
                ->after('is_online');
        });

        DB::table('consultation_locations')
            ->where('is_online', true)
            ->update(['requires_cost_savings_estimate' => true]);
    }

    public function down(): void
    {
        Schema::table('consultation_locations', function (Blueprint $table): void {
            $table->dropColumn('requires_cost_savings_estimate');
        });
    }
};
