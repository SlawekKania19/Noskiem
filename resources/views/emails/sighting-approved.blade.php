@extends('emails.layout')

@section('title', 'Twoje zgłoszenie zostało opublikowane')

@section('content')
    <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Zgłoszenie opublikowane!</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Dobra wiadomość — Twoje zgłoszenie „też widziałem" zostało zatwierdzone i jest już widoczne
        na stronie ogłoszenia „{{ $sighting->animal->generated_title }}".
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">
        <tr>
            <td style="border-radius:10px; background-color:#283618;">
                <a
                    href="{{ $animalUrl }}"
                    style="display:inline-block; padding:12px 24px; color:#fefae0; font-size:14px; font-weight:600; text-decoration:none;"
                >
                    Zobacz ogłoszenie
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-size:14px; line-height:1.6; color:#616657;">
        Pozdrawiamy,<br>Zespół noskiem.org
    </p>
@endsection
