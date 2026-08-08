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
            // Wolny tekst — użytkownik dopisuje własny opis lub wybiera gotowe frazy
            // z panelu admina (przyciski szybkiego dodawania w formularzu, jak przy ident_marks)
            $table->text('behavior')->nullable()->after('ident_marks');
        });
    }

    /**
     * Cofnij migrację.
     */
    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropColumn('behavior');
        });
    }
};
