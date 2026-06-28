<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dodajemy animal_edit_id i czynimy user_id nullable w moderation_logs
        Schema::table('moderation_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->unsignedBigInteger('animal_edit_id')->nullable()->after('animal_id');
            $table->foreign('animal_edit_id')->references('id')->on('animal_edits')->onDelete('set null');
        });

        // Dodajemy mod_reject_reason do animal_edits
        Schema::table('animal_edits', function (Blueprint $table) {
            $table->text('mod_reject_reason')->nullable()->after('mod_status');
        });
    }

    public function down(): void
    {
        Schema::table('moderation_logs', function (Blueprint $table) {
            $table->dropForeign(['animal_edit_id']);
            $table->dropColumn('animal_edit_id');
            $table->dropForeign(['user_id']);
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('animal_edits', function (Blueprint $table) {
            $table->dropColumn('mod_reject_reason');
        });
    }
};
