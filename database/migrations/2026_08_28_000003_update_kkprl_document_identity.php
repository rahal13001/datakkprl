<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kkprl_proposal_documents', function (Blueprint $table): void {
            $table->dropUnique('kkprl_document_snapshot_unique');
            $table->unique(
                ['proposal_id', 'chapter', 'format', 'snapshot_hash', 'attachment_manifest_hash'],
                'kkprl_document_snapshot_manifest_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('kkprl_proposal_documents', function (Blueprint $table): void {
            $table->dropUnique('kkprl_document_snapshot_manifest_unique');
            $table->unique(
                ['proposal_id', 'chapter', 'format', 'snapshot_hash'],
                'kkprl_document_snapshot_unique',
            );
        });
    }
};
