@extends('emails.layout')

@section('title', 'Twoje ogłoszenie zostało zatwierdzone')

@section('content')
    <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Ogłoszenie zatwierdzone!</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Dobra wiadomość — Twoje zgłoszenie (#{{ $animal->id }}) zostało zatwierdzone i jest już widoczne na stronie.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4ef; border-radius:12px; margin:0 0 16px;">
        <tr>
            <td style="padding:20px;">
                <p style="margin:0; font-size:16px; font-weight:600; color:#283618;">{{ $animal->generated_title }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Jeśli chcesz coś poprawić, zwierzak się znalazł albo chcesz usunąć ogłoszenie —
        wszystko zrobisz z jednego miejsca:
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">
        <tr>
            <td style="border-radius:10px; background-color:#283618;">
                <a
                    href="{{ $editUrl }}"
                    style="display:inline-block; padding:12px 24px; color:#fefae0; font-size:14px; font-weight:600; text-decoration:none;"
                >
                    Zarządzaj ogłoszeniem
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Raz w miesiący wyślemy Ci przypomnienie o tym, że ogłoszenie jest aktywne i że możesz je edytować lub usunąć.
    </p>

    <p style="margin:0; font-size:14px; line-height:1.6; color:#616657;">
        Pozdrawiamy,<br>Zespół noskiem.org
    </p>
@endsection
