@extends('emails.layout')

@section('title', 'Nowa wiadomość z formularza kontaktowego')

@section('content')
    <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Nowa wiadomość z formularza kontaktowego</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        {{ $senderName }} ({{ $senderEmail }}) napisał(a) przez formularz kontaktowy na stronie:
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 16px; background-color:#f4f4ef; border-radius:10px;">
        <tr>
            <td style="padding:16px; font-size:14px; line-height:1.6; color:#283618; white-space:pre-line;">{{ $body }}</td>
        </tr>
    </table>

    <p style="margin:0; font-size:14px; line-height:1.6; color:#616657;">
        Możesz odpowiedzieć bezpośrednio na tego maila — trafi prosto do {{ $senderName }}.
    </p>
@endsection
