// ---------------------------
// Mapa wszystkich zatwierdzonych ogłoszeń (GET /map) — pinezki kolorowane wg statusu
// (lost/found), z podglądem po kliknięciu. Filtry działają w całości po stronie
// przeglądarki na już wczytanym zbiorze, dzięki temu zmiana filtra nie resetuje
// przybliżenia/przesunięcia mapy tak jak przeładowanie strony na /animals.
// ---------------------------

const STATUS_COLORS = {
    lost: '#994d0a',
    found: '#3f6212',
};

const STATUS_LABELS = {
    lost: 'Zaginiony',
    found: 'Znaleziony',
};

const STATUS_BADGE_CLASSES = {
    lost: 'bg-[#fcecd1] text-[#994d0a]',
    found: 'bg-[#dbe9d8] text-[#3f6212]',
};

export default function animalsMap({ animals = [], breeds = [], initialStatus = '' } = {}) {
    // ** Obiekty Leaflet trzymamy POZA zwracanym stanem Alpine — Alpine (jak Vue) głęboko
    // owija przypisane obiekty w reaktywne Proxy, co koliduje z wewnętrznym śledzeniem
    // tożsamości obiektów w Leaflet (m.in. L.Util.stamp/_leaflet_id) i psuje m.in. popupy
    // po kliknięciu w pinezkę. Ten sam powód, dla którego animal-map.js w ogóle nie używa Alpine.
    let map = null;
    let markersLayer = null;
    let hasFitBounds = false;

    return {
        animals,
        breeds,
        speciesId: '',
        breedId: '',
        voivodeshipId: '',
        cityId: '',
        status: initialStatus,
        colorId: '',

        // ** Rasy zawężone do wybranego gatunku — ta sama logika co w formularzu zgłoszenia
        get filteredBreeds() {
            return this.breeds.filter((b) => !this.speciesId || String(b.species_id) === String(this.speciesId));
        },

        get filteredAnimals() {
            return this.animals.filter((a) => (
                (!this.speciesId || String(a.species_id) === String(this.speciesId))
                && (!this.breedId || String(a.breed_id) === String(this.breedId))
                && (!this.voivodeshipId || String(a.voivodeship_id) === String(this.voivodeshipId))
                && (!this.cityId || String(a.city_id) === String(this.cityId))
                && (!this.status || a.status === this.status)
                && (!this.colorId || a.color_ids.includes(Number(this.colorId)))
            ));
        },

        get hasActiveFilters() {
            return Boolean(
                this.speciesId || this.breedId || this.voivodeshipId
                || this.cityId || this.status || this.colorId,
            );
        },

        // ** Zmiana gatunku unieważnia wybraną rasę, jeśli nie należy już do nowej listy
        onSpeciesChange() {
            if (this.breedId && !this.filteredBreeds.some((b) => String(b.id) === String(this.breedId))) {
                this.breedId = '';
            }
        },

        // ** Nasłuch zdarzenia z <x-city-picker> — komponent ma własny, odizolowany stan Alpine
        onCityPickerChange(detail) {
            this.voivodeshipId = detail.voivodeshipId;
            this.cityId = detail.cityId;
        },

        resetFilters() {
            this.speciesId = '';
            this.breedId = '';
            this.voivodeshipId = '';
            this.cityId = '';
            this.status = '';
            this.colorId = '';
            window.dispatchEvent(new CustomEvent('city-picker-reset'));
        },

        initMap(elementId) {
            map = L.map(elementId, { zoomControl: true }).setView([52.0, 19.0], 6);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(map);

            markersLayer = L.layerGroup().addTo(map);

            // ** Przycisk "Przybliż" w popupie to zwykły HTML (string wstawiany przez bindPopup),
            // więc nie da się go spiąć z Alpine — łapiemy klik po otwarciu popupu i czytamy
            // współrzędne z atrybutów data-* zamiast trzymać osobny handler na marker
            map.on('popupopen', (e) => {
                const zoomButton = e.popup.getElement()?.querySelector('.js-pin-zoom');

                if (!zoomButton) {
                    return;
                }

                zoomButton.addEventListener('click', () => {
                    const lat = parseFloat(zoomButton.dataset.lat);
                    const lng = parseFloat(zoomButton.dataset.lng);
                    map.flyTo([lat, lng], Math.max(map.getZoom() + 3, 17));
                }, { once: true });
            });
        },

        renderMarkers() {
            if (!markersLayer) {
                return;
            }

            markersLayer.clearLayers();

            const visible = this.filteredAnimals;

            visible.forEach((animal) => {
                L.marker([animal.lat, animal.lng], { icon: pinIcon(animal.status) })
                    .bindPopup(popupHtml(animal))
                    .addTo(markersLayer);
            });

            // ** Dopasowanie widoku mapy tylko raz, przy pierwszym renderze (uwzględnia ewentualny
            // filtr startowy z URL) — kolejne zmiany filtrów nie mogą już ruszać mapą użytkownika
            if (!hasFitBounds && visible.length) {
                hasFitBounds = true;
                map.fitBounds(visible.map((a) => [a.lat, a.lng]), { padding: [30, 30], maxZoom: 14 });
            }
        },
    };
}

function pinIcon(status) {
    const color = STATUS_COLORS[status] ?? '#616657';

    return L.divIcon({
        className: '',
        html: `<svg width="26" height="34" viewBox="0 0 26 34" xmlns="http://www.w3.org/2000/svg">
            <path d="M13 33S2 20.8 2 13a11 11 0 0 1 22 0c0 7.8-11 20-11 20Z" fill="${color}" stroke="#ffffff" stroke-width="1.5"/>
            <circle cx="13" cy="13" r="4.5" fill="#ffffff"/>
        </svg>`,
        iconSize: [26, 34],
        iconAnchor: [13, 34],
        popupAnchor: [0, -30],
    });
}

// ** Treść ogłoszenia (tytuł, lokalizacja) pochodzi od zgłaszającego — escapujemy przed
// wstrzyknięciem do HTML popupu, żeby nie otworzyć furtki na XSS
function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (char) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    }[char]));
}

function popupHtml(animal) {
    const statusLabel = STATUS_LABELS[animal.status] ?? animal.status;
    const statusClass = STATUS_BADGE_CLASSES[animal.status] ?? 'bg-[#eee] text-[#616657]';
    const thumb = animal.thumbnail
        ? `<img src="${escapeHtml(animal.thumbnail)}" alt="" class="mb-2 h-24 w-full rounded-lg object-cover">`
        : '';
    const location = animal.location
        ? `<p class="mt-1 truncate text-[11px] text-[#8f9485]">📍 ${escapeHtml(animal.location)}</p>`
        : '';

    return `
        <div class="w-44">
            ${thumb}
            <span class="inline-flex rounded-md px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide ${statusClass}">${statusLabel}</span>
            <p class="mt-1.5 line-clamp-2 text-[13px] font-semibold text-[#1e2612]">${escapeHtml(animal.title)}</p>
            ${location}
            <div class="mt-2 flex items-center justify-between gap-2">
                <button
                    type="button"
                    class="js-pin-zoom inline-flex cursor-pointer items-center gap-1 text-[12px] font-semibold text-[#616657] hover:text-[#283618]"
                    data-lat="${animal.lat}"
                    data-lng="${animal.lng}"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m20 20-3.5-3.5M11 8v6M8 11h6"/>
                    </svg>
                    Przybliż
                </button>
                <a href="${escapeHtml(animal.url)}" target="_blank" rel="noopener noreferrer" class="text-[12px] font-semibold text-[#283618] underline hover:text-[#1e2812]">Zobacz pełne ogłoszenie →</a>
            </div>
        </div>
    `;
}
