<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Propozycje AI odrzucone przez moderatora (App\Services\MatchExclusions).
// Para: zgłoszenie/ogłoszenie, dla którego szukamy + kandydat, który do niego nie pasuje.
// Odrzucony kandydat nie trafia do maila (ani do autora, ani do autora kandydata).
// Jak pozostałe logi dopasowań - BEZ kluczy obcych do animals/animal_edits.
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_exclusions', function (Blueprint $table) {
            $table->id();
            // Strona zapytania: oczekujące zgłoszenie i/lub ogłoszenie (po zatwierdzeniu
            // zgłoszenia animal_id uzupełnia się automatycznie - ModerationLog::created)
            $table->unsignedBigInteger('animal_edit_id')->nullable()->index();
            $table->unsignedBigInteger('animal_id')->nullable()->index();
            // Kandydat odrzucony przez moderatora
            $table->unsignedBigInteger('candidate_animal_id')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });

        // ** W logu wyszukiwań - kandydat ponad progiem, którego ukrył moderator (dane do kalibracji progów)
        Schema::table('match_search_results', function (Blueprint $table) {
            $table->boolean('excluded')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('match_search_results', function (Blueprint $table) {
            $table->dropColumn('excluded');
        });

        Schema::dropIfExists('match_exclusions');
    }
};
