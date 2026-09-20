// ---------------------------
// Karuzela paska partnerów (nad stopką).
//
// Zasada działania: w torze renderujemy o jeden kafelek więcej niż mamy slotów.
// Co `interval` sekund przesuwamy tor o szerokość jednego kafelka (animacja CSS),
// a po zakończeniu animacji rotujemy kolejkę (pierwszy element na koniec) i
// zerujemy przesunięcie bez animacji — dzięki temu pętla jest bezszwowa i nie
// trzeba klonować DOM-u ani liczyć pozycji w pikselach.
//
// Strzałka "wstecz" robi to samo w lustrze: najpierw ostatni element kolejki idzie
// na początek, a tor bez animacji staje o jeden slot w lewo (dla oka nic się nie
// zmienia), potem z animacją wraca na zero.
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
        offset: 0,          // Pozycja toru w slotach (0 = spoczynek, 1 = przesunięty o jeden w lewo)
        animating: false,   // Czy zmiana pozycji ma być animowana
        direction: null,    // 'next' / 'prev' w trakcie przesuwu, null w spoczynku
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
                this.offset = 0;
                this.animating = false;
                this.direction = null;
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
                transform: `translateX(-${this.offset * 100 / this.slots}%)`,
                transitionDuration: this.animating ? `${this.duration}ms` : '0ms',
            };
        },

        itemStyle() {
            return { flex: `0 0 ${100 / this.slots}%` };
        },

        // Kolorowy kafelek: ten, który stoi (lub właśnie wjeżdża) na środek widocznego okna
        isHighlighted(index) {
            if (! this.canAnimate) {
                return true;
            }

            return index === Math.floor(this.slots / 2) + this.offset;
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
            if (! this.canAnimate || this.direction) {
                return;
            }

            this.direction = 'next';
            this.animating = true;
            this.offset = 1;
            this.armFallback();
        },

        prev() {
            if (! this.canAnimate || this.direction) {
                return;
            }

            this.direction = 'prev';

            // ** Krok 1 (bez animacji): ostatni kafelek na początek kolejki, tor o slot w lewo.
            // Nowy kafelek stoi poza lewą krawędzią, więc widok się nie zmienia
            this.queue.unshift(this.queue.pop());
            this.animating = false;
            this.offset = 1;

            // ** Krok 2 (po wyrenderowaniu kroku 1): tor z animacją wraca na zero.
            // Odczyt offsetWidth wymusza przeliczenie stylów — bez tego przeglądarka
            // scaliłaby oba kroki i przesunięcia by nie było
            this.$nextTick(() => {
                void this.$root.offsetWidth;
                this.animating = true;
                this.offset = 0;
                this.armFallback();
            });
        },

        // ** Bezpiecznik: w nieaktywnej karcie (albo gdy pasek jest ukryty) przeglądarka
        // nie wyśle transitionend i karuzela stanęłaby na zawsze — onSlideEnd i tak
        // sprawdza, czy przesuw jest w toku, więc podwójne wywołanie nic nie psuje
        armFallback() {
            setTimeout(() => this.onSlideEnd(), DURATION + 100);
        },

        // Po animacji "next": pierwszy kafelek ląduje na końcu kolejki, a tor wraca na zero
        // bez animacji — dla oka nic się nie dzieje, bo treść kafelków przesuwa się
        // o dokładnie tyle, o ile cofamy transformację. Po "prev" tor już stoi na zerze.
        onSlideEnd() {
            if (! this.direction) {
                return;
            }

            if (this.direction === 'next') {
                this.queue.push(this.queue.shift());
                this.offset = 0;
            }

            this.animating = false;
            this.direction = null;
        },

        // ** Kliknięcie strzałki: przesuw od razu i licznik od nowa, żeby autoprzesuw
        // nie odpalił się chwilę po ręcznym (w strefie hover start() i tak nic nie robi)
        clickNext() {
            this.next();
            this.start();
        },

        clickPrev() {
            this.prev();
            this.start();
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
