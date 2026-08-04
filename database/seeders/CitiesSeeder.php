<?php

namespace Database\Seeders;

use App\Models\Voivodeship;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// ---------------------------
// Wczytuje pełną listę miejscowości z oficjalnego rejestru SIMC (GUS), ok. 100 tys. wierszy.
// Kolumny: WOJ;POW;GMI;RODZ_GMI;RM;MZ;NAZWA;SYM;SYMPOD;STAN_NA
// Wstawiane partiami (chunk), żeby nie budować jednego ogromnego zapytania INSERT.
// ---------------------------

class CitiesSeeder extends Seeder
{
    private const CHUNK_SIZE = 1000;

    public function run(): void
    {
        $path = database_path('data/SIMC.csv');

        if (! file_exists($path)) {
            throw new \Exception("Brak pliku: $path");
        }

        // Mapa kod TERC województwa -> id, żeby powiązać wiersze SIMC z wojewódz­twami zasianymi wcześniej
        $voivodeshipIdByTerc = Voivodeship::pluck('id', 'teryt_code')->all();

        $file = fopen($path, 'r');

        // Pomijamy nagłówek
        fgetcsv($file, separator: ';');

        $buffer = [];

        while (($row = fgetcsv($file, separator: ';')) !== false) {
            // Pomijamy puste/niekompletne linie
            if (count($row) < 9 || $row[6] === '') {
                continue;
            }

            $voivodeshipId = $voivodeshipIdByTerc[$row[0]] ?? null;

            if ($voivodeshipId === null) {
                continue;
            }

            $buffer[] = [
                'teryt_sym'      => $row[7],
                'name_pl'        => $row[6],
                'name_en'        => null,
                'voivodeship_id' => $voivodeshipId,
            ];

            if (count($buffer) >= self::CHUNK_SIZE) {
                DB::table('cities')->insert($buffer);
                $buffer = [];
            }
        }

        if ($buffer !== []) {
            DB::table('cities')->insert($buffer);
        }

        fclose($file);
    }
}
