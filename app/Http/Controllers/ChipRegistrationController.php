<?php

namespace App\Http\Controllers;

use App\Mail\ChipRegistrationReceived;
use App\Mail\NewChipRegistrationForModeration;
use App\Models\Breed;
use App\Models\ChipRegistration;
use App\Models\City;
use App\Models\Species;
use App\Models\User;
use App\Models\Voivodeship;
use App\Services\ImageCompressor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

// ---------------------------
// Kontroler bezpłatnej bazy chipów — podstrona z wyszukiwarką po numerze chipa
// i formularzem rejestracji. Niezależny od ogłoszeń. Flow jak przy sightings:
// potwierdzenie e-mail -> moderacja (patrz ChipRegistrationController::confirmEmail).
// ---------------------------

class ChipRegistrationController extends Controller
{
    // Podstrona bazy chipów. Miejscowości nie wczytujemy w całości (rejestr SIMC) —
    // podpowiadają się przez GET /cities/search, tu tylko nazwa ewentualnie już wybranej.
    public function index()
    {
        return view('chips.index', [
            'species' => Species::orderBy('sortkey')->get(),
            'breeds' => Breed::orderBy('breed_pl')->get(),
            'voivodeships' => Voivodeship::orderBy('name_pl')->get(),
            'selectedCityName' => City::find(old('city_id'))?->name_pl,
        ]);
    }

    // Wyszukiwarka (AJAX) — dokładne dopasowanie numeru, tylko zatwierdzone wpisy.
    // Zwraca dane publiczne, bez e-maila i telefonu (telefon osobno, przez phone()).
    public function search(Request $request)
    {
        $registration = $this->findApproved($request->query('chip'));

        if (! $registration) {
            return response()->json(['found' => false]);
        }

        $registration->load(['species', 'breed', 'city', 'voivodeship']);

        return response()->json([
            'found'  => true,
            'result' => [
                'chip_number'     => $registration->chip_number,
                'animal_name'     => $registration->animal_name,
                'species'         => $registration->species?->name_pl,
                'breed'           => $registration->breed?->breed_pl,
                'city'            => $registration->city?->name_pl,
                'voivodeship'     => $registration->voivodeship?->name_pl,
                'additional_info' => $registration->additional_info,
            ],
        ]);
    }

    // Telefon właściciela — dociągany dopiero po kliknięciu "Pokaż numer", jak w ogłoszeniach.
    // ** Po numerze chipa, nie po ID — ID dałoby się iterować i zebrać wszystkie telefony
    public function phone(Request $request)
    {
        $registration = $this->findApproved($request->query('chip'));

        abort_unless($registration, 404);

        return response()->json(['phone' => $registration->formatted_phone]);
    }

    public function store(Request $request)
    {
        // ** Honeypot — udajemy sukces, żeby bot nie wiedział, że został złapany
        if ($request->filled('website')) {
            return redirect()->route('chips.index')
                ->with('success', 'Zgłoszenie zostało zapisane. Sprawdź swoją skrzynkę e-mail i potwierdź adres, żeby trafiło do moderacji.');
        }

        $data = $this->validateRegistration($request, isEdit: false);

        $sticker = $data['sticker_photo'];
        unset($data['sticker_photo']);

        // ** Dysk "local" (storage/app/private) — naklejka nie ma publicznego URL-a,
        // podgląd tylko dla moderatorów w panelu
        $data['sticker_photo_path'] = ImageCompressor::store($sticker, 'chip-stickers', 'local');
        $data['mod_status']   = 'pending';
        $data['edit_token']   = Str::random(64);
        $data['submitter_ip'] = $request->ip();

        $registration = ChipRegistration::create($data);

        // ** Wpis jest już zapisany — awaria wysyłki nie może zablokować odpowiedzi
        try {
            Mail::to($registration->contact_email)
                ->send(new ChipRegistrationReceived($registration));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('chips.index')
            ->with('success', "Zgłoszenie zostało zapisane. Sprawdź skrzynkę {$registration->contact_email} i potwierdź adres, żeby trafiło do moderacji.");
    }

    // Potwierdza adres e-mail (link z maila ChipRegistrationReceived). Dopiero po tym
    // moderatorzy dostają powiadomienie. Idempotentne — ponowne kliknięcie nic nie wysyła.
    public function confirmEmail(ChipRegistration $chipRegistration, Request $request)
    {
        $this->authorizeToken($chipRegistration, $request->query('token'));

        if ($chipRegistration->email_verified_at === null) {
            $chipRegistration->update(['email_verified_at' => now()]);

            // ** Osobno do każdego moderatora — nie widzą się nawzajem w "Do",
            // a jeden zły adres nie blokuje reszty
            $moderators = User::where('is_admin', true)->orWhere('is_moderator', true)->get();

            foreach ($moderators as $moderator) {
                try {
                    Mail::to($moderator->email, $moderator->name)
                        ->send(new NewChipRegistrationForModeration($chipRegistration));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        return redirect()->route('chips.index')
            ->with('success', 'Dziękujemy za potwierdzenie! Zgłoszenie chipa trafiło do moderacji.');
    }

    // Formularz edycji wpisu przez właściciela (link z tokenem z maila po zatwierdzeniu).
    // Jeśli poprzednia edycja czeka na moderację — formularz pokazuje właśnie ją.
    public function edit(ChipRegistration $chipRegistration, Request $request)
    {
        $this->authorizeToken($chipRegistration, $request->query('token'));

        if ($chipRegistration->mod_status === 'rejected') {
            return redirect()->route('chips.index')
                ->with('success', 'Ten wpis został odrzucony przez moderatora — możesz zarejestrować chip ponownie.');
        }

        $values = array_merge($chipRegistration->only(ChipRegistration::EDITABLE_FIELDS), $chipRegistration->pending_changes ?? []);

        return view('chips.edit', [
            'registration' => $chipRegistration,
            'token' => $chipRegistration->edit_token,
            'values' => $values,
            'species' => Species::orderBy('sortkey')->get(),
            'breeds' => Breed::orderBy('breed_pl')->get(),
            'voivodeships' => Voivodeship::orderBy('name_pl')->get(),
            'selectedCityName' => City::find(old('city_id', $values['city_id'] ?? null))?->name_pl,
        ]);
    }

    // Zapis edycji. Wpis zatwierdzony -> zmiany czekają w pending_changes na moderację
    // (w wyszukiwarce do tego czasu dotychczasowa wersja). Wpis jeszcze niezatwierdzony ->
    // zmiany od razu na rekordzie, i tak przejdzie moderację w całości.
    public function update(ChipRegistration $chipRegistration, Request $request)
    {
        $this->authorizeToken($chipRegistration, $request->input('token'));
        abort_if($chipRegistration->mod_status === 'rejected', 404);

        $data = $this->validateRegistration($request, isEdit: true);

        $sticker = $data['sticker_photo'] ?? null;
        unset($data['sticker_photo']);

        if ($chipRegistration->mod_status === 'approved') {
            $previous = $chipRegistration->pending_changes ?? [];

            if ($sticker) {
                // ** Nowa naklejka zastępuje tę z poprzedniej, jeszcze nierozpatrzonej edycji
                if (! empty($previous['sticker_photo_path'])) {
                    Storage::disk('local')->delete($previous['sticker_photo_path']);
                }
                $data['sticker_photo_path'] = ImageCompressor::store($sticker, 'chip-stickers', 'local');
            } elseif (! empty($previous['sticker_photo_path'])) {
                $data['sticker_photo_path'] = $previous['sticker_photo_path'];
            }

            $chipRegistration->update(['pending_changes' => $data]);

            return redirect()->route('chips.edit', ['chipRegistration' => $chipRegistration, 'token' => $chipRegistration->edit_token])
                ->with('success', 'Zmiany zostały wysłane do moderacji. Do czasu zatwierdzenia w bazie widoczna jest poprzednia wersja.');
        }

        if ($sticker) {
            Storage::disk('local')->delete($chipRegistration->sticker_photo_path);
            $data['sticker_photo_path'] = ImageCompressor::store($sticker, 'chip-stickers', 'local');
        }

        $chipRegistration->update($data);

        return redirect()->route('chips.edit', ['chipRegistration' => $chipRegistration, 'token' => $chipRegistration->edit_token])
            ->with('success', 'Zmiany zostały zapisane. Wpis nadal czeka na moderację.');
    }

    // Usunięcie wpisu przez właściciela — od razu, bez moderacji (pliki naklejek
    // usuwa model, patrz ChipRegistration::booted)
    public function destroy(ChipRegistration $chipRegistration, Request $request)
    {
        $this->authorizeToken($chipRegistration, $request->input('token'));

        $chipRegistration->delete();

        return redirect()->route('chips.index')
            ->with('success', 'Wpis został usunięty z bazy chipów.');
    }

    // Walidacja formularza rejestracji/edycji. Przy edycji naklejka jest opcjonalna
    // (zostaje dotychczasowa), a zgoda na regulamin była już wyrażona przy rejestracji.
    private function validateRegistration(Request $request, bool $isEdit): array
    {
        $request->merge(['chip_number' => $this->normalizeChip($request->input('chip_number'))]);

        $data = $request->validate([
            // ** Standard to 15 cyfr, ale dopuszczamy starsze, krótsze chipy (podpowiedź w formularzu)
            'chip_number'     => ['required', 'regex:/^[0-9]{9,'.ChipRegistration::STANDARD_CHIP_LENGTH.'}$/'],
            'animal_name'     => ['required', 'string', 'max:255', 'regex:/^[\p{L}\s\-]+$/u'],
            'species_id'      => 'required|exists:species,id',
            'breed_id'        => [
                'required',
                Rule::exists('breeds', 'id')->where('species_id', $request->input('species_id')),
            ],
            'voivodeship_id'  => 'required|exists:voivodeships,id',
            'city_id'         => [
                'required',
                Rule::exists('cities', 'id')->where('voivodeship_id', $request->input('voivodeship_id')),
            ],
            'contact_phone'   => 'required|string|max:20',
            'contact_email'   => 'required|email|max:255',
            'additional_info' => 'nullable|string|max:2000',
            'sticker_photo'   => ($isEdit ? 'nullable' : 'required').'|image|max:5120',
            'accept_terms'    => $isEdit ? 'exclude' : 'accepted',
        ], [
            'chip_number.regex'      => 'Numer chipa powinien mieć od 9 do 15 cyfr.',
            'animal_name.regex'      => 'Imię zwierzaka może zawierać tylko litery.',
            'sticker_photo.required' => 'Dodaj zdjęcie naklejki z kodem kreskowym i numerem chipa.',
        ]);

        unset($data['accept_terms']);

        // ** Rzutowanie — w pending_changes (JSON) id muszą być liczbami, jak na rekordzie
        foreach (['species_id', 'breed_id', 'voivodeship_id', 'city_id'] as $field) {
            $data[$field] = (int) $data[$field];
        }

        return $data;
    }

    private function authorizeToken(ChipRegistration $registration, mixed $token): void
    {
        if (! hash_equals($registration->edit_token, (string) $token)) {
            abort(403, 'Nieprawidłowy token – brak dostępu.');
        }
    }

    // Numer z naklejki często jest przepisywany ze spacjami/myślnikami — zostawiamy resztę
    private function normalizeChip(mixed $value): string
    {
        return preg_replace('/[\s\-]+/', '', (string) $value);
    }

    // Zatwierdzony wpis o danym numerze. Przy konflikcie (dwa zatwierdzone) — najnowszy
    private function findApproved(mixed $chip): ?ChipRegistration
    {
        $chip = $this->normalizeChip($chip);

        if (! preg_match('/^[0-9]{1,'.ChipRegistration::STANDARD_CHIP_LENGTH.'}$/', $chip)) {
            return null;
        }

        return ChipRegistration::where('chip_number', $chip)
            ->where('mod_status', 'approved')
            ->latest('id')
            ->first();
    }
}
