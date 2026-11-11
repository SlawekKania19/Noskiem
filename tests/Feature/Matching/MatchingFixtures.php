<?php

namespace Tests\Feature\Matching;

use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\Breed;
use App\Models\City;
use App\Models\ModerationLog;
use App\Models\Photo;
use App\Models\PhotoEmbedding;
use App\Models\Species;
use App\Models\Voivodeship;
use App\Services\PhotoEmbeddingService;
use Illuminate\Support\Str;

// ---------------------------
// Wspólne dane testowe modułu dopasowań: słowniki, ogłoszenia, zdjęcia, wektory
// ---------------------------
trait MatchingFixtures
{
    protected array $base;

    protected string $mv;

    protected Species $dog;

    protected function setUpMatching(): void
    {
        config(['noskiem_match.enabled' => true, 'noskiem_match.queue.hours' => '', 'noskiem_match.queue.paused' => false]);
        $this->mv = app(PhotoEmbeddingService::class)->modelVersion();

        $this->dog = Species::forceCreate(['name_pl' => 'Pies', 'name_en' => 'Dog']);
        $voivodeship = Voivodeship::forceCreate(['name_pl' => 'śląskie', 'name_en' => 'Silesia']);

        // Katowice
        $this->base = [
            'status' => 'lost', 'title' => 'T', 'description' => 'Opis', 'animal_name' => 'Burek',
            'species_id' => $this->dog->id,
            'breed_id' => $this->breed('Mieszaniec')->id,
            'date_event' => '2026-11-01', 'voivodeship_id' => $voivodeship->id,
            'city_id' => City::forceCreate(['name_pl' => 'Katowice', 'name_en' => 'Katowice', 'voivodeship_id' => $voivodeship->id])->id,
            'location_text' => 'Park', 'latitude' => 50.26, 'longitude' => 19.02,
            'contact_name' => 'Jan', 'contact_email' => 'jan@example.com',
        ];
    }

    protected function breed(string $name, ?int $speciesId = null): Breed
    {
        return Breed::forceCreate(['breed_pl' => $name, 'breed_en' => $name, 'species_id' => $speciesId ?? $this->dog->id]);
    }

    protected function animal(string $modStatus = 'approved', array $attrs = []): Animal
    {
        return Animal::forceCreate(array_merge($this->base, ['mod_status' => $modStatus, 'edit_token' => (string) Str::uuid()], $attrs));
    }

    protected function edit(bool $verified = true, string $modStatus = 'pending', array $attrs = []): AnimalEdit
    {
        return AnimalEdit::forceCreate(array_merge($this->base, [
            'mod_status' => $modStatus, 'edit_token' => (string) Str::uuid(),
            'email_verified_at' => $verified ? now() : null,
        ], $attrs));
    }

    // ** Zatwierdzenie zgłoszenia jak w ModerationService: nowy Animal + wpis w logu moderacji.
    // $at - moment zatwierdzenia (domyślnie teraz)
    protected function approve(AnimalEdit $edit, ?\DateTimeInterface $at = null): Animal
    {
        $animal = $this->animal('approved', ['contact_email' => $edit->contact_email]);
        $edit->update(['mod_status' => 'approved']);
        $log = ModerationLog::create(['animal_id' => $animal->id, 'animal_edit_id' => $edit->id, 'action' => 'approved']);

        if ($at) {
            ModerationLog::whereKey($log->id)->update(['created_at' => $at]);
        }

        return $animal;
    }

    protected function photo(array $owner): Photo
    {
        return Photo::create($owner + ['path' => 'photos/'.Str::random(8).'.jpg', 'is_main' => false]);
    }

    // ** Zdjęcie z gotowym wektorem (znormalizowanym); null = zapis błędu liczenia
    protected function photoWithVector(array $owner, ?array $vec): Photo
    {
        $photo = $this->photo($owner);
        $norm = $vec ? sqrt(array_sum(array_map(fn ($x) => $x * $x, $vec))) : 1;

        PhotoEmbedding::create([
            'photo_id' => $photo->id, 'model_version' => $this->mv,
            'vector' => $vec ? pack('g*', ...array_map(fn ($x) => $x / $norm, $vec)) : null,
            'dim' => $vec ? count($vec) : null,
            'error' => $vec ? null : 'OSError',
        ]);

        return $photo;
    }
}
