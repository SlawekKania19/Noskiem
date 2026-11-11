@extends('emails.layout')

@section('title', 'Podobne ogłoszenia')

@section('content')
    @php
        $isLost = $animalEdit->status === 'lost';
    @endphp

    <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Witaj, {{ explode(' ', trim($animalEdit->contact_name))[0] }}!</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Porównaliśmy zdjęcia z Twojego zgłoszenia (#{{ $animalEdit->id }}) ze zdjęciami
        {{ $isLost ? 'znalezionych' : 'zaginionych' }} zwierzaków w naszej
        bazie{{ $radiusKm > 0 ? ' (w promieniu ok. '.(int) $radiusKm.' km)' : '' }}.
        Te ogłoszenia wyglądają podobnie:
    </p>

    @foreach ($items as $item)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4ef; border-radius:12px; margin:0 0 12px;">
            <tr>
                @if ($item['photo_url'])
                    <td width="96" style="padding:12px 0 12px 12px; vertical-align:top;">
                        <a href="{{ $item['url'] }}">
                            <img src="{{ $item['photo_url'] }}" width="96" height="96" alt="" style="display:block; width:96px; height:96px; object-fit:cover; object-position:{{ $item['photo_position'] ?? '50% 50%' }}; border-radius:8px;">
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
                        · ok. {{ $item['distance_km'] < 1 ? '<1' : (int) round($item['distance_km']) }} km od Ciebie
                    </p>
                    <a href="{{ $item['url'] }}" style="font-size:13px; font-weight:600; color:#283618;">Zobacz ogłoszenie →</a>
                </td>
            </tr>
        </table>
    @endforeach

    <p style="margin:16px 0 16px; font-size:13px; line-height:1.6; color:#8f9485;">
        Porównanie zdjęć jest automatyczne i może się mylić - sprawdź dokładnie zdjęcia, opis
        i znaki szczególne.
        {{ $isLost
            ? 'Jeśli rozpoznajesz swojego zwierzaka, skontaktuj się z autorem przez stronę ogłoszenia.'
            : 'Jeśli to zwierzak, którego znalazłeś/aś, skontaktuj się z autorem przez stronę ogłoszenia.' }}
    </p>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Nie ma tu tego zwierzaka? Przejrzyj wszystkie ogłoszenia - nowe pojawiają się codziennie:
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 16px;">
        <tr>
            <td style="border-radius:10px; border:1px solid #283618;">
                <a href="{{ $listUrl }}" style="display:inline-block; padding:12px 24px; color:#283618; font-size:14px; font-weight:600; text-decoration:none;">
                    Wszystkie ogłoszenia
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-size:14px; line-height:1.6; color:#616657;">
        Trzymamy kciuki,<br>Zespół @include('emails.partials.brand')
    </p>
@endsection
