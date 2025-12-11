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
        Schema::table('clients', function (Blueprint $table) {
            // New columns replacing contact_details JSON
            $table->string('name')->nullable()->after('ticket_number');
            $table->string('email')->nullable()->after('name');
            $table->string('whatsapp')->nullable()->after('email');
            $table->string('instance')->nullable()->after('whatsapp');
            $table->text('address')->nullable()->after('instance');
            $table->string('booking_type')->nullable()->default('personal')->after('address'); // personal, company
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['name', 'email', 'whatsapp', 'instance', 'address', 'booking_type']);
        });
    }
};
