{{-- ---------------------------
     Obszary działania agenta - okno z zakładki "Agenci" (App\Filament\Resources\AgentResource).
     Tylko podgląd; obszarami zarządza sam agent w swoim panelu na stronie.
     --------------------------- --}}
@if ($areas->isEmpty())
    <p style="color: var(--gray-500); font-size: .875rem;">Agent nie dodał jeszcze żadnego obszaru - nie dostaje powiadomień.</p>
@else
    <div style="display: flex; flex-direction: column; gap: .5rem;">
        @foreach ($areas as $i => $area)
            <div style="display: flex; justify-content: space-between; gap: 1rem; align-items: center; padding: .625rem .75rem; border: 1px solid var(--gray-200); border-radius: .5rem; font-size: .875rem;">
                <div>
                    <strong>{{ $area->name ?: 'Obszar '.($i + 1) }}</strong>
                    <div style="color: var(--gray-500); font-size: .75rem;">
                        {{ number_format($area->latitude, 5, '.', '') }}, {{ number_format($area->longitude, 5, '.', '') }}
                        · dodany {{ $area->created_at?->format('d.m.Y') }}
                    </div>
                </div>
                <div style="display: flex; gap: .75rem; align-items: center; flex-shrink: 0;">
                    <x-filament::badge color="gray">{{ $area->radius_km }} km</x-filament::badge>
                    <x-filament::link
                        href="https://www.openstreetmap.org/?mlat={{ $area->latitude }}&mlon={{ $area->longitude }}#map=12/{{ $area->latitude }}/{{ $area->longitude }}"
                        target="_blank"
                    >
                        Mapa
                    </x-filament::link>
                </div>
            </div>
        @endforeach
    </div>
@endif
