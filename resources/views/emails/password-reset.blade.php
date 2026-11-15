@extends('emails.layout')

@section('title', 'Zmiana hasła')

@section('content')
    <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Zmiana hasła</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Dostaliśmy prośbę o zmianę hasła do Twojego konta. Kliknij przycisk, żeby ustawić nowe.
    </p>

    <p style="margin:0 0 24px;">
        <a href="{{ $url }}" style="display:inline-block; background-color:#283618; color:#fefae0; font-size:14px; font-weight:600; text-decoration:none; padding:12px 24px; border-radius:12px;">Ustaw nowe hasło</a>
    </p>

    <p style="margin:0 0 16px; font-size:13px; line-height:1.6; color:#8f9485;">
        Link jest ważny {{ $minutes }} minut. Jeśli to nie Ty prosiłeś/aś o zmianę hasła,
        zignoruj tę wiadomość - hasło zostanie bez zmian.
    </p>

    <p style="margin:0; font-size:14px; line-height:1.6; color:#616657;">
        Zespół @include('emails.partials.brand')
    </p>
@endsection
