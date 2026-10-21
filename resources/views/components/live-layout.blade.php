{{--
    Page frame for the pages built on the live piseth.design design: the
    homepage, course library, resources, activity and profile pages.

        <x-live-layout active="courses">
            <x-slot:styles><style>...</style></x-slot:styles>
            ...page content (inside <main>)...
            <x-slot:after>...dialogs...</x-slot:after>
            <x-slot:scripts><script>...</script></x-slot:scripts>
        </x-live-layout>

    It brings the top bar, footer, phone bottom bar and the language / theme
    pill, and the Alpine scope (`dark`, `toggleTheme`) they rely on.
    `active` underlines the matching tab: home, courses, resources, about,
    activity or profile. `promotion` shows the promotion bar above the top bar
    (the homepage passes it; other pages don't). `footerCourses` replaces the
    footer's course links (null = the first four published courses).
--}}
@props([
    'title' => 'Piseth.Design Classroom',
    'active' => null,
    'promotion' => null,
    'mainClass' => 'hm-main',
    'footerCourses' => null,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    @include('partials._favicon')

    {{-- Set theme before paint so there's no flash of the wrong theme (same key and default as every page). --}}
    <script>
        (function() {
            var saved = localStorage.getItem('pd-theme');
            var theme = saved || 'dark';
            if (theme === 'dark') document.documentElement.classList.add('dark');
        })();
    </script>

    @include('partials._head-shared')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    {{-- Archivo + Kantumruy Pro: the promotion bar and Telegram popup. Inter, Noto Sans Khmer, JetBrains Mono: the live design. --}}
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800&family=Archivo+Expanded:wght@700;800;900&family=Kantumruy+Pro:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&family=Noto+Sans+Khmer:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @include('partials.live._styles')
    {{ $styles ?? '' }}
</head>

<body class="antialiased hm-body">

    <div class="hm-shell"
        x-data="{
            dark: document.documentElement.classList.contains('dark'),
            announce: '',

            toggleTheme(e) {
                const next = !this.dark;
                const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                /* Where the wipe starts: the button you actually clicked. */
                const x = (e && e.clientX) ? e.clientX : window.innerWidth - 60;
                const y = (e && e.clientY) ? e.clientY : 50;

                if (!reduce) {
                    const r = Math.hypot(
                        Math.max(x, window.innerWidth - x),
                        Math.max(y, window.innerHeight - y)
                    ) * 2.2;

                    const ripple = document.createElement('span');
                    ripple.className = 'theme-ripple';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.style.setProperty('--r', r + 'px');
                    ripple.style.background = next ? '#0B0B0D' : '#F5F7FA';
                    document.body.appendChild(ripple);
                    setTimeout(() => ripple.remove(), 700);
                }

                setTimeout(() => {
                    this.dark = next;
                    document.documentElement.classList.toggle('dark', next);
                    localStorage.setItem('pd-theme', next ? 'dark' : 'light');
                }, reduce ? 0 : 180);

                this.announce = next ? 'Dark mode enabled' : 'Light mode enabled';
                setTimeout(() => { this.announce = ''; }, 2000);
            }
        }">

        {{-- Screen readers still hear the mode change --}}
        <div class="hm-sr" role="status" aria-live="polite" x-text="announce"></div>

        {{-- The live promotion sits above the top bar. --}}
        @if ($promotion)
            @include('partials._promo', ['promotion' => $promotion])
        @endif

        @include('partials.live._header', ['liveActive' => $active, 'promotion' => $promotion])

        <main {{ $attributes->merge(['class' => $mainClass]) }}>
            {{ $slot }}
        </main>

        @include('partials.live._footer', ['footerCourses' => $footerCourses])

        @include('partials.live._bottom-nav', ['liveActive' => $active])

        @include('partials.live._prefs')

        {{ $after ?? '' }}
    </div>

    @include('partials.live._tabs-script')
    {{ $scripts ?? '' }}
</body>

</html>
