@php
    // ---------------------------
    // Ikony linków społecznościowych autora. Wejście: $user (User).
    // Kolejność i zestaw pochodzą z User::socialLinks().
    // ---------------------------
    $links = $user->socialLinks();

    $labels = [
        'website' => 'Strona WWW',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'x' => 'X',
        'youtube' => 'YouTube',
        'linkedin' => 'LinkedIn',
    ];
@endphp

@if ($links)
    <div class="flex flex-wrap items-center gap-2">
        @foreach ($links as $key => $url)
            <a
                href="{{ $url }}"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="{{ $labels[$key] ?? $key }}"
                title="{{ $labels[$key] ?? $key }}"
                class="flex h-9 w-9 items-center justify-center rounded-full bg-[#f4f4ef] text-[#616657] transition hover:bg-[#283618] hover:text-[#fefae0]"
            >
                @switch($key)
                    @case('website')
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/>
                        </svg>
                        @break
                    @case('facebook')
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M13.5 21v-8h2.7l.4-3.1h-3.1V7.9c0-.9.25-1.5 1.55-1.5H17V3.6c-.3-.04-1.3-.13-2.5-.13-2.5 0-4.2 1.5-4.2 4.3v2.1H7.5V13h2.8v8h3.2z"/>
                        </svg>
                        @break
                    @case('instagram')
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17" cy="7" r="1" fill="currentColor" stroke="none"/>
                        </svg>
                        @break
                    @case('tiktok')
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16.5 3c.3 2.1 1.6 3.7 3.7 3.9v2.6c-1.4.1-2.7-.3-3.9-1.1v6.4c0 3.4-2.6 5.7-5.7 5.7A5.6 5.6 0 0 1 5 15.2c0-3.3 3.1-5.9 6.5-5.1v2.8a2.8 2.8 0 0 0-3.7 2.7c0 1.6 1.3 2.8 2.9 2.8 1.7 0 2.9-1.3 2.9-3V3h2.9z"/>
                        </svg>
                        @break
                    @case('x')
                        <svg class="h-[16px] w-[16px]" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18.9 2h3.3l-7.2 8.2L23.5 22h-6.6l-5.2-6.8L5.7 22H2.4l7.7-8.8L1 2h6.8l4.7 6.2L18.9 2zm-1.2 18h1.8L7.1 3.9H5.2L17.7 20z"/>
                        </svg>
                        @break
                    @case('youtube')
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round">
                            <path d="M22 12s0-3.6-.46-5.3a2.9 2.9 0 0 0-2-2C17.7 4.2 12 4.2 12 4.2s-5.7 0-7.54.5a2.9 2.9 0 0 0-2 2C2 8.4 2 12 2 12s0 3.6.46 5.3a2.9 2.9 0 0 0 2 2c1.84.5 7.54.5 7.54.5s5.7 0 7.54-.5a2.9 2.9 0 0 0 2-2C22 15.6 22 12 22 12Z"/>
                            <path d="M10 15V9l5 3z" fill="currentColor" stroke="none"/>
                        </svg>
                        @break
                    @case('linkedin')
                        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>
                        </svg>
                        @break
                @endswitch
            </a>
        @endforeach
    </div>
@endif
