import Alpine from 'alpinejs';
import L from 'leaflet';
import './animal-map';
import animalsMap from './animals-map';
import agentAreas from './agent-areas';

// ** animals-map.js korzysta z globalnego L (nie importuje go samo)
window.L = L;

// ** Alpine (ten sam, współdzielony moduł co w app.js) - rejestracja musi zdążyć
// przed Alpine.start() wywoływanym w app.js, patrz komentarz tam
Alpine.data('animalsMap', animalsMap);
Alpine.data('agentAreas', agentAreas);
