{{-- ---------------------------
     Pole "kadr miniaturki" jednego zdjęcia (App\Filament\Actions\PhotoFocusAction).
     Stan pola: [x, y] w ułamkach 0..1 (punkt moderatora) albo null (kadr automatyczny).
     Po lewej zdjęcie do klikania, po prawej PRAWDZIWA karta ogłoszenia z tym zdjęciem
     (ramka z route admin.card-preview, style strony). Każda zmiana punktu idzie do ramki
     przez postMessage - karta zmienia kadr na żywo, bez przeładowania.
     Pozycję liczymy tak samo jak Photo::focusPosition dla punktu: środek kwadratu
     na punkcie, dosunięty do krawędzi, gdy punkt jest blisko brzegu.
     Style inline - panel Filament nie ma klas Tailwinda z frontu strony.
     --------------------------- --}}
<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.$entangle(@js($getStatePath())),
            ratio: 1,
            autoPosition: @js($autoPosition),
            setFromClick(event) {
                const rect = event.currentTarget.getBoundingClientRect();
                const x = (event.clientX - rect.left) / rect.width;
                const y = (event.clientY - rect.top) / rect.height;
                this.state = [Math.round(x * 10000) / 10000, Math.round(y * 10000) / 10000];
            },
            onLoad(event) {
                this.ratio = event.target.naturalWidth / event.target.naturalHeight || 1;
            },
            hasPoint() {
                return Array.isArray(this.state) && this.state.length === 2;
            },
            // ** Pozycja w jednej osi dla punktu p przy widocznym ułamku v (zdjęcie karty jest kwadratowe)
            axis(p, v) {
                if (v >= 0.999) return 50;
                const offset = Math.min(Math.max(p - v / 2, 0), 1 - v);
                return Math.round(offset / (1 - v) * 1000) / 10;
            },
            previewPosition() {
                if (!this.hasPoint()) return this.autoPosition;
                const vx = this.ratio > 1 ? 1 / this.ratio : 1;
                const vy = this.ratio < 1 ? this.ratio : 1;
                return this.axis(this.state[0], vx) + '% ' + this.axis(this.state[1], vy) + '%';
            },
            // ** Wysyłka kadru do karty w ramce (ta sama domena - patrz admin/card-preview.blade.php)
            sendToCard() {
                this.$refs.card?.contentWindow?.postMessage(
                    { type: 'noskiem-card-focus', position: this.previewPosition() },
                    window.location.origin,
                );
            },
        }"
        x-effect="state; ratio; sendToCard()"
        style="display: flex; gap: 1.5rem; align-items: flex-start; flex-wrap: wrap; padding: .75rem 0; border-bottom: 1px solid rgba(128,128,128,.2);"
    >
        {{-- ** Zdjęcie w całości - kliknięcie ustawia punkt --}}
        <div style="display: flex; flex-direction: column; gap: .5rem;">
            <span style="font-size: .8125rem; opacity: .7;">Kliknij, gdzie ma być środek miniaturki</span>
            <div style="position: relative; display: inline-block; line-height: 0; cursor: crosshair;" @click="setFromClick($event)">
                <img src="{{ $url }}" alt="" @load="onLoad($event)" style="max-height: 420px; max-width: 100%; border-radius: .5rem; display: block;">
                <span
                    x-show="hasPoint()"
                    x-cloak
                    :style="hasPoint() ? `left: ${state[0] * 100}%; top: ${state[1] * 100}%` : ''"
                    style="position: absolute; width: 18px; height: 18px; margin: -9px 0 0 -9px; border-radius: 9999px; border: 3px solid #fff; background: #f59e0b; box-shadow: 0 0 0 1px rgba(0,0,0,.4); pointer-events: none;"
                ></span>
            </div>
        </div>

        {{-- ** Prawdziwa karta ogłoszenia (desktop, 292 px) + stan kadru --}}
        <div style="display: flex; flex-direction: column; gap: .5rem; font-size: .8125rem;">
            <span style="opacity: .7;">Tak będzie wyglądać karta na stronie</span>
            <iframe
                x-ref="card"
                src="{{ $previewUrl }}"
                title="Podgląd karty ogłoszenia"
                @load="sendToCard()"
                style="width: 308px; height: 430px; border: 0; border-radius: .75rem; background: #fff;"
            ></iframe>

            <span x-show="hasPoint()" x-cloak style="color: #d97706; font-weight: 600;">Kadr ustawiony ręcznie</span>
            <span x-show="!hasPoint()" style="opacity: .7;">
                @if ($detected === 'auto')
                    Kadr automatyczny (wykryte zwierzę)
                @elseif ($detected === 'none')
                    Kadr domyślny (nie wykryto zwierzęcia)
                @else
                    Kadr domyślny (detekcja jeszcze nie ruszyła)
                @endif
            </span>

            <button
                type="button"
                x-show="hasPoint()"
                x-cloak
                @click="state = null"
                style="text-align: left; text-decoration: underline; opacity: .8; cursor: pointer; background: none; border: 0; padding: 0; color: inherit;"
            >Przywróć automatyczny</button>
        </div>
    </div>
</x-dynamic-component>
