<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

// ---------------------------
// Mail do Agenta noskiem: w Twoim obszarze działania zaginął zwierzak - prośba o pomoc
// (App\Services\AgentNotifier). Zbiorczy - wszystkie nowe ogłoszenia z ostatniej godziny.
// Bez HasUnsubscribeLink: tamta rezygnacja usuwa ogłoszenia, tu wystarczy wyłączyć
// powiadomienia agenta (link opt_out), konto i obszary zostają.
// ---------------------------
class AgentHelpRequest extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{title:string,url:string,photo_url:?string,photo_position:string,location:?string,date:?string,distance_km:float,area_name:?string}>  $items
     * @param  array{panel:string,opt_out:string}  $links
     */
    public function __construct(public User $agent, public array $items, public array $links)
    {
    }

    public function build(): self
    {
        $count = count($this->items);

        return $this
            ->subject($count === 1
                ? 'Zaginął zwierzak w Twojej okolicy - pomożesz szukać?'
                : "Zaginione zwierzaki w Twojej okolicy ({$count}) - pomożesz szukać?")
            ->replyTo('kontakt@noskiem.org', 'Noskiem.org')
            ->view('emails.agent-help-request', [
                'shown' => array_slice($this->items, 0, (int) config('noskiem_agents.max_items_per_mail')),
                'more'  => max(0, $count - (int) config('noskiem_agents.max_items_per_mail')),
            ]);
    }

    // ** Bez List-Unsubscribe-Post (one-click) - wyłączenie dopiero po kliknięciu przycisku na stronie
    public function headers(): Headers
    {
        return new Headers(text: ['List-Unsubscribe' => '<'.$this->links['opt_out'].'>']);
    }
}
