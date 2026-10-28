@extends('emails.layout')

@section('title', 'Wciąż szukamy Twojego zwierzaka')

@section('content')
    <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Wciąż szukamy Twojego zwierzaka</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Twoje ogłoszenie (#{{ $animal->id }}) jest nadal aktywne i widoczne na stronie — nie przestajemy szukać.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4ef; border-radius:12px; margin:0 0 16px;">
        <tr>
            <td style="padding:20px;">
                <p style="margin:0; font-size:16px; font-weight:600; color:#283618;">{{ $animal->generated_title }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Jeśli zwierzak już się odnalazł — daj nam znać! Oznacz ogłoszenie jako „Znaleziony" albo je usuń,
        żeby inni nie szukali niepotrzebnie. Możesz tam też zaktualizować opis czy zdjęcia:
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
        Nadal szukasz? Odświeżony plakat w okolicy często pomaga — pobierzesz go tutaj:
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">
        <tr>
            <td style="border-radius:10px; border:1px solid #283618;">
                <a
                    href="{{ $posterUrl }}"
                    style="display:inline-block; padding:12px 24px; color:#283618; font-size:14px; font-weight:600; text-decoration:none;"
                >
                    Wydrukuj plakat
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        To przypomnienie wysyłamy raz w miesiącu, dopóki ogłoszenie jest aktywne.
    </p>

    <p style="margin:0; font-size:14px; line-height:1.6; color:#616657;">
        Trzymamy kciuki,<br>Zespół noskiem.org
    </p>
@endsection
