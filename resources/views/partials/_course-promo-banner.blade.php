{{--
    The live promotion, featured at the top of the Course Library.

    Three parts, like a ticket: a blue offer block (the discount, big, over a
    slowly sweeping polished-metal reflection), the details, and a side panel
    with the countdown, the real before/after price and the button.

    When the promotion is about one course it shows that course (tags, title,
    level, length, students, price). For a category or everything it shows the
    promotion's own title and message and where it applies.

    Everything comes from Admin > Promotions; nothing here is fixed to a course.
    Include with $promotion in scope. Renders nothing without one.
--}}
@if ($promotion ?? null)
    @php
        $promoMessage = $promotion->localized('message');
        $pc = $promotion->applies_to === 'course' ? $promotion->course : null;
        $pc?->loadCount(['lessons', 'enrollments']);
        $isDiscount = $promotion->isDiscount();
        $onSale = $pc && $isDiscount && $pc->isOnSale();
        $saving = $onSale ? round((float) $pc->price - $pc->salePrice(), 2) : 0;
        $pKey = 'pd-pbn-'.$promotion->id;
    @endphp

    <style>
        .pbn {
            position: relative;
            display: grid;
            grid-template-columns: 300px minmax(0, 1fr) 290px;
            margin-bottom: 32px;
            border: 1px solid var(--mist);
            border-radius: .125rem;
            background: var(--paper);
            color: var(--ink);
            overflow: hidden;
            animation: pbn-in .5s cubic-bezier(.23, 1, .32, 1) both;
        }

        html.pbn-dismissed .pbn { display: none; }

        @keyframes pbn-in {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: none; }
        }

        /* ---------- Blue offer block ---------- */
        .pbn-block {
            position: relative;
            isolation: isolate;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 28px;
            padding: 22px 24px 26px;
            min-height: 270px;
            overflow: hidden;
            color: #FFFFFF;
            background: linear-gradient(150deg, #243B9E 0%, #2E46C0 55%, #3552D6 100%);
        }

        /* Brushed steel: very fine vertical grain under everything, barely there. */
        .pbn-block::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -2;
            background: repeating-linear-gradient(90deg, rgba(255, 255, 255, .035) 0 1px, transparent 1px 3px);
            pointer-events: none;
        }

        /* Polished-metal reflection: a soft band of light that travels from the
           upper-left to the bottom-right, fades out at the far edge, and starts
           again from the upper-left. It is opaque only in the middle of its trip,
           so the restart is invisible, and it sits behind the number. */
        .pbn-sheen {
            position: absolute;
            top: -60%;
            left: -60%;
            width: 220%;
            height: 220%;
            z-index: -1;
            pointer-events: none;
            background: linear-gradient(115deg,
                transparent 38%,
                rgba(210, 225, 255, .05) 44%,
                rgba(235, 242, 255, .16) 49%,
                rgba(255, 255, 255, .24) 50.5%,
                rgba(235, 242, 255, .12) 53%,
                rgba(210, 225, 255, .04) 57%,
                transparent 63%);
            opacity: 0;
            transform: translate(-32%, -32%);
            animation: pbn-sheen 7s cubic-bezier(.45, .05, .55, .95) infinite;
        }

        @keyframes pbn-sheen {
            0%   { opacity: 0; transform: translate(-32%, -32%); }
            14%  { opacity: 1; }
            78%  { opacity: 1; }
            100% { opacity: 0; transform: translate(32%, 32%); }
        }

        .pbn-ghost {
            position: absolute;
            right: -4px;
            bottom: -28px;
            z-index: -1;
            font-family: 'Archivo Expanded', 'Archivo', system-ui, sans-serif;
            font-weight: 900;
            font-size: clamp(8rem, 18vw, 12rem);
            line-height: 1;
            color: transparent;
            -webkit-text-stroke: 2px rgba(255, 255, 255, .16);
            pointer-events: none;
            user-select: none;
        }

        /* Ticket notches where the block meets the details. */
        .pbn-block::after {
            content: "";
            position: absolute;
            top: 0;
            right: -1px;
            bottom: 0;
            width: 12px;
            background: radial-gradient(circle at 12px 50%, var(--paper) 6px, transparent 6.5px) 0 0 / 12px 26px repeat-y;
            pointer-events: none;
        }

        .pbn-tags { position: relative; display: flex; flex-wrap: wrap; gap: 8px; }

        .pbn-tag {
            display: inline-flex;
            align-items: center;
            padding: 4px 9px;
            border-radius: .125rem;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            line-height: 1.3;
        }

        .pbn-tag.is-light { background: #FFFFFF; color: #0A0A0A; }
        .pbn-tag.is-glass { background: #4A65E8; color: #FFFFFF; }

        .pbn-offer { position: relative; display: flex; align-items: center; gap: 14px; }

        .pbn-figure {
            font-family: 'Archivo Expanded', 'Archivo', system-ui, sans-serif;
            font-weight: 800;
            font-size: clamp(3.4rem, 8vw, 5rem);
            line-height: 1;
            letter-spacing: -.04em;
            font-variant-numeric: tabular-nums;
        }

        .pbn-off { display: grid; gap: 3px; padding-left: 14px; border-left: 1px solid rgba(255, 255, 255, .35); line-height: 1.1; }
        .pbn-off b { font-size: 1.35rem; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; }
        .pbn-off span { font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; opacity: .85; }

        .pbn-news { display: grid; place-items: center; width: 64px; height: 64px; border-radius: 50%; background: rgba(255, 255, 255, .16); }

        /* ---------- Details ---------- */
        .pbn-body { position: relative; min-width: 0; padding: 28px 32px; display: flex; flex-direction: column; justify-content: center; gap: 14px; }

        .pbn-chips { display: flex; flex-wrap: wrap; gap: 8px; }
        .pbn-chip { display: inline-flex; padding: 4px 9px; border-radius: .125rem; font-size: 11px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; background: var(--surface); color: var(--graphite); }
        .pbn-chip.is-ink { background: var(--ink); color: var(--paper); }

        .pbn-title { margin: 0; font-family: 'Archivo Expanded', 'Archivo', 'Kantumruy Pro', system-ui, sans-serif; font-weight: 800; font-size: clamp(1.3rem, 2.2vw, 1.75rem); line-height: 1.25; overflow-wrap: anywhere; }
        .pbn-msg { margin: 0; max-width: 56ch; font-size: 15px; line-height: 1.6; color: var(--graphite); }

        .pbn-meta { display: flex; flex-wrap: wrap; gap: 8px 20px; margin: 0; padding: 0; list-style: none; font-size: 13px; color: var(--graphite); }
        .pbn-meta li { display: inline-flex; align-items: center; gap: 7px; }
        .pbn-meta svg { flex: none; }

        .pbn-stack { display: inline-flex; }
        .pbn-stack i { width: 18px; height: 18px; margin-left: -6px; border-radius: 50%; border: 2px solid var(--paper); background: var(--ink); opacity: .9; }
        .pbn-stack i:first-child { margin-left: 0; background: var(--action); }
        .pbn-stack i:nth-child(2) { background: var(--graphite); }

        /* ---------- Side panel: countdown, price, button ---------- */
        .pbn-side { position: relative; display: flex; flex-direction: column; justify-content: center; gap: 18px; padding: 28px 28px 28px 28px; border-left: 1px solid var(--mist); }

        .pbn-count-label { margin: 0 0 8px; font-size: 11px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--action-ink, var(--action)); }
        .pbn-count { display: flex; align-items: flex-start; gap: 5px; }
        .pbn-unit { display: grid; justify-items: center; gap: 4px; }
        .pbn-num { display: grid; place-items: center; min-width: 40px; height: 36px; padding: 0 5px; border-radius: .125rem; background: var(--ink); color: var(--paper); font-family: 'Archivo Expanded', 'Archivo', system-ui, sans-serif; font-weight: 800; font-size: 16px; font-variant-numeric: tabular-nums; }
        .pbn-cap { font-size: 9px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--graphite); }
        .pbn-colon { padding-top: 7px; font-weight: 800; color: var(--graphite); }

        .pbn-price { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 10px; }
        .pbn-price b { font-family: 'Archivo Expanded', 'Archivo', system-ui, sans-serif; font-weight: 800; font-size: 1.9rem; line-height: 1; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
        .pbn-price s { font-size: 14px; color: var(--graphite); }
        .pbn-save { padding: 3px 8px; border-radius: .125rem; background: #15803D; color: #FFFFFF; font-size: 11px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }

        .pbn-cta {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            min-height: 46px; padding: 0 20px; border-radius: .125rem; width: 100%;
            background: var(--ink); color: var(--paper); font-size: 14px; font-weight: 700; text-align: center;
            transition: background-color .16s ease, color .16s ease, scale .16s cubic-bezier(.23, 1, .32, 1);
        }
        .pbn-cta svg { flex: none; transition: translate .2s cubic-bezier(.23, 1, .32, 1); }
        .pbn-cta:active { scale: .97; }

        .pbn-close {
            position: absolute; top: 8px; right: 8px; z-index: 2; display: grid; place-items: center;
            width: 44px; height: 44px; border-radius: 10px; color: var(--graphite);
            transition: background-color .15s ease, color .15s ease, scale .14s cubic-bezier(.23, 1, .32, 1);
        }
        .pbn-close:active { scale: .95; }

        @media (hover: hover) and (pointer: fine) {
            .pbn-cta:hover { background: var(--action); color: #FFFFFF; }
            .pbn-cta:hover svg { translate: 3px 0; }
            .pbn-close:hover { background: var(--surface); color: var(--ink); }
        }

        .pbn-cta:focus-visible, .pbn-close:focus-visible { outline: 2px solid var(--action); outline-offset: 2px; }

        /* Narrower: the side panel drops under the block and details, laid out in a row. */
        @media (max-width: 1080px) {
            .pbn { grid-template-columns: 300px minmax(0, 1fr); }
            .pbn-side { grid-column: 1 / -1; flex-direction: row; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 18px 28px; border-left: 0; border-top: 1px dashed var(--mist); padding: 20px 28px 24px; }
            .pbn-side .pbn-buy { display: grid; gap: 10px; min-width: 240px; }
            .pbn-body { padding-right: 56px; }
        }

        /* Phone and small tablet: the block sits on top, the notches run along its bottom edge. */
        @media (max-width: 760px) {
            .pbn { grid-template-columns: minmax(0, 1fr); }
            .pbn-block { min-height: 0; padding-bottom: 34px; }
            .pbn-block::after { top: auto; right: 0; left: 0; bottom: -1px; width: auto; height: 12px; background: radial-gradient(circle at 50% 12px, var(--paper) 6px, transparent 6.5px) 0 0 / 26px 12px repeat-x; }
            .pbn-body { padding: 22px 20px 4px; }
            .pbn-title { padding-right: 0; }
            .pbn-side { padding: 18px 20px 22px; }
            .pbn-side .pbn-buy { width: 100%; min-width: 0; }
            /* On a phone the X sits over the blue block, so it turns white. */
            .pbn-close { top: 6px; right: 6px; color: #FFFFFF; }
        }

        @media (max-width: 420px) {
            .pbn-num { min-width: 36px; height: 34px; font-size: 15px; }
        }

        html:lang(km) .pbn-title { font-family: 'Archivo', 'Kantumruy Pro', system-ui, sans-serif; line-height: 1.5; }

        @media (prefers-reduced-motion: reduce) {
            .pbn { animation: none; }
            .pbn-sheen { animation: none; opacity: 0; }
            .pbn-cta, .pbn-cta svg, .pbn-close { transition: none; }
            .pbn-cta:active, .pbn-close:active { scale: none; }
        }
    </style>

    <script>
        try { if (localStorage.getItem(@js($pKey))) document.documentElement.classList.add('pbn-dismissed'); } catch (e) {}
    </script>

    <aside class="pbn" aria-labelledby="pbn-title">
        <div class="pbn-block">
            <span class="pbn-sheen" aria-hidden="true"></span>

            <div class="pbn-tags">
                <span class="pbn-tag is-light">{{ $pc ? __($pc->category) : $promotion->targetLabel() }}</span>
                <span class="pbn-tag is-glass">{{ $isDiscount ? __('Limited offer') : __('News') }}</span>
            </div>

            @if ($isDiscount)
                <div class="pbn-offer" aria-hidden="true">
                    <span class="pbn-figure"><span data-pbn-count="{{ $promotion->discount_percent }}">{{ $promotion->discount_percent }}</span>%</span>
                    <span class="pbn-off"><b>{{ __('Off') }}</b><span>{{ __('Get it now') }}</span></span>
                </div>
                <span class="sr-only">{{ __(':percent% off', ['percent' => $promotion->discount_percent]) }}</span>
            @else
                <div class="pbn-offer" aria-hidden="true">
                    <span class="pbn-news">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1z" /><path d="M15.5 8.5a5 5 0 0 1 0 7M18.5 5.5a9 9 0 0 1 0 13" /></svg>
                    </span>
                </div>
            @endif

            @if ($pc)
                <span class="pbn-ghost" aria-hidden="true">{{ $pc->mark }}</span>
            @endif
        </div>

        <div class="pbn-body">
            <div class="pbn-chips">
                @if ($pc)
                    <span class="pbn-chip is-ink">{{ __($pc->level) }}</span>
                @endif
                <span class="pbn-chip">{{ $pc ? $promotion->localized('title') : __('Promotion') }}</span>
            </div>

            <h2 id="pbn-title" class="pbn-title">{{ $pc ? $pc->localized('title') : $promotion->localized('title') }}</h2>

            @if (filled($promoMessage))
                <p class="pbn-msg">{{ $promoMessage }}</p>
            @elseif ($pc && filled($pc->localized('description')))
                <p class="pbn-msg">{{ \Illuminate\Support\Str::limit($pc->localized('description'), 140) }}</p>
            @endif

            <ul class="pbn-meta">
                @if ($pc)
                    <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="m9 8 6 4-6 4Z"/></svg>{{ __(':count lessons', ['count' => $pc->lessons_count]) }}</li>
                    <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>{{ $pc->formattedDuration() }}</li>
                    @if ($pc->enrollments_count > 0)
                        <li><span class="pbn-stack" aria-hidden="true"><i></i><i></i><i></i></span>{{ trans_choice('{1} :count student enrolled|[2,*] :count students enrolled', $pc->enrollments_count, ['count' => number_format($pc->enrollments_count)]) }}</li>
                    @endif
                @else
                    <li><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5.5C4 4.67 4.67 4 5.5 4H12v16H5.5A1.5 1.5 0 014 18.5v-13zM20 5.5c0-.83-.67-1.5-1.5-1.5H12v16h6.5a1.5 1.5 0 001.5-1.5v-13z" /></svg>{{ $promotion->targetLabel() }}</li>
                @endif
            </ul>
        </div>

        <div class="pbn-side">
            @if ($promotion->ends_at)
                <div data-pbn-ends="{{ $promotion->ends_at->toIso8601String() }}">
                    <p class="pbn-count-label">{{ __('Offer ends in') }}</p>
                    <div class="pbn-count" role="timer" aria-label="{{ __('Ends') }} {{ $promotion->ends_at->translatedFormat('j M') }}">
                        @foreach (['d' => 'Days', 'h' => 'Hrs', 'm' => 'Min', 's' => 'Sec'] as $u => $cap)
                            <div class="pbn-unit"><span class="pbn-num" data-u="{{ $u }}">00</span><span class="pbn-cap">{{ __($cap) }}</span></div>
                            @unless ($loop->last)<span class="pbn-colon" aria-hidden="true">:</span>@endunless
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="pbn-buy" style="display:grid; gap:12px">
                @if ($onSale)
                    <div class="pbn-price">
                        <b>{{ $pc->salePriceLabel() }}</b>
                        <s><span class="sr-only">{{ __('Regular price') }}</span>{{ $pc->priceLabel() }}</s>
                        <span class="pbn-save">{{ __('Save :amount', ['amount' => \App\Models\Course::money($saving)]) }}</span>
                    </div>
                @endif
                <a href="{{ $promotion->ctaUrl() }}" class="pbn-cta">
                    {{ $promotion->ctaLabel() }}
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h13M12 5l7 7-7 7" /></svg>
                </a>
            </div>
        </div>

        <button type="button" class="pbn-close" aria-label="{{ __('Hide this promotion') }}" data-pbn-close="{{ $pKey }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
        </button>
    </aside>

    <script>
        (function () {
            var bar = document.querySelector('.pbn');
            if (!bar) return;
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            /* The discount counts up once as the page opens. Final value is already in the page. */
            var fig = bar.querySelector('[data-pbn-count]');
            if (fig && !reduce) {
                var to = parseInt(fig.getAttribute('data-pbn-count'), 10), t0 = null;
                fig.textContent = '0';
                var step = function (t) {
                    if (t0 === null) t0 = t;
                    var p = Math.min(1, (t - t0) / 800);
                    fig.textContent = String(Math.round(to * (1 - Math.pow(1 - p, 3))));
                    if (p < 1) requestAnimationFrame(step); else fig.textContent = String(to);
                };
                requestAnimationFrame(step);
            }

            /* Countdown to the real end time from the server, so a tab left open cannot drift. */
            var box = bar.querySelector('[data-pbn-ends]');
            if (box) {
                var end = new Date(box.getAttribute('data-pbn-ends')).getTime();
                var nums = {};
                box.querySelectorAll('[data-u]').forEach(function (n) { nums[n.getAttribute('data-u')] = n; });
                var pad = function (n) { return n < 10 ? '0' + n : String(n); };
                var timer;
                var tick = function () {
                    var left = end - Date.now();
                    if (!isFinite(left) || left <= 0) { box.remove(); clearInterval(timer); return; }
                    var s = Math.floor(left / 1000);
                    nums.d.textContent = pad(Math.floor(s / 86400));
                    nums.h.textContent = pad(Math.floor((s % 86400) / 3600));
                    nums.m.textContent = pad(Math.floor((s % 3600) / 60));
                    nums.s.textContent = pad(s % 60);
                };
                tick();
                timer = setInterval(tick, 1000);
            }

            /* Close: folds away (no page jump) and stays closed for this promotion on this browser. */
            var close = bar.querySelector('[data-pbn-close]');
            close.addEventListener('click', function () {
                if (bar.dataset.closing) return;
                bar.dataset.closing = '1';
                try { localStorage.setItem(close.getAttribute('data-pbn-close'), '1'); } catch (e) {}
                var done = false;
                var finish = function () { if (done) return; done = true; document.documentElement.classList.add('pbn-dismissed'); };
                if (!bar.animate || reduce) return finish();
                bar.style.overflow = 'hidden';
                bar.animate([{ height: bar.offsetHeight + 'px', opacity: 1, marginBottom: '32px' }, { height: '0px', opacity: 0, marginBottom: '0px' }],
                    { duration: 240, easing: 'cubic-bezier(.23, 1, .32, 1)', fill: 'forwards' }).onfinish = finish;
                setTimeout(finish, 400);
            });
        })();
    </script>
@endif
