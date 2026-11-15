<?php

namespace App\Http\Controllers;

use App\Mail\AgentVerifyEmail;
use App\Models\City;
use App\Models\User;
use App\Models\Voivodeship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

// ---------------------------
// Konto Agenta noskiem bez logowania: strona informacyjna z rejestracją, potwierdzenie
// e-maila (link podpisany) i wyłączenie powiadomień z maila (link podpisany).
// Logowanie i reset hasła - standardowe (routes/auth.php). Panel: AgentPanelController.
// ---------------------------

class AgentAccountController extends Controller
{
    // ** Strona "Agenci noskiem" z formularzem rejestracji
    public function create()
    {
        if (Auth::user()?->is_agent) {
            return redirect()->route('agent.panel');
        }

        return view('agents.register', [
            'voivodeships' => Voivodeship::orderBy('name_pl')->get(),
            'selectedCityLabel' => City::find(old('city_id'))?->label,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'voivodeship_id' => ['required', 'integer', 'exists:voivodeships,id'],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where('voivodeship_id', $request->input('voivodeship_id'))],
            'terms' => ['accepted'],
        ], [
            'email.unique' => 'Konto z tym adresem już istnieje - zaloguj się.',
            'terms.accepted' => 'Potrzebujemy Twojej zgody, żeby założyć konto.',
        ], [
            'name' => 'imię',
            'city_id' => 'miejscowość',
            'voivodeship_id' => 'województwo',
        ]);

        $agent = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_agent' => true,
            'voivodeship_id' => $data['voivodeship_id'],
            'city_id' => $data['city_id'],
        ]);

        $this->sendVerification($agent);

        Auth::login($agent);
        $request->session()->regenerate();

        return redirect()->route('agent.panel')
            ->with('success', 'Konto założone. Wysłaliśmy link potwierdzający na Twój adres e-mail - a teraz dodaj swój pierwszy obszar działania.');
    }

    // ---------------------------
    // Potwierdzenie e-maila - bez logowania (link często otwierany na innym urządzeniu).
    // Samo wejście niczego nie niszczy, więc GET wystarcza - skaner poczty najwyżej
    // potwierdzi adres, na który i tak doszedł mail
    // ---------------------------
    public function verify(User $user, string $hash)
    {
        abort_unless($user->is_agent && hash_equals(sha1($user->email), $hash), 403);

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return redirect()->route(Auth::check() ? 'agent.panel' : 'login')
            ->with('success', 'Adres e-mail potwierdzony. Od teraz damy Ci znać, gdy w Twoim obszarze zaginie zwierzak.');
    }

    // ** Ponowna wysyłka linku (przycisk w panelu agenta)
    public function resend(Request $request)
    {
        $agent = $request->user();

        if ($agent->is_agent && $agent->email_verified_at === null) {
            $this->sendVerification($agent);
        }

        return back()->with('success', 'Link potwierdzający wysłany ponownie.');
    }

    // ---------------------------
    // Wyłączenie powiadomień z maila (link podpisany, bez logowania). GET pokazuje
    // przycisk, zapis dopiero po POST - skanery poczty same otwierają linki
    // ---------------------------
    public function optOut(User $user)
    {
        abort_unless($user->is_agent, 404);

        return view('agents.opt-out', ['done' => ! $user->agent_notify]);
    }

    public function optOutStore(User $user)
    {
        abort_unless($user->is_agent, 404);

        $user->update(['agent_notify' => false]);

        return view('agents.opt-out', ['done' => true]);
    }

    // ** Błąd wysyłki nie może zepsuć rejestracji - link da się wysłać ponownie z panelu
    protected function sendVerification(User $agent): void
    {
        try {
            Mail::to($agent->email, $agent->name)->send(new AgentVerifyEmail($agent));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
