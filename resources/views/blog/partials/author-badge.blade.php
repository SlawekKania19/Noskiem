@php
    // ---------------------------
    // Miniatura + nazwa autora, klikalne → /blog/autor/{slug}.
    // Wejście: $author (User), $size ('sm' domyślnie | 'lg').
    // ---------------------------
    $size = $size ?? 'sm';
    $linked = $author->hasPublicAuthorProfile();

    $avatarClass = $size === 'lg' ? 'h-16 w-16 text-[18px]' : 'h-9 w-9 text-[12px]';
    $nameClass = $size === 'lg' ? 'text-[16px] font-semibold' : 'text-[13px] font-medium';

    $initials = \Illuminate\Support\Str::of($author->name)
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $tag = $linked ? 'a' : 'span';
@endphp

<{{ $tag }}
    @if ($linked) href="{{ route('blog.author', $author) }}" @endif
    class="group inline-flex items-center gap-2.5 align-middle"
>
    <span class="flex {{ $avatarClass }} shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#dbe3d1] font-semibold text-[#3f6212]">
        @if ($author->avatar_url)
            <img src="{{ $author->avatar_url }}" alt="{{ $author->name }}" class="h-full w-full object-cover">
        @else
            {{ $initials }}
        @endif
    </span>
    <span class="{{ $nameClass }} text-[#283618] {{ $linked ? 'group-hover:underline' : '' }}">{{ $author->name }}</span>
</{{ $tag }}>
