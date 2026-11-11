@extends('emails.layout')

@section('title', $isEdit ? 'Zmiany we wpisie zatwierdzone' : 'Chip zarejestrowany w bazie')

@section('content')
    @if ($isEdit)
        <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Zmiany zatwierdzone!</h1>

        <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
            Moderator zatwierdził zmiany we wpisie - w wyszukiwarce widoczna jest już nowa wersja.
        </p>
    @else
        <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Chip zarejestrowany!</h1>

        <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
            Dobra wiadomość - wpis został zatwierdzony. Jeśli ktoś znajdzie Twojego zwierzaka i sprawdzi
            numer chipa w naszej bazie, zobaczy jego dane i będzie mógł do Ciebie zadzwonić.
        </p>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4ef; border-radius:12px; margin:0 0 16px;">
        <tr>
            <td style="padding:20px;">
                <p style="margin:0 0 4px; font-size:16px; font-weight:600; color:#283618;">{{ $registration->animal_name }}</p>
                <p style="margin:0; font-size:14px; color:#616657;">Chip nr {{ $registration->chip_number }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Możesz to sprawdzić w <a href="{{ $searchUrl }}" style="color:#283618;">wyszukiwarce bazy chipów</a>.
        Twój adres e-mail nie jest nigdzie pokazywany.
    </p>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Zmieniłeś numer telefonu albo chcesz usunąć wpis? Wszystko zrobisz z jednego miejsca -
        zachowaj tego maila:
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">
        <tr>
            <td style="border-radius:10px; background-color:#283618;">
                <a
                    href="{{ $editUrl }}"
                    style="display:inline-block; padding:12px 24px; color:#fefae0; font-size:14px; font-weight:600; text-decoration:none;"
                >
                    Zarządzaj wpisem
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-size:14px; line-height:1.6; color:#616657;">
        Pozdrawiamy,<br>Zespół @include('emails.partials.brand')
    </p>
@endsection
