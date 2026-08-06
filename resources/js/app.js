import Alpine from 'alpinejs';
import L from 'leaflet';
import './animal-map';
import cityPicker from './city-picker';
import animalsMap from './animals-map';

window.Alpine = Alpine;
window.L = L;

// Tryb (szukam/znalazlem) wybrany w przełączniku na stronie głównej — global,
// żeby linki "Dodaj ogłoszenie" w navbarze/stopce/dolnym menu mogły z niego skorzystać
Alpine.store('petMode', 'szukam');

Alpine.data('cityPicker', cityPicker);
Alpine.data('animalsMap', animalsMap);

Alpine.start();
