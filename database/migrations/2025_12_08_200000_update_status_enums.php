<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'scheduled' to assignments status
        DB::statement("ALTER TABLE assignments MODIFY COLUMN status ENUM('scheduled', 'hadir', 'izin_mendadak') NOT NULL DEFAULT 'scheduled'");
        
        // Add 'in_progress' to clients status (keeping waiting_approval for legacy safety)
        DB::statement("ALTER TABLE clients MODIFY COLUMN status ENUM('pending', 'scheduled', 'waiting_approval', 'in_progress', 'finished', 'canceled') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Revert assignments
        // Warning: This will fail if 'scheduled' values exist.
        DB::statement("ALTER TABLE assignments MODIFY COLUMN status ENUM('hadir', 'izin_mendadak') NOT NULL DEFAULT 'hadir'");
        
        // Revert clients
        DB::statement("ALTER TABLE clients MODIFY COLUMN status ENUM('pending', 'scheduled', 'waiting_approval', 'finished', 'canceled') NOT NULL DEFAULT 'pending'");
    }
};
