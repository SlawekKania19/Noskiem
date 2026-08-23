@extends('emails.layout')

@section('title', 'Informacja o Twoim zgłoszeniu')

@section('content')
    <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">Twoje zgłoszenie nie zostało zatwierdzone</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        Niestety, Twoje zgłoszenie „też widziałem" (#{{ $sighting->id }}) zostało odrzucone przez moderatora
        i nie pojawi się na stronie.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fcecd1; border-radius:12px; margin:0 0 16px;">
        <tr>
            <td style="padding:20px;">
                <p style="margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:0.05em; color:#994d0a;">Powód odrzucenia</p>
                <p style="margin:0; font-size:14px; color:#994d0a;">{{ $sighting->mod_reject_reason }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-size:14px; line-height:1.6; color:#616657;">
        Masz pytania? Po prostu odpowiedz na tego maila.<br>
        Pozdrawiamy,<br>Zespół noskiem.org
    </p>
@endsection
