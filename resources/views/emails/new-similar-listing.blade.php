@extends('emails.layout')

@section('title', 'Nowe podobne ogłoszenie')

@section('content')
    @php
        // ** Adresat szukał zaginionego - nowe ogłoszenie to znaleziony zwierzak (i odwrotnie)
        $isLost = $animal->status === 'lost';
    @endphp

    <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Witaj, {{ explode(' ', trim($animal->contact_name))[0] }}!</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Na stronie pojawiło się nowe ogłoszenie o {{ $isLost ? 'znalezionym' : 'zaginionym' }} zwierzaku,
        które po zdjęciach wygląda podobnie do Twojego ogłoszenia{{ $animal->animal_name ? ' ('.$animal->animal_name.')' : '' }}.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4ef; border-radius:12px; margin:0 0 12px;">
        <tr>
            @if ($item['photo_url'])
                <td width="96" style="padding:12px 0 12px 12px; vertical-align:top;">
                    <a href="{{ $item['url'] }}">
                        <img src="{{ $item['photo_url'] }}" width="96" height="96" alt="" style="display:block; width:96px; height:96px; object-fit:cover; object-position:{{ $item['photo_position'] }}; border-radius:8px;">
                    </a>
                </td>
            @endif
            <td style="padding:12px 16px; vertical-align:top;">
                {{-- ** "Bardzo podobne" tylko od wyższego progu; poniżej - ostrożniej --}}
                <p style="margin:0 0 4px; font-size:11px; text-transform:uppercase; letter-spacing:0.05em; color:{{ $item['level'] === 'strong' ? '#283618' : '#8f9485' }}; font-weight:600;">
                    {{ $item['level'] === 'strong' ? 'Bardzo podobne' : 'Możliwe dopasowanie' }}
                </p>
                <p style="margin:0 0 4px; font-size:15px; font-weight:600; color:#283618;">
                    <a href="{{ $item['url'] }}" style="color:#283618; text-decoration:none;">{{ $item['title'] }}</a>
                </p>
                <p style="margin:0 0 8px; font-size:13px; line-height:1.5; color:#616657;">
                    {{ $item['location'] }}
                    @if ($item['date'])
                        · {{ $item['date'] }}
                    @endif
                    · ok. {{ $item['distance_km'] < 1 ? '<1' : (int) round($item['distance_km']) }} km od miejsca z Twojego ogłoszenia
                </p>
                <a href="{{ $item['url'] }}" style="font-size:13px; font-weight:600; color:#283618;">Zobacz ogłoszenie →</a>
            </td>
        </tr>
    </table>

    <p style="margin:16px 0 16px; font-size:13px; line-height:1.6; color:#8f9485;">
        Porównanie zdjęć jest automatyczne i może się mylić - sprawdź dokładnie zdjęcia, opis
        i znaki szczególne.
        {{ $isLost
            ? 'Jeśli rozpoznajesz swojego zwierzaka, skontaktuj się z autorem przez stronę ogłoszenia.'
            : 'Jeśli to zwierzak, którego znalazłeś/aś, skontaktuj się z autorem przez stronę ogłoszenia.' }}
    </p>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        {{ $isLost ? 'Zwierzak już się znalazł?' : 'Zwierzak wrócił już do domu?' }}
        <a href="{{ $links['manage'] }}" style="color:#283618; font-weight:600;">Oznacz ogłoszenie jako zakończone</a>
        - wtedy zniknie ze strony i nie będziemy już pisać.
    </p>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Trzymamy kciuki,<br>Zespół @include('emails.partials.brand')
    </p>

    {{-- ** Mail jest nieproszony - wypisanie się zawsze pod ręką --}}
    <p style="margin:0; font-size:12px; line-height:1.6; color:#8f9485;">
        Piszemy, bo masz u nas aktywne ogłoszenie. Najwyżej raz na kilka dni.
        <a href="{{ $links['opt_out'] }}" style="color:#8f9485;">Nie chcę więcej takich powiadomień dla tego ogłoszenia</a>.
    </p>
@endsection
