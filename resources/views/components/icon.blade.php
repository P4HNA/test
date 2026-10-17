{{--
    One stroke icon set for the whole product (public site, classroom, admin).
    24px grid, round caps and joins, currentColor. Decorative by default:
    give the surrounding button or link the accessible name.

        <x-icon name="search" class="w-4 h-4" />
--}}
@props(['name', 'stroke' => 1.75])

@php
    $paths = [
        // Navigation + chrome
        'menu'          => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'x'             => '<path d="M6 6l12 12M18 6L6 18"/>',
        'search'        => '<circle cx="11" cy="11" r="6.5"/><path d="M16 16l4.5 4.5"/>',
        'sun'           => '<circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4L6 18M18 6l1.4-1.4"/>',
        'moon'          => '<path d="M20.5 13.2A8.5 8.5 0 1110.8 3.5a6.6 6.6 0 009.7 9.7z"/>',
        'chevron-down'  => '<path d="M6 9l6 6 6-6"/>',
        'chevron-right' => '<path d="M9 6l6 6-6 6"/>',
        'chevron-left'  => '<path d="M15 6l-6 6 6 6"/>',
        'arrow-right'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left'    => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'external'      => '<path d="M14 4h6v6M20 4l-9 9"/><path d="M18 14v5a1 1 0 01-1 1H5a1 1 0 01-1-1V7a1 1 0 011-1h5"/>',
        'logout'        => '<path d="M15 4h3a2 2 0 012 2v12a2 2 0 01-2 2h-3M10 16l-4-4 4-4M6 12h10"/>',
        'home'          => '<path d="M4 10.5L12 4l8 6.5V19a1 1 0 01-1 1h-4.5v-5.5h-5V20H5a1 1 0 01-1-1z"/>',
        'globe'         => '<circle cx="12" cy="12" r="8.5"/><path d="M3.5 12h17M12 3.5c2.3 2.4 3.4 5.2 3.4 8.5s-1.1 6.1-3.4 8.5c-2.3-2.4-3.4-5.2-3.4-8.5s1.1-6.1 3.4-8.5z"/>',

        // Learning
        'book'          => '<path d="M4 5.5A1.5 1.5 0 015.5 4H12v16H5.5A1.5 1.5 0 014 18.5v-13z"/><path d="M20 5.5A1.5 1.5 0 0018.5 4H12v16h6.5a1.5 1.5 0 001.5-1.5v-13z"/>',
        'book-open'     => '<path d="M3 5.5c2.7-1 5.7-.8 9 1 3.3-1.8 6.3-2 9-1V19c-2.7-1-5.7-.8-9 1-3.3-1.8-6.3-2-9-1z"/><path d="M12 6.5V20"/>',
        'play'          => '<path d="M8 5.5v13l10.5-6.5z"/>',
        'play-circle'   => '<circle cx="12" cy="12" r="8.5"/><path d="M10.2 8.8v6.4l5-3.2z"/>',
        'clock'         => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'award'         => '<circle cx="12" cy="9" r="5.5"/><path d="M8.8 13.6L7.5 21l4.5-2.4 4.5 2.4-1.3-7.4"/>',
        'graduation'    => '<path d="M2.5 9L12 4.5 21.5 9 12 13.5z"/><path d="M6.5 11v4.5c0 1.4 2.5 3 5.5 3s5.5-1.6 5.5-3V11M21.5 9v5"/>',
        'layers'        => '<path d="M12 3l9 5-9 5-9-5 9-5z"/><path d="M3 13l9 5 9-5"/><path d="M3 17.5l9 5 9-5"/>',
        'list'          => '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r=".8"/><circle cx="4.5" cy="12" r=".8"/><circle cx="4.5" cy="18" r=".8"/>',
        'message'       => '<path d="M4 5.5A1.5 1.5 0 015.5 4h13A1.5 1.5 0 0120 5.5v9a1.5 1.5 0 01-1.5 1.5H9l-5 4z"/><path d="M8 9h8M8 12.5h5"/>',
        'languages'     => '<path d="M3.5 6h9M8 4v2c0 4-2 7-4.5 8.5M5.5 9.5c.8 2 2.4 3.6 4.5 4.5"/><path d="M12.5 20l3.8-9 3.7 9M13.8 17h5"/>',
        'file'          => '<path d="M14 3.5H7A1.5 1.5 0 005.5 5v14A1.5 1.5 0 007 20.5h10a1.5 1.5 0 001.5-1.5V8z"/><path d="M14 3.5V8h4.5"/>',
        'download'      => '<path d="M12 4v11M7 10.5l5 5 5-5"/><path d="M4.5 19.5h15"/>',
        'pencil-line'   => '<path d="M4 20l1-4L16.5 4.5a2 2 0 013 3L8 19l-4 1z"/><path d="M13.5 20H20"/>',

        // People + account
        'user'          => '<circle cx="12" cy="8" r="4"/><path d="M4.5 20.5a7.5 7.5 0 0115 0"/>',
        'users'         => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0113 0"/><path d="M16 4.6a3.5 3.5 0 010 6.8"/><path d="M18 14.2A6.5 6.5 0 0121.5 20"/>',
        'settings'      => '<circle cx="12" cy="12" r="3"/><path d="M19.4 14.6l1.3 1-2 3.4-1.6-.6a7 7 0 01-2.1 1.2L14.7 21h-4l-.3-1.6a7 7 0 01-2.1-1.2l-1.6.6-2-3.4 1.3-1a7 7 0 010-2.4l-1.3-1 2-3.4 1.6.6a7 7 0 012.1-1.2L10.7 3h4l.3 1.6a7 7 0 012.1 1.2l1.6-.6 2 3.4-1.3 1a7 7 0 010 2.4z"/>',
        'lock'          => '<rect x="5" y="10.5" width="14" height="10" rx="2"/><path d="M8.5 10.5V7.5a3.5 3.5 0 017 0v3"/>',
        'shield'        => '<path d="M12 3.5l7 2.5v5.5c0 4.4-2.9 8-7 9-4.1-1-7-4.6-7-9V6z"/><path d="M9 12l2.2 2.2L15.5 10"/>',
        'mail'          => '<rect x="3.5" y="5.5" width="17" height="13" rx="2"/><path d="M4 7l8 6 8-6"/>',
        'phone'         => '<rect x="7" y="3" width="10" height="18" rx="2.2"/><path d="M11 17.5h2"/>',

        // Commerce
        'qr'            => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><path d="M14 14h2.5v2.5H14zM17.5 17.5H20V20h-2.5zM14 19.5V20M20 14v.5"/>',
        'wallet'        => '<path d="M4 7.5A2.5 2.5 0 016.5 5H18v3"/><rect x="4" y="8" width="16.5" height="11" rx="2"/><path d="M16 13.5h1.5"/>',
        'receipt'       => '<path d="M6 3.5h12v17l-3-1.8-3 1.8-3-1.8-3 1.8z"/><path d="M9 8h6M9 11.5h6M9 15h3"/>',
        'tag'           => '<path d="M3.5 12.3V4.5a1 1 0 011-1h7.8l8.2 8.2a1.4 1.4 0 010 2L13.7 20.5a1.4 1.4 0 01-2 0z"/><circle cx="8" cy="8" r="1.3"/>',

        // Status
        'check'         => '<path d="M5 12.5l4.5 4.5L19 7"/>',
        'check-circle'  => '<circle cx="12" cy="12" r="8.5"/><path d="M8.3 12.3l2.5 2.5 5-5.2"/>',
        'alert'         => '<circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.4v.1"/>',
        'info'          => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5.5M12 7.8v.1"/>',
        'x-circle'      => '<circle cx="12" cy="12" r="8.5"/><path d="M9.2 9.2l5.6 5.6M14.8 9.2l-5.6 5.6"/>',
        'refresh'       => '<path d="M20 11.5A8 8 0 006.3 6.3L4 8.5M4 4v4.5h4.5"/><path d="M4 12.5a8 8 0 0013.7 5.2L20 15.5M20 20v-4.5h-4.5"/>',
        'sparkle'       => '<path d="M12 3.5l1.8 5.2 5.2 1.8-5.2 1.8L12 17.5l-1.8-5.2L5 10.5l5.2-1.8z"/><path d="M19 16l.7 1.8 1.8.7-1.8.7L19 21l-.7-1.8-1.8-.7 1.8-.7z"/>',
        'filter'        => '<path d="M4 6h16M7 12h10M10 18h4"/>',
        'sliders'       => '<path d="M4 6h9M17 6h3M4 12h3M11 12h9M4 18h11M19 18h1"/><circle cx="15" cy="6" r="2"/><circle cx="9" cy="12" r="2"/><circle cx="17" cy="18" r="2"/>',
        'printer'       => '<path d="M7 8V3.5h10V8"/><rect x="3.5" y="8" width="17" height="8.5" rx="2"/><path d="M7 14h10v6.5H7z"/>',
        'calendar'      => '<rect x="4" y="5" width="16" height="15.5" rx="2"/><path d="M4 9.5h16M8.5 3v4M15.5 3v4"/>',
        'star'          => '<path d="M12 3.8l2.5 5.2 5.7.8-4.1 4 1 5.6L12 16.7l-5.1 2.7 1-5.6-4.1-4 5.7-.8z"/>',

        // Social
        'facebook'      => '<path d="M17 3.5h-2.5A4.5 4.5 0 0010 8v2.5H7.5v3.5H10v6.5h3.5V14H16l.5-3.5h-3V8.2a1 1 0 011-1H17z"/>',
        'instagram'     => '<rect x="3.5" y="3.5" width="17" height="17" rx="4.8"/><circle cx="12" cy="12" r="3.9"/><path d="M17.2 6.8v.01"/>',
        'telegram'      => '<path d="M20.5 4.2L3.4 10.9c-.9.4-.9 1.6.1 1.9l4.3 1.3 1.6 5c.3.8 1.3 1 1.9.4l2.4-2.3 4.4 3.2c.7.5 1.6.1 1.8-.7l2.9-13.4c.2-1-.7-1.7-1.6-1.3z"/><path d="M8 14l9.3-7"/>',

        // Admin (kept for x-admin.icon)
        'grid'          => '<rect x="3" y="3" width="7" height="7" rx="1.6"/><rect x="14" y="3" width="7" height="7" rx="1.6"/><rect x="3" y="14" width="7" height="7" rx="1.6"/><rect x="14" y="14" width="7" height="7" rx="1.6"/>',
        'video'         => '<rect x="3" y="5" width="18" height="14" rx="2.6"/><path d="M10 9.5l5 2.5-5 2.5z"/>',
        'pulse'         => '<path d="M3 12h4l2.5-6 4 12 2.5-6H21"/>',
        'bell'          => '<path d="M6 9a6 6 0 0112 0c0 6 2.5 7.5 2.5 7.5h-17S6 15 6 9z"/><path d="M10 20a2 2 0 004 0"/>',
        'plus'          => '<path d="M12 5v14M5 12h14"/>',
        'upload'        => '<path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v3a1 1 0 001 1h14a1 1 0 001-1v-3"/>',
        'key'           => '<circle cx="8" cy="15" r="3.5"/><path d="M10.5 12.5L20 3M16 7l3 3M13.5 9.5l2 2"/>',
        'trash'         => '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"/>',
        'pencil'        => '<path d="M4 20l1-4L16.5 4.5a2 2 0 013 3L8 19l-4 1z"/>',
        'up'            => '<path d="M12 19V5M6 11l6-6 6 6"/>',
        'down'          => '<path d="M12 5v14M6 13l6 6 6-6"/>',
        'link'          => '<path d="M10 14a4 4 0 005.7 0l3-3a4 4 0 00-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 00-5.7 0l-3 3a4 4 0 005.7 5.7l1-1"/>',
        'eye'           => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off'       => '<path d="M9.9 4.2A10.4 10.4 0 0112 4c6.4 0 10 8 10 8a17.6 17.6 0 01-2.9 4.1M6.6 6.6C3.9 8.4 2 12 2 12s3.6 8 10 8a9.7 9.7 0 005.4-1.6"/><path d="M9.9 9.9a3 3 0 004.2 4.2"/><path d="M2 2l20 20"/>',
        'database'      => '<path d="M4 7c0-1.7 3.6-3 8-3s8 1.3 8 3-3.6 3-8 3-8-1.3-8-3zM4 7v10c0 1.7 3.6 3 8 3s8-1.3 8-3V7M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3"/>',
        'wand'          => '<path d="M5 19L15 9M14 4l1.2 2.8L18 8l-2.8 1.2L14 12l-1.2-2.8L10 8l2.8-1.2zM19 14l.7 1.6 1.6.7-1.6.7L19 18.6l-.7-1.6-1.6-.7 1.6-.7z"/>',
        'copy'          => '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2h2"/>',
        'film'          => '<rect x="4" y="4" width="16" height="16" rx="2"/><path d="M8 4v16M16 4v16M4 9h4M16 9h4M4 15h4M16 15h4"/>',
        'card'          => '<rect x="2.5" y="5.5" width="19" height="13" rx="2"/><path d="M2.5 10h19"/><path d="M6 14.5h4"/>',
        'image'         => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.8"/><path d="M3 16l5-5 4 4 3-3 6 6"/>',
        'archive'       => '<rect x="3" y="4" width="18" height="4.5" rx="1.2"/><path d="M4.5 8.5V19a1 1 0 001 1h13a1 1 0 001-1V8.5"/><path d="M10 12.5h4"/>',
        'undo'          => '<path d="M4 9h9a5 5 0 010 10H8"/><path d="M8 5L4 9l4 4"/>',
    ];
@endphp
<svg width="20" height="20" {{ $attributes }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="{{ $stroke }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $paths[$name] ?? '' !!}</svg>
