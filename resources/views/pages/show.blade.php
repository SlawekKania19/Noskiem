@extends('layouts.public')

@section('title', $page->title . ' — Noskiem.pl')

@section('content')

    {{-- ---------------------------
         Statyczna podstrona — treść z panelu Filament (Markdown → HTML)
         --------------------------- --}}
    <section class="bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <h1 class="text-[32px] font-semibold text-[#283618]">{{ $page->title }}</h1>

            <div class="prose prose-neutral mt-8 max-w-none prose-headings:text-[#283618] prose-a:text-[#283618]">
                {!! $page->content_html !!}
            </div>
        </div>
    </section>

@endsection
