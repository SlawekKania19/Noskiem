<?php

use App\Models\City;
use App\Support\TerytRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// ---------------------------
// Porządkuje tabelę cities pod kątem powtarzających się nazw w województwie:
// 1. Dodaje kolumny rozróżniające (rodzaj miejscowości, gmina, powiat, is_ambiguous).
// 2. Usuwa części miejscowości (SIMC: SYM != SYMPOD - przysiółki, części wsi, dzielnice),
//    przepinając wcześniej odwołania z ogłoszeń/edycji/chipów na miejscowość nadrzędną.
// 3. Uzupełnia gminę/powiat/rodzaj z TERC i SIMC oraz przelicza is_ambiguous.
// Na pustej tabeli (świeża instalacja, testy) wykonuje tylko krok 1 - dane wgra seeder.
// ---------------------------
return new class extends Migration
{
    // ** Tabele z kluczem obcym do cities
    private const REFERENCING_TABLES = ['animals', 'animal_edits', 'chip_registrations'];

    private const CHUNK_SIZE = 1000;

    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->string('kind', 30)->nullable()->after('name_en');   // np. wieś, kolonia, miasto
            $table->string('gmina')->nullable()->after('kind');         // np. Gnojnik
            $table->string('powiat')->nullable()->after('gmina');       // np. brzeski
            $table->boolean('is_ambiguous')->default(false)->after('powiat');
        });

        if (! DB::table('cities')->exists()) {
            return;
        }

        $idBySym = DB::table('cities')->whereNotNull('teryt_sym')->pluck('id', 'teryt_sym')->all();

        // ** Mapa część -> miejscowość nadrzędna (id w naszej bazie) oraz dane samodzielnych
        $parentIdByPartId = [];
        $independent = [];

        foreach (TerytRegistry::localities() as $locality) {
            if ($locality['is_part']) {
                if (isset($idBySym[$locality['sym']])) {
                    $parentIdByPartId[$idBySym[$locality['sym']]] = $idBySym[$locality['sympod']] ?? null;
                }
            } else {
                $independent[] = $locality;
            }
        }

        $this->remapReferences($parentIdByPartId);

        foreach (array_chunk(array_keys($parentIdByPartId), self::CHUNK_SIZE) as $ids) {
            DB::table('cities')->whereIn('id', $ids)->delete();
        }

        $this->fillDetails($independent, $idBySym);

        City::recomputeAmbiguity();
    }

    // ---------------------------
    // Przepina odwołania z części miejscowości na miejscowość nadrzędną.
    // Sprawdzamy tylko faktycznie używane city_id - to zwykle garstka rekordów.
    // ---------------------------
    private function remapReferences(array $parentIdByPartId): void
    {
        foreach (self::REFERENCING_TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $usedIds = DB::table($table)->whereNotNull('city_id')->distinct()->pluck('city_id');

            foreach ($usedIds as $cityId) {
                if (! array_key_exists($cityId, $parentIdByPartId)) {
                    continue;
                }

                $parentId = $parentIdByPartId[$cityId];

                // Brak nadrzędnej w bazie nie powinien się zdarzyć - wtedy przerywamy, zamiast gubić dane
                if ($parentId === null) {
                    throw new \RuntimeException("Brak miejscowości nadrzędnej dla city_id=$cityId (tabela $table)");
                }

                DB::table($table)->where('city_id', $cityId)->update(['city_id' => $parentId]);
            }
        }
    }

    // ** Uzupełnia kind/gmina/powiat dla samodzielnych miejscowości obecnych w bazie
    private function fillDetails(array $independent, array $idBySym): void
    {
        $units = TerytRegistry::units();

        // Grupujemy po identycznych wartościach - jedno UPDATE ... WHERE id IN (...) na grupę
        $idsByDetails = [];

        foreach ($independent as $locality) {
            if (! isset($idBySym[$locality['sym']])) {
                continue;
            }

            $details = TerytRegistry::details($locality, $units);
            $idsByDetails[json_encode($details)][] = $idBySym[$locality['sym']];
        }

        foreach ($idsByDetails as $detailsJson => $ids) {
            foreach (array_chunk($ids, self::CHUNK_SIZE) as $chunk) {
                DB::table('cities')->whereIn('id', $chunk)->update(json_decode($detailsJson, true));
            }
        }
    }

    // ** Usuniętych części miejscowości nie przywracamy - w razie potrzeby ponowny seed
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn(['kind', 'gmina', 'powiat', 'is_ambiguous']);
        });
    }
};
