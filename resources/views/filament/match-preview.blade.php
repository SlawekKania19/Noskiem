{{-- ---------------------------
     Panel "Podobne (AI)" (App\Filament\Actions\MatchPreviewAction).
     Panel nie ma własnego motywu CSS — tylko komponenty Filamenta + lokalne style
     na zmiennych kolorów panelu (działają też w trybie ciemnym).
     --------------------------- --}}
@php
    $live = $data['live'];
    $request = $data['request'];

    $requestLabel = [
        'queued' => 'czeka (na zatwierdzenie albo wektory zdjęć)',
        'notified' => 'mail wysłany',
        'no_matches' => 'brak wyników ponad progiem — mail nie poszedł',
        'skipped' => 'pominięte (zgłoszenie odrzucone)',
        'expired' => 'wygasło bez maila',
        'failed' => 'błąd — mail nie poszedł',
    ];
    $shownCount = collect($live['rows'])->where('shown', true)->count();
    $belowCount = count($live['rows']) - $shownCount;
@endphp

<style>
    .nm-box { display: flex; flex-direction: column; gap: 1.5rem; }
    .nm-muted { color: var(--gray-500); font-size: .875rem; }
    .nm-info { padding: .75rem 1rem; border-radius: .5rem; background: var(--gray-50); font-size: .875rem; }
    .dark .nm-info { background: color-mix(in oklab, var(--gray-800) 60%, transparent); }
    .nm-list { display: flex; flex-direction: column; gap: .5rem; }
    .nm-row { display: flex; gap: .75rem; align-items: center; padding: .5rem; border-radius: .5rem; border: 1px solid var(--gray-200); }
    .dark .nm-row { border-color: var(--gray-700); }
    .nm-row.nm-below { opacity: .65; }
    .nm-pos { width: 1.75rem; text-align: center; font-weight: 600; color: var(--gray-500); flex-shrink: 0; }
    .nm-thumb { width: 64px; height: 64px; object-fit: cover; border-radius: .375rem; flex-shrink: 0; background: var(--gray-100); }
    .dark .nm-thumb { background: var(--gray-800); }
    .nm-main { flex: 1; min-width: 0; }
    .nm-title { font-weight: 600; font-size: .875rem; }
    .nm-meta { display: flex; flex-wrap: wrap; gap: .25rem .75rem; font-size: .75rem; color: var(--gray-500); margin-top: .125rem; }
    .nm-score { text-align: right; flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-end; gap: .25rem; }
    .nm-score strong { font-size: 1.125rem; font-variant-numeric: tabular-nums; }
    .nm-h { font-weight: 600; font-size: 1rem; margin-bottom: .5rem; }
    .nm-toggle { display: inline-flex; gap: .5rem; align-items: center; font-size: .875rem; cursor: pointer; }
</style>

<div class="nm-box" x-data="{ showBelow: false }">

    {{-- ** Co oznacza ten podgląd --}}
    <div class="nm-info">
        @if ($data['is_pending'])
            <strong>Podgląd przed zatwierdzeniem:</strong> tak wyglądałby mail do autora, gdyby zgłoszenie
            zatwierdzić teraz. Mail idzie dopiero po zatwierdzeniu — do tego czasu lista może się zmienić,
            jeśli pojawią się nowe ogłoszenia.
        @else
            <strong>Wyniki na teraz</strong> — te same filtry i progi co przy mailu do autora.
            Wyszukiwanie niczego nie zapisuje i nie wysyła.
        @endif
    </div>

    {{-- ---------------------------
         Wyniki na żywo
         --------------------------- --}}
    <section>
        <div class="nm-h">Podobne ogłoszenia</div>

        @switch($live['status'])
            @case('error')
                <x-filament::badge color="danger">Błąd wyszukiwania</x-filament::badge>
                <p class="nm-muted" style="margin-top:.5rem">{{ \Illuminate\Support\Str::limit($live['error'], 300) }}</p>
                @break

            @case('no_photos')
                <p class="nm-muted">Brak zdjęć — nie ma czego porównywać.</p>
                @break

            @case('pending')
                <p class="nm-muted">
                    Zdjęcia czekają w kolejce do przeliczenia (zwykle do minuty; dla zgłoszeń dopiero
                    po potwierdzeniu e-maila przez autora). Otwórz ponownie za chwilę.
                </p>
                @break

            @case('no_embedding')
                <p class="nm-muted">Żadnego zdjęcia nie udało się przeliczyć (uszkodzone pliki?). Zobacz „Ponów błędne zdjęcia” w zakładce Dopasowanie AI.</p>
                @break

            @default
                <p class="nm-muted" style="margin-bottom:.75rem">
                    Kandydaci po filtrach: <strong>{{ $live['n_candidates'] }}</strong>,
                    porównani: <strong>{{ $live['n_compared'] }}</strong>
                    @if ($live['n_pending_embedding'] > 0)
                        · bez przeliczonych zdjęć: <strong>{{ $live['n_pending_embedding'] }}</strong>
                    @endif
                    · progi: {{ $live['params']['threshold_possible'] }} / {{ $live['params']['threshold_strong'] }}
                </p>

                @if ($live['n_candidates'] === 0)
                    <p class="nm-muted">
                        Żadne ogłoszenie nie spełnia filtrów: przeciwny status, ten sam gatunek,
                        promień {{ $live['params']['filters']['radius_km'] ?: '∞' }} km, inny e-mail autora.
                        Filtry można zmienić w zakładce Dopasowanie AI.
                    </p>
                @else
                    @if ($shownCount === 0)
                        <p class="nm-muted" style="margin-bottom:.75rem">
                            Nic nie przekracza progu — {{ $data['is_pending'] ? 'mail nie zostałby wysłany' : 'autor nie dostałby maila' }}.
                        </p>
                    @endif

                    @if ($belowCount > 0)
                        <label class="nm-toggle" style="margin-bottom:.75rem">
                            <input type="checkbox" x-model="showBelow">
                            Pokaż też poniżej progu ({{ $belowCount }})
                        </label>
                    @endif

                    <div class="nm-list">
                        @foreach ($live['rows'] as $row)
                            @include('filament.match-preview-row', ['row' => $row, 'below' => ! $row['shown']])
                        @endforeach
                    </div>
                @endif
        @endswitch
    </section>

    {{-- ---------------------------
         Historia: co faktycznie poszło do autora
         --------------------------- --}}
    <section>
        <div class="nm-h">Mail do autora</div>

        @if ($request)
            <p class="nm-muted" style="margin-bottom:.75rem">
                Status: <strong>{{ $requestLabel[$request->status] ?? $request->status }}</strong>
                @if ($request->processed_at)
                    · {{ $request->processed_at->format('d.m.Y H:i') }}
                @endif
                @if ($request->last_error)
                    · ostatni błąd: {{ \Illuminate\Support\Str::limit($request->last_error, 120) }}
                @endif
            </p>
        @else
            <p class="nm-muted" style="margin-bottom:.75rem">Brak zlecenia maila (ogłoszenie dodane bez zdjęć, przed włączeniem modułu albo to ogłoszenie nie powstało z nowego zgłoszenia).</p>
        @endif

        @foreach ($data['history'] as $search)
            <p class="nm-muted" style="margin:.75rem 0 .5rem">
                {{ $search['created_at']?->format('d.m.Y H:i') }}
                · {{ $search['channel'] === 'notify' ? 'mail' : $search['channel'] }}
                · pokazane: {{ $search['n_shown'] }}
                · model {{ $search['model_version'] }}
            </p>

            @if ($search['rows'] === [])
                <p class="nm-muted">Nic ponad progiem.</p>
            @else
                <div class="nm-list">
                    @foreach ($search['rows'] as $row)
                        @include('filament.match-preview-row', ['row' => $row + ['shown' => true], 'below' => false])
                    @endforeach
                </div>
            @endif
        @endforeach
    </section>
</div>
