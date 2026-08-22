<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\AnimalEdit;
use App\Models\Behavior;
use App\Models\Breed;
use App\Models\City;
use App\Models\Color;
use App\Models\IdentMarksTag;
use App\Models\Species;
use App\Models\User;
use App\Models\Voivodeship;
use App\Mail\AnimalDeletionConfirmed;
use App\Mail\AnimalSubmissionReceived;
use App\Mail\NewSubmissionForModeration;
use App\Services\ImageCompressor;
use App\Services\TitleGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

// ---------------------------
// Kontroler do obsługi zgłoszeń edycji zwierząt (AnimalEdit)
// ---------------------------

class AnimalEditController extends Controller
{
    // Wyświetla listę zgłoszeń edycji zwierząt oczekujących na moderację
    public function indexPending()
    {
        return AnimalEdit::where('mod_status', 'pending')
            ->with(['species', 'breed', 'voivodeship', 'city', 'photos'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Wyświetla szczegóły konkretnego zgłoszenia edycji zwierzęcia, w tym informacje o gatunku, rasie, województwie, mieście, powiązanym ogłoszeniu i zdjęciach. Zwraca dane w formacie JSON.
    public function show(AnimalEdit $animalEdit)
    {
        return $animalEdit->load(['species', 'breed', 'voivodeship', 'city', 'animal', 'photos']);
    }

    // Wyświetla formularz zgłoszenia nowego ogłoszenia wraz z listami słownikowymi do pól select.
    // Miejscowości nie wczytujemy w całości (rejestr SIMC ma ~100 tys. rekordów) — pole
    // podpowiada się przez GET /cities/search, tu dociągamy tylko nazwę ewentualnie już wybranej.
    public function create()
    {
        return view('animals.create', [
            'species' => Species::orderBy('sortkey')->get(),
            'breeds' => Breed::orderBy('breed_pl')->get(),
            'voivodeships' => Voivodeship::orderBy('name_pl')->get(),
            'colors' => Color::orderBy('name')->get(),
            'behaviors' => Behavior::orderBy('name')->get(),
            'identMarksTags' => IdentMarksTag::orderBy('name')->get(),
            'selectedCityName' => City::find(old('city_id'))?->name_pl,
        ]);
    }

    // Zapisuje nowe zgłoszenie edycji zwierzęcia wraz ze zdjęciami, walidując dane wejściowe i ustawiając status moderacji na "pending". Generuje unikalny token edycji i przekierowuje użytkownika z komunikatem o powodzeniu operacji.
    public function store(Request $request)
    {
        $data = $request->validate([
            'status'         => 'required|in:lost,found',
            'description'    => 'required|string',
            // ** Przy "Znaleziony" imię prawie nigdy nie jest znane — wymagane tylko dla "Zaginiony"
            'animal_name'    => ['nullable', 'required_if:status,lost', 'string', 'max:255', 'regex:/^[\p{L}\s\-]+$/u'],
            'ident_marks'    => 'nullable|string',
            'behavior'       => 'nullable|string',
            'chip_present'   => 'boolean',
            'chip_number'    => ['nullable', 'max:50', 'regex:/^[0-9]*$/'],
            'species_id'     => 'required|exists:species,id',
            'breed_id'       => 'required|exists:breeds,id',
            'date_event'     => 'required|date',
            'voivodeship_id' => 'required|exists:voivodeships,id',
            'city_id'        => [
                'required',
                Rule::exists('cities', 'id')->where('voivodeship_id', $request->input('voivodeship_id')),
            ],
            'location_text'  => 'required|string|max:255',
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
            'contact_name'   => 'required|string|max:255',
            'contact_email'  => 'required|email|max:255',
            'contact_phone'  => 'nullable|string|max:20',
            'photos'         => 'nullable|array|max:6',
            'photos.*'       => 'image|max:5120',
            'main_photo_index' => 'nullable|integer|min:0',
            'colors'         => 'nullable|array',
            'colors.*'       => 'integer|exists:colors,id',
            'accept_terms'   => 'accepted',
        ], [
            'animal_name.regex' => 'Imię zwierzaka może zawierać tylko litery.',
            'chip_number.regex' => 'Numer chipa może zawierać tylko cyfry.',
        ]);

        // ** Zdjęcia, kolory i zgoda na regulamin są obsługiwane osobno — nie należą do fillable AnimalEdit
        $photos = $data['photos'] ?? [];
        $colors = $data['colors'] ?? [];
        $mainPhotoIndex = (int) ($data['main_photo_index'] ?? 0);
        unset($data['photos'], $data['colors'], $data['main_photo_index'], $data['accept_terms']);

        // ** Spoza zakresu (np. przy manipulacji formularzem) — wraca do pierwszego zdjęcia
        if ($mainPhotoIndex < 0 || $mainPhotoIndex >= count($photos)) {
            $mainPhotoIndex = 0;
        }

        $data['title'] = $this->generateTitle($data);
        $data['mod_status'] = 'pending';
        // ** (string) jest tu konieczne — Str::uuid() zwraca obiekt, a nie string. Przekazany
        // bez rzutowania np. do route() (jak w AnimalSubmissionReceived) po cichu znika z
        // query stringa zamiast się wystringować, więc link w mailu wychodzi bez tokenu.
        $data['edit_token'] = (string) Str::uuid();
        $data['submitter_ip'] = $request->ip();

        $animalEdit = AnimalEdit::create($data);
        $animalEdit->colors()->sync($colors);

        // ** Zapis plików do storage/app/public (poza public/) + rekordy w tabeli photos
        // Zdjęcia są przeskalowane i skompresowane (App\Services\ImageCompressor), żeby
        // duże pliki z telefonów nie zapychały storage
        foreach ($photos as $index => $photo) {
            $path = ImageCompressor::store($photo, 'photos', 'public');

            $animalEdit->photos()->create([
                'path' => $path,
                'is_main' => $index === $mainPhotoIndex,
            ]);
        }

        // ** Zgłoszenie jest już zapisane w bazie — awaria wysyłki (zły adres, padły
        // SMTP itp.) nie może zablokować odpowiedzi użytkownikowi, który realnie
        // zgłoszenie dodał poprawnie. Błędy tylko logujemy.
        // Moderatorzy dostaną powiadomienie dopiero po potwierdzeniu maila —
        // patrz confirmEmail() — więc tu wysyłamy tylko to jedno, z linkiem do potwierdzenia.
        try {
            Mail::to($animalEdit->contact_email, $animalEdit->contact_name)
                ->send(new AnimalSubmissionReceived($animalEdit));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('animals.index')
            ->with('success', 'Zgłoszenie zostało zapisane. Sprawdź skrzynkę e-mail i potwierdź adres, żeby ogłoszenie trafiło do moderacji.');
    }

    // Potwierdza adres e-mail zgłaszającego (link z maila AnimalSubmissionReceived).
    // Dopiero po tym moderatorzy dostają powiadomienie o nowym zgłoszeniu — ochrona
    // przed botami i fałszywymi adresami. Idempotentne: ponowne kliknięcie nie wysyła
    // powiadomień drugi raz.
    public function confirmEmail(AnimalEdit $animalEdit, Request $request)
    {
        if ($request->get('token') !== $animalEdit->edit_token) {
            abort(403, 'Nieprawidłowy token – brak dostępu.');
        }

        if ($animalEdit->email_verified_at === null) {
            $animalEdit->update(['email_verified_at' => now()]);

            // ** Osobno do każdego, nie jednym Mail::to([...]) — inaczej wszyscy moderatorzy
            // widzieliby się nawzajem w nagłówku "Do" tej samej wiadomości. Też w try/catch,
            // każdy z osobna — jeden zły adres nie może zablokować powiadomienia reszty.
            $moderators = User::where('is_admin', true)->orWhere('is_moderator', true)->get();

            foreach ($moderators as $moderator) {
                try {
                    Mail::to($moderator->email, $moderator->name)
                        ->send(new NewSubmissionForModeration($animalEdit));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return redirect()->route('animals.index')
            ->with('success', 'Dziękujemy za potwierdzenie! Twoje zgłoszenie trafiło do moderacji.');
    }

    // Wyświetla formularz edycji konkretnego ogłoszenia zwierzęcia, sprawdzając poprawność tokenu edycji. Jeśli token jest nieprawidłowy, zwraca błąd 403. Zwraca widok z formularzem edycji.
    public function edit(Animal $animal, Request $request)
    {
        if ($request->get('token') !== $animal->edit_token) {
            abort(403, 'Nieprawidłowy token – brak dostępu do edycji.');
        }

        $animal->load(['photos', 'colors']);

        $selectedCityId = old('city_id', $animal->city_id);

        return view('animals.edit', [
            'animal' => $animal,
            'species' => Species::orderBy('sortkey')->get(),
            'breeds' => Breed::orderBy('breed_pl')->get(),
            'voivodeships' => Voivodeship::orderBy('name_pl')->get(),
            'colors' => Color::orderBy('name')->get(),
            'behaviors' => Behavior::orderBy('name')->get(),
            'identMarksTags' => IdentMarksTag::orderBy('name')->get(),
            'selectedCityName' => City::find($selectedCityId)?->name_pl,
        ]);
    }

    // Aktualizuje konkretne ogłoszenie zwierzęcia na podstawie zgłoszenia edycji, sprawdzając poprawność tokenu edycji. Jeśli token jest nieprawidłowy, zwraca błąd 403. Waliduje dane wejściowe, tworzy nowe zgłoszenie edycji i przekierowuje użytkownika z komunikatem o powodzeniu operacji.
    public function update(Request $request, Animal $animal)
    {
        if ($request->get('token') !== $animal->edit_token) {
            abort(403, 'Nieprawidłowy token – brak dostępu do edycji.');
        }

        $data = $request->validate([
            'status'         => 'required|in:lost,found',
            'description'    => 'required|string',
            // ** Przy "Znaleziony" imię prawie nigdy nie jest znane — wymagane tylko dla "Zaginiony"
            'animal_name'    => ['nullable', 'required_if:status,lost', 'string', 'max:255', 'regex:/^[\p{L}\s\-]+$/u'],
            'ident_marks'    => 'nullable|string',
            'behavior'       => 'nullable|string',
            'chip_present'   => 'boolean',
            'chip_number'    => ['nullable', 'max:50', 'regex:/^[0-9]*$/'],
            'species_id'     => 'required|exists:species,id',
            'breed_id'       => 'required|exists:breeds,id',
            'date_event'     => 'required|date',
            'voivodeship_id' => 'required|exists:voivodeships,id',
            'city_id'        => [
                'required',
                Rule::exists('cities', 'id')->where('voivodeship_id', $request->input('voivodeship_id')),
            ],
            'location_text'  => 'required|string|max:255',
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
            'contact_name'   => 'required|string|max:255',
            'contact_email'  => 'required|email|max:255',
            'contact_phone'  => 'nullable|string|max:20',
            'colors'         => 'nullable|array',
            'colors.*'       => 'integer|exists:colors,id',
        ], [
            'animal_name.regex' => 'Imię zwierzaka może zawierać tylko litery.',
            'chip_number.regex' => 'Numer chipa może zawierać tylko cyfry.',
        ]);

        $colors = $data['colors'] ?? [];
        unset($data['colors']);

        $data['title']        = $this->generateTitle($data);
        $data['animal_id']    = $animal->id;
        $data['mod_status']   = 'pending';
        $data['edit_token']   = $animal->edit_token;
        $data['submitter_ip'] = $request->ip();

        $animalEdit = AnimalEdit::create($data);
        $animalEdit->colors()->sync($colors);

        return redirect()->route('animals.show', $animal)
            ->with('success', 'Edycja została wysłana i oczekuje na moderację.');
    }

    // Oznacza ogłoszenie jako rozwiązane ("Znaleziono zwierzaka") — bezpośrednio przez
    // zgłaszającego (token), bez moderacji. Ogłoszenie znika z publicznych list, bo te
    // filtrują po mod_status="approved" — "resolved" tam już nie pasuje.
    public function markResolved(Animal $animal, Request $request)
    {
        if ($request->get('token') !== $animal->edit_token) {
            abort(403, 'Nieprawidłowy token – brak dostępu.');
        }

        $animal->update(['mod_status' => 'resolved']);

        return redirect()->route('home')
            ->with('success', 'Super! Cieszymy się, że zwierzak się znalazł. Ogłoszenie zostało zdjęte ze strony.');
    }

    // Usuwa własne ogłoszenie — bezpośrednio przez zgłaszającego (token), bez moderacji
    public function destroySelf(Animal $animal, Request $request)
    {
        if ($request->get('token') !== $animal->edit_token) {
            abort(403, 'Nieprawidłowy token – brak dostępu.');
        }

        // ** Zbieramy dane do maila z potwierdzeniem PRZED usunięciem — jako zwykłe
        // skalary, nie referencję do modelu, który za chwilę przestanie istnieć w bazie
        $title = $animal->generated_title;
        $animalId = $animal->id;
        $contactName = $animal->contact_name;
        $contactEmail = $animal->contact_email;

        foreach ($animal->photos as $photo) {
            \Storage::disk('public')->delete($photo->path);
        }

        $animal->delete();

        try {
            Mail::to($contactEmail, $contactName)
                ->send(new AnimalDeletionConfirmed($title, $animalId, $contactName));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('home')
            ->with('success', 'Ogłoszenie zostało usunięte.');
    }

    // Wylicza tytuł ogłoszenia z szablonu w ustawieniach (App\Filament\Pages\Settings) —
    // użytkownik formularza publicznego tytułu nie wpisuje samodzielnie
    private function generateTitle(array $data): string
    {
        return TitleGenerator::generate([
            'animal_name'      => $data['animal_name'],
            'species_name'     => Species::find($data['species_id'])?->name_pl,
            'breed_name'       => Breed::find($data['breed_id'])?->breed_pl,
            'city_name'        => City::find($data['city_id'])?->name_pl,
            'voivodeship_name' => Voivodeship::find($data['voivodeship_id'])?->name_pl,
            'status'           => $data['status'],
        ]);
    }
}
