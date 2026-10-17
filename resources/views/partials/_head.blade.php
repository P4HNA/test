{{--
    Shared <head> for every page: theme (set before first paint so there is
    no flash), fonts, the compiled Tailwind bundle and the design system.

    @include('partials._head')                          themed page
    @include('partials._head', ['lightOnly' => true])   sign-in screens
--}}
@php($lightOnly = $lightOnly ?? false)
<meta name="theme-color" content="#FAFAF9">
<script>
    (function () {
        var root = document.documentElement;
        var lightOnly = @json($lightOnly);
        var system = window.matchMedia('(prefers-color-scheme: dark)');
        var stored = null;
        try { stored = localStorage.getItem('pd-theme'); } catch (e) {}

        function paint(dark) {
            root.classList.toggle('dark', dark);
            var meta = document.querySelector('meta[name="theme-color"]');
            if (meta) meta.setAttribute('content', dark ? '#0D0C0B' : '#FAFAF9');
        }

        paint(!lightOnly && (stored ? stored === 'dark' : system.matches));

        // Follow the OS setting live until the visitor picks a theme themselves.
        system.addEventListener && system.addEventListener('change', function (e) {
            var chosen = null;
            try { chosen = localStorage.getItem('pd-theme'); } catch (err) {}
            if (!lightOnly && !chosen) paint(e.matches);
        });

        // One theme switch for every layout. A short cross-fade (View
        // Transitions, where supported) keeps a whole page flipping from
        // light to dark from feeling like a flash; reduced motion skips it.
        window.pdToggleTheme = function () {
            var next = !root.classList.contains('dark');
            var apply = function () {
                paint(next);
                try { localStorage.setItem('pd-theme', next ? 'dark' : 'light'); } catch (e) {}
                window.dispatchEvent(new CustomEvent('pd-theme', { detail: { dark: next } }));
            };
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (document.startViewTransition && !reduce && document.visibilityState === 'visible') {
                // A skipped transition still applies the theme; its "ready"
                // promise rejects, which is expected and not an error.
                var vt = document.startViewTransition(apply);
                vt.ready.catch(function () {});
                vt.finished.catch(function () {});
            } else {
                apply();
            }
            return next;
        };
    })();
</script>
@include('partials._head-shared')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Archivo:wdth,wght@62..125,100..900&family=Kantumruy+Pro:wght@400;500;600;700&display=swap">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<link rel="stylesheet" href="{{ asset('css/ui.css') }}?v={{ filemtime(public_path('css/ui.css')) }}">
