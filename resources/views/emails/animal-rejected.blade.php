@extends('emails.layout')

@section('title', 'Informacja o Twoim zgłoszeniu')

@section('content')
    <h1 style="margin:0 0 16px; font-size:20px; color:#283618;">
        @if ($isEdit)
            Zmiana w Twoim ogłoszeniu nie została zatwierdzona
        @else
            Twoje ogłoszenie nie zostało zatwierdzone
        @endif
    </h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#616657;">
        @if ($isEdit)
            Niestety, zmiana zgłoszona w ogłoszeniu (#{{ $animalEdit->id }}) została odrzucona przez moderatora.
            Twoje oryginalne ogłoszenie pozostaje bez zmian i nadal jest widoczne na stronie.
        @else
            Niestety, Twoje ogłoszenie (#{{ $animalEdit->id }}) zostało odrzucone przez moderatora i nie pojawi się na stronie.
        @endif
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#fcecd1; border-radius:12px; margin:0 0 16px;">
        <tr>
            <td style="padding:20px;">
                <p style="margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:0.05em; color:#994d0a;">Powód odrzucenia</p>
                <p style="margin:0; font-size:14px; color:#994d0a;">{{ $animalEdit->mod_reject_reason }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:0; font-size:14px; line-height:1.6; color:#616657;">
        Masz pytania? Po prostu odpowiedz na tego maila.<br>
        Pozdrawiamy,<br>Zespół noskiem.org
    </p>
@endsection
