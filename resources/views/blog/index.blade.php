@extends('layouts.public')

@section('title', ($activeCategory ? $activeCategory->name.' — ' : '').'Blog — Noskiem.pl')
@section('description', $activeCategory?->description ?: 'Porady i artykuły dla właścicieli zwierząt. Jak reagować na zaginięcie, chipowanie, adopcja i pierwsze dni w domu.')

@push('head-assets')
    <link rel="alternate" type="application/rss+xml" title="Blog — Noskiem.pl" href="{{ route('blog.feed') }}">
@endpush

@section('content')

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <h1 class="text-[28px] font-semibold text-[#283618]">Blog</h1>
        <p class="mt-1 text-[14px] text-[#616657]">Porady i artykuły dla właścicieli zwierząt.</p>

        {{-- ---------------------------
             Pasek kategorii — „Wszystkie" + kategorie z co najmniej jednym wpisem
             --------------------------- --}}
        @if ($categories->isNotEmpty())
            <div class="mt-6 flex flex-wrap gap-2">
                <a
                    href="{{ route('blog.index') }}"
                    class="rounded-full px-4 py-1.5 text-[13px] font-medium transition {{ ! $activeCategory ? 'bg-[#283618] text-[#fefae0]' : 'bg-[#f4f4ef] text-[#616657] hover:bg-[#e9e9e0]' }}"
                >
                    Wszystkie
                </a>
                @foreach ($categories as $cat)
                    <a
                        href="{{ route('blog.category', $cat) }}"
                        class="rounded-full px-4 py-1.5 text-[13px] font-medium transition {{ $activeCategory && $activeCategory->is($cat) ? 'bg-[#283618] text-[#fefae0]' : 'bg-[#f4f4ef] text-[#616657] hover:bg-[#e9e9e0]' }}"
                    >
                        {{ $cat->name }}
                    </a>
                @endforeach
            </div>
        @endif

        @if ($activeCategory && $activeCategory->description)
            <p class="mt-4 max-w-2xl text-[14px] leading-relaxed text-[#616657]">{{ $activeCategory->description }}</p>
        @endif

        {{-- ---------------------------
             Siatka wpisów
             --------------------------- --}}
        <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($posts as $post)
                @include('blog.partials.card', ['post' => $post])
            @empty
                <p class="col-span-full py-10 text-center text-[14px] text-[#8f9485]">
                    Brak wpisów{{ $activeCategory ? ' w tej kategorii' : '' }}.
                </p>
            @endforelse
        </div>

        @if ($posts->hasPages())
            <div class="mt-10">
                {{ $posts->links() }}
            </div>
        @endif
    </div>

@endsection
