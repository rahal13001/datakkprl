<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('activity_code')->index();
            $table->text('detail')->nullable();
            $table->date('date');
            $table->string('organizer')->nullable();
            $table->decimal('application_size', 15, 2)->nullable();
            $table->decimal('technical_assessment_size', 15, 2)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('pnbp_potential', 15, 2)->nullable();
            $table->decimal('zero_rupiah_incentive', 15, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
