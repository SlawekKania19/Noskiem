<?php

namespace Database\Seeders;

use App\Models\IdentMarksTag;
use Illuminate\Database\Seeder;

class IdentMarksTagsSeeder extends Seeder
{
    // ** firstOrCreate zamiast insert — seeder jest uruchamiany przy każdym deployu
    // (deploy-qa.yml), więc musi być bezpieczny do wielokrotnego wykonania
    public function run(): void
    {
        foreach ([
            'Blizna',
            'Kulawy',
            'Brak łapy',
            'Zez',
            'Brak ucha',
            'Przycięty ogon',
            'Zakręcony ogon',
            'Zez',
            'Bielmo',
            'Brak oka',
            'Łysina',
            'Znamię',
            'Braki w uzębieniu',
            'Krzywy pysk',
            'Depigmentacja nosa',
            'Otyły',
            'Wygolony (miejscowo lub w całości)',
            'Łaciata sierść',
            'Duża plama/znamię',
            'Różnokolorowe oczy (heterochromia)',
            'Obroża',
            'Sterylizowany/kastrowany',
        ] as $name) {
            IdentMarksTag::firstOrCreate(['name' => $name]);
        }
    }
}
