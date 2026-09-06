<?php echo '<?xml version="1.0" encoding="UTF-8"?>', "\n"; ?>
{{-- ---------------------------
     Kanał RSS 2.0 bloga. Zwracany przez BlogController@feed z nagłówkiem
     Content-Type: application/xml. Treść wpisu w <content:encoded> (CDATA).
     --------------------------- --}}
<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel>
        <title>Blog — Noskiem.pl</title>
        <link>{{ route('blog.index') }}</link>
        <atom:link href="{{ route('blog.feed') }}" rel="self" type="application/rss+xml" />
        <description>Porady i artykuły dla właścicieli zwierząt — Noskiem.pl</description>
        <language>pl-pl</language>
        @if ($posts->isNotEmpty())
        <lastBuildDate>{{ $posts->first()->published_at->toRssString() }}</lastBuildDate>
        @endif
        @foreach ($posts as $post)
        <item>
            <title>{{ $post->title }}</title>
            <link>{{ route('blog.show', $post) }}</link>
            <guid isPermaLink="true">{{ route('blog.show', $post) }}</guid>
            <pubDate>{{ $post->published_at->toRssString() }}</pubDate>
            @if ($post->author)
            <dc:creator>{{ $post->author->name }}</dc:creator>
            @endif
            @if ($post->category)
            <category>{{ $post->category->name }}</category>
            @endif
            @if ($post->excerpt)
            <description>{{ $post->excerpt }}</description>
            @endif
            <content:encoded><![CDATA[{!! str_replace(']]>', ']]]]><![CDATA[>', (string) $post->body_html) !!}]]></content:encoded>
        </item>
        @endforeach
    </channel>
</rss>
