@php
    // ---------------------------
    // Karta wpisu na liście bloga. Wejście: $post (z załadowanymi author, category).
    // ---------------------------
    $url = \Illuminate\Support\Facades\Route::has('blog.show') ? route('blog.show', $post) : '#';
@endphp

<article class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-[0px_4px_14px_0px_rgba(30,38,18,0.07)] transition hover:shadow-[0px_6px_18px_0px_rgba(30,38,18,0.12)]">
    <a href="{{ $url }}" class="block aspect-[16/9] w-full overflow-hidden bg-[#dbe3d1]">
        @if ($post->cover_url)
            <img
                src="{{ $post->cover_url }}"
                alt="{{ $post->title }}"
                class="h-full w-full object-cover transition group-hover:scale-105"
                loading="lazy"
            >
        @else
            <div class="flex h-full items-center justify-center text-[32px]">🐾</div>
        @endif
    </a>

    <div class="flex flex-1 flex-col p-4">
        @if ($post->category)
            <a
                href="{{ route('blog.category', $post->category) }}"
                class="text-[11px] font-semibold uppercase tracking-wide text-[#994d0a] hover:underline"
            >{{ $post->category->name }}</a>
        @endif

        <h2 class="mt-1 text-[16px] font-semibold text-[#1e2612]">
            <a href="{{ $url }}" class="hover:underline">{{ $post->title }}</a>
        </h2>

        @if ($post->excerpt)
            <p class="mt-2 line-clamp-3 text-[13px] leading-relaxed text-[#616657]">{{ $post->excerpt }}</p>
        @endif

        {{-- ** Metryczka na dole karty — dosuwana w dół (mt-auto), żeby karty w rzędzie miały równą stopkę --}}
        <p class="mt-auto pt-4 flex flex-wrap items-center gap-x-2 gap-y-1 text-[12px] text-[#8f9485]">
            @if ($post->author)
                <span>{{ $post->author->name }}</span>
                <span aria-hidden="true">·</span>
            @endif
            <span>{{ $post->published_at->locale('pl')->translatedFormat('d F Y') }}</span>
            <span aria-hidden="true">·</span>
            <span>{{ $post->reading_time }} min czytania</span>
        </p>
    </div>
</article>
