<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ColorsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('colors')->insert([
            ['name' => 'Biały'],
            ['name' => 'Czarny'],
            ['name' => 'Szary'],
            ['name' => 'Brązowy'],
            ['name' => 'Rudy'],
            ['name' => 'Kremowy'],
            ['name' => 'Beżowy'],
            ['name' => 'Szylkret'],
            ['name' => 'Łaciaty'],
            ['name' => 'Pręgowany'],
            ['name' => 'Złoty'],
            ['name' => 'Zielony'],
            ['name' => 'Niebieski'],
            ['name' => 'Żółty'],
            ['name' => 'Czerwony'],
            ['name' => 'Inny'],
        ]);
    }
}
