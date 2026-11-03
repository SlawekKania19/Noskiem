@extends('emails.layout')

@section('title', $isEdit ? 'Informacja o zmianach we wpisie' : 'Informacja o zgłoszeniu chipa')

@section('content')
    @if ($isEdit)
        <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Zmiany we wpisie nie zostały zatwierdzone</h1>

        <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
            Moderator odrzucił zmiany we wpisie chipa nr {{ $registration->chip_number }} ({{ $registration->animal_name }}).
            Wpis pozostaje w bazie w dotychczasowej wersji.
        </p>
    @else
        <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Zgłoszenie chipa nie zostało zatwierdzone</h1>

        <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
            Niestety, zgłoszenie chipa nr {{ $registration->chip_number }} ({{ $registration->animal_name }})
            zostało odrzucone przez moderatora i nie pojawi się w bazie.
        </p>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fcecd1; border-radius:12px; margin:0 0 16px;">
        <tr>
            <td style="padding:20px;">
                <p style="margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:0.05em; color:#994d0a;">Powód odrzucenia</p>
                <p style="margin:0; font-size:14px; color:#994d0a;">{{ $reason }}</p>
            </td>
        </tr>
    </table>

    @if ($isEdit)
        <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
            Możesz poprawić zmiany i wysłać je ponownie: <a href="{{ $editUrl }}" style="color:#283618;">zarządzaj wpisem</a>.
        </p>
    @endif

    <p style="margin:0; font-size:14px; line-height:1.6; color:#616657;">
        Masz pytania? Po prostu odpowiedz na tego maila.<br>
        Pozdrawiamy,<br>Zespół noskiem.org
    </p>
@endsection
