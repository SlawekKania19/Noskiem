<?php

namespace App\View\Components;

use App\Models\Partner;
use App\Models\Setting;
use Illuminate\View\Component;
use Illuminate\View\View;

// ---------------------------
// Pasek partnerów nad stopką — karuzela logotypów.
// Komponent renderuje się na każdej podstronie (layouts/public), więc dane
// bierze z cache'owanej listy (Partner::forBar()).
// ---------------------------

class PartnersBar extends Component
{
    public array $partners;

    // Liczba banerów widocznych naraz na desktopie (na tablecie/mobile ustala ją JS)
    public int $visibleSlots;

    // Co ile sekund pasek przesuwa się o jeden baner
    public int $interval;

    public function __construct()
    {
        $this->partners = Partner::forBar();
        $this->visibleSlots = (int) Setting::get('partners_visible', '5');
        $this->interval = (int) Setting::get('partners_interval', '4');
    }

    // Bez partnerów nie ma czego pokazywać — pasek w ogóle się nie renderuje
    public function shouldRender(): bool
    {
        return $this->partners !== [];
    }

    public function render(): View
    {
        return view('components.partners-bar');
    }
}
