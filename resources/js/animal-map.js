import L from 'leaflet';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

// ---------------------------
// Domyślna ikona Leaflet próbuje wykryć ścieżkę do grafik przez CSS, co nie działa
// pod Vite (stąd "połamana" ikonka) — podajemy zbundlowane URL-e jawnie.
// ---------------------------
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

// ---------------------------
// Inicjalizacja mapy Leaflet z pojedynczą, nieprzesuwalną pinezką (tryb tylko do odczytu).
// Współdzielone między widokiem szczegółów ogłoszenia a formularzem zgłoszenia (Krok 8).
// ---------------------------
export function initAnimalMap(elementId, lat, lng, options = {}) {
    const element = document.getElementById(elementId);

    if (!element || lat === null || lng === null) {
        return null;
    }

    const map = L.map(element, { zoomControl: true }).setView([lat, lng], options.zoom ?? 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    // ** Pinezka nieprzesuwalna — lokalizacja jest tu tylko prezentowana, nie edytowana
    L.marker([lat, lng], { draggable: false }).addTo(map);

    return map;
}

window.initAnimalMap = initAnimalMap;

// ---------------------------
// Tryb edycji (formularz zgłoszenia) — kliknięcie na mapie ustawia pinezkę
// i wypełnia ukryte pola latitude/longitude. Pinezka nie jest przeciągalna,
// jedynym sposobem zmiany położenia jest kolejne kliknięcie na mapie.
// ---------------------------
export function initLocationPicker(elementId, options = {}) {
    const element = document.getElementById(elementId);

    if (!element) {
        return null;
    }

    const latInput = document.getElementById(options.latInputId);
    const lngInput = document.getElementById(options.lngInputId);

    const hasInitialPosition = options.lat !== null && options.lat !== undefined
        && options.lng !== null && options.lng !== undefined;

    const startLat = hasInitialPosition ? options.lat : (options.defaultLat ?? 52.0);
    const startLng = hasInitialPosition ? options.lng : (options.defaultLng ?? 19.0);

    const map = L.map(element, { zoomControl: true })
        .setView([startLat, startLng], hasInitialPosition ? 15 : 6);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);

    let marker = hasInitialPosition
        ? L.marker([startLat, startLng], { draggable: false }).addTo(map)
        : null;

    const setPosition = (lat, lng) => {
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { draggable: false }).addTo(map);
        }

        if (latInput) {
            latInput.value = lat.toFixed(6);
        }

        if (lngInput) {
            lngInput.value = lng.toFixed(6);
        }
    };

    map.on('click', (e) => setPosition(e.latlng.lat, e.latlng.lng));

    return map;
}

window.initLocationPicker = initLocationPicker;
