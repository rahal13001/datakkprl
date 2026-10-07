<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kkprl_applicants', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamp('approved_at')->nullable()->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('session_version')->default(1);
            $table->timestamps();
        });

        Schema::table('kkprl_proposals', function (Blueprint $table): void {
            $table->foreignId('applicant_id')->nullable()->constrained('kkprl_applicants')->restrictOnDelete();
        });

        Schema::create('kkprl_applicant_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('applicant_id')->constrained('kkprl_applicants')->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 48)->index();
            $table->foreignId('root_proposal_id')->nullable()->constrained('kkprl_proposals')->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->text('verification_reference')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kkprl_applicant_events');
        Schema::table('kkprl_proposals', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('applicant_id');
        });
        Schema::dropIfExists('kkprl_applicants');
    }
};
