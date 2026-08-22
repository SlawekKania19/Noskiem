@extends('emails.layout')

@section('title', 'Ogłoszenie zostało usunięte')

@section('content')
    <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Cześć, {{ explode(' ', trim($contactName))[0] }}</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Potwierdzamy, że Twoje ogłoszenie (#{{ $animalId }}) — <strong>{{ $title }}</strong> — zostało trwale usunięte na Twoją prośbę.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fcecd1; border-radius:12px; margin:0 0 16px;">
        <tr>
            <td style="padding:20px;">
                <p style="margin:0; font-size:13px; line-height:1.6; color:#994d0a;">
                    Wszystkie dane podane przy dodawaniu tego ogłoszenia — w tym dane osobowe (imię i nazwisko,
                    adres e-mail, numer telefonu) — zostały bezpowrotnie usunięte z naszej bazy. Nie mamy już
                    możliwości wglądu w te dane, ich edycji ani odzyskania.
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-size:14px; line-height:1.6; color:#616657;">
        Jeśli to pomyłka i chcesz dodać ogłoszenie ponownie, musisz zgłosić je od nowa — nie jesteśmy w stanie
        przywrócić usuniętych danych.
    </p>

    <p style="margin:0; font-size:14px; line-height:1.6; color:#616657;">
        Pozdrawiamy,<br>Zespół noskiem.org
    </p>
@endsection
