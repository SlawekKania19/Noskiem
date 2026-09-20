{{-- ---------------------------
     Pasek partnerów nad stopką — karuzela logotypów.

     Widocznych jest N banerów naraz (ustawienie w panelu; na tablecie 3, na telefonie 1),
     kolorowy jest ten na środku, pozostałe czarno-białe. Co kilka sekund pasek przesuwa
     się o jeden baner — logika w resources/js/partners-carousel.js.
     --------------------------- --}}
<section
    x-data="partnersCarousel({
        items: {{ Js::from($partners) }},
        desktopSlots: {{ $visibleSlots }},
        interval: {{ $interval }},
    })"
    class="border-t border-[#e5e5dc] bg-[#f4f4ef]"
    aria-label="Partnerzy wspierający noskiem.org"
>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <p class="text-center text-[12px] font-semibold uppercase tracking-[0.12em] text-[#616657]">
            Partnerzy wspierający noskiem.org
        </p>

        {{-- ** Najechanie myszą (lub wejście tabem) zatrzymuje przesuw, żeby dało się
             spokojnie kliknąć baner. Strzałki są w tej samej strefie. --}}
        <div
            class="mt-6 flex items-center gap-2 sm:gap-4"
            @mouseenter="pause()"
            @mouseleave="resume()"
            @focusin="pause()"
            @focusout="resume()"
        >
            {{-- ** Strzałki: klikalne, ale przede wszystkim sygnał, że partnerów jest więcej
                 niż widać. Znikają, gdy nie ma czego przewijać (canAnimate = false). --}}
            <button
                type="button"
                x-show="canAnimate"
                @click="clickPrev()"
                aria-label="Poprzedni partner"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[#616657] transition hover:bg-white hover:text-[#283618] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#283618]"
            >
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12.5 15 7.5 10l5-5" />
                </svg>
            </button>

            <div class="min-w-0 flex-1 overflow-hidden">
                <div
                    class="flex ease-in-out"
                    :class="canAnimate ? 'transition-transform' : 'flex-wrap justify-center gap-y-4'"
                    :style="trackStyle"
                    @transitionend.self="onSlideEnd()"
                >
                    <template x-for="(partner, index) in visible" :key="partner.key">
                        <div class="shrink-0 px-2 sm:px-3" :style="itemStyle()">
                            {{-- Bez linku Alpine usuwa href i baner zostaje zwykłym, nieklikalnym kafelkiem.
                                 Biały box z cieniem tylko gdy nie ma logo (sama nazwa) — logo idzie "gołe" na tle paska. --}}
                            <a
                                :href="partner.url || null"
                                :target="partner.url ? '_blank' : null"
                                :rel="partner.url ? 'noopener nofollow' : null"
                                :title="partner.name"
                                class="flex h-20 items-center justify-center rounded-xl px-4 transition duration-500 hover:grayscale-0 hover:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#283618]"
                                :class="{
                                    'bg-white': ! partner.logo,
                                    'shadow-[0px_3px_10px_0px_rgba(40,54,24,0.12)]': ! partner.logo && isHighlighted(index),
                                    'grayscale-0 opacity-100': isHighlighted(index),
                                    'grayscale opacity-60': ! isHighlighted(index),
                                }"
                            >
                                <template x-if="partner.logo">
                                    <img
                                        :src="partner.logo"
                                        :alt="partner.name"
                                        loading="lazy"
                                        class="max-h-12 w-auto max-w-full object-contain"
                                    >
                                </template>
                                {{-- Brak logo — pokazujemy samą nazwę partnera --}}
                                <template x-if="! partner.logo">
                                    <span class="text-center text-[15px] font-semibold text-[#283618]" x-text="partner.name"></span>
                                </template>
                            </a>
                        </div>
                    </template>
                </div>
            </div>

            <button
                type="button"
                x-show="canAnimate"
                @click="clickNext()"
                aria-label="Następny partner"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[#616657] transition hover:bg-white hover:text-[#283618] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#283618]"
            >
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m7.5 5 5 5-5 5" />
                </svg>
            </button>
        </div>

        {{-- ** Bez JS karuzela się nie zbuduje (x-for), więc partnerzy dostają zwykłą,
             statyczną listę — sponsor ma być widoczny zawsze --}}
        <noscript>
            <ul class="mt-6 flex flex-wrap items-center justify-center gap-x-8 gap-y-4">
                @foreach ($partners as $partner)
                    <li>
                        @if ($partner['url'])
                            <a href="{{ $partner['url'] }}" target="_blank" rel="noopener nofollow" class="text-[15px] font-semibold text-[#283618] hover:underline">
                                @if ($partner['logo'])
                                    <img src="{{ $partner['logo'] }}" alt="{{ $partner['name'] }}" class="max-h-12 w-auto object-contain">
                                @else
                                    {{ $partner['name'] }}
                                @endif
                            </a>
                        @else
                            @if ($partner['logo'])
                                <img src="{{ $partner['logo'] }}" alt="{{ $partner['name'] }}" class="max-h-12 w-auto object-contain">
                            @else
                                <span class="text-[15px] font-semibold text-[#283618]">{{ $partner['name'] }}</span>
                            @endif
                        @endif
                    </li>
                @endforeach
            </ul>
        </noscript>
    </div>
</section>
