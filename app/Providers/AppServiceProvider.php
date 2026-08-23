<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
    }

    // ---------------------------
    // Limity zgłoszeń i wiadomości kontaktowych — ochrona przed botami. Okno stałe
    // (10 min), liczba prób edytowalna w panelu Ustawień (App\Filament\Pages\Settings),
    // patrz routes/web.php (animals.store, messages.store)
    // ---------------------------
    protected function registerRateLimiters(): void
    {
        RateLimiter::for('animals-store', fn (Request $request) => Limit::perMinutes(
            10,
            (int) Setting::get('rate_limit_animals_max', '1')
        )->by($request->ip()));

        RateLimiter::for('messages-store', fn (Request $request) => Limit::perMinutes(
            10,
            (int) Setting::get('rate_limit_messages_max', '5')
        )->by($request->ip()));
    }
}
