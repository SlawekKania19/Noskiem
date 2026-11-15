@extends('emails.layout')

@section('title', 'Prośba o pomoc w poszukiwaniach')

@section('content')
    <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Witaj, {{ explode(' ', trim($agent->name))[0] }}!</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        @if (count($items) === 1)
            W Twoim obszarze działania zaginął zwierzak. Jeśli możesz, rozejrzyj się po okolicy -
            każda para oczu zwiększa szansę, że wróci do domu.
        @else
            W Twoim obszarze działania pojawiły się nowe ogłoszenia o zaginionych zwierzakach
            ({{ count($items) }}). Jeśli możesz, rozejrzyj się po okolicy - każda para oczu
            zwiększa szansę, że wrócą do domu.
        @endif
    </p>

    @foreach ($shown as $item)
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
                    <p style="margin:0 0 4px; font-size:15px; font-weight:600; color:#283618;">
                        <a href="{{ $item['url'] }}" style="color:#283618; text-decoration:none;">{{ $item['title'] }}</a>
                    </p>
                    <p style="margin:0 0 8px; font-size:13px; line-height:1.5; color:#616657;">
                        {{ $item['location'] }}
                        @if ($item['date'])
                            · zaginął {{ $item['date'] }}
                        @endif
                        <br>
                        ok. {{ $item['distance_km'] < 1 ? '<1' : (int) round($item['distance_km']) }} km od środka
                        {{ $item['area_name'] ? 'obszaru „'.$item['area_name'].'”' : 'Twojego obszaru' }}
                    </p>
                    <a href="{{ $item['url'] }}" style="font-size:13px; font-weight:600; color:#283618;">Zobacz ogłoszenie →</a>
                </td>
            </tr>
        </table>
    @endforeach

    @if ($more > 0)
        <p style="margin:0 0 16px; font-size:13px; line-height:1.6; color:#616657;">
            ...i jeszcze {{ $more }} - wszystkie znajdziesz na
            <a href="{{ route('animals.index', ['status' => 'lost']) }}" style="color:#283618; font-weight:600;">liście zaginionych</a>.
        </p>
    @endif

    <p style="margin:16px 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        <strong style="color:#283618;">Ruszasz na poszukiwania?</strong> Zaloguj się i kliknij
        „Szukam” na stronie ogłoszenia - właściciel zobaczy, że nie jest sam.
        Jeśli zauważysz zwierzaka, skontaktuj się z autorem przez formularz na stronie ogłoszenia.
    </p>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Dziękujemy, że pomagasz,<br>Zespół @include('emails.partials.brand')
    </p>

    <p style="margin:0; font-size:12px; line-height:1.6; color:#8f9485;">
        Piszemy, bo masz konto Agenta noskiem i to ogłoszenie mieści się w Twoim obszarze działania.
        <a href="{{ $links['panel'] }}" style="color:#8f9485;">Zmień obszary</a> albo
        <a href="{{ $links['opt_out'] }}" style="color:#8f9485;">wyłącz powiadomienia</a>.
    </p>
@endsection
