<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Podstawowe statystyki prowadzone przez aplikację (strona "Statystyki" w panelu):
// - stat_events - dziennik zdarzeń (zgłoszenie, publikacja, odnalezienie, usunięcie...).
//   Celowo BEZ kluczy obcych i z kopią typu/gatunku/województwa - ogłoszenia są
//   usuwane (retencja, rezygnacja z subskrypcji), a statystyki historyczne muszą zostać.
//   Żadnych danych osobowych - tylko liczby i słowniki.
// - posts.views_count + post_views - odsłony wpisów bloga (łącznie i per dzień).
// Na końcu uzupełnienie dziennika z danych, które już są w bazie (tyle, ile przetrwało).
// ---------------------------
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stat_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);                              // np. animal_published, animal_resolved
            $table->string('listing_status', 10)->nullable();        // lost / found
            $table->unsignedBigInteger('species_id')->nullable();
            $table->unsignedBigInteger('voivodeship_id')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();    // id ogłoszenia/zgłoszenia - tylko informacyjnie
            $table->unsignedInteger('days')->nullable();             // dla animal_resolved: dni od publikacji
            $table->timestamp('occurred_at');

            $table->index(['type', 'occurred_at']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedBigInteger('views_count')->default(0)->after('meta_description');
        });

        Schema::create('post_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('posts')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('views')->default(0);

            $table->unique(['post_id', 'date']);
            $table->index('date');
        });

        $this->backfill();
    }

    // ---------------------------
    // Historia z istniejących rekordów. Usuniętych ogłoszeń nie odtworzymy - dlatego
    // od teraz zdarzenia zapisujemy na bieżąco (App\Support\Stats).
    // Nowe zgłoszenie = AnimalEdit bez animal_id (zostaje takie także po zatwierdzeniu).
    // ---------------------------
    private function backfill(): void
    {
        $rows = [];

        // ** Zgłoszenia z formularza - liczone od potwierdzenia e-maila (bez spamu), i odrzucone
        foreach (DB::table('animal_edits')->whereNull('animal_id')->get(['id', 'status', 'species_id', 'voivodeship_id', 'mod_status', 'email_verified_at', 'updated_at']) as $edit) {
            if ($edit->email_verified_at) {
                $rows[] = $this->row('animal_submitted', $edit, $edit->id, $edit->email_verified_at);
            }

            if ($edit->mod_status === 'rejected') {
                $rows[] = $this->row('animal_rejected', $edit, $edit->id, $edit->updated_at);
            }
        }

        // ** Opublikowane ogłoszenia i odnalezione (czas zakończenia = ostatnia zmiana rekordu)
        foreach (DB::table('animals')->get(['id', 'status', 'species_id', 'voivodeship_id', 'mod_status', 'created_at', 'updated_at']) as $animal) {
            $rows[] = $this->row('animal_published', $animal, $animal->id, $animal->created_at);

            if ($animal->mod_status === 'resolved') {
                $days = max(0, (int) floor((strtotime($animal->updated_at) - strtotime($animal->created_at)) / 86400));
                $rows[] = [...$this->row('animal_resolved', $animal, $animal->id, $animal->updated_at), 'days' => $days];
            }
        }

        // ** Zgłoszenia "widziałem" (gatunek własny, województwo z ogłoszenia) i chipy -
        // tak jak ogłoszenia, liczone od potwierdzenia e-maila
        foreach (DB::table('sightings')->leftJoin('animals', 'animals.id', '=', 'sightings.animal_id')->whereNotNull('sightings.email_verified_at')
            ->get(['sightings.id', 'sightings.species_id', 'animals.voivodeship_id', 'animals.status', 'sightings.email_verified_at']) as $sighting) {
            $rows[] = $this->row('sighting_submitted', $sighting, $sighting->id, $sighting->email_verified_at);
        }

        foreach (DB::table('chip_registrations')->whereNotNull('email_verified_at')->get(['id', 'species_id', 'voivodeship_id', 'email_verified_at']) as $chip) {
            $rows[] = $this->row('chip_registered', $chip, $chip->id, $chip->email_verified_at);
        }

        foreach (DB::table('messages')->get(['id', 'created_at']) as $message) {
            $rows[] = $this->row('message_sent', $message, $message->id, $message->created_at);
        }

        foreach (array_chunk(array_filter($rows, fn ($row) => $row['occurred_at'] !== null), 500) as $chunk) {
            DB::table('stat_events')->insert($chunk);
        }
    }

    private function row(string $type, object $source, int $subjectId, ?string $occurredAt): array
    {
        return [
            'type'           => $type,
            'listing_status' => $source->status ?? null,
            'species_id'     => $source->species_id ?? null,
            'voivodeship_id' => $source->voivodeship_id ?? null,
            'subject_id'     => $subjectId,
            'days'           => null,
            'occurred_at'    => $occurredAt,
        ];
    }

    public function down(): void
    {
        Schema::dropIfExists('post_views');

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });

        Schema::dropIfExists('stat_events');
    }
};
