@extends('layouts.public')

@php
    $metaDescription = \Illuminate\Support\Str::of(strip_tags($author->bio_html))
        ->squish()
        ->limit(160)
        ->value()
        ?: ($author->headline ?: 'Autor na blogu Noskiem.pl');

    $initials = \Illuminate\Support\Str::of($author->name)
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

@section('title', $author->name.' — autor na blogu Noskiem.pl')
@section('description', $metaDescription)

@push('head-assets')
    <link rel="alternate" type="application/rss+xml" title="Blog — Noskiem.pl" href="{{ route('blog.feed') }}">
@endpush

@section('content')

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <a href="{{ route('blog.index') }}" class="text-[13px] font-medium text-[#616657] transition-colors hover:text-[#283618]">
            &larr; Wróć do bloga
        </a>

        {{-- ---------------------------
             Nagłówek profilu
             --------------------------- --}}
        <div class="mt-6 flex flex-col items-start gap-4 sm:flex-row sm:items-center">
            <span class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#dbe3d1] text-[22px] font-semibold text-[#3f6212]">
                @if ($author->avatar_url)
                    <img src="{{ $author->avatar_url }}" alt="{{ $author->name }}" class="h-full w-full object-cover">
                @else
                    {{ $initials }}
                @endif
            </span>
            <div>
                <h1 class="text-[28px] font-semibold text-[#283618]">{{ $author->name }}</h1>
                @if ($author->headline)
                    <p class="mt-1 text-[14px] text-[#616657]">{{ $author->headline }}</p>
                @endif
                <p class="mt-1 text-[13px] text-[#8f9485]">{{ $articlesLabel }}</p>
            </div>
        </div>

        @if ($author->socialLinks())
            <div class="mt-5">
                @include('blog.partials.social-links', ['user' => $author])
            </div>
        @endif

        @if ($author->bio_html)
            <div class="prose prose-neutral mt-8 max-w-none prose-headings:text-[#283618] prose-a:text-[#283618]">
                {!! $author->bio_html !!}
            </div>
        @endif

        {{-- ---------------------------
             Artykuły autora
             --------------------------- --}}
        <div class="mt-12">
            <h2 class="text-[16px] font-semibold text-[#283618]">Artykuły autora</h2>

            <div class="mt-5 grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2">
                @forelse ($posts as $post)
                    @include('blog.partials.card', ['post' => $post])
                @empty
                    <p class="col-span-full text-[14px] text-[#8f9485]">Ten autor nie ma jeszcze opublikowanych artykułów.</p>
                @endforelse
            </div>

            @if ($posts->hasPages())
                <div class="mt-10">
                    {{ $posts->links() }}
                </div>
            @endif
        </div>

        {{-- ---------------------------
             Stopka autora — konfigurowana przez autora, ta sama pod jego wpisami
             --------------------------- --}}
        @if ($author->signature_html)
            <div class="mt-14 rounded-2xl border border-[#e5e5dc] bg-[#f8f8f4] p-6">
                <div class="prose prose-sm prose-neutral max-w-none prose-headings:text-[#283618] prose-a:text-[#283618]">
                    {!! $author->signature_html !!}
                </div>
            </div>
        @endif
    </div>

@endsection
