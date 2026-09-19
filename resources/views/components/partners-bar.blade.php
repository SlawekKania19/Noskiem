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
             spokojnie kliknąć baner --}}
        <div
            class="mt-6 overflow-hidden"
            @mouseenter="pause()"
            @mouseleave="resume()"
            @focusin="pause()"
            @focusout="resume()"
        >
            <div
                class="flex ease-in-out"
                :class="canAnimate ? 'transition-transform' : 'flex-wrap justify-center gap-y-4'"
                :style="trackStyle"
                @transitionend.self="onSlideEnd()"
            >
                <template x-for="(partner, index) in visible" :key="index">
                    <div class="shrink-0 px-2 sm:px-3" :style="itemStyle()">
                        {{-- Bez linku Alpine usuwa href i baner zostaje zwykłym, nieklikalnym kafelkiem --}}
                        <a
                            :href="partner.url || null"
                            :target="partner.url ? '_blank' : null"
                            :rel="partner.url ? 'noopener nofollow' : null"
                            :title="partner.name"
                            class="flex h-20 items-center justify-center rounded-xl bg-white px-4 transition duration-500 hover:grayscale-0 hover:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#283618]"
                            :class="isHighlighted(index)
                                ? 'grayscale-0 opacity-100 shadow-[0px_3px_10px_0px_rgba(40,54,24,0.12)]'
                                : 'grayscale opacity-60'"
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
