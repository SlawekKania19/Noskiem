@extends('emails.layout')

@section('title', 'Potwierdź adres e-mail')

@section('content')
    <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Prawie gotowe!</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Dziękujemy za zgłoszenie „też widziałem" pod ogłoszeniem
        <a href="{{ $animalUrl }}" style="color:#283618;">{{ $sighting->animal->generated_title }}</a>.
        Zanim trafi do moderacji, potwierdź swój adres e-mail:
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">
        <tr>
            <td style="border-radius:10px; background-color:#283618;">
                <a
                    href="{{ $confirmUrl }}"
                    style="display:inline-block; padding:12px 24px; color:#fefae0; font-size:14px; font-weight:600; text-decoration:none;"
                >
                    Potwierdź adres e-mail
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-size:14px; line-height:1.6; color:#616657;">
        Pozdrawiamy,<br>Zespół noskiem.org
    </p>
@endsection
