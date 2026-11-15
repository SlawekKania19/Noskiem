import L from 'leaflet';

// ---------------------------
// Mapa obszarów działania w panelu Agenta noskiem (agents/panel.blade.php).
// - zapisane obszary = zielone okręgi; suwak promienia przy obszarze zmienia okrąg na żywo
//   (zapis dopiero po kliknięciu "Zapisz" - zwykły formularz)
// - nowy obszar = pomarańczowy okrąg: środek z kliknięcia w mapę, wyszukanego adresu
//   albo geolokalizacji, promień z suwaka
// Obiekty Leaflet trzymamy POZA stanem Alpine - proxy reaktywności psuje ich metody.
// ---------------------------
export default function agentAreas(config) {
    let map = null;
    let draftCircle = null;
    let draftMarker = null;
    const circles = {};

    const SAVED_STYLE = { color: '#283618', weight: 2, fillColor: '#283618', fillOpacity: 0.12 };
    const ACTIVE_STYLE = { color: '#283618', weight: 3, fillColor: '#283618', fillOpacity: 0.25 };
    const DRAFT_STYLE = { color: '#994d0a', weight: 2, dashArray: '6 6', fillColor: '#f0a04b', fillOpacity: 0.2 };

    return {
        areas: config.areas,
        activeId: null,
        draft: { lat: config.draft?.lat ?? null, lng: config.draft?.lng ?? null, radius: config.draft?.radius ?? config.defaultRadius },
        query: '',
        searching: false,
        searchError: '',

        init() {
            // ** Start: cała Polska; po narysowaniu obszarów widok dopasowuje się do nich
            map = L.map(this.$refs.map, { zoomControl: true }).setView([52.0, 19.0], 6);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(map);

            this.areas.forEach((area) => {
                circles[area.id] = L.circle([area.latitude, area.longitude], { ...SAVED_STYLE, radius: area.radius_km * 1000 })
                    .addTo(map)
                    .on('click', (event) => {
                        // ** Kliknięcie w zapisany obszar wybiera go, nie stawia nowego punktu
                        L.DomEvent.stopPropagation(event);
                        this.focus(area.id);
                    });
            });

            map.on('click', (event) => this.setDraft(event.latlng.lat, event.latlng.lng, false));

            // ** Po błędzie walidacji formularz wraca z zaznaczonym punktem
            if (this.draft.lat !== null) {
                this.drawDraft();
            }

            this.fitAll();
        },

        // ---------------------------
        // Nowy obszar
        // ---------------------------
        setDraft(lat, lng, pan = true) {
            this.draft.lat = Number(lat.toFixed(7));
            this.draft.lng = Number(lng.toFixed(7));
            this.searchError = '';
            this.drawDraft();

            if (pan) {
                map.fitBounds(draftCircle.getBounds(), { padding: [24, 24] });
            }
        },

        drawDraft() {
            const center = [this.draft.lat, this.draft.lng];

            if (draftCircle) {
                draftCircle.setLatLng(center).setRadius(this.draft.radius * 1000);
                draftMarker.setLatLng(center);

                return;
            }

            draftCircle = L.circle(center, { ...DRAFT_STYLE, radius: this.draft.radius * 1000, interactive: false }).addTo(map);
            draftMarker = L.marker(center, { draggable: false }).addTo(map);
        },

        // ** Suwak promienia nowego obszaru
        onDraftRadius() {
            if (draftCircle) {
                draftCircle.setRadius(this.draft.radius * 1000);
            }
        },

        clearDraft() {
            if (draftCircle) {
                draftCircle.remove();
                draftMarker.remove();
                draftCircle = draftMarker = null;
            }

            this.draft.lat = this.draft.lng = null;
        },

        // ** Adres -> punkt (ten sam endpoint co w formularzu ogłoszenia, Nominatim po stronie serwera)
        async search() {
            const q = this.query.trim();

            if (q.length < 3 || this.searching) {
                return;
            }

            this.searching = true;
            this.searchError = '';

            try {
                const response = await fetch(`${config.searchUrl}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
                const data = response.ok ? await response.json() : { found: false };

                if (data.found) {
                    this.setDraft(Number(data.lat), Number(data.lng));
                } else {
                    this.searchError = 'Nie znaleźliśmy takiego miejsca. Spróbuj inaczej albo kliknij w mapę.';
                }
            } catch {
                this.searchError = 'Wyszukiwanie chwilowo nie działa. Kliknij w mapę, żeby wskazać miejsce.';
            } finally {
                this.searching = false;
            }
        },

        locate() {
            if (!navigator.geolocation) {
                this.searchError = 'Ta przeglądarka nie udostępnia lokalizacji. Kliknij w mapę.';

                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => this.setDraft(position.coords.latitude, position.coords.longitude),
                () => { this.searchError = 'Nie udało się pobrać lokalizacji. Kliknij w mapę.'; },
                { timeout: 10000 },
            );
        },

        // ---------------------------
        // Zapisane obszary
        // ---------------------------
        focus(id) {
            this.activeId = id;

            Object.entries(circles).forEach(([key, circle]) => circle.setStyle(Number(key) === id ? ACTIVE_STYLE : SAVED_STYLE));
            map.fitBounds(circles[id].getBounds(), { padding: [24, 24] });
        },

        // ** Suwak promienia zapisanego obszaru - podgląd na żywo
        onAreaRadius(area) {
            circles[area.id]?.setRadius(area.radius_km * 1000);
        },

        fitAll() {
            const layers = Object.values(circles);

            if (draftCircle) {
                layers.push(draftCircle);
            }

            if (layers.length) {
                map.fitBounds(L.featureGroup(layers).getBounds(), { padding: [24, 24] });
            }
        },
    };
}
