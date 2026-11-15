@extends('layouts.public')

@section('title', 'Rezygnacja z subskrypcji - Noskiem.pl')

@php
    // ** Adres częściowo zamaskowany (j***@example.com) - link mógł zostać komuś przekazany
    [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
    $maskedEmail = mb_substr($local, 0, 1).'***@'.$domain;

    $modStatusLabels = [
        'pending'  => 'Czeka na moderację',
        'approved' => 'Opublikowane',
        'rejected' => 'Odrzucone',
        'resolved' => 'Zakończone',
    ];

    // ** Miniaturka: zdjęcie główne, a gdy brak - pierwsze
    $thumb = fn ($photos) => $photos->firstWhere('is_main', true) ?? $photos->first();
@endphp

@section('content')

    {{-- ---------------------------
         Rezygnacja z subskrypcji (UnsubscribeController) = usunięcie wszystkich danych
         powiązanych z adresem e-mail. GET pokazuje, co zniknie; usunięcie dopiero po
         zaznaczeniu potwierdzenia i kliknięciu przycisku (skanery poczty otwierają linki same).
         --------------------------- --}}
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

        <h1 class="text-[28px] font-semibold text-[#283618]">Rezygnacja z subskrypcji</h1>

        @if ($deleted !== null)
            {{-- ** Po usunięciu --}}
            <p class="mt-6 text-[15px] leading-relaxed text-[#616657]">
                Gotowe - usunęliśmy z noskiem.org wszystkie dane powiązane z adresem
                <strong class="text-[#283618]">{{ $maskedEmail }}</strong> i nie wyślemy na niego więcej maili.
            </p>

            <ul class="mt-4 list-disc space-y-1 pl-5 text-[14px] text-[#616657]">
                <li>Ogłoszenia: {{ $deleted['animals'] }}</li>
                <li>Zgłoszenia czekające na moderację: {{ $deleted['submissions'] }}</li>
                <li>Zgłoszenia „widziałem": {{ $deleted['sightings'] }}</li>
                <li>Wpisy w bazie chipów: {{ $deleted['chips'] }}</li>
                <li>Wysłane wiadomości: {{ $deleted['messages'] }}</li>
                @if ($deleted['agent'] ?? 0)
                    <li>Konto Agenta noskiem: usunięte</li>
                @endif
            </ul>

            <a href="{{ route('home') }}" class="mt-8 inline-block rounded-xl border border-[#283618] px-6 py-2.5 text-[13px] font-semibold text-[#283618] transition hover:bg-[#f5f5f0]">
                Wróć na stronę główną
            </a>
        @elseif ($isEmpty)
            {{-- ** Nic nie ma pod tym adresem (np. dane już usunięte) --}}
            <p class="mt-6 text-[15px] leading-relaxed text-[#616657]">
                Nie mamy żadnych ogłoszeń ani innych danych powiązanych z adresem
                <strong class="text-[#283618]">{{ $maskedEmail }}</strong>, więc nie wyślemy na niego żadnych maili.
            </p>

            <a href="{{ route('home') }}" class="mt-8 inline-block rounded-xl border border-[#283618] px-6 py-2.5 text-[13px] font-semibold text-[#283618] transition hover:bg-[#f5f5f0]">
                Wróć na stronę główną
            </a>
        @else
            <p class="mt-6 text-[15px] leading-relaxed text-[#616657]">
                Maile wysyłamy tylko w sprawie Twoich ogłoszeń i zgłoszeń dodanych z adresu
                <strong class="text-[#283618]">{{ $maskedEmail }}</strong>. Żeby przestały przychodzić,
                musimy usunąć te dane z serwisu.
            </p>

            {{-- ** Wyraźne ostrzeżenie - usunięcie jest nieodwracalne --}}
            <div class="mt-6 flex gap-3 rounded-2xl border border-[#f0c9b0] bg-[#fdf0e8] p-4">
                {{-- Ikona ostrzeżenia (Heroicons exclamation-triangle, outline) --}}
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-[#994d0a]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <div class="text-[14px] text-[#283618]">
                    <p class="font-semibold">Rezygnacja z subskrypcji oznacza bezpowrotne usunięcie z noskiem.org wszystkich ogłoszeń i zdjęć wymienionych poniżej.</p>
                    <p class="mt-1 text-[#616657]">Nie da się ich później przywrócić. Jeśli zwierzak wciąż się nie odnalazł, ogłoszenie zniknie też z wyszukiwarki i nikt nie będzie mógł się z Tobą skontaktować.</p>
                </div>
            </div>

            {{-- ** Ogłoszenia --}}
            @if ($data['animals']->isNotEmpty())
                <h2 class="mt-10 text-[16px] font-semibold text-[#283618]">Ogłoszenia ({{ $data['animals']->count() }})</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($data['animals'] as $animal)
                        @php($photo = $thumb($animal->photos))
                        <li class="flex items-center gap-3 rounded-xl border border-[#e5e5dc] bg-white p-3">
                            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-[#dbe3d1]">
                                @if ($photo)
                                    <img src="{{ asset('storage/'.$photo->path) }}" alt="" class="h-full w-full object-cover" style="object-position: {{ $photo->objectPosition() }}" loading="lazy">
                                @else
                                    <div class="flex h-full items-center justify-center text-[20px]">🐾</div>
                                @endif
                            </div>
                            <div class="min-w-0 text-[14px]">
                                @if ($animal->mod_status === 'approved')
                                    <a href="{{ route('animals.show', $animal) }}" class="font-semibold text-[#283618] underline-offset-2 hover:underline">{{ $animal->generated_title }}</a>
                                @else
                                    <p class="font-semibold text-[#283618]">{{ $animal->generated_title }}</p>
                                @endif
                                <p class="text-[12px] text-[#8f9485]">
                                    {{ $modStatusLabels[$animal->mod_status] ?? $animal->mod_status }}
                                    · dodane {{ $animal->created_at?->format('d.m.Y') }}
                                    · zdjęcia: {{ $animal->photos->count() }}
                                </p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- ** Nowe zgłoszenia przed pierwszym zatwierdzeniem --}}
            @if ($data['submissions']->isNotEmpty())
                <h2 class="mt-8 text-[16px] font-semibold text-[#283618]">Zgłoszenia czekające na moderację ({{ $data['submissions']->count() }})</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($data['submissions'] as $submission)
                        <li class="rounded-xl border border-[#e5e5dc] bg-white p-3 text-[14px]">
                            <p class="font-semibold text-[#283618]">{{ $submission->title }}</p>
                            <p class="text-[12px] text-[#8f9485]">
                                dodane {{ $submission->created_at?->format('d.m.Y') }} · zdjęcia: {{ $submission->photos->count() }}
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- ** Zgłoszenia "widziałem" pod cudzymi ogłoszeniami --}}
            @if ($data['sightings']->isNotEmpty())
                <h2 class="mt-8 text-[16px] font-semibold text-[#283618]">Zgłoszenia „widziałem" ({{ $data['sightings']->count() }})</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($data['sightings'] as $sighting)
                        <li class="rounded-xl border border-[#e5e5dc] bg-white p-3 text-[14px]">
                            <p class="font-semibold text-[#283618]">
                                {{ $sighting->animal ? 'Do ogłoszenia: '.$sighting->animal->generated_title : 'Zgłoszenie „widziałem"' }}
                            </p>
                            <p class="text-[12px] text-[#8f9485]">
                                widziane {{ $sighting->date_seen?->format('d.m.Y') ?? '-' }}
                                @if ($sighting->location) · {{ $sighting->location }} @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- ** Baza chipów --}}
            @if ($data['chips']->isNotEmpty())
                <h2 class="mt-8 text-[16px] font-semibold text-[#283618]">Wpisy w bazie chipów ({{ $data['chips']->count() }})</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($data['chips'] as $chip)
                        <li class="rounded-xl border border-[#e5e5dc] bg-white p-3 text-[14px]">
                            <p class="font-semibold text-[#283618]">{{ $chip->animal_name ?: 'Zwierzak' }} - chip nr {{ $chip->chip_number }}</p>
                            <p class="text-[12px] text-[#8f9485]">{{ $modStatusLabels[$chip->mod_status] ?? $chip->mod_status }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif

            {{-- ** Konto Agenta noskiem pod tym samym adresem --}}
            @if ($data['agent'])
                <h2 class="mt-8 text-[16px] font-semibold text-[#283618]">Konto Agenta noskiem</h2>
                <p class="mt-2 text-[14px] text-[#616657]">
                    Usuniemy konto agenta razem z obszarami działania ({{ $data['agent']->agent_areas_count }})
                    i oznaczeniami „Szukam”. Jeśli chcesz tylko przestać dostawać prośby o pomoc,
                    wystarczy wyłączyć powiadomienia w panelu agenta.
                </p>
            @endif

            @if ($data['messagesCount'] > 0)
                <p class="mt-8 text-[14px] text-[#616657]">
                    Usuniemy też wiadomości wysłane z tego adresu do autorów ogłoszeń ({{ $data['messagesCount'] }}).
                </p>
            @endif

            {{-- ** Akcja na ten sam adres z tokenem - potwierdzenie wymagane (checkbox) --}}
            <form method="POST" action="{{ route('unsubscribe.destroy', ['token' => request()->route('token')]) }}" class="mt-10 rounded-2xl border border-[#e5e5dc] bg-[#f5f5f0] p-5">
                @csrf

                <label class="flex cursor-pointer items-start gap-3 text-[14px] text-[#283618]">
                    <input type="checkbox" name="confirm" value="1" required class="mt-0.5 h-4 w-4 shrink-0 rounded border-[#c9cbbf] text-[#994d0a] focus:ring-[#994d0a]">
                    <span>Rozumiem, że ogłoszenia, zdjęcia i pozostałe dane wymienione wyżej zostaną <strong>bezpowrotnie usunięte</strong> z noskiem.org.</span>
                </label>

                @error('confirm')
                    <p class="mt-2 text-[12px] text-[#994d0a]">{{ $message }}</p>
                @enderror

                <button
                    type="submit"
                    class="mt-5 cursor-pointer rounded-xl bg-[#994d0a] px-6 py-2.5 text-[13px] font-semibold text-white shadow-[0px_3px_10px_0px_rgba(153,77,10,0.25)] transition hover:bg-[#7f3f08] active:transform-[scale(0.98)]"
                >
                    Zrezygnuj i usuń wszystko
                </button>
            </form>

            <p class="mt-6 text-[13px] text-[#8f9485]">
                Chcesz usunąć tylko jedno ogłoszenie albo oznaczyć zwierzaka jako odnalezionego?
                Skorzystaj z linku do edycji z maila, który przyszedł po zatwierdzeniu ogłoszenia.
            </p>
        @endif
    </div>

@endsection
