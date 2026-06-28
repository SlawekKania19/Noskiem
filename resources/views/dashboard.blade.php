@extends('layouts.app')

@section('content')
<div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8">

    <h1 class="text-2xl font-bold text-gray-800 mb-6">Zgłoszenia oczekujące na moderację</h1>

    @if($edits->isEmpty())
        <p class="text-gray-600">Brak zgłoszeń do moderacji.</p>
    @else
        <div class="space-y-4">
            @foreach($edits as $edit)
                <div class="bg-white p-4 rounded shadow flex items-center justify-between">

                    <div>
                        <h2 class="text-xl font-semibold">{{ $edit->title }}</h2>
                        <p class="text-gray-600">
                            {{ $edit->species?->name_pl }} •
                            {{ $edit->breed?->breed_pl }} •
                            {{ $edit->city?->city_pl }}
                        </p>
                        <p class="text-sm text-gray-500">
                            Dodano: {{ $edit->created_at->format('Y-m-d H:i') }}
                        </p>
                    </div>

                    <div class="flex items-center space-x-3">
                        @if($edit->photos->first())
                            <img src="{{ asset('storage/' . $edit->photos->first()->path) }}"
                                 class="w-20 h-20 object-cover rounded border">
                        @else
                            <div class="w-20 h-20 bg-gray-200 rounded flex items-center justify-center text-gray-500 text-sm">
                                brak zdjęć
                            </div>
                        @endif

                        <a href="{{ route('moderation.show', $edit) }}"
                           class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Otwórz
                        </a>
                    </div>

                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
