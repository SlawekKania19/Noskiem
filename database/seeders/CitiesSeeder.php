<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Voivodeship;
use App\Support\TerytRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// ---------------------------
// Wczytuje listę samodzielnych miejscowości z oficjalnego rejestru SIMC (GUS), ok. 54 tys. wierszy.
// Części miejscowości (przysiółki, części wsi, dzielnice - SYM != SYMPOD) pomijamy - mnożyły
// powtórzenia nazw w podpowiedziach, a dla ogłoszenia wystarczy miejscowość nadrzędna.
// Gminę i powiat bierzemy z rejestru TERC - rozróżniają miejscowości o tej samej nazwie.
// Wstawiane partiami (chunk), żeby nie budować jednego ogromnego zapytania INSERT.
// ---------------------------

class CitiesSeeder extends Seeder
{
    private const CHUNK_SIZE = 1000;

    public function run(): void
    {
        // Mapa kod TERC województwa -> id, żeby powiązać wiersze SIMC z województwami zasianymi wcześniej
        $voivodeshipIdByTerc = Voivodeship::pluck('id', 'teryt_code')->all();

        $units = TerytRegistry::units();

        $buffer = [];

        foreach (TerytRegistry::localities() as $locality) {
            if ($locality['is_part']) {
                continue;
            }

            $voivodeshipId = $voivodeshipIdByTerc[$locality['woj']] ?? null;

            if ($voivodeshipId === null) {
                continue;
            }

            $buffer[] = [
                'teryt_sym'      => $locality['sym'],
                'name_pl'        => $locality['name'],
                'name_en'        => null,
                'voivodeship_id' => $voivodeshipId,
                ...TerytRegistry::details($locality, $units),
            ];

            if (count($buffer) >= self::CHUNK_SIZE) {
                DB::table('cities')->insert($buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            DB::table('cities')->insert($buffer);
        }

        // ** Flaga powtarzającej się nazwy - liczona po wgraniu całości
        City::recomputeAmbiguity();
    }
}
