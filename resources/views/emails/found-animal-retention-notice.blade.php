@extends('emails.layout')

@section('title', 'Twoje ogłoszenie wkrótce zniknie')

@section('content')
    <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Twoje ogłoszenie wkrótce zniknie</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        @if ($wasExtended)
            Minął termin przedłużenia Twojego ogłoszenia (#{{ $animal->id }}).
        @else
            Od dodania Twojego ogłoszenia (#{{ $animal->id }}) minęły już dwa lata.
        @endif
        Najprawdopodobniej jest ono już nieaktualne, dlatego
        <strong>{{ $deleteAt->locale('pl')->translatedFormat('j F Y') }}</strong>
        usuniemy je ze strony razem ze zdjęciami.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4ef; border-radius:12px; margin:0 0 16px;">
        <tr>
            <td style="padding:20px;">
                <p style="margin:0; font-size:16px; font-weight:600; color:#283618;">{{ $animal->generated_title }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Zwierzak wciąż czeka na swojego opiekuna? Możesz przedłużyć ogłoszenie o kolejne 6 miesięcy:
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">
        <tr>
            <td style="border-radius:10px; background-color:#283618;">
                <a
                    href="{{ $extendUrl }}"
                    style="display:inline-block; padding:12px 24px; color:#fefae0; font-size:14px; font-weight:600; text-decoration:none;"
                >
                    Przedłuż ogłoszenie
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Jeśli ogłoszenie nie jest już potrzebne, nie musisz nic robić - usuniemy je automatycznie.
    </p>

    <p style="margin:0; font-size:14px; line-height:1.6; color:#616657;">
        Dziękujemy, że pomagasz zwierzakom wrócić do domu,<br>Zespół noskiem.org
    </p>
@endsection
