@extends('emails.layout')

@section('title', 'Nowa wiadomość w sprawie ogłoszenia')

@section('content')
    <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">
        @if ($isSighting)
            Nowa wiadomość w sprawie Twojego zgłoszenia
        @else
            Nowa wiadomość w sprawie Twojego ogłoszenia
        @endif
    </h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        @if ($isSighting)
            {{ $contactMessage->name }} ({{ $contactMessage->email }}) napisał(a) w sprawie Twojego zgłoszenia
            "też widziałem" pod ogłoszeniem „{{ $animal->generated_title }}":
        @else
            {{ $contactMessage->name }} ({{ $contactMessage->email }}) napisał(a) w sprawie ogłoszenia
            „{{ $animal->generated_title }}":
        @endif
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 16px; background-color:#f4f4ef; border-radius:10px;">
        <tr>
            <td style="padding:16px; font-size:14px; line-height:1.6; color:#283618; white-space:pre-line;">{{ $contactMessage->message }}</td>
        </tr>
    </table>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Możesz odpowiedzieć bezpośrednio na tego maila — trafi prosto do {{ $contactMessage->name }}.
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
