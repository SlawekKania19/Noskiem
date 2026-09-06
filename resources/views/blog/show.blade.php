@extends('layouts.public')

@php
    // ---------------------------
    // Meta — pola SEO z panelu, a gdy puste: tytuł / zajawka wpisu
    // ---------------------------
    $metaTitle = $post->meta_title ?: $post->title;
    $metaDescription = $post->meta_description ?: $post->excerpt;
@endphp

@section('title', $metaTitle.' — Noskiem.pl')
@section('description', $metaDescription)

@push('head-assets')
    <link rel="alternate" type="application/rss+xml" title="Blog — Noskiem.pl" href="{{ route('blog.feed') }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $metaTitle }}">
    @if ($metaDescription)
        <meta property="og:description" content="{{ $metaDescription }}">
    @endif
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($post->cover_url)
        <meta property="og:image" content="{{ $post->cover_url }}">
    @endif
@endpush

@section('content')

    <article class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        {{-- ** Baner podglądu — tylko dla admina/autora oglądającego nieopublikowany wpis --}}
        @unless ($post->isPublished())
            <div class="mb-6 rounded-xl bg-[#fcecd1] px-4 py-3 text-[13px] font-medium text-[#994d0a]">
                Podgląd — wpis nie jest jeszcze opublikowany. Ten link nie zadziała dla czytelników.
            </div>
        @endunless

        <a href="{{ route('blog.index') }}" class="text-[13px] font-medium text-[#616657] transition-colors hover:text-[#283618]">
            &larr; Wróć do bloga
        </a>

        @if ($post->category)
            <a
                href="{{ route('blog.category', $post->category) }}"
                class="mt-6 block text-[12px] font-semibold uppercase tracking-wide text-[#994d0a] hover:underline"
            >{{ $post->category->name }}</a>
        @endif

        <h1 class="mt-2 text-[32px] font-semibold leading-tight text-[#283618]">{{ $post->title }}</h1>

        {{-- ** Wyróżniony autor pod tytułem — miniatura + nazwa, klikalne do strony autora --}}
        @if ($post->author)
            <div class="mt-4">
                @include('blog.partials.author-badge', ['author' => $post->author, 'size' => 'sm'])
            </div>
        @endif

        <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-[13px] text-[#8f9485]">
            <span>{{ $post->published_at?->locale('pl')->translatedFormat('d F Y') ?? 'wersja robocza' }}</span>
            <span aria-hidden="true">·</span>
            <span>{{ $post->reading_time }} min czytania</span>
        </p>

        @if ($post->cover_url)
            {{-- ** Limit wysokości — przy szerokim kontenerze pionowa okładka potrafiła
                 zająć cały ekran i zepchnąć treść poniżej zgięcia --}}
            <img
                src="{{ $post->cover_url }}"
                alt="{{ $post->title }}"
                class="mt-6 max-h-[420px] w-full rounded-2xl object-cover"
            >
        @endif

        {{-- ** Treść z RichEditora (HTML) — renderujemy jak treść stron statycznych --}}
        <div class="prose prose-neutral mt-8 max-w-none prose-headings:text-[#283618] prose-a:text-[#283618]">
            {!! $post->body_html !!}
        </div>

        {{-- ---------------------------
             Stopka autora — skonfigurowana przez autora, wyraźnie oddzielona od treści
             --------------------------- --}}
        @if ($post->author && $post->author->signature_html)
            <div class="mt-12 rounded-2xl border border-[#e5e5dc] bg-[#f8f8f4] p-6">
                @include('blog.partials.author-badge', ['author' => $post->author, 'size' => 'sm'])
                <div class="prose prose-sm prose-neutral mt-4 max-w-none prose-headings:text-[#283618] prose-a:text-[#283618]">
                    {!! $post->author->signature_html !!}
                </div>
            </div>
        @endif

        @if ($related->isNotEmpty())
            <div class="mt-14 border-t border-[#e5e5dc] pt-8">
                <h2 class="text-[16px] font-semibold text-[#283618]">Zobacz też</h2>
                <div class="mt-4 grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-3">
                    @foreach ($related as $item)
                        @include('blog.partials.card', ['post' => $item])
                    @endforeach
                </div>
            </div>
        @endif
    </article>

@endsection
