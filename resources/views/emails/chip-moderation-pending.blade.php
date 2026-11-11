@extends('emails.layout')

@section('title', 'Nowe zgłoszenie do bazy chipów czeka na moderację')

@section('content')
    <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Nowe zgłoszenie do bazy chipów czeka na moderację</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Zgłoszenie #{{ $registration->id }} - chip nr {{ $registration->chip_number }}
        ({{ $registration->animal_name }}) czeka na Twoją decyzję.
        @if ($registration->hasChipConflict())
            <br><strong style="color:#994d0a;">Uwaga: ten numer chipa jest już zatwierdzony w innym wpisie.</strong>
        @endif
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">
        <tr>
            <td style="border-radius:10px; background-color:#283618;">
                <a
                    href="{{ $moderationUrl }}"
                    style="display:inline-block; padding:12px 24px; color:#fefae0; font-size:14px; font-weight:600; text-decoration:none;"
                >
                    Otwórz w panelu moderacji
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-size:14px; line-height:1.6; color:#616657;">
        Pozdrawiamy,<br>Zespół noskiem.org
    </p>
@endsection
