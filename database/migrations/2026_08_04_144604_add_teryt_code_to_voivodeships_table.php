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
        Schema::table('voivodeships', function (Blueprint $table) {
            // Kod TERC województwa (np. "24" dla śląskiego) — źródło: rejestr TERC GUS
            $table->string('teryt_code', 2)->nullable()->unique()->after('id');
        });
    }

    /**
     * Cofnij migrację.
     */
    public function down(): void
    {
        Schema::table('voivodeships', function (Blueprint $table) {
            $table->dropColumn('teryt_code');
        });
    }
};
