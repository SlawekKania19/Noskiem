<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VoivodeshipsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('voivodeships')->insert([
            ['id' => 1,  'name_PL' => 'Dolnośląskie',         'name_EN' => 'Lower Silesian'],
            ['id' => 2,  'name_PL' => 'Kujawsko-pomorskie',   'name_EN' => 'Kuyavian-Pomeranian'],
            ['id' => 3,  'name_PL' => 'Lubelskie',            'name_EN' => 'Lublin'],
            ['id' => 4,  'name_PL' => 'Lubuskie',             'name_EN' => 'Lubusz'],
            ['id' => 5,  'name_PL' => 'Łódzkie',              'name_EN' => 'Łódź'],
            ['id' => 6,  'name_PL' => 'Małopolskie',          'name_EN' => 'Lesser Poland'],
            ['id' => 7,  'name_PL' => 'Mazowieckie',          'name_EN' => 'Masovian'],
            ['id' => 8,  'name_PL' => 'Opolskie',             'name_EN' => 'Opole'],
            ['id' => 9,  'name_PL' => 'Podkarpackie',         'name_EN' => 'Subcarpathian'],
            ['id' => 10, 'name_PL' => 'Podlaskie',            'name_EN' => 'Podlaskie'],
            ['id' => 11, 'name_PL' => 'Pomorskie',            'name_EN' => 'Pomeranian'],
            ['id' => 12, 'name_PL' => 'Śląskie',              'name_EN' => 'Silesian'],
            ['id' => 13, 'name_PL' => 'Świętokrzyskie',       'name_EN' => 'Holy Cross'],
            ['id' => 14, 'name_PL' => 'Warmińsko-mazurskie',  'name_EN' => 'Warmian-Masurian'],
            ['id' => 15, 'name_PL' => 'Wielkopolskie',        'name_EN' => 'Greater Poland'],
            ['id' => 16, 'name_PL' => 'Zachodniopomorskie',   'name_EN' => 'West Pomeranian'],
        ]);
    }
}
