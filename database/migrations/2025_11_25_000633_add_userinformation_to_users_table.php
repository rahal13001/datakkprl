<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('summary_user_id')->nullable()->unique()->after('id');
            $table->string('jabatan')->nullable()->after('name');
            $table->string('nip')->nullable()->after('jabatan');
            $table->string('avatar_url')->nullable();
            $table->text('fcm_token')->nullable();
            $table->boolean('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('summary_user_id');
            $table->dropColumn('jabatan');
            $table->dropColumn('nip');
            $table->dropColumn('status');
        });
    }
};
