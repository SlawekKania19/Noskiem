<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// ---------------------------
// Tryb serwisowy sterowany z panelu (Ustawienia → Tryb serwisowy).
//
// Celowo nie `php artisan down` — tamten blokuje też panel i wyłącza się tylko
// z konsoli. Tu przełącznik siedzi w bazie, więc admin włącza i wyłącza go sam.
// Zalogowany admin oraz trasy potrzebne do zalogowania się i obsługi panelu
// przechodzą normalnie — reszta dostaje 503 ze stroną serwisową.
// ---------------------------

class MaintenanceMode
{
    // Ścieżki, które muszą działać także w trybie serwisowym (logowanie, panel, healthcheck)
    protected const ALLOWED_PATHS = [
        'admin',
        'admin/*',
        'livewire/*',
        'login',
        'logout',
        'up',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isEnabled() || $this->isAllowed($request)) {
            return $next($request);
        }

        // ** Data wpisana w panelu — traktujemy ją jako czas "ścienny" (bez przeliczania stref)
        $until = $this->plannedReturn();

        $response = response()->view('errors.maintenance', [
            'until' => $until,
            'message' => Setting::get('maintenance_message'),
        ], 503);

        // Retry-After podpowiada botom (Google), że to przerwa, a nie zniknięcie strony
        if ($until && $until->isFuture()) {
            $response->headers->set('Retry-After', (string) max(60, (int) now()->diffInSeconds($until, false)));
        }

        return $response;
    }

    protected function isEnabled(): bool
    {
        return Setting::get('maintenance_enabled') === '1';
    }

    // Admin widzi normalną stronę — może sprawdzić, czy wszystko działa, zanim wyłączy tryb
    protected function isAllowed(Request $request): bool
    {
        if ($request->user()?->is_admin) {
            return true;
        }

        return $request->is(...self::ALLOWED_PATHS);
    }

    protected function plannedReturn(): ?Carbon
    {
        $value = Setting::get('maintenance_until');

        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
