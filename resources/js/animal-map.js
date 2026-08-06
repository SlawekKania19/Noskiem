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

        resolveLocation(lat, lng, options);
    };

    map.on('click', (e) => setPosition(e.latlng.lat, e.latlng.lng));

    if (options.locateButtonId) {
        setupLocateButton(options.locateButtonId, options.locateErrorId, map, setPosition);
    }

    return map;
}

window.initLocationPicker = initLocationPicker;

// ---------------------------
// Przycisk "Użyj mojej bieżącej lokalizacji" — przeglądarkowe Geolocation API.
// Wymaga bezpiecznego originu (https lub localhost), więc na starcie sprawdzamy
// tylko dostępność API — przycisk celowo nie jest ukrywany przy braku zgody,
// błąd użytkownik zobaczy dopiero po kliknięciu (patrz locateErrorMessage).
// ---------------------------
function setupLocateButton(buttonId, errorId, map, setPosition) {
    const button = document.getElementById(buttonId);

    if (!button) {
        return;
    }

    const errorEl = errorId ? document.getElementById(errorId) : null;
    const defaultLabel = button.textContent;

    const showError = (message) => {
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');
        }
    };

    const hideError = () => {
        if (errorEl) {
            errorEl.classList.add('hidden');
        }
    };

    if (!navigator.geolocation) {
        button.disabled = true;
        button.title = 'Twoja przeglądarka nie obsługuje geolokalizacji';
        return;
    }

    button.addEventListener('click', () => {
        hideError();
        button.disabled = true;
        button.textContent = 'Lokalizowanie...';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                button.disabled = false;
                button.textContent = defaultLabel;

                const { latitude, longitude } = position.coords;
                map.setView([latitude, longitude], 16);
                setPosition(latitude, longitude);
            },
            (error) => {
                button.disabled = false;
                button.textContent = defaultLabel;
                showError(locateErrorMessage(error));
            },
            { enableHighAccuracy: true, timeout: 10000 },
        );
    });
}

function locateErrorMessage(error) {
    switch (error.code) {
        case error.PERMISSION_DENIED:
            return 'Brak zgody na dostęp do lokalizacji — sprawdź uprawnienia witryny w przeglądarce.';
        case error.POSITION_UNAVAILABLE:
            return 'Nie udało się ustalić Twojej lokalizacji.';
        case error.TIMEOUT:
            return 'Upłynął limit czasu podczas ustalania lokalizacji, spróbuj ponownie.';
        default:
            return 'Nie udało się pobrać lokalizacji.';
    }
}

// ---------------------------
// Odwrotne geokodowanie pinezki (lat/lng -> województwo/miejscowość) przez Nominatim,
// pośredniczy nasz backend (GET /location/reverse). Wynik trafia do pola "Opis lokalizacji"
// oraz — przez zdarzenie DOM — do komponentu <x-city-picker> (osobny zakres Alpine).
// Błędy są ciche: to tylko podpowiedź, formularz da się wypełnić ręcznie.
// ---------------------------
async function resolveLocation(lat, lng, options = {}) {
    // ** Start/koniec zgłaszamy zawsze (finally) — formularz pokazuje spinner przy polu
    // "Opis lokalizacji" na czas zapytania, patrz x-data w create/edit.blade.php
    window.dispatchEvent(new CustomEvent('location-resolving'));

    try {
        const params = new URLSearchParams({ lat, lng });
        const response = await fetch(`/location/reverse?${params}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            return;
        }

        const data = await response.json();

        if (!data.resolved) {
            return;
        }

        if (options.locationTextInputId && data.location_text_suggestion) {
            const locationTextInput = document.getElementById(options.locationTextInputId);
            if (locationTextInput) {
                locationTextInput.value = data.location_text_suggestion;
            }
        }

        window.dispatchEvent(new CustomEvent('location-resolved', { detail: data }));
    } catch {
        // ** Ciszej się nie da — to tylko podpowiedź, nie blokujemy formularza
    } finally {
        window.dispatchEvent(new CustomEvent('location-resolved-end'));
    }
}
