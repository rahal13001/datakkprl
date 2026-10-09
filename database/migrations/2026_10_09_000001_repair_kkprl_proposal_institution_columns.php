<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('kkprl_proposals', 'institution_name')) {
            Schema::table('kkprl_proposals', function (Blueprint $table): void {
                $table->text('institution_name')->nullable();
            });
        }

        if (! Schema::hasColumn('kkprl_proposals', 'institution_name_encrypted')) {
            Schema::table('kkprl_proposals', function (Blueprint $table): void {
                $table->text('institution_name_encrypted')->nullable();
            });
        }
    }

    public function down(): void
    {
        // A repaired or pre-existing column may contain data; retain both on rollback.
    }
};
