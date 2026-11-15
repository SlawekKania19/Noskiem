<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\ChipRegistration;
use App\Models\Message;
use App\Models\Sighting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

// ---------------------------
// Rezygnacja z subskrypcji = bezpowrotne usunięcie wszystkich danych powiązanych z adresem
// e-mail: ogłoszeń (ze zdjęciami, historią edycji, zgłoszeniami "widziałem" pod nimi
// i wiadomościami), oczekujących zgłoszeń, własnych zgłoszeń "widziałem", wpisów
// w bazie chipów i wiadomości wysłanych z tego adresu. Patrz UnsubscribeController.
// Adres porównujemy bez rozróżniania wielkości liter (Jan@x.pl == jan@x.pl).
// ---------------------------
class EmailDataEraser
{
    // ---------------------------
    // Dane do pokazania na stronie rezygnacji, pogrupowane jak na liście.
    // Oczekujące zgłoszenia = nowe zgłoszenia przed pierwszym zatwierdzeniem (bez animal_id);
    // edycje istniejących ogłoszeń zawierają się w samych ogłoszeniach.
    // ---------------------------
    public function collect(string $email): array
    {
        return [
            'animals'       => $this->byEmail(Animal::query(), $email)->with('photos')->orderByDesc('created_at')->get(),
            'submissions'   => $this->byEmail(AnimalEdit::query(), $email)->whereNull('animal_id')->with('photos')->orderByDesc('created_at')->get(),
            'sightings'     => $this->byEmail(Sighting::query(), $email)->with('animal')->orderByDesc('created_at')->get(),
            'chips'         => $this->byEmail(ChipRegistration::query(), $email)->orderByDesc('created_at')->get(),
            'messagesCount' => Message::whereRaw('LOWER(email) = ?', [$this->normalize($email)])->count(),
        ];
    }

    // ** Czy pod adresem jest cokolwiek do usunięcia
    public function isEmpty(array $data): bool
    {
        return $data['animals']->isEmpty() && $data['submissions']->isEmpty() && $data['sightings']->isEmpty()
            && $data['chips']->isEmpty() && $data['messagesCount'] === 0;
    }

    // ---------------------------
    // Usuwa wszystko i zwraca liczby usuniętych rekordów (do komunikatu na stronie).
    // Przez Eloquenta, nie masowym DELETE - hooki modeli kasują pliki zdjęć i naklejek
    // z dysku (kaskady FK w bazie by je pominęły, patrz Animal::booted()).
    // ---------------------------
    public function erase(string $email): array
    {
        return DB::transaction(function () use ($email) {
            // ** Ogłoszenia - kaskada FK zabiera edycje, zgłoszenia "widziałem" pod nimi i wiadomości
            $animals = $this->byEmail(Animal::query(), $email)->get();
            $animals->each->delete();

            // ** Pozostałe edycje z tym adresem (nowe zgłoszenia i edycje cudzych już nie ma, ale
            // edycja może mieć inny adres niż ogłoszenie) - zdjęcia po kolei, żeby skasować pliki
            $edits = $this->byEmail(AnimalEdit::query(), $email)->get();
            $edits->each(fn (AnimalEdit $edit) => $this->deleteWithPhotos($edit));

            // ** Własne zgłoszenia "widziałem" pod cudzymi ogłoszeniami
            $sightings = $this->byEmail(Sighting::query(), $email)->get();
            $sightings->each(fn (Sighting $sighting) => $this->deleteWithPhotos($sighting));

            // ** Wpisy w bazie chipów - hook "deleted" kasuje zdjęcia naklejek
            $chips = $this->byEmail(ChipRegistration::query(), $email)->get();
            $chips->each->delete();

            $messages = Message::whereRaw('LOWER(email) = ?', [$this->normalize($email)])->delete();

            return [
                'animals'     => $animals->count(),
                'submissions' => $edits->whereNull('animal_id')->count(),
                'sightings'   => $sightings->count(),
                'chips'       => $chips->count(),
                'messages'    => $messages,
            ];
        });
    }

    public function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function byEmail(Builder $query, string $email): Builder
    {
        return $query->whereRaw('LOWER(contact_email) = ?', [$this->normalize($email)]);
    }

    // ** Photo::deleting kasuje plik z dysku - dlatego zdjęcia przez Eloquenta przed rekordem
    private function deleteWithPhotos(AnimalEdit|Sighting $model): void
    {
        $model->photos()->get()->each->delete();
        $model->delete();
    }
}
