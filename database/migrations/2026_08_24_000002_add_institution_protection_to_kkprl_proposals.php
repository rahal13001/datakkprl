<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kkprl_proposals', function (Blueprint $table): void {
            $table->text('institution_name')->nullable()->after('applicant_name');
            $table->text('institution_name_encrypted')->nullable()->after('institution_name');
        });
    }

    public function down(): void
    {
        Schema::table('kkprl_proposals', function (Blueprint $table): void {
            $table->dropColumn(['institution_name', 'institution_name_encrypted']);
        });
    }
};
