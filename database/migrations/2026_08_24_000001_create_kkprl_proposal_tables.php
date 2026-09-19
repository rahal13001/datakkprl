<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kkprl_proposals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('root_proposal_id')->nullable()->constrained('kkprl_proposals')->nullOnDelete();
            $table->foreignId('revision_of_id')->nullable()->constrained('kkprl_proposals')->nullOnDelete();
            $table->unsignedInteger('revision_number')->default(0);
            $table->string('ticket_number', 64)->index();
            $table->string('revision_label', 32)->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->string('applicant_type', 32)->nullable()->index();
            $table->text('applicant_name')->nullable();
            $table->text('applicant_name_encrypted')->nullable();
            $table->text('phone')->nullable();
            $table->text('phone_encrypted')->nullable();
            $table->char('phone_hash', 64)->nullable()->index();
            $table->text('email')->nullable();
            $table->text('email_encrypted')->nullable();
            $table->char('email_hash', 64)->nullable()->index();
            $table->string('province', 120)->nullable()->index();
            $table->string('regency', 120)->nullable()->index();
            $table->string('activity_type', 32)->nullable()->index();
            $table->longText('payload')->nullable();
            $table->longText('payload_encrypted')->nullable();
            $table->string('form_version', 32)->default('1');
            $table->string('template_version', 32)->default('2026-08-24');
            $table->timestamp('last_saved_at')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['root_proposal_id', 'status']);
            $table->index(['revision_of_id', 'revision_number']);
            $table->unique(['ticket_number', 'revision_number'], 'kkprl_ticket_revision_unique');
        });

        Schema::create('kkprl_proposal_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proposal_id')->constrained('kkprl_proposals')->cascadeOnDelete();
            $table->string('chapter', 16)->index();
            $table->string('section', 120)->nullable();
            $table->string('field', 120)->nullable();
            $table->string('attachment_role', 48);
            $table->string('placement', 16)->default('appendix');
            $table->string('anchor_key', 180)->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->text('caption')->nullable();
            $table->string('original_name', 255);
            $table->string('storage_disk', 32)->default('kkprl_private');
            $table->text('storage_path');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->string('checksum', 128)->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['proposal_id', 'chapter', 'deleted_at']);
        });

        Schema::create('kkprl_proposal_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proposal_id')->constrained('kkprl_proposals')->cascadeOnDelete();
            $table->string('chapter', 16);
            $table->string('format', 8);
            $table->string('generation_scope', 16)->default('chapter');
            $table->string('template_version', 32);
            $table->char('snapshot_hash', 64);
            $table->char('attachment_manifest_hash', 64);
            $table->longText('snapshot_payload_encrypted')->nullable();
            $table->longText('attachment_manifest_encrypted')->nullable();
            $table->string('storage_disk', 32)->default('kkprl_private');
            $table->text('storage_path')->nullable();
            $table->string('generation_status', 24)->default('pending')->index();
            $table->string('error_code', 80)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['proposal_id', 'chapter', 'format', 'snapshot_hash'], 'kkprl_document_snapshot_unique');
        });

        Schema::create('kkprl_proposal_review_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proposal_id')->constrained('kkprl_proposals')->cascadeOnDelete();
            $table->string('event_type', 48)->index();
            $table->string('actor_type', 24);
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->text('reason')->nullable();
            $table->longText('before_payload')->nullable();
            $table->longText('after_payload')->nullable();
            $table->longText('metadata')->nullable();
            $table->string('request_id', 100)->nullable()->index();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('kkprl_proposal_edit_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proposal_id')->constrained('kkprl_proposals')->cascadeOnDelete();
            $table->foreignId('revision_id')->constrained('kkprl_proposals')->cascadeOnDelete();
            $table->unsignedBigInteger('approval_id')->nullable()->index();
            $table->unsignedBigInteger('started_by')->index();
            $table->string('status', 24)->default('active')->index();
            $table->timestamp('session_started_at')->useCurrent();
            $table->timestamp('session_finished_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('end_reason', 48)->nullable();
            $table->timestamps();
        });

        Schema::create('kkprl_proposal_edit_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('proposal_id')->constrained('kkprl_proposals')->cascadeOnDelete();
            $table->foreignId('revision_id')->constrained('kkprl_proposals')->cascadeOnDelete();
            $table->unsignedBigInteger('requested_by')->index();
            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->string('status', 24)->default('pending')->index();
            $table->text('reason');
            $table->longText('scope');
            $table->longText('actor_metadata')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->unsignedBigInteger('approval_event_id')->nullable()->index();
            $table->unsignedBigInteger('edit_session_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kkprl_proposal_edit_approvals');
        Schema::dropIfExists('kkprl_proposal_edit_sessions');
        Schema::dropIfExists('kkprl_proposal_review_events');
        Schema::dropIfExists('kkprl_proposal_documents');
        Schema::dropIfExists('kkprl_proposal_attachments');
        Schema::dropIfExists('kkprl_proposals');
    }
};
