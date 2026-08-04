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
        Schema::table('cities', function (Blueprint $table) {
            // Kod SYM miejscowości — źródło: rejestr SIMC GUS
            $table->string('teryt_sym', 7)->nullable()->unique()->after('id');

            // Indeks pod wyszukiwanie "miejscowości w danym województwie zaczynające się na..."
            $table->index(['voivodeship_id', 'name_pl']);
        });
    }

    /**
     * Cofnij migrację.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropIndex(['voivodeship_id', 'name_pl']);
            $table->dropColumn('teryt_sym');
        });
    }
};
