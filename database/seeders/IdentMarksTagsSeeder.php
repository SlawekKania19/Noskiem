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
            'Kulawizna',
            'Zez',
            'Brak ucha',
            'Przycięty ogon',
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
