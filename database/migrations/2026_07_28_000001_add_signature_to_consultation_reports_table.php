<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_reports', function (Blueprint $table): void {
            $table->text('officer_signature_encrypted')->nullable()->after('documentation_encrypted');
            $table->foreignId('signed_by')->nullable()->after('officer_signature_encrypted')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('signed_at')->nullable()->after('signed_by');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_reports', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('signed_by');
            $table->dropColumn(['officer_signature_encrypted', 'signed_at']);
        });
    }
};
