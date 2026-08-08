<?php

namespace Database\Seeders;

use App\Models\Behavior;
use Illuminate\Database\Seeder;

class BehaviorsSeeder extends Seeder
{
    // ** firstOrCreate zamiast insert — seeder jest uruchamiany przy każdym deployu
    // (deploy-qa.yml), więc musi być bezpieczny do wielokrotnego wykonania
    public function run(): void
    {
        foreach ([
            'Agresywny',
            'Płochliwy',
            'Reaguje na imię',
            'Nie reaguje na imię',
            'Głuchy',
            'Niewidomy',
            'Przyjazny',
        ] as $name) {
            Behavior::firstOrCreate(['name' => $name]);
        }
    }
}
