@props(['title' => 'Piseth.Design Classroom'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    @include('partials._favicon')
    @include('partials._classroom-theme')
    {{ $head ?? '' }}
</head>
<body class="antialiased min-h-screen flex flex-col" style="background:var(--paper); color:var(--ink);">
    <div class="flex-1 flex flex-col"
        x-data="{
            dark: document.documentElement.classList.contains('dark'),
            mobileOpen: false,
            announce: '',
            toggleTheme(e) {
                const next = !this.dark;
                const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                /* Where the wipe starts: the button you actually clicked.
                   Falls back to the top-right corner for keyboard activation. */
                const x = (e && e.clientX) ? e.clientX : window.innerWidth - 60;
                const y = (e && e.clientY) ? e.clientY : 50;

                if (!reduce) {
                    /* Radius needed to cover the furthest corner of the viewport */
                    const r = Math.hypot(
                        Math.max(x, window.innerWidth - x),
                        Math.max(y, window.innerHeight - y)
                    ) * 2.2;

                    const ripple = document.createElement('span');
                    ripple.className = 'theme-ripple';
                    ripple.style.left = x + 'px';
                    ripple.style.top = y + 'px';
                    ripple.style.setProperty('--r', r + 'px');
                    /* Paint the colour we are moving INTO, so the swap happens behind it */
                    ripple.style.background = next ? '#0A0A0A' : '#FFFFFF';
                    document.body.appendChild(ripple);
                    setTimeout(() => ripple.remove(), 700);
                }

                /* Flip the theme once the wipe has covered the screen */
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
        <div class="sr-only" role="status" aria-live="polite" x-text="announce"></div>

        @include('partials._classroom-nav')

        @if (session('success'))
            <div class="max-w-6xl mx-auto w-full px-6 pt-4 text-sm font-semibold" style="color:#16A34A" role="status">
                {{ session('success') }}
            </div>
        @endif

        @php
            // A few status codes are short slugs that a specific page (like
            // Account settings) already turns into their own friendly message.
            // Skip the generic banner for those so the raw slug never shows
            // up twice. Every other page still flashes a full sentence here
            // exactly as before.
            $globalStatus = session('status');
            $hasOwnStatusUi = in_array($globalStatus, [
                'profile-updated',
                'password-updated',
                'verification-link-sent',
                'email-verification-sent',
                'email-updated',
                'email-code-resent',
            ], true);
        @endphp
        @if ($globalStatus && ! $hasOwnStatusUi)
            <div class="max-w-6xl mx-auto w-full px-6 pt-4 text-sm" style="color:var(--redline)" role="status">
                {{ $globalStatus }}
            </div>
        @endif

        <div class="flex-1">
            {{ $slot }}
        </div>

        @include('partials._classroom-footer')
    </div>
</body>
</html>