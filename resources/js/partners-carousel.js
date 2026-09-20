// ---------------------------
// Karuzela paska partnerów (nad stopką).
//
// Zasada działania: w torze renderujemy o jeden kafelek więcej niż mamy slotów.
// Co `interval` sekund przesuwamy tor o szerokość jednego kafelka (animacja CSS),
// a po zakończeniu animacji rotujemy kolejkę (pierwszy element na koniec) i
// zerujemy przesunięcie bez animacji — dzięki temu pętla jest bezszwowa i nie
// trzeba klonować DOM-u ani liczyć pozycji w pikselach.
//
// Kolorowy jest zawsze kafelek na środku; w trakcie przesuwu podświetlenie
// przechodzi na kafelek wjeżdżający na środek, więc kolor "jedzie" razem z nim.
// ---------------------------

const DURATION = 600;

export default function partnersCarousel({ items = [], desktopSlots = 5, interval = 4 } = {}) {
    return {
        items,
        desktopSlots,
        interval: Math.max(2, interval) * 1000,

        queue: [],
        slots: 1,
        shifted: false,
        timer: null,
        paused: false,
        duration: DURATION,

        init() {
            this.applySlots();

            // ** Zmiana szerokości okna zmienia liczbę slotów (mobile/tablet/desktop)
            this.onResize = () => this.applySlots();
            window.addEventListener('resize', this.onResize);

            // ** Karuzela w nieaktywnej karcie tylko marnuje cykle; przy powrocie rusza od nowa
            this.onVisibility = () => (document.hidden ? this.stop() : this.start());
            document.addEventListener('visibilitychange', this.onVisibility);

            this.start();
        },

        destroy() {
            this.stop();
            window.removeEventListener('resize', this.onResize);
            document.removeEventListener('visibilitychange', this.onVisibility);
        },

        // ** Liczba widocznych banerów: telefon 1, tablet 3, desktop wg ustawienia z panelu.
        // Nieparzysta, bo kolorowy jest ten na środku.
        applySlots() {
            const width = window.innerWidth;
            const slots = width >= 1024 ? this.desktopSlots : (width >= 640 ? 3 : 1);

            if (slots !== this.slots) {
                this.slots = slots;
                this.shifted = false;
            }

            this.queue = this.buildQueue();
            this.start();
        },

        // Kolejka musi mieć co najmniej slots + 1 pozycji, żeby tor miał czym wypełnić
        // kafelek wjeżdżający z prawej — przy małej liczbie partnerów powielamy listę.
        //
        // Każdy wpis dostaje stały, unikalny `key` (nr kopii + nr partnera) — x-for
        // kluczuje po nim, więc przy rotacji Alpine przesuwa istniejące elementy DOM
        // zamiast podmieniać w nich dane. Bez tego kafelek na środku po resecie toru
        // tracił na chwilę kolor (klasy grayscale/opacity mają 500 ms przejścia).
        buildQueue() {
            const withKeys = (copy) => this.items.map((item, index) => ({ key: `${copy}-${index}`, ...item }));

            if (! this.canAnimate) {
                return withKeys(0);
            }

            const queue = [];

            for (let copy = 0; queue.length < this.slots + 1; copy++) {
                queue.push(...withKeys(copy));
            }

            return queue;
        },

        // ** Mniej partnerów niż slotów = nie ma czego przewijać: pokazujemy statyczny,
        // wyśrodkowany rząd (wtedy wszystkie banery są kolorowe — patrz isHighlighted)
        get canAnimate() {
            return this.items.length > this.slots && ! this.prefersReducedMotion;
        },

        get prefersReducedMotion() {
            return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        },

        get visible() {
            return this.canAnimate ? this.queue.slice(0, this.slots + 1) : this.queue;
        },

        get trackStyle() {
            return {
                transform: this.shifted ? `translateX(-${100 / this.slots}%)` : 'translateX(0)',
                transitionDuration: this.shifted ? `${this.duration}ms` : '0ms',
            };
        },

        itemStyle() {
            return { flex: `0 0 ${100 / this.slots}%` };
        },

        // Kolorowy kafelek: środkowy, a w trakcie przesuwu ten, który właśnie na środek wjeżdża
        isHighlighted(index) {
            if (! this.canAnimate) {
                return true;
            }

            const center = Math.floor(this.slots / 2);

            return index === (this.shifted ? center + 1 : center);
        },

        start() {
            this.stop();

            if (! this.canAnimate || this.paused) {
                return;
            }

            this.timer = setInterval(() => this.next(), this.interval);
        },

        stop() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },

        next() {
            if (! this.canAnimate || this.shifted) {
                return;
            }

            this.shifted = true;

            // ** Bezpiecznik: w nieaktywnej karcie (albo gdy pasek jest ukryty) przeglądarka
            // nie wyśle transitionend i karuzela stanęłaby na zawsze — onSlideEnd i tak
            // sprawdza, czy przesuw jest w toku, więc podwójne wywołanie nic nie psuje
            setTimeout(() => this.onSlideEnd(), DURATION + 100);
        },

        // Po animacji: pierwszy kafelek ląduje na końcu kolejki, a tor wraca na zero
        // bez animacji — dla oka nic się nie dzieje, bo treść kafelków przesuwa się
        // o dokładnie tyle, o ile cofamy transformację
        onSlideEnd() {
            if (! this.shifted) {
                return;
            }

            this.queue.push(this.queue.shift());
            this.shifted = false;
        },

        pause() {
            this.paused = true;
            this.stop();
        },

        resume() {
            this.paused = false;
            this.start();
        },
    };
}
