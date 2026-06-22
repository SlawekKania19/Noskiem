@extends('layouts.app')

@section('content')

<a href="{{ url('/moderation/pending') }}" class="text-blue-600 hover:underline">
    ← Powrót do listy
</a>

<h1 class="text-3xl font-bold mt-4 mb-6">
    Szczegóły zgłoszenia #{{ $edit->id }}
</h1>

<div class="bg-white p-6 rounded shadow space-y-6">

    {{-- Podstawowe informacje --}}
    <div>
        <h2 class="text-xl font-semibold mb-2">Podstawowe dane</h2>

        <p><strong>Tytuł:</strong> {{ $edit->title }}</p>
        <p><strong>Status:</strong> {{ $edit->status }}</p>
        <p><strong>Opis:</strong> {{ $edit->description }}</p>
        <p><strong>Imię zwierzęcia:</strong> {{ $edit->animal_name }}</p>
        <p><strong>Znaki szczególne:</strong> {{ $edit->ident_marks ?? '—' }}</p>
        <p><strong>Chip:</strong> {{ $edit->chip_present ? 'Tak' : 'Nie' }}</p>
        <p><strong>Numer chipa:</strong> {{ $edit->chip_number ?? '—' }}</p>
    </div>

    {{-- Lokalizacja --}}
    <div>
        <h2 class="text-xl font-semibold mb-2">Lokalizacja</h2>

        <p><strong>Województwo:</strong> {{ $edit->voivodeship->name }}</p>
        <p><strong>Miasto:</strong> {{ $edit->city->name }}</p>
        <p><strong>Adres / opis:</strong> {{ $edit->location_text }}</p>
        <p><strong>Współrzędne:</strong> {{ $edit->latitude }}, {{ $edit->longitude }}</p>
    </div>

    {{-- Kontakt --}}
    <div>
        <h2 class="text-xl font-semibold mb-2">Kontakt</h2>

        <p><strong>Imię:</strong> {{ $edit->contact_name }}</p>
        <p><strong>Email:</strong> {{ $edit->contact_email }}</p>
        <p><strong>Telefon:</strong> {{ $edit->contact_phone ?? '—' }}</p>
    </div>

    {{-- Zdjęcia --}}
    <div>
        <h2 class="text-xl font-semibold mb-2">Zdjęcia</h2>

        @if($edit->photos->isEmpty())
            <p class="text-gray-600">Brak zdjęć.</p>
        @else
            <div class="grid grid-cols-3 gap-4">
                @foreach($edit->photos as $photo)
                    <img src="{{ asset('storage/' . $photo->path) }}"
                         class="w-full h-40 object-cover rounded border">
                @endforeach
            </div>
        @endif
    </div>

    {{-- Diff jeśli to edycja istniejącego ogłoszenia --}}
    @if($edit->animal_id)
        <div>
            <h2 class="text-xl font-semibold mb-2">Różnice względem oryginału</h2>

            <a href="{{ url('/moderation/' . $edit->id . '/diff') }}"
               class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-800">
                Pokaż diff (JSON)
            </a>

            <p class="text-gray-500 mt-2 text-sm">
                W kolejnych krokach możemy zrobić ładny wizualny diff.
            </p>
        </div>
    @endif

    {{-- Akcje moderacyjne --}}
    <div class="flex space-x-4 mt-6">

        {{-- Zatwierdź --}}
        <form method="POST" action="{{ url('/moderation/' . $edit->id . '/approve') }}">
            @csrf
            <button class="px-6 py-3 bg-green-600 text-white rounded hover:bg-green-700">
                Zatwierdź
            </button>
        </form>

        {{-- Odrzuć --}}
        <form method="POST" action="{{ url('/moderation/' . $edit->id . '/reject') }}">
            @csrf
            <input type="hidden" name="reason" value="Odrzucono przez moderatora.">
            <button class="px-6 py-3 bg-red-600 text-white rounded hover:bg-red-700">
                Odrzuć
            </button>
        </form>

    </div>

</div>

@endsection
