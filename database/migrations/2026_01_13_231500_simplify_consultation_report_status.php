<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Expand enum
        DB::statement("ALTER TABLE consultation_reports MODIFY COLUMN status ENUM('draft', 'review', 'approved', 'rejected', 'completed') DEFAULT 'draft'");

        // 2. Migrate data
        DB::table('consultation_reports')->where('status', 'approved')->update(['status' => 'completed']);
        DB::table('consultation_reports')->whereIn('status', ['review', 'rejected'])->update(['status' => 'draft']);

        // 3. Restrict enum
        DB::statement("ALTER TABLE consultation_reports MODIFY COLUMN status ENUM('draft', 'completed') DEFAULT 'draft'");
    }

    public function down(): void
    {
        // 1. Expand enum
        DB::statement("ALTER TABLE consultation_reports MODIFY COLUMN status ENUM('draft', 'review', 'approved', 'rejected', 'completed') DEFAULT 'draft'");

        // 2. Migrate data
        DB::table('consultation_reports')->where('status', 'completed')->update(['status' => 'approved']);

        // 3. Restrict enum
        DB::statement("ALTER TABLE consultation_reports MODIFY COLUMN status ENUM('draft', 'review', 'approved', 'rejected') DEFAULT 'draft'");
    }
};
