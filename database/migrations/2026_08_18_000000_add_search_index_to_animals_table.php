<?php

use App\Models\Animal;
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
            // Zdenormalizowany tekst do wyszukiwania pełnotekstowego — budowany i odświeżany
            // automatycznie przez Animal::syncSearchIndex(), nie wypełniany ręcznie.
            $table->text('search_index')->nullable()->after('longitude');
        });

        Schema::table('animals', function (Blueprint $table) {
            $table->fullText('search_index');
        });

        // Wypełnienie pola dla istniejących ogłoszeń
        Animal::with(['species', 'breed', 'city', 'voivodeship', 'colors'])
            ->each(fn (Animal $animal) => $animal->syncSearchIndex());
    }

    /**
     * Cofnij migrację.
     */
    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table) {
            $table->dropFullText(['search_index']);
            $table->dropColumn('search_index');
        });
    }
};
