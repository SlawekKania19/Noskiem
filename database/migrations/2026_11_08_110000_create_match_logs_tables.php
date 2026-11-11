<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Logi dopasowań po zdjęciach - do późniejszej kalibracji progów. Tego nie da się
// odtworzyć wstecz, dlatego zapisujemy też kandydatów PONIŻEJ progu (top log_floor_n).
// Celowo BEZ kluczy obcych do animals/animal_edits: ogłoszenia są usuwane (retencja,
// autor), a log ma przetrwać. Tylko ID - bez danych osobowych.
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        // ** Jedno wyszukiwanie (status "ok"): kto pytał, jaką wersją modelu, z jakimi parametrami
        Schema::create('match_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_request_id')->nullable()->constrained('match_requests')->nullOnDelete();
            // Zapytanie: zgłoszenie (przed moderacją) albo ogłoszenie (po zatwierdzeniu)
            $table->unsignedBigInteger('query_animal_edit_id')->nullable()->index();
            $table->unsignedBigInteger('query_animal_id')->nullable()->index();
            $table->string('model_version', 64);
            // Progi, top_n, wagi, filtry - dokładnie to, co poszło do Pythona
            $table->json('params');
            $table->unsignedInteger('n_candidates');
            $table->unsignedInteger('n_compared');
            $table->unsignedInteger('n_pending_embedding');
            $table->unsignedSmallInteger('n_shown');
            // Skąd wyszukiwanie: "notify" (mail po zgłoszeniu); na przyszłość np. "page"
            $table->string('channel', 20);
            $table->timestamp('created_at')->nullable();
        });

        // ** Kandydaci z wynikami - pokazani i niepokazani (do log_floor_n)
        Schema::create('match_search_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_search_id')->constrained('match_searches')->cascadeOnDelete();
            $table->unsignedBigInteger('animal_id')->index();
            // Czyste podobieństwo zdjęć (max po parach) i bonus rasa/kolor (tylko kolejność)
            $table->float('score');
            $table->float('bonus');
            $table->unsignedSmallInteger('rank');
            // strong | possible | below
            $table->string('level', 10);
            $table->boolean('shown');
            // Pozycja na wyświetlonej liście (null = niepokazany)
            $table->unsignedSmallInteger('position')->nullable();
            $table->boolean('breed_match')->nullable();
            $table->float('color_overlap')->nullable();
            $table->float('distance_km')->nullable();
            $table->unsignedSmallInteger('best_photo_index')->nullable();

            $table->index(['match_search_id', 'position']);
        });

        // ** Zdarzenia użytkownika. Teraz tylko "click" - SŁABY, obciążony sygnał (pozycja
        // wpływa na klik, skanery linków w poczcie otwierają linki same), więc wyłącznie log.
        // Później np. "user_confirmed_same" / "user_rejected" jako osobne, jawne typy
        Schema::create('match_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_search_id')->nullable()->constrained('match_searches')->nullOnDelete();
            $table->string('event', 30);
            // "email" - link z maila MatchCandidatesFound
            $table->string('source', 20);
            $table->unsignedBigInteger('query_animal_edit_id')->nullable();
            $table->unsignedBigInteger('query_animal_id')->nullable();
            $table->unsignedBigInteger('animal_id')->index();
            $table->unsignedSmallInteger('position')->nullable();
            $table->string('model_version', 64);
            // ** Do odfiltrowania automatów (skanery linków) przy analizie - bez IP
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_events');
        Schema::dropIfExists('match_search_results');
        Schema::dropIfExists('match_searches');
    }
};
