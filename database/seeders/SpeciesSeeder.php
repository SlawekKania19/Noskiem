<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpeciesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('species')->insert([
            ['id' => 1, 'name_PL' => 'Pies', 'sortkey' => 1],
            ['id' => 2, 'name_PL' => 'Kot', 'sortkey' => 2],
            ['id' => 3, 'name_PL' => 'Inne', 'sortkey' => 3],
        ]);
    }
}
