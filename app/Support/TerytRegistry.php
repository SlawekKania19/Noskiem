<?php

namespace App\Support;

// ---------------------------
// Odczyt rejestrów TERYT (GUS) z plików CSV w database/data:
// - SIMC.csv - miejscowości (kolumny: WOJ;POW;GMI;RODZ_GMI;RM;MZ;NAZWA;SYM;SYMPOD;STAN_NA),
// - TERC.csv - jednostki podziału (kolumny: WOJ;POW;GMI;RODZ;NAZWA;NAZWA_DOD;STAN_NA).
// Wspólne dla seedera miejscowości i migracji porządkującej tabelę cities.
// ---------------------------
class TerytRegistry
{
    // ** Rodzaje miejscowości (kolumna RM w SIMC) - tylko samodzielne, części pomijamy
    public const LOCALITY_KINDS = [
        '01' => 'wieś',
        '02' => 'kolonia',
        '03' => 'przysiółek',
        '04' => 'osada',
        '05' => 'osada leśna',
        '06' => 'osiedle',
        '07' => 'schronisko turystyczne',
        '96' => 'miasto',
    ];

    // ---------------------------
    // Iteruje po wierszach SIMC jako tablice asocjacyjne. Generator - plik ma ~100 tys.
    // wierszy, więc nie trzymamy go w pamięci w całości.
    // Samodzielna miejscowość ma SYM == SYMPOD; inaczej to część innej miejscowości
    // (przysiółek, część wsi, dzielnica), a SYMPOD wskazuje na jej miejscowość nadrzędną.
    // ---------------------------
    public static function localities(): \Generator
    {
        $file = self::open('SIMC.csv');

        while (($row = fgetcsv($file, separator: ';')) !== false) {
            // Pomijamy puste/niekompletne linie
            if (count($row) < 9 || $row[6] === '') {
                continue;
            }

            yield [
                'woj'    => $row[0],
                'pow'    => $row[1],
                'gmi'    => $row[2],
                'rodz'   => $row[3],
                'rm'     => $row[4],
                'name'   => $row[6],
                'sym'    => $row[7],
                'sympod' => $row[8],
                'is_part' => $row[7] !== $row[8],
            ];
        }

        fclose($file);
    }

    // ---------------------------
    // Nazwy powiatów i gmin z TERC, kluczowane kodami z SIMC:
    // powiaty "WOJ|POW", gminy "WOJ|POW|GMI|RODZ".
    // ---------------------------
    public static function units(): array
    {
        $powiats = [];
        $gminas = [];

        $file = self::open('TERC.csv');

        while (($row = fgetcsv($file, separator: ';')) !== false) {
            if (count($row) < 5 || $row[1] === '') {
                continue; // Województwo albo pusta linia
            }

            if ($row[2] === '') {
                $powiats[$row[0].'|'.$row[1]] = $row[4];
            } else {
                $gminas[$row[0].'|'.$row[1].'|'.$row[2].'|'.$row[3]] = $row[4];
            }
        }

        fclose($file);

        return ['powiats' => $powiats, 'gminas' => $gminas];
    }

    // ** Pola kind/gmina/powiat dla wiersza z localities() na podstawie units()
    public static function details(array $locality, array $units): array
    {
        return [
            'kind'   => self::LOCALITY_KINDS[$locality['rm']] ?? null,
            'gmina'  => $units['gminas'][$locality['woj'].'|'.$locality['pow'].'|'.$locality['gmi'].'|'.$locality['rodz']] ?? null,
            'powiat' => $units['powiats'][$locality['woj'].'|'.$locality['pow']] ?? null,
        ];
    }

    // ** Otwiera plik CSV i pomija wiersz nagłówka
    private static function open(string $name)
    {
        $path = database_path('data/'.$name);

        if (! file_exists($path)) {
            throw new \RuntimeException("Brak pliku: $path");
        }

        $file = fopen($path, 'r');
        fgetcsv($file, separator: ';');

        return $file;
    }
}
