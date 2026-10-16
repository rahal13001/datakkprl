<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kkprl_proposal_documents', function (Blueprint $table): void {
            $table->timestamp('deleted_at')->nullable();
            $table->unsignedBigInteger('deleted_by_applicant_id')->nullable();
            $table->index(['proposal_id', 'deleted_at'], 'kkprl_document_deletion_index');
        });
    }

    public function down(): void
    {
        Schema::table('kkprl_proposal_documents', function (Blueprint $table): void {
            $table->dropIndex('kkprl_document_deletion_index');
            $table->dropColumn(['deleted_at', 'deleted_by_applicant_id']);
        });
    }
};
