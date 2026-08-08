<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BehaviorsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('behaviors')->insert([
            ['name' => 'Agresywny'],
            ['name' => 'Płochliwy'],
            ['name' => 'Reaguje na imię'],
            ['name' => 'Nie reaguje na imię'],
            ['name' => 'Głuchy'],
            ['name' => 'Niewidomy'],
            ['name' => 'Przyjazny'],
        ]);
    }
}
