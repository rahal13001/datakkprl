<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Step 0: Fix any invalid data that might cause truncation (e.g., empty strings)
        DB::update("UPDATE clients SET status = 'pending' WHERE status NOT IN ('pending', 'scheduled', 'waiting_approval', 'finished', 'canceled')");

        // Step 1: First expand the enum to include ALL values (old + new)
        DB::statement("ALTER TABLE clients MODIFY COLUMN status ENUM('pending', 'scheduled', 'waiting_approval', 'finished', 'canceled', 'waiting', 'completed') DEFAULT 'waiting'");
        
        // Step 2: Update existing status values to the new simplified values
        DB::table('clients')->where('status', 'pending')->update(['status' => 'waiting']);
        DB::table('clients')->where('status', 'waiting_approval')->update(['status' => 'waiting']);
        DB::table('clients')->where('status', 'finished')->update(['status' => 'completed']);
        DB::table('clients')->where('status', 'canceled')->update(['status' => 'completed']);
        
        // Step 3: Now restrict the enum to only the new values
        DB::statement("ALTER TABLE clients MODIFY COLUMN status ENUM('waiting', 'scheduled', 'completed') DEFAULT 'waiting'");
    }

    public function down(): void
    {
        // Expand enum to include all values
        DB::statement("ALTER TABLE clients MODIFY COLUMN status ENUM('pending', 'scheduled', 'waiting_approval', 'finished', 'canceled', 'waiting', 'completed') DEFAULT 'pending'");
        
        // Revert status values
        DB::table('clients')->where('status', 'waiting')->update(['status' => 'pending']);
        DB::table('clients')->where('status', 'completed')->update(['status' => 'finished']);
        
        // Restrict to old enum values
        DB::statement("ALTER TABLE clients MODIFY COLUMN status ENUM('pending', 'scheduled', 'waiting_approval', 'finished', 'canceled') DEFAULT 'pending'");
    }
};
