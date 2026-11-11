<?php

namespace App\Mail;

use App\Models\AnimalEdit;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Mail do autora nowego zgłoszenia z ogłoszeniami podobnymi po zdjęciach
// (php artisan match:notify, App\Services\PhotoMatchNotifier). Wysyłany tylko,
// gdy któryś kandydat przekracza próg. Synchronicznie - jak pozostałe maile.
// ---------------------------
class MatchCandidatesFound extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{title:string,url:string,photo_url:?string,level:string,location:?string,date:?string,distance_km:float}>  $items
     * @param  array  $params  parametry wyszukiwania (m.in. promień - do treści maila)
     */
    public function __construct(public AnimalEdit $animalEdit, public array $items, public array $params)
    {
    }

    public function build(): self
    {
        return $this
            ->subject('Podobne ogłoszenia do Twojego zgłoszenia - '.config('app.name'))
            ->replyTo('kontakt@noskiem.org', 'Noskiem.org')
            ->with([
                'radiusKm' => (float) ($this->params['filters']['radius_km'] ?? 0),
                'listUrl'  => route('animals.index'),
            ])
            ->view('emails.match-candidates');
    }
}
