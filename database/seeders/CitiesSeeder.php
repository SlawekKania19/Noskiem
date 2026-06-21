<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitiesSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/cities.csv');

        if (!file_exists($path)) {
            throw new \Exception("Brak pliku: $path");
        }

        $file = fopen($path, 'r');

        // Pomijamy nagłówek
        fgetcsv($file);

        while (($row = fgetcsv($file)) !== false) {

            // pomiń puste linie lub linie z mniej niż 3 kolumnami
            if (count($row) < 3) {
                continue;
            }

            DB::table('cities')->insert([
                'voivodeship_id' => $row[0],
                'name_pl'        => $row[1],
                'name_en'        => $row[2],
            ]);
        }


        fclose($file);
    }
}
