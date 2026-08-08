<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IdentMarksTagsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('ident_marks_tags')->insert([
            ['name' => 'Blizna'],
            ['name' => 'Kulawizna'],
            ['name' => 'Zez'],
            ['name' => 'Brak ucha'],
            ['name' => 'Przycięty ogon'],
            ['name' => 'Łaciata sierść'],
            ['name' => 'Duża plama/znamię'],
            ['name' => 'Różnokolorowe oczy (heterochromia)'],
            ['name' => 'Obroża'],
            ['name' => 'Sterylizowany/kastrowany'],
        ]);
    }
}
