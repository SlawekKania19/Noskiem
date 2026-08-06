// ---------------------------
// Autouzupełnianie miejscowości (rejestr SIMC) zależne od wybranego województwa.
// Zapytanie do /cities/search wysyłane dopiero od 3 znaków, z debounce, żeby nie
// odpytywać serwera przy każdym naciśnięciu klawisza.
// ---------------------------
export default function cityPicker({ voivodeshipId = '', cityId = '', cityName = '' } = {}) {
    return {
        voivodeshipId: voivodeshipId ? String(voivodeshipId) : '',
        cityId: cityId ? String(cityId) : '',
        query: cityName ?? '',
        results: [],
        open: false,
        loading: false,
        resolving: false,
        debounceTimer: null,

        // ** Nasłuch na wynik odwrotnego geokodowania pinezki z mapy (patrz animal-map.js).
        // Zdarzenie globalne, bo mapa i to pole żyją w osobnych zakresach Alpine.
        // Pola blokujemy na czas trwania zapytania, żeby użytkownik nie edytował ich
        // w momencie, w którym zaraz zostaną nadpisane wynikiem geokodowania.
        init() {
            window.addEventListener('location-resolving', () => { this.resolving = true; });
            window.addEventListener('location-resolved-end', () => { this.resolving = false; });
            window.addEventListener('location-resolved', (event) => this.applyResolvedLocation(event.detail));
        },

        applyResolvedLocation(detail) {
            if (!detail?.voivodeship_id) {
                return;
            }

            this.voivodeshipId = String(detail.voivodeship_id);
            this.cityId = detail.city_id ? String(detail.city_id) : '';
            this.query = detail.city_name ?? '';
            this.results = [];
            this.open = false;
        },

        // ** Zmiana województwa czyści wybraną miejscowość — lista wyników jej dotyczyła
        onVoivodeshipChange() {
            this.cityId = '';
            this.query = '';
            this.results = [];
            this.open = false;
        },

        // ** Ręczna edycja tekstu unieważnia wcześniejszy wybór, dopóki użytkownik nie kliknie podpowiedzi
        onQueryInput() {
            this.cityId = '';
            clearTimeout(this.debounceTimer);

            if (!this.voivodeshipId || this.query.trim().length < 3) {
                this.results = [];
                this.open = false;
                return;
            }

            this.debounceTimer = setTimeout(() => this.fetchResults(), 250);
        },

        async fetchResults() {
            this.loading = true;

            try {
                const params = new URLSearchParams({
                    voivodeship_id: this.voivodeshipId,
                    q: this.query.trim(),
                });

                const response = await fetch(`/cities/search?${params}`, {
                    headers: { Accept: 'application/json' },
                });
                this.results = response.ok ? await response.json() : [];
                this.open = this.results.length > 0;
            } catch {
                this.results = [];
                this.open = false;
            } finally {
                this.loading = false;
            }
        },

        select(city) {
            this.cityId = String(city.id);
            this.query = city.name_pl;
            this.results = [];
            this.open = false;
        },
    };
}
