<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Adres IP osoby dodającej/edytującej zgłoszenie — na wypadek problemów prawnych
// (kto faktycznie umieścił daną treść). Widoczny wyłącznie w panelu admina
// (AnimalResource — tylko Admin, patrz RestrictedToAdmin), nigdzie publicznie.
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->string('submitter_ip', 45)->nullable()->after('edit_token');
        });

        Schema::table('animal_edits', function (Blueprint $table) {
            $table->string('submitter_ip', 45)->nullable()->after('edit_token');
        });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn('submitter_ip');
        });

        Schema::table('animal_edits', function (Blueprint $table) {
            $table->dropColumn('submitter_ip');
        });
    }
};
