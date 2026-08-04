<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// ---------------------------
// Wczytuje listę województw z oficjalnego rejestru TERC (GUS).
// Wiersz województwa w TERC.csv ma puste kolumny POW/GMI/RODZ — tylko WOJ i NAZWA.
// Angielskie nazwy nie występują w TERC, więc zachowujemy je ze starej, ręcznej listy.
// ---------------------------

class VoivodeshipsSeeder extends Seeder
{
    private const NAMES_EN = [
        'DOLNOŚLĄSKIE'        => 'Lower Silesian',
        'KUJAWSKO-POMORSKIE'  => 'Kuyavian-Pomeranian',
        'LUBELSKIE'           => 'Lublin',
        'LUBUSKIE'            => 'Lubusz',
        'ŁÓDZKIE'             => 'Łódź',
        'MAŁOPOLSKIE'         => 'Lesser Poland',
        'MAZOWIECKIE'         => 'Masovian',
        'OPOLSKIE'            => 'Opole',
        'PODKARPACKIE'        => 'Subcarpathian',
        'PODLASKIE'           => 'Podlaskie',
        'POMORSKIE'           => 'Pomeranian',
        'ŚLĄSKIE'             => 'Silesian',
        'ŚWIĘTOKRZYSKIE'      => 'Holy Cross',
        'WARMIŃSKO-MAZURSKIE' => 'Warmian-Masurian',
        'WIELKOPOLSKIE'       => 'Greater Poland',
        'ZACHODNIOPOMORSKIE'  => 'West Pomeranian',
    ];

    public function run(): void
    {
        $path = database_path('data/TERC.csv');

        if (! file_exists($path)) {
            throw new \Exception("Brak pliku: $path");
        }

        $file = fopen($path, 'r');

        // Pomijamy nagłówek
        fgetcsv($file, separator: ';');

        while (($row = fgetcsv($file, separator: ';')) !== false) {
            // Wiersz województwa: WOJ;POW;GMI;RODZ;NAZWA;NAZWA_DOD;STAN_NA — POW/GMI/RODZ puste
            if (count($row) < 5 || $row[1] !== '' || $row[2] !== '' || $row[3] !== '') {
                continue;
            }

            $tercCode = $row[0];
            $nameUpper = $row[4];
            $namePl = mb_convert_case($nameUpper, MB_CASE_TITLE, 'UTF-8');

            DB::table('voivodeships')->insert([
                'teryt_code' => $tercCode,
                'name_pl'    => $namePl,
                'name_en'    => self::NAMES_EN[$nameUpper] ?? null,
            ]);
        }

        fclose($file);
    }
}
