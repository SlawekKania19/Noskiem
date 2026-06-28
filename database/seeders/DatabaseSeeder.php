<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Jeśli chcesz zostawić usera testowego — odkomentuj:
        /*
        \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        */

        $this->call([
            SpeciesSeeder::class,
            BreedsSeeder::class,
            ColorsSeeder::class,
            VoivodeshipsSeeder::class,
            CitiesSeeder::class,
            AnimalSeeder::class,
            AnimalEditSeeder::class,
        ]);
    }
}
