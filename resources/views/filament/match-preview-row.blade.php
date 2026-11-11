{{-- Jeden kandydat w panelu "Podobne (AI)" (filament/match-preview.blade.php).
     $below - kandydat poniżej progu, widoczny tylko po zaznaczeniu przełącznika --}}
@php
    $levelLabel = ['strong' => 'bardzo podobne', 'possible' => 'możliwe', 'below' => 'poniżej progu'];
    $levelColor = ['strong' => 'success', 'possible' => 'warning', 'below' => 'gray'];
    $statusLabel = ['lost' => 'Zaginiony', 'found' => 'Znaleziony'];
@endphp

<div class="nm-row {{ $below ? 'nm-below' : '' }}" @if ($below) x-show="showBelow" x-cloak @endif>
    <div class="nm-pos">{{ $row['position'] ?? '–' }}</div>

    @if ($row['photo_url'])
        <img class="nm-thumb" src="{{ $row['photo_url'] }}" alt="" loading="lazy" style="object-position: {{ $row['photo_position'] ?? '50% 50%' }}">
    @else
        <div class="nm-thumb"></div>
    @endif

    <div class="nm-main">
        <div class="nm-title">
            @if ($row['url'])
                <x-filament::link :href="$row['url']" target="_blank">{{ $row['title'] }}</x-filament::link>
            @else
                {{ $row['title'] }}
            @endif
        </div>
        <div class="nm-meta">
            @isset($row['status'])
                <span>{{ $statusLabel[$row['status']] ?? $row['status'] }}</span>
            @endisset
            @if ($row['location'])
                <span>{{ $row['location'] }}</span>
            @endif
            @if ($row['distance_km'] !== null)
                <span>{{ number_format($row['distance_km'], 1, ',', '') }} km</span>
            @endif
            @if (array_key_exists('breed_match', $row) && $row['breed_match'] !== null)
                <span>rasa: {{ $row['breed_match'] ? 'zgodna' : 'inna' }}</span>
            @endif
            @if (array_key_exists('color_overlap', $row) && $row['color_overlap'] !== null)
                <span>kolory: {{ round($row['color_overlap'] * 100) }}%</span>
            @endif
            @if (($row['clicks'] ?? 0) > 0)
                <span>kliknięte w mailu: {{ $row['clicks'] }}×</span>
            @endif
        </div>
    </div>

    <div class="nm-score">
        <strong>{{ number_format($row['score'], 3, ',', '') }}</strong>
        <x-filament::badge :color="$levelColor[$row['level']] ?? 'gray'" size="sm">
            {{ $levelLabel[$row['level']] ?? $row['level'] }}
        </x-filament::badge>
    </div>
</div>
