@extends('emails.layout')

@section('title', 'Potwierdź adres e-mail')

@section('content')
    <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Witaj, {{ explode(' ', trim($agent->name))[0] }}!</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Dziękujemy za dołączenie do Agentów noskiem. Potwierdź adres e-mail, żebyśmy mogli
        pisać do Ciebie, gdy w Twojej okolicy zaginie zwierzak.
    </p>

    <p style="margin:0 0 24px;">
        <a href="{{ $url }}" style="display:inline-block; background-color:#283618; color:#fefae0; font-size:14px; font-weight:600; text-decoration:none; padding:12px 24px; border-radius:12px;">Potwierdzam adres e-mail</a>
    </p>

    <p style="margin:0 0 16px; font-size:13px; line-height:1.6; color:#8f9485;">
        Link jest ważny 7 dni. Jeśli to nie Ty zakładałeś/aś konto, zignoruj tę wiadomość -
        bez potwierdzenia nie wyślemy nic więcej.
    </p>

    <p style="margin:0; font-size:14px; line-height:1.6; color:#616657;">
        Zespół @include('emails.partials.brand')
    </p>
@endsection
