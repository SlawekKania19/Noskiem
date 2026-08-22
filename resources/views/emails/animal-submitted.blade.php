@extends('emails.layout')

@section('title', 'Otrzymaliśmy Twoje zgłoszenie')

@section('content')
    <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Dziękujemy, {{ $animalEdit->contact_name }}!</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Otrzymaliśmy Twoje zgłoszenie o {{ $animalEdit->status === 'lost' ? 'zaginionym' : 'znalezionym' }}
        zwierzaku i trafiło ono do skrzynki moderatora. 
        {{ $animalEdit->status === 'lost' ? 'Jest nam bardzo przykro że zaginął Ci zwierzak, ale zrobimy co w naszej mocy aby Ci pomóc' : 'Bardzo się cieszymy, że udało Ci się znaleźć zwierzaka i chcesz pomóc go odnaleźć!' }}
        Gdy tylko zatwierdzimy Twoje ogłoszenie, pojawi się ono na stronie —
        wyślemy Ci wtedy kolejną wiadomość z linkiem do jego edycji.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4ef; border-radius:12px; margin:0 0 16px;">
        <tr>
            <td style="padding:20px;">
                <p style="margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:0.05em; color:#8f9485;">Podsumowanie zgłoszenia</p>
                <p style="margin:0 0 4px; font-size:16px; font-weight:600; color:#283618;">{{ $animalEdit->title }}</p>
                <p style="margin:0; font-size:13px; line-height:1.6; color:#616657;">
                    {{ $animalEdit->status === 'lost' ? 'Zaginiony' : 'Znaleziony' }}
                    · {{ $animalEdit->species?->name_pl }}
                    @if ($animalEdit->breed?->breed_pl)
                        · {{ $animalEdit->breed->breed_pl }}
                    @endif
                    · {{ $animalEdit->location_text }}
                    · {{ $animalEdit->date_event?->format('d.m.Y') }}
                </p>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-size:14px; line-height:1.6; color:#616657;">
        Pozdrawiamy,<br>Zespół noskiem.org
    </p>
@endsection
