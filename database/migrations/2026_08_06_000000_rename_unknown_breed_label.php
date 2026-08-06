<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Wykonaj migrację.
     *
     * "Nie wiem" jako nazwa rasy wyglądało źle na kartach ogłoszeń i stronie
     * szczegółów (np. "Rasa: Nie wiem") — zmieniamy istniejące wiersze na
     * "Rasa nieznana", zgodnie z aktualizacją BreedsSeeder.
     */
    public function up(): void
    {
        DB::table('breeds')
            ->where('breed_PL', 'Nie wiem')
            ->update(['breed_PL' => 'Rasa nieznana']);
    }

    /**
     * Cofnij migrację.
     */
    public function down(): void
    {
        DB::table('breeds')
            ->where('breed_PL', 'Rasa nieznana')
            ->update(['breed_PL' => 'Nie wiem']);
    }
};
