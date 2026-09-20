{{-- ---------------------------
     Strona trybu serwisowego (503) — włączana z panelu: Ustawienia → Tryb serwisowy.
     Renderowana przez App\Http\Middleware\MaintenanceMode.
     $until   — Carbon|null, planowany powrót (czas "ścienny" wpisany przez admina)
     $message — string|null, dodatkowa informacja od admina
     --------------------------- --}}
{{-- Zwykła ścieżka, nie errors:: — ten namespace rejestruje dopiero handler wyjątków, a tu renderuje middleware --}}
@extends('errors.minimal')

@section('title', 'Przerwa techniczna')
@section('code', 'Przerwa techniczna')

@section('message')
    Trwają prace serwisowe.
    @if ($until && $until->isFuture())
        {{-- Data wpisana przez admina — bez przeliczania stref, dlatego bez ->timezone() --}}
        Planowany powrót: <strong>{{ $until->locale('pl')->translatedFormat('l, j F Y, H:i') }}</strong>.
    @else
        {{-- Termin minął, a tryb wciąż włączony — prace się przeciągnęły --}}
        Prace trwają dłużej, niż planowaliśmy — wracamy najszybciej, jak się da.
    @endif
@endsection

@section('details')
    @if (filled($message))
        <p style="margin:24px auto 0; max-width:480px; font-size:15px; line-height:1.6; color:#616657;">
            {{ $message }}
        </p>
    @endif
@endsection

{{-- Zamiast "Wróć do strony głównej" — w trybie serwisowym i tak trafiłoby tu z powrotem --}}
@section('footer')
    <a href="" style="margin-top:40px; font-size:14px; color:#283618; text-decoration:underline;">
        Odśwież stronę
    </a>
@endsection
