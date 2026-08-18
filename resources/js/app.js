import Alpine from 'alpinejs';
import cityPicker from './city-picker';

window.Alpine = Alpine;

// Tryb (szukam/znalazlem) wybrany w przełączniku na stronie głównej — global,
// żeby linki "Dodaj ogłoszenie" w navbarze/stopce/dolnym menu mogły z niego skorzystać
Alpine.store('petMode', 'szukam');

Alpine.data('cityPicker', cityPicker);

// ** Leaflet + komponenty mapy (animal-map.js, animals-map.js) są w osobnym wpisie
// (resources/js/maps.js), dociąganym tylko przez strony z mapą — patrz @push('head-assets')
// w animals/create|edit|show|map.blade.php. Musi się wykonać PRZED poniższym Alpine.start(),
// żeby zdążyć zarejestrować Alpine.data('animalsMap', ...) — stąd @stack('head-assets') w
// layouts/public.blade.php stoi w <head> przed tym plikiem (kolejność <script type=module>
// w dokumencie = kolejność wykonania).
Alpine.start();
