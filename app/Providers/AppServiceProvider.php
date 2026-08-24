<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerRateLimiters();
        $this->registerPasswordDefaults();
    }

    // ---------------------------
    // Domyślne wymogi hasła — obowiązują wszędzie, gdzie kod woła Password::default()
    // lub Password::defaults() (panel admina, reset hasła, rejestracja)
    // ---------------------------
    protected function registerPasswordDefaults(): void
    {
        Password::defaults(fn () => Password::min(8)->mixedCase()->numbers());
    }

    // ---------------------------
    // Limity zgłoszeń i wiadomości kontaktowych — ochrona przed botami. Okno stałe
    // (10 min), liczba prób edytowalna w panelu Ustawień (App\Filament\Pages\Settings),
    // patrz routes/web.php (animals.store, messages.store)
    // ---------------------------
    protected function registerRateLimiters(): void
    {
        // ** Zalogowani (np. admin/moderator testujący formularz) nie podlegają limitowi
        RateLimiter::for('animals-store', fn (Request $request) => $request->user()
            ? Limit::none()
            : Limit::perMinutes(10, (int) Setting::get('rate_limit_animals_max', '1'))->by($request->ip()));

        RateLimiter::for('messages-store', fn (Request $request) => Limit::perMinutes(
            10,
            (int) Setting::get('rate_limit_messages_max', '5')
        )->by($request->ip()));

        // ** Pokazanie numeru telefonu (patrz AnimalController::phone, SightingController::phone) —
        // bez tego numer i tak siedziałby w źródle HTML, tylko schowany za CSS, co nic nie daje
        // przeciwko botom zbierającym numery hurtowo
        RateLimiter::for('reveal-phone', fn (Request $request) => Limit::perMinutes(
            10,
            (int) Setting::get('rate_limit_phone_max', '20')
        )->by($request->ip()));
    }
}
