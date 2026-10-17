{{--
    The live promotion, above the top bar. Included by the homepage only: the
    course library announces the same promotion as a full banner above its
    course grid (partials/_course-promo-banner), and one page wants one of
    them, not both.

    Include it with the promotion in scope; it renders nothing without one:

        @include('partials._promo')

    Styling is .promo-* in resources/css/app.css (shared, like the top bar).
    Closing it hides that promotion on this browser only; a new promotion has a
    new id, so it shows again.
--}}

@php
    // Safe to include on a page whose controller passes no promotion.
    $promotion = $promotion ?? null;
@endphp

@if ($promotion)
    @php
        $promoKey = 'pd-promo-'.$promotion->id;
        $promoMessage = $promotion->localized('message');
    @endphp

    {{-- Applied before first paint, so a bar the visitor already closed never
         flashes in and back out. --}}
    <script>
        document.documentElement.classList.add('has-promo');
        try {
            if (localStorage.getItem(@js($promoKey))) {
                document.documentElement.classList.add('promo-dismissed');
            }
        } catch (e) {}
    </script>

    <aside class="promo" aria-label="{{ __('Promotion') }}">
        <div class="promo-inner">
            <span class="promo-pill">
                @if ($promotion->isDiscount())
                    {{ __(':percent% off', ['percent' => $promotion->discount_percent]) }}
                @else
                    {{ __('News') }}
                @endif
            </span>

            <span class="promo-title">{{ $promotion->localized('title') }}</span>

            @if (filled($promoMessage))
                <span class="promo-msg">{{ $promoMessage }}</span>
            @endif

            @if ($promotion->ends_at)
                {{-- Counts down against a fixed deadline from the server, so a
                     tab left open overnight cannot drift. --}}
                <span class="promo-clock" data-promo-ends="{{ $promotion->ends_at->toIso8601String() }}">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" /><path d="M12 7.5V12l3 2" />
                    </svg>
                    <span class="sr-only">{{ __('Ends') }}</span>
                    <b data-promo-countdown>{{ $promotion->ends_at->translatedFormat('j M') }}</b>
                </span>
            @endif

            <a href="{{ $promotion->ctaUrl() }}" class="promo-cta">
                {{ $promotion->ctaLabel() }}
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 12h13M12 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        <button type="button" class="promo-close" aria-label="{{ __('Hide this promotion') }}"
            data-promo-close="{{ $promoKey }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>
    </aside>

    <script>
        (function () {
            var bar = document.querySelector('.promo');
            if (!bar) return;

            /* ---------- Countdown ---------- */
            var clock = bar.querySelector('[data-promo-ends]');
            var out = bar.querySelector('[data-promo-countdown]');

            if (clock && out) {
                var deadline = new Date(clock.getAttribute('data-promo-ends')).getTime();

                var pad = function (n) { return n < 10 ? '0' + n : String(n); };

                var tick = function () {
                    var left = deadline - Date.now();

                    // Past its end date: the server will stop sending it on the
                    // next load, so just drop the clock rather than show 0.
                    if (!isFinite(left) || left <= 0) {
                        clock.remove();
                        clearInterval(timer);
                        return;
                    }

                    var s = Math.floor(left / 1000);
                    var d = Math.floor(s / 86400);
                    var h = Math.floor((s % 86400) / 3600);
                    var m = Math.floor((s % 3600) / 60);

                    out.textContent = d > 0
                        ? d + 'd ' + pad(h) + ':' + pad(m) + ':' + pad(s % 60)
                        : pad(h) + ':' + pad(m) + ':' + pad(s % 60);
                };

                tick();
                var timer = setInterval(tick, 1000);
            }

            /* ---------- Dismiss ----------
               Folds the bar away so the page does not jump, then hands focus to
               the top bar's logo so it is not lost with the button. */
            var close = bar.querySelector('[data-promo-close]');
            if (!close) return;

            close.addEventListener('click', function () {
                if (bar.dataset.closing) return;
                bar.dataset.closing = '1';

                try { localStorage.setItem(close.getAttribute('data-promo-close'), '1'); } catch (e) {}

                var hadFocus = document.activeElement === close;
                var done = false;

                /* Idempotent, and never the only way out: a background tab
                   freezes the document timeline, so onfinish may never fire.
                   The timer below closes the bar regardless -- whichever
                   arrives first wins, and the second call does nothing. */
                var finish = function () {
                    if (done) return;
                    done = true;
                    document.documentElement.classList.add('promo-dismissed');
                    var home = document.querySelector('#site-nav .sn-brand');
                    if (hadFocus && home) home.focus();
                };

                if (!bar.animate || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    return finish();
                }

                bar.style.overflow = 'hidden';
                bar.animate(
                    [{ height: bar.offsetHeight + 'px', opacity: 1 }, { height: '0px', opacity: 0 }],
                    { duration: 240, easing: 'cubic-bezier(.23, 1, .32, 1)', fill: 'forwards' }
                ).onfinish = finish;

                setTimeout(finish, 400);
            });
        })();
    </script>
@endif
