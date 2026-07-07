<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('satisfaction_surveys', function (Blueprint $table): void {
            $table->unsignedBigInteger('estimated_cost_savings')
                ->nullable()
                ->after('suggestion_encrypted');
        });
    }

    public function down(): void
    {
        Schema::table('satisfaction_surveys', function (Blueprint $table): void {
            $table->dropColumn('estimated_cost_savings');
        });
    }
};
