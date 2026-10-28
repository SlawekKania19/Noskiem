<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wykonaj migrację.
     */
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            // Moment ostatniego comiesięcznego przypomnienia (animals:send-reminders).
            // Null = jeszcze nie wysłano — wtedy miesiąc liczymy od created_at (zatwierdzenia).
            $table->timestamp('last_reminder_at')->nullable()->after('mod_status');
        });
    }

    /**
     * Cofnij migrację.
     */
    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn('last_reminder_at');
        });
    }
};
