<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 6. Clients (The Ticket)
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique();
            $table->uuid('access_token');
            $table->json('contact_details'); // {name, email, wa, agency}
            $table->enum('status', ['pending', 'scheduled', 'waiting_approval', 'finished', 'canceled'])->default('pending');
            $table->json('metadata')->nullable();
            $table->foreignId('service_id')->constrained('services');
            $table->timestamps();
            $table->softDeletes();
        });

        // 7. Schedules (The Time Slot)
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_online')->default(false);
            $table->text('meeting_link')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 8. Assignments (The Pivot)
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->enum('status', ['hadir', 'izin_mendadak'])->default('hadir');
            $table->integer('score')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('clients');
    }
};
