<?php

namespace App\Mail;

use App\Models\Animal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Mail do autora STAREGO ogłoszenia: pojawiło się nowe ogłoszenie podobne po zdjęciach
// (App\Services\MatchOwnerNotifier). Jedno ogłoszenie w mailu, link do wypisania się
// i do strony ogłoszenia autora (oznaczenie "odnaleziony").
// ---------------------------
class NewSimilarListing extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Animal  $animal  ogłoszenie adresata
     * @param  array{title:string,url:string,photo_url:?string,photo_position:string,level:string,location:?string,date:?string,distance_km:float}  $item  nowe podobne ogłoszenie
     * @param  array{opt_out:string,manage:string}  $links
     */
    public function __construct(public Animal $animal, public array $item, public array $links)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Nowe ogłoszenie podobne do Twojego - '.config('app.name'))
            ->replyTo('kontakt@noskiem.org', 'Noskiem.org')
            ->view('emails.new-similar-listing');
    }
}
