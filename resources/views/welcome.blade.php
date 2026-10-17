<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Piseth.Design Classroom</title>
    @include('partials._favicon')

    {{-- Set theme before paint so there's no flash of the wrong theme --}}
    <script>
        (function() {
            var saved = localStorage.getItem('pd-theme');
            var theme = saved || 'dark';
            if (theme === 'dark') document.documentElement.classList.add('dark');

            /* Sections below the hero rise in as they scroll into view. They are
               hidden up front only when that can actually run, so the page never
               flashes content in and back out, and never leaves it hidden. */
            if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                /* per-section scroll reveal switched off */
            }

            /* Curtain intro: once per browser session, never with reduced motion. */
            try {
                if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches && !sessionStorage.getItem('pd-intro')) {
                    sessionStorage.setItem('pd-intro', '1');
                    document.documentElement.classList.add('js-intro');
                }
            } catch (e) {}
        })();
    </script>

    @include('partials._head-shared')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800&family=Archivo+Expanded:wght@700;800;900&family=Caveat:wght@500;600&family=Kantumruy+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{--
        Requires class-based dark mode in your Tailwind config:
        Tailwind v3 (tailwind.config.js):   darkMode: 'class'
        Tailwind v4 (app.css):              @custom-variant dark (&:where(.dark, .dark *));
    --}}
    <style>
        :root {
            --paper: #FFFFFF;
            --surface: #F4F4F6;
            --ink: #0A0A0A;
            --graphite: #5E5E5E;
            --mist: #E6E6EA;
            --action: #0866FF;
            /* Blue fill: buttons, pills. White text on it is 4.8:1 */
            --action-ink: #0062EB;
            /* Blue text/links: 4.8:1+ on paper, surface and tints */
            --action-soft: #EAF4FF;
            /* Light: tinted backgrounds behind action elements */
            --danger: #DC2626;
            /* Red: log out and other destructive actions */
            --blue: #0866FF;
            --promo-bg: #EEF5FF;
            --promo-line: #D6E6FF;
        }

        html.dark {
            --paper: #0A0A0A;
            --surface: #151516;
            --ink: #FFFFFF;
            --graphite: #A1A1A8;
            --mist: #26262A;
            --action: #0866FF;
            --action-ink: #3D85FF;
            --action-soft: rgba(24, 119, 242, .18);
            --danger: #FF5A5F;
            --blue: #3D85FF;
            --promo-bg: #0B1A33;
            --promo-line: #16294A;
        }

        body {
            font-family: 'Archivo', 'Kantumruy Pro', system-ui, sans-serif;
            background: var(--paper);
            color: var(--ink);
            transition: background-color .45s ease, color .45s ease;
        }

        .font-display {
            font-family: 'Archivo Expanded', 'Kantumruy Pro', system-ui, sans-serif;
        }

        .font-hand {
            font-family: 'Caveat', 'Kantumruy Pro', cursive;
        }

        [x-cloak] {
            display: none !important;
        }

        :focus-visible {
            outline: 2px solid var(--action);
            outline-offset: 3px;
        }

        /* ============================================================
           THEME SWITCH ANIMATION
           A circle of the incoming theme's paper colour expands from the
           toggle button and washes over the whole page.
           ============================================================ */

        .theme-ripple {
            position: fixed;
            z-index: 90;
            width: 0;
            height: 0;
            border-radius: 50%;
            pointer-events: none;
            transform: translate(-50%, -50%);
            animation: theme-ripple-grow .62s cubic-bezier(.4, 0, .2, 1) forwards;
        }

        @keyframes theme-ripple-grow {
            0% {
                width: 0;
                height: 0;
                opacity: 1;
            }

            62% {
                opacity: 1;
            }

            100% {
                width: var(--r);
                height: var(--r);
                opacity: 0;
            }
        }

        .theme-btn {
            transition: transform .3s cubic-bezier(.19, 1, .22, 1), border-color .3s ease;
        }

        .theme-btn:hover {
            transform: rotate(18deg);
        }

        .theme-btn:active {
            transform: scale(.88) rotate(-14deg);
        }

        /* ============================================================
           HERO
           Short headline on top, then a fan of course cards below it.
           The fan is positioned by JS (see the script at the bottom):
           it sways left and right by itself, follows the page scroll,
           and can be dragged or stepped with the arrow buttons.
           ============================================================ */
        .hero {
            position: relative;
            z-index: 0;
            isolation: isolate;
            padding: 0 0 clamp(20px, 3vh, 36px);
            /* The headline slides in from the right on load; clip sideways so
               that never shows a horizontal scrollbar on a phone. */
            overflow-x: clip;
        }

        /* ==========================================================
           HEADER STAGE
           Built after the hero on pamidordesign.co: a big two-line wordmark
           (PISETH. / DESIGN) beside a tall portrait on the right, set in
           --blue (#0866FF) with the headline and script line.
             .intro      first-visit curtain (once per browser session)
             .stage-word giant PISETH. / DESIGN, one .w-line per row
             .stage-card the portrait; tilts with the pointer, and the photo
                         inside slides at a different rate for depth
           Timing: everything hangs off --t0, which is 0s normally and the
           length of the curtain on a first visit (see html.js-intro).
           ========================================================== */
        :root {
            --t0: 0s;
        }

        html.js-intro {
            --t0: 2.05s;
            animation: intro-lock 2.9s steps(1, end);
        }

        @keyframes intro-lock {

            0%,
            100% {
                overflow: hidden;
            }
        }

        /* ---------- Intro curtain ---------- */
        @property --n {
            syntax: '<integer>';
            inherits: false;
            initial-value: 0;
        }

        .intro {
            display: none;
        }

        html.js-intro .intro {
            position: fixed;
            inset: 0;
            z-index: 200;
            display: grid;
            place-items: center;
            background: #0A0A0A;
            color: #FFFFFF;
            animation: intro-out .8s cubic-bezier(.76, 0, .24, 1) 2.05s forwards;
        }

        .intro-words {
            position: relative;
            display: grid;
            place-items: center;
            width: min(92vw, 1100px);
            height: clamp(64px, 14vw, 170px);
            font-family: 'Archivo Expanded', 'Kantumruy Pro', system-ui, sans-serif;
            font-weight: 800;
            font-size: clamp(2rem, 8.5vw, 7rem);
            line-height: 1;
            letter-spacing: -.04em;
            text-transform: uppercase;
            text-align: center;
        }

        .intro-words>span {
            position: absolute;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            opacity: 0;
            animation: intro-flash .26s steps(1, end) calc(.15s + var(--k) * .26s) forwards;
        }

        .intro-words>.intro-final {
            animation: intro-final .75s cubic-bezier(.16, 1, .3, 1) 1.5s forwards;
        }

        .intro-final i {
            display: inline-block;
            width: .16em;
            height: .16em;
            margin-left: .06em;
            border-radius: 50%;
            background: #3D85FF;
        }

        .intro-count {
            position: absolute;
            bottom: clamp(18px, 4vh, 34px);
            font-size: .8rem;
            font-weight: 500;
            letter-spacing: .14em;
            text-transform: uppercase;
            opacity: .7;
        }

        .intro-count {
            left: clamp(18px, 4vw, 40px);
            counter-reset: n var(--n);
            animation: intro-n 1.5s cubic-bezier(.6, 0, .2, 1) .1s forwards;
        }

        .intro-count::before {
            content: counter(n, decimal-leading-zero) '%';
        }

        @keyframes intro-n {
            to {
                --n: 100;
            }
        }

        @keyframes intro-flash {

            0%,
            99.9% {
                opacity: 1;
            }

            100% {
                opacity: 0;
            }
        }

        @keyframes intro-final {
            from {
                opacity: 0;
                translate: 0 40%;
                scale: .92;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes intro-out {
            to {
                clip-path: inset(0 0 100% 0);
                visibility: hidden;
            }
        }

        /* ---------- Stage layout ---------- */
        .stage,
        .stage-inner {
            box-sizing: border-box;
        }

        .stage {
            --mx: 0;
            --my: 0;
            --sy: 0;
            position: relative;
            isolation: isolate;
            display: flex;
            min-height: max(560px, calc(100vh - 64px));
            min-height: max(560px, calc(100svh - 64px));
            padding: clamp(16px, 3vh, 32px) 0 clamp(20px, 4vh, 40px);
            background: var(--paper);
            overflow-x: clip;
        }

        .stage-inner {
            flex: 1;
            display: grid;
            grid-template-columns: minmax(0, 1.16fr) minmax(0, .84fr);
            column-gap: clamp(16px, 3vw, 48px);
            width: 100%;
            max-width: 1320px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .stage-left {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: clamp(12px, 2vh, 24px);
            min-width: 0;
        }

        .stage-eyebrow {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 16px;
            margin-bottom: clamp(6px, 1.4vh, 16px);
        }

        .stage-script {
            font-family: 'Caveat', 'Kantumruy Pro', cursive;
            font-weight: 600;
            font-size: clamp(1.25rem, 2.2vw, 1.9rem);
            line-height: 1;
            color: var(--blue);
            rotate: -4deg;
            transform-origin: 100% 50%;
            animation: script-in .8s cubic-bezier(.34, 1.5, .64, 1) calc(var(--t0) + 1.2s) backwards;
        }

        html:lang(km) .stage-script {
            font-size: 1rem;
            line-height: 1.4;
        }

        /* The giant word. Font size is fitted to the layout by the script;
           the vw value is only the no-JS fallback. */
        .stage-word {
            position: relative;
            z-index: 4;
            display: block;
            width: max-content;
            margin: 0 0 0 -.05em;
            font-family: 'Archivo Expanded', 'Kantumruy Pro', system-ui, sans-serif;
            font-weight: 900;
            font-size: clamp(2.6rem, 11vw, 11rem);
            line-height: .84;
            letter-spacing: -.05em;
            color: var(--blue);
            translate: calc(var(--mx) * -14px) calc(var(--sy) * -46px);
            user-select: none;
            -webkit-user-select: none;
            pointer-events: none;
        }

        /* One clipped line per row so the letters rise out of a mask. */
        .w-line {
            display: flex;
            padding: .07em .06em .02em;
            overflow: hidden;
        }

        .w-line+.w-line {
            margin-top: -.04em;
        }

        .stage-word .w-line>span {
            display: block;
            animation: letter-in 1.25s cubic-bezier(.16, 1, .3, 1) calc(var(--t0) + .1s + var(--i) * .05s) backwards;
        }

        .stage-bottom {
            display: flex;
            flex-direction: column;
            gap: clamp(14px, 2.4vh, 24px);
        }

        .hero-title {
            margin: 0;
            max-width: 19em;
            font-family: 'Archivo Expanded', 'Kantumruy Pro', system-ui, sans-serif;
            font-weight: 800;
            font-size: clamp(1.25rem, 2.2vw, 2.05rem);
            line-height: 1.1;
            letter-spacing: -.03em;
            text-wrap: balance;
            color: var(--ink);
        }

        .hero-line {
            display: block;
        }

        .hero-title em {
            display: block;
            margin-top: .3em;
            font-style: normal;
            color: var(--ink);
        }

        .rise {
            animation: rise-in .95s cubic-bezier(.16, 1, .3, 1) calc(var(--t0) + var(--d, 0s)) backwards;
        }

        .stage-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 14px 28px;
        }

        .hero-sub {
            margin: 0;
            max-width: 26ch;
            font-size: clamp(.92rem, 1.25vw, 1.02rem);
            line-height: 1.5;
            color: var(--graphite);
        }

        .hero-ctas {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        /* Hero buttons
           Primary   = brand blue, white text. On hover: white/paper fill with blue text.
           Secondary = paper fill with a hairline border. On hover: fills with ink (black in
                       light mode, white in dark mode).
           They never end up the same colour, and both work in light and dark mode. */
        .stage .hero-cta {
            border: 1px solid transparent;
            border-radius: 12px;
            letter-spacing: -.005em;
            transition: background-color .25s ease, color .25s ease, border-color .25s ease, box-shadow .3s ease;
        }

        .stage .hero-cta.is-primary {
            background: var(--action);
            color: #FFFFFF;
            border-color: var(--action);
            box-shadow: 0 14px 26px -12px rgba(24, 119, 242, .65);
        }

        .stage .hero-cta.is-primary:hover,
        .stage .hero-cta.is-primary:focus-visible {
            background: var(--action-soft);
            color: var(--action-ink);
            border-color: var(--action);
            box-shadow: 0 18px 30px -14px rgba(24, 119, 242, .5);
        }

        .stage .hero-cta.is-secondary {
            background: var(--paper);
            color: var(--ink);
            border-color: var(--mist);
            box-shadow: 0 10px 22px -14px rgba(10, 10, 10, .35);
        }

        .stage .hero-cta.is-secondary:hover,
        .stage .hero-cta.is-secondary:focus-visible {
            background: var(--ink);
            color: var(--paper);
            border-color: var(--ink);
            box-shadow: 0 18px 30px -14px rgba(10, 10, 10, .55);
        }

        /* ---------- Portrait card ---------- */
        .stage-right {
            position: relative;
            min-width: 0;
        }

        .stage-card {
            position: relative;
            z-index: 1;
            height: 100%;
            min-height: 440px;
            background: none;
            transform-origin: 50% 100%;
            animation: card-in 1.4s cubic-bezier(.16, 1, .3, 1) calc(var(--t0) + .2s) backwards;
        }

        /* Cut-out portrait: no card, no crop. The person stands on the page and sits on the bottom edge. */
        .stage-card img {
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: 50% 100%;
            user-select: none;
            -webkit-user-select: none;
            -webkit-user-drag: none;
            animation: photo-breathe 14s ease-in-out calc(var(--t0) + 1.6s) infinite alternate;
            transform-origin: 50% 100%;
        }

        html.dark .stage-card img {
            filter: brightness(.95) contrast(1.05);
        }

        /* ---------- Keyframes ---------- */
        @keyframes rise-in {
            from {
                opacity: 0;
                translate: 0 28px;
            }
        }

        @keyframes letter-in {
            from {
                translate: 0 108%;
                rotate: 7deg;
            }
        }

        @keyframes card-in {
            from {
                opacity: 0;
                translate: 0 48px;
            }
        }

        @keyframes photo-in {
            from {
                scale: 1.4;
            }
        }

        /* After the reveal the photo keeps a slow, barely-there zoom, like a held breath */
        @keyframes photo-breathe {
            from {
                scale: 1;
            }

            to {
                scale: 1.02;
            }
        }

        @keyframes script-in {
            from {
                opacity: 0;
                rotate: 6deg;
                translate: 0 14px;
            }
        }

        /* ---------- Tablet and phone ---------- */
        @media (max-width: 900px) {
            .stage {
                min-height: 0;
                padding-top: 14px;
            }

            .stage-inner {
                grid-template-columns: minmax(0, 1fr);
                row-gap: 0;
            }

            /* Let the two halves of the left column be ordered around the
               portrait: eyebrow + word, then photo, then the copy. */
            .stage-left {
                display: contents;
            }

            .stage-top {
                order: 1;
            }

            .stage-right {
                order: 2;
                margin-top: -7vw;
            }

            .stage-bottom {
                order: 3;
                margin-top: 22px;
            }

            .stage-card {
                height: auto;
                min-height: 0;
                aspect-ratio: 4 / 4.6;
            }

            .hero-title {
                font-size: clamp(1.3rem, 5.6vw, 1.9rem);
            }
        }

        /* ---------- Fan entrance ----------
           The header stage above animates itself; the course fan then rises
           into place and its cards follow, timed by the fan script with the
           same offsets (HERO_DELAY). Keyframes only describe the starting
           pose, so nothing stays hidden if an animation never runs. */
        .fan.hero-in-up {
            animation: hero-rise .9s cubic-bezier(.16, 1, .3, 1) calc(var(--t0) + 1.1s) backwards;
        }

        @keyframes hero-rise {
            from {
                opacity: 0;
                translate: 0 56px;
            }
        }

        /* ---------- Scroll reveal ----------
           Sections below the hero rise in once, as they reach the viewport;
           --i staggers siblings. They are hidden first only while the reveal
           script is in charge (html.js-reveal, set in <head>), so with no
           JavaScript, or with reduced motion, everything simply shows. */
        .js-reveal [data-reveal]:not(.is-in) {
            opacity: 0;
        }

        .js-reveal [data-reveal].is-in {
            animation: reveal-up .85s cubic-bezier(.16, 1, .3, 1) calc(var(--i, 0) * 90ms) backwards;
        }

        @keyframes reveal-up {
            from {
                opacity: 0;
                translate: 0 32px;
            }
        }

        /* ---------- The fan ---------- */
        .fan {
            --cw: clamp(150px, 18vw, 250px);
            /* card width, everything else scales from it */
            --ch: calc(var(--cw) * 1.65);
            /* card height */
            --pivot: calc(var(--cw) * 3.6);
            /* distance from card bottom to the point the fan swings around */
            position: relative;
            isolation: isolate;
            overflow: hidden;
            margin: clamp(20px, 3.5vh, 36px) clamp(16px, 5vw, 96px) 0;
            height: calc(var(--ch) * 1.42 + 28px);
            border: 1px solid var(--mist);
            border-radius: 22px;
            background:
                radial-gradient(120% 55% at 50% 108%, color-mix(in srgb, var(--ink) 9%, transparent), transparent 62%),
                var(--surface);
            touch-action: pan-y;
            cursor: grab;
            user-select: none;
            -webkit-user-select: none;
        }

        .fan.is-dragging {
            cursor: grabbing;
        }

        /* The grain is a fixed, mix-blend-mode layer covering the whole viewport, so the
           browser has to reblend the entire screen on every repaint underneath it — not
           just wherever changed. That cost isn't worth a barely-visible texture on a
           phone, especially stacked on top of the fan's own animation frames below. */
        @media (max-width: 640px),
        (pointer: coarse) {
            .paper-grain {
                display: none;
            }
        }

        .fan-card {
            position: absolute;
            left: 50%;
            top: 28px;
            display: block;
            width: var(--cw);
            height: var(--ch);
            margin-left: calc(var(--cw) / -2);
            color: inherit;
            text-decoration: none;
            transform-origin: 50% calc(100% + var(--pivot));
            visibility: hidden;
            will-change: transform, opacity;
            -webkit-user-drag: none;
        }

        .fan-face {
            position: relative;
            display: flex;
            flex-direction: column;
            width: 100%;
            height: 100%;
            padding: calc(var(--cw) * .09);
            border-radius: calc(var(--cw) * .07);
            overflow: hidden;
            background: var(--bg);
            color: var(--fg);
            box-shadow: 0 30px 44px -24px rgba(0, 0, 0, .5), inset 0 0 0 1px rgba(255, 255, 255, .1);
            /* the springy cubic-bezier overshoots on purpose: that is the "pop" on hover */
            transition:
                translate .45s cubic-bezier(.34, 1.56, .64, 1),
                scale .45s cubic-bezier(.34, 1.56, .64, 1),
                box-shadow .35s ease;
        }

        .fan-card:hover .fan-face,
        .fan-card:focus-visible .fan-face {
            translate: 0 -16px;
            scale: 1.05;
            box-shadow: 0 46px 60px -28px rgba(0, 0, 0, .6), inset 0 0 0 1px rgba(255, 255, 255, .1);
        }

        .fan-card:focus-visible {
            outline-offset: 6px;
        }

        .fan-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
        }

        .fan-mark {
            font-family: 'Archivo Expanded', 'Kantumruy Pro', system-ui, sans-serif;
            font-weight: 900;
            font-size: calc(var(--cw) * .34);
            line-height: .9;
            letter-spacing: -.04em;
        }

        .fan-badge {
            margin-top: 2px;
            padding: 2px 7px;
            border: 1px solid currentColor;
            border-radius: 99px;
            font-size: max(9px, calc(var(--cw) * .05));
            font-weight: 600;
            line-height: 1.5;
            opacity: .85;
        }

        .fan-badge.is-sale {
            border-color: transparent;
            background: #FFFFFF;
            color: #0866FF;
            opacity: 1;
            white-space: nowrap;
        }

        /* The old price stays full colour (faded blue fails contrast); the
           strike and a lighter weight are what set it apart. */
        .fan-badge.is-sale s {
            font-weight: 500;
        }

        .fan-title {
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            overflow: hidden;
            margin-top: calc(var(--cw) * .09);
            font-family: 'Archivo Expanded', 'Kantumruy Pro', system-ui, sans-serif;
            font-weight: 800;
            font-size: calc(var(--cw) * .085);
            line-height: 1.12;
            letter-spacing: -.02em;
        }

        .fan-meta {
            margin-top: calc(var(--cw) * .03);
            font-size: max(9px, calc(var(--cw) * .05));
            opacity: .7;
        }

        /* Khmer stacks marks above and below the line: give the two-line
           title room, and keep the small print above Khmer's legible floor. */
        html:lang(km) .fan-title {
            line-height: 1.34;
        }

        html:lang(km) :is(.fan-meta, .fan-badge) {
            font-size: max(10.5px, calc(var(--cw) * .05));
        }

        /* Device shots peeking in from the bottom, like the phone and laptop mockups in the reference */
        .fan-device {
            position: absolute;
            pointer-events: none;
            padding: 3.5%;
            background: #0d0c0a;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, .14), 0 26px 30px -12px rgba(0, 0, 0, .55);
        }

        .fan-device.is-tab {
            left: 12%;
            right: -14%;
            bottom: -7%;
            height: 43%;
            border-radius: calc(var(--cw) * .06);
            transform: rotate(-7deg);
            transform-origin: left bottom;
        }

        .fan-device.is-phone {
            left: 20%;
            width: 60%;
            bottom: -10%;
            height: 52%;
            border-radius: calc(var(--cw) * .11);
            transform: rotate(4deg);
            transform-origin: center bottom;
        }

        .fan-device.is-phone::before {
            content: '';
            position: absolute;
            top: 3%;
            left: 50%;
            z-index: 2;
            width: 26%;
            height: 4%;
            transform: translateX(-50%);
            border-radius: 99px;
            background: #0d0c0a;
        }

        .dv-screen {
            display: flex;
            flex-direction: column;
            gap: 7%;
            width: 100%;
            height: 100%;
            padding: 10% 9% 0;
            overflow: hidden;
            border-radius: calc(var(--cw) * .045);
            background: var(--fg);
        }

        .is-phone .dv-screen {
            border-radius: calc(var(--cw) * .085);
            padding-top: 16%;
        }

        .dv-screen i {
            display: block;
            flex: 0 0 auto;
            height: 7%;
            min-height: 4px;
            border-radius: 99px;
            background: var(--bg);
            opacity: .35;
        }

        .dv-screen i:nth-child(1) {
            width: 30%;
            opacity: .9;
        }

        .dv-screen i:nth-child(2) {
            width: 80%;
        }

        .dv-screen i:nth-child(3) {
            width: 60%;
        }

        .dv-screen b {
            display: block;
            flex: 1;
            border-radius: 6px 6px 0 0;
            background: var(--bg);
            opacity: .16;
        }

        .fan-ui {
            position: absolute;
            left: 50%;
            bottom: 10px;
            z-index: 500;
            display: flex;
            align-items: center;
            gap: 14px;
            transform: translateX(-50%);
            cursor: default;
        }

        .fan-btn {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border: 1px solid var(--mist);
            border-radius: 50%;
            background: var(--paper);
            color: var(--ink);
            cursor: pointer;
            transition: background-color .2s ease, color .2s ease, border-color .2s ease, transform .2s ease;
        }

        .fan-btn:hover {
            background: var(--ink);
            color: var(--paper);
            border-color: transparent;
        }

        .fan-btn:active {
            transform: scale(.92);
        }

        .fan-caption {
            min-width: 15ch;
            max-width: 26ch;
            text-align: center;
            font-size: 13px;
            font-weight: 500;
            color: var(--graphite);
        }

        @media (max-width: 480px) {
            .fan-caption {
                min-width: 11ch;
                max-width: 14ch;
                font-size: 12px;
            }
        }

        .crop-mark {
            position: absolute;
            width: 16px;
            height: 16px;
            pointer-events: none;
        }

        .crop-mark.tl {
            top: -8px;
            left: -8px;
            border-top: 1.5px solid var(--action);
            border-left: 1.5px solid var(--action);
        }

        .crop-mark.tr {
            top: -8px;
            right: -8px;
            border-top: 1.5px solid var(--action);
            border-right: 1.5px solid var(--action);
        }

        .crop-mark.bl {
            bottom: -8px;
            left: -8px;
            border-bottom: 1.5px solid var(--action);
            border-left: 1.5px solid var(--action);
        }

        .crop-mark.br {
            bottom: -8px;
            right: -8px;
            border-bottom: 1.5px solid var(--action);
            border-right: 1.5px solid var(--action);
        }

        /* The four corner marks drift out and back along their diagonals, each
           all in step, like the card is floating. Slow and small, so it
           stays a detail. Off for reduced motion. */
        @keyframes crop-float {
            0%, 100% { translate: 0 0; }
            50% { translate: calc(var(--dx) * 5px) calc(var(--dy) * 5px); }
        }

        .crop-mark.tl { --dx: -1; --dy: -1; }
        .crop-mark.tr { --dx: 1; --dy: -1; }
        .crop-mark.bl { --dx: -1; --dy: 1; }
        .crop-mark.br { --dx: 1; --dy: 1; }

        @media (prefers-reduced-motion: no-preference) {
            .crop-mark {
                animation: crop-float 3.2s ease-in-out infinite;
            }

        }

        /* FAQ answers drop down from the question and lift back on close. */
        .faq-enter { transition: opacity .22s cubic-bezier(.23, 1, .32, 1), transform .26s cubic-bezier(.23, 1, .32, 1); transform-origin: top; }
        .faq-leave { transition: opacity .14s ease-out, transform .14s ease-out; transform-origin: top; }
        .faq-from { opacity: 0; transform: translateY(-8px); }
        .faq-to { opacity: 1; transform: translateY(0); }

        @media (prefers-reduced-motion: reduce) {
            .faq-enter, .faq-leave { transition: opacity .15s linear; }
            .faq-from { transform: none; }
        }

        /* Pinned-proof feel on a couple of catalogue tiles */
        .pin {
            position: absolute;
            top: -9px;
            left: 24px;
            width: 13px;
            height: 13px;
            border-radius: 50%;
            background: var(--action);
            box-shadow: 0 2px 5px rgba(0, 0, 0, .35), inset 0 -2px 3px rgba(0, 0, 0, .25);
        }

        .course-tile {
            transition: background-color .2s ease, color .2s ease, border-color .2s ease, transform .25s ease;
        }

        .course-tile:nth-child(3n+1) {
            transform: rotate(-.6deg);
        }

        .course-tile:nth-child(3n+2) {
            transform: rotate(.45deg);
        }

        .course-tile:nth-child(3n+3) {
            transform: rotate(-.3deg);
        }

        .course-tile:hover {
            background: var(--ink) !important;
            color: var(--paper) !important;
            border-color: transparent !important;
            transform: rotate(0deg) translateY(-3px);
        }

        /* The promotion bar above the menu is .promo-* in resources/css/app.css. The
           hero fills the first screen, so with the bar showing it gives up that height. */
        html.has-promo .stage {
            min-height: max(560px, calc(100vh - 64px - 52px));
            min-height: max(560px, calc(100svh - 64px - 52px));
        }

        html.has-promo.promo-dismissed .stage {
            min-height: max(560px, calc(100svh - 64px));
        }

        /* The brand lockup (logo + wordmark) is components/brand, styled in app.css. */

        /* Nav slides away while scrolling down, and comes back on any scroll up */
        #site-nav {
            transition: transform .35s cubic-bezier(.19, 1, .22, 1);
        }

        #site-nav.nav-hidden {
            transform: translateY(-100%);
        }

        .nav-link {
            position: relative;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            bottom: -6px;
            height: 1px;
            background: currentColor;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform .3s ease;
        }

        .nav-link:hover::after,
        .nav-link:focus-visible::after {
            transform: scaleX(1);
        }

        /* Typing caret for the testimonial */
        .type-caret {
            display: inline-block;
            width: 2px;
            height: .95em;
            margin-left: 4px;
            vertical-align: -.1em;
            background: var(--action);
            animation: caret-blink 1s steps(1, end) infinite;
        }

        .type-caret.is-done {
            animation: caret-fade .9s ease forwards;
        }

        @keyframes caret-blink {

            0%,
            50% {
                opacity: 1;
            }

            50.01%,
            100% {
                opacity: 0;
            }
        }

        @keyframes caret-fade {
            to {
                opacity: 0;
            }
        }

        @media (prefers-reduced-motion: reduce) {

            /* Every entrance on this page (intro, wordmark, portrait, chip, fan,
               scroll reveals) only animates FROM a starting pose, so removing the
               animations leaves each element in its finished, on-screen state. */
            * {
                transition: none !important;
                animation: none !important;
            }

            .intro {
                display: none !important;
            }
        }
    </style>
</head>

<body class="antialiased" style="background:var(--paper); color:var(--ink);">

    <div
        x-data="{
            dark: document.documentElement.classList.contains('dark'),
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
        {{-- Faint paper grain — reinforces the print/proof material instead of a flat vector gradient --}}
        <div class="paper-grain fixed inset-0 z-[60] pointer-events-none" aria-hidden="true"
            style="opacity:.045; mix-blend-mode:overlay; background-repeat:repeat; background-size:120px 120px; background-image:url(data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20width='120'%20height='120'%3E%3Cfilter%20id='n'%3E%3CfeTurbulence%20type='fractalNoise'%20baseFrequency='0.9'%20numOctaves='2'%20stitchTiles='stitch'/%3E%3C/filter%3E%3Crect%20width='100%25'%20height='100%25'%20filter='url(%23n)'/%3E%3C/svg%3E);"></div>

        {{-- Screen readers still hear the mode change --}}
        <div class="sr-only" role="status" aria-live="polite" x-text="announce"></div>

        <!-- Nav: the bar shared with every classroom page -->
        {{-- The live promotion, above the menu (it is also pinned in the Courses section below). --}}
        @include('partials._promo')

        @include('partials._site-nav')

        <!-- ===================================================================
             HERO. A short headline, then the course cards fanned out in an arc.
             The fan sways left and right on its own, follows the page scroll,
             and can be dragged, swiped, or stepped with the arrow buttons.
             =================================================================== -->
        @php
        // One plain array per card. Real courses are used when the database has any;
        // otherwise placeholder cards are shown so the hero is never empty.
        // Adobe app colours: [card background, big letters]. Looked up by the course's
        // short mark (Ps, Ai, Id...). Unknown marks cycle through the list below.
        $adobeColors = [
        'ps' => ['#001E36', '#31A8FF'], // Photoshop
        'ai' => ['#330000', '#FF9A00'], // Illustrator
        'id' => ['#49021F', '#FF3366'], // InDesign
        'pr' => ['#00005B', '#9999FF'], // Premiere Pro
        'ae' => ['#00005B', '#9999FF'], // After Effects
        'mo' => ['#00005B', '#9999FF'], // Motion Graphics -> After Effects
        'xd' => ['#470137', '#FF61F6'], // XD
        'fg' => ['#470137', '#FF61F6'], // Figma / UI-UX -> XD
        'au' => ['#00242B', '#00E4BB'], // Audition
        'dw' => ['#072401', '#4AFF99'], // Dreamweaver
        'aa' => ['#EB1000', '#FFFFFF'], // Adobe red
        ];
        $adobeCycle = ['ps', 'ai', 'id', 'pr', 'xd', 'au', 'dw'];

        $cards = collect($courses ?? [])->map(function ($c, $n) use ($adobeColors, $adobeCycle) {
        $pick = $adobeColors[strtolower(trim((string) $c->mark))] ?? $adobeColors[$adobeCycle[$n % count($adobeCycle)]];
        return [
        'mark' => $c->mark,
        'title' => $c->localized('title'),
        'meta' => __($c->level) . ' · ' . __(':count lessons', ['count' => $c->lessons_count]),
        'badge' => $c->isFree() ? __('Free') : $c->priceLabel(),
        // A live promotion: the badge shows the regular price struck through and today's price.
        'sale' => $c->isOnSale() ? $c->salePriceLabel() : null,
        'bg' => $pick[0],
        'fg' => $pick[1],
        'url' => route('courses.show', $c),
        ];
        })->values();

        if ($cards->isEmpty()) {
        $cards = collect([
        ['mark' => 'Ps', 'title' => 'Photoshop', 'meta' => __('Photo editing & composites'), 'bg' => '#1E3A8A', 'fg' => '#EAF1FF'],
        ['mark' => 'Ai', 'title' => 'Illustrator', 'meta' => __('Logos & branding'), 'bg' => '#FF8A00', 'fg' => '#1B0F00'],
        ['mark' => 'Fg', 'title' => 'Figma', 'meta' => __('UI / UX design'), 'bg' => '#6B21A8', 'fg' => '#F5E9FF'],
        ['mark' => 'Id', 'title' => 'InDesign', 'meta' => __('Editorial layout'), 'bg' => '#E8471E', 'fg' => '#FFF7EF'],
        ['mark' => 'Mo', 'title' => 'Motion Graphics', 'meta' => __('Animation basics'), 'bg' => '#1F7A3E', 'fg' => '#EFFFF3'],
        ['mark' => 'Aa', 'title' => 'Design Fundamentals', 'meta' => __('Type, colour, layout'), 'bg' => '#EDEDF1', 'fg' => '#0A0A0A'],
        ])->map(function ($c) use ($adobeColors) {
        $pick = $adobeColors[strtolower($c['mark'])] ?? $adobeColors['ps'];
        $c['badge'] = '';
        $c['bg'] = $pick[0];
        $c['fg'] = $pick[1];
        $c['url'] = route('courses');
        return $c;
        });
        }

        // A short list is repeated so the fan looks full and can loop without a gap.
        $realCount = $cards->count();
        $deck = collect();
        while ($deck->count() < 10) {
            $deck=$deck->concat($cards);
            }
            @endphp

            @php
            $adobe = ['PS', 'AI', 'ID', 'PR', 'AE', 'LR', 'XD', 'AU'];
            @endphp

            <div class="intro" aria-hidden="true">
                <div class="intro-words">
                    @foreach (array_slice($adobe, 0, 5) as $k => $abbr)
                    <span style="--k:{{ $k }}">{{ $abbr }}</span>
                    @endforeach
                    <span class="intro-final">PISETH<i></i></span>
                </div>
                <span class="intro-count"></span>
            </div>

            <section class="hero">
                <div class="stage" id="stage">
                    <div class="stage-inner">
                        <div class="stage-left">
                            <div class="stage-top">
                                <div class="stage-eyebrow">
                                    <span class="stage-script">{{ __('Founder & lead instructor') }}</span>
                                </div>
                                <p class="stage-word" id="stage-word" aria-hidden="true">
                                    <span class="w-line">@foreach (str_split('PISETH.') as $i => $ch)<span style="--i:{{ $i }}">{{ $ch }}</span>@endforeach</span>
                                    <span class="w-line">@foreach (str_split('DESIGN') as $i => $ch)<span style="--i:{{ $i + 7 }}">{{ $ch }}</span>@endforeach</span>
                                </p>
                            </div>

                            <div class="stage-bottom">
                                <h1 class="hero-title">
                                    <span class="hero-line rise" style="--d:.7s">{{ __('We can teach you the software in a fortnight.') }}</span>
                                    <em class="hero-line rise" style="--d:.82s">{{ __('The eye takes a little longer.') }}</em>
                                </h1>

                                <div class="stage-row">
                                    <p class="hero-sub rise" style="--d:.95s">{{ __('Free to start. Lessons run in English and Khmer.') }}</p>
                                    <div class="hero-ctas rise" style="--d:1.05s">
                                        @auth
                                        <a href="{{ route('learning.index') }}" class="inline-flex items-center px-6 py-3 font-medium rounded-sm hero-cta is-primary">
                                            {{ __('Continue learning') }}
                                        </a>
                                        @else
                                        <a href="{{ route('register') }}" class="inline-flex items-center px-6 py-3 font-medium rounded-sm hero-cta is-primary">
                                            {{ __('Start learning free') }}
                                        </a>
                                        @endauth
                                        <a href="{{ route('courses') }}" class="inline-flex items-center px-6 py-3 font-medium rounded-sm hero-cta is-secondary">
                                            {{ __('See the courses') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="stage-right">
                            <figure class="stage-card" style="margin:0">
                                <img src="{{ asset('images/piseth-cutout.png') }}" width="777" height="1184"
                                    alt="{{ __('Piseth, founder of Piseth.Design Classroom') }}" decoding="async" fetchpriority="high" draggable="false">
                            </figure>
                        </div>
                    </div>
                </div>
                <div id="fan" class="fan hero-in-up" role="region" aria-roledescription="carousel" aria-label="{{ __('Courses') }}">
                    @foreach ($deck as $i => $card)
                    <a href="{{ $card['url'] }}"
                        class="fan-card"
                        draggable="false"
                        data-title="{{ $card['title'] }}"
                        @if ($i>= $realCount) aria-hidden="true" tabindex="-1" @endif>
                        <span class="fan-face" style="--bg:{{ $card['bg'] }}; --fg:{{ $card['fg'] }}">
                            <span class="fan-head">
                                <span class="fan-mark">{{ $card['mark'] }}</span>
                                @if (!empty($card['sale']))
                                <span class="fan-badge is-sale"><span class="sr-only">{{ __('Regular price') }}</span><s>{{ $card['badge'] }}</s> <span class="sr-only">{{ __('Sale price') }}</span>{{ $card['sale'] }}</span>
                                @elseif (!empty($card['badge']))
                                <span class="fan-badge">{{ $card['badge'] }}</span>
                                @endif
                            </span>
                            <span class="fan-title">{{ $card['title'] }}</span>
                            <span class="fan-meta">{{ $card['meta'] }}</span>
                            <span class="fan-device {{ $i % 2 === 0 ? 'is-phone' : 'is-tab' }}" aria-hidden="true">
                                <span class="dv-screen"><i></i><i></i><i></i><b></b></span>
                            </span>
                        </span>
                    </a>
                    @endforeach

                    <div class="fan-ui">
                        <button type="button" id="fan-prev" class="fan-btn" aria-label="{{ __('Previous course') }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M15 5l-7 7 7 7" />
                            </svg>
                        </button>
                        <span id="fan-caption" class="fan-caption"></span>
                        <button type="button" id="fan-next" class="fan-btn" aria-label="{{ __('Next course') }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Features -->
            <div id="features" class="max-w-6xl px-6 mx-auto pb-28 scroll-mt-20">
                <h2 class="mb-10 text-2xl font-bold font-display" data-reveal>{{ __('What you actually get') }}</h2>
                <div class="grid gap-10 sm:grid-cols-3 sm:gap-8">
                    <div class="pt-6" style="border-top:1px solid var(--mist); --i:1" data-reveal>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mb-4" style="box-sizing:content-box; color:var(--action); background:var(--action-soft); padding:10px; border-radius:12px">
                            <path d="M4 5.5C4 4.67 4.67 4 5.5 4H12v16H5.5A1.5 1.5 0 014 18.5v-13z" />
                            <path d="M20 5.5c0-.83-.67-1.5-1.5-1.5H12v16h6.5a1.5 1.5 0 001.5-1.5v-13z" />
                        </svg>
                        <h3 class="font-semibold mb-1.5">{{ __('Structured courses') }}</h3>
                        <p class="text-sm" style="color:var(--graphite)">{{ __('Step-by-step lessons that build real skills, not just theory.') }}</p>
                    </div>
                    <div class="pt-6" style="border-top:1px solid var(--mist); --i:2" data-reveal>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mb-4" style="box-sizing:content-box; color:var(--action); background:var(--action-soft); padding:10px; border-radius:12px">
                            <path d="M4 5h16v11H9l-4 4V5z" stroke-linejoin="round" />
                            <path d="M8 9h8M8 12.5h5" />
                        </svg>
                        <h3 class="font-semibold mb-1.5">{{ __('Real feedback') }}</h3>
                        <p class="text-sm" style="color:var(--graphite)">{{ __('Get reviewed work and guidance, not just pre-recorded videos.') }}</p>
                    </div>
                    <div class="pt-6" style="border-top:1px solid var(--mist); --i:3" data-reveal>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mb-4" style="box-sizing:content-box; color:var(--action); background:var(--action-soft); padding:10px; border-radius:12px">
                            <circle cx="12" cy="9" r="5" />
                            <path d="M9 13.5L7 21l5-2.5L17 21l-2-7.5" stroke-linejoin="round" />
                        </svg>
                        <h3 class="font-semibold mb-1.5">{{ __('Certificates') }}</h3>
                        <p class="text-sm" style="color:var(--graphite)">{{ __('Finish a course and get a shareable certificate for your portfolio.') }}</p>
                    </div>
                </div>
            </div>

            <!-- Courses -->
            <div id="courses" class="scroll-mt-20" style="background:var(--surface); border-top:1px solid var(--mist); border-bottom:1px solid var(--mist)">
                <div class="max-w-6xl px-6 py-20 mx-auto">
                    <div class="flex items-end justify-between mb-10" data-reveal>
                        <div>
                            <h2 class="mb-2 text-2xl font-bold font-display">{{ __('Courses') }}</h2>
                            <p class="text-sm" style="color:var(--graphite)">{{ __('Start free, or go pro with a full course.') }}</p>
                        </div>
                        <a href="{{ url('/courses') }}" class="items-center hidden gap-1 text-sm font-medium transition-all sm:inline-flex hover:gap-2">
                            {{ __('View all') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                    </div>


                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($courses as $course)
                        <a href="{{ route('courses.show', $course) }}" class="relative block p-6 rounded-sm course-tile" style="background:var(--paper); border:1px solid var(--mist); color:var(--ink); --i:{{ $loop->index % 3 }}" data-reveal>
                            @if ($loop->iteration === 1 || $loop->iteration === 4)
                            <span class="pin" aria-hidden="true"></span>
                            @endif

                            <div class="mb-5 text-3xl font-bold font-display">{{ $course->mark }}</div>

                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-medium tracking-wide uppercase opacity-60">{{ __($course->level) }}</span>
                                @if ($course->isFree())
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-sm" style="color:var(--action-ink); background:var(--action-soft); border:1px solid var(--action)">{{ __('Free') }}</span>
                                @else
                                <x-sale-price :course="$course" class="text-xs font-semibold px-2 py-0.5 rounded-sm border border-current opacity-80" />
                                @endif
                            </div>
                            <h3 class="mb-3 font-medium">{{ $course->localized('title') }}</h3>
                            <span class="text-xs opacity-60">{{ __(':count lessons', ['count' => $course->lessons_count]) }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- How it works -->
            <div class="max-w-6xl px-6 py-24 mx-auto">
                <div data-reveal>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] mb-3" style="color:var(--action-ink)">{{ __('Step-by-step') }}</p>
                    <h2 class="mb-10 text-2xl font-bold font-display">{{ __('How it works') }}</h2>
                </div>
                <ol class="grid gap-6 sm:grid-cols-4">
                    @foreach ([
                    [__('Create an account'), __('Sign up in seconds and open the free starter lessons.')],
                    [__('Select your tool'), __('Photoshop, Illustrator, Figma, InDesign, or Motion Graphics.')],
                    [__('Learn & practise'), __('Watch the sheet, mark complete, keep the notes.')],
                    [__('Get certified'), __('Finish every lesson and stamp a certificate on your profile.')],
                    ] as $i => $step)
                    <li class="pt-6" style="border-top:1px solid var(--mist); --i:{{ $i + 1 }}" data-reveal>
                        <span class="text-xs font-semibold" style="color:var(--action-ink)">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <h3 class="font-semibold mt-2 mb-1.5">{{ $step[0] }}</h3>
                        <p class="text-sm" style="color:var(--graphite)">{{ $step[1] }}</p>
                    </li>
                    @endforeach
                </ol>
            </div>

            <!-- Student work -->
            <div class="scroll-mt-20" style="background:var(--surface); border-top:1px solid var(--mist); border-bottom:1px solid var(--mist)">
                <div class="max-w-6xl px-6 py-20 mx-auto">
                    <div data-reveal>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] mb-3" style="color:var(--action-ink)">{{ __('Real student outcomes') }}</p>
                        <h2 class="mb-10 text-2xl font-bold font-display">{{ __('Student work & projects') }}</h2>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                        ['mark' => 'Ai', 'tag' => __('Illustrator & Branding'), 'title' => __('Phnom Penh Specialty Coffee Branding'), 'name' => 'Vicheka Seng'],
                        ['mark' => 'Ps', 'tag' => __('Photoshop Composite'), 'title' => __('Cambodian Water Festival Social Poster'), 'name' => 'Sokha Chan'],
                        ['mark' => 'Fg', 'tag' => __('Figma UI/UX'), 'title' => __('Fintech Mobile Banking App UI'), 'name' => 'Dara Meas'],
                        ['mark' => 'Id', 'tag' => __('Editorial Print'), 'title' => __('Heritage Khmer Architecture Book Layout'), 'name' => 'Bopha Lim'],
                        ] as $w => $work)
                        <div class="p-5 rounded-sm" style="background:var(--paper); border:1px solid var(--mist); --i:{{ $w + 1 }}" data-reveal>
                            <div class="mb-8 text-3xl font-bold font-display">{{ $work['mark'] }}</div>
                            <p class="mb-1 text-xs tracking-wide uppercase opacity-60">{{ $work['tag'] }}</p>
                            <h3 class="mb-3 text-sm font-medium leading-snug">{{ $work['title'] }}</h3>
                            <p class="text-xs opacity-60">{{ $work['name'] }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- FAQ -->
            <div class="max-w-3xl px-6 py-24 mx-auto" x-data="{ open: 0 }">
                <div data-reveal>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] mb-3 text-center" style="color:var(--action-ink)">{{ __('Answers & guidance') }}</p>
                    <h2 class="mb-10 text-2xl font-bold text-center font-display">{{ __('Frequently asked questions') }}</h2>
                </div>
                @php
                $faqs = [
                [
                'en' => 'Do I need prior graphic design skills to join?',
                'km' => 'តើខ្ញុំត្រូវមានជំនាញ Graphic Design រួចហើយទេ?',
                'a_en' => 'Not at all. Beginner courses (Photoshop Beginner and Graphic Design Fundamentals) start from workspace setup, basic tools, and practical exercises.',
                'a_km' => 'មិនចាំបាច់ទេ។ វគ្គសម្រាប់អ្នកចាប់ផ្តើមចាប់ពីការរៀបចំ workspace រហូតដល់លំហាត់ជាក់ស្តែង។',
                ],
                [
                'en' => 'Can I watch the videos at my own pace?',
                'km' => 'តើខ្ញុំអាចមើលវីដេអូតាមល្បឿនខ្លួនឯងបានទេ?',
                'a_en' => 'Yes. Every lesson stays on your desk. Mark complete when you are ready; progress is saved to your account.',
                'a_km' => 'បាន។ មេរៀននៅលើតុរបស់អ្នក។ គូសថាបានបញ្ចប់ពេលអ្នករួចរាល់។',
                ],
                [
                'en' => 'How do video lessons work with YouTube and Google Drive?',
                'km' => 'តើមេរៀន YouTube និង Google Drive ដំណើរការយ៉ាងដូចម្តេច?',
                'a_en' => 'Instructors paste a YouTube or Drive URL. Students watch the embed in the lesson player — no extra accounts required for playback.',
                'a_km' => 'គ្រូដាក់តំណ YouTube ឬ Drive។ សិស្សមើលក្នុងទំព័រមេរៀនដោយមិនចាំបាច់គណនីផ្សេង។',
                ],
                [
                'en' => 'Will I receive a verified certificate upon completion?',
                'km' => 'តើខ្ញុំនឹងទទួលបានវិញ្ញាបនបត្រទេ?',
                'a_en' => 'Yes. Complete every lesson in a course and a certificate with a unique code is issued on My Learning.',
                'a_km' => 'បាទ/ចាស។ បញ្ចប់គ្រប់មេរៀនក្នុងវគ្គ នឹងមានលេខកូដវិញ្ញាបនបត្រនៅទំព័រ My Learning។',
                ],
                ];
                @endphp
                <div class="space-y-3">
                    @foreach ($faqs as $i => $faq)
                    <div class="rounded-sm" style="border:1px solid var(--mist); --i:{{ $i + 1 }}" data-reveal>
                        <button type="button" class="w-full px-5 py-4 text-left" :aria-expanded="open === {{ $i }}" @click="open = open === {{ $i }} ? null : {{ $i }}">
                            <span class="font-medium">{{ app()->getLocale() === 'km' ? $faq['km'] : $faq['en'] }}</span>
                        </button>
                        <div x-show="open === {{ $i }}" x-cloak
                            x-transition:enter="faq-enter" x-transition:enter-start="faq-from" x-transition:enter-end="faq-to"
                            x-transition:leave="faq-leave" x-transition:leave-start="faq-to" x-transition:leave-end="faq-from"
                            class="px-5 pb-4 text-sm" style="color:var(--graphite)">
                            <p>{{ app()->getLocale() === 'km' ? $faq['a_km'] : $faq['a_en'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Testimonial — types itself out the first time it scrolls into view -->
            @php
            $quote = __('"I went from knowing nothing about design to landing freelance work in three months. The feedback loop here is what actually made the difference."');
            $attribution = __('Sokha Chan — Freelance Designer, Phnom Penh');
            @endphp

            <div class="max-w-3xl px-6 py-24 mx-auto"
                x-data="{
                full: @js($quote),
                shown: '',
                done: false,
                started: false,
                start() {
                    if (this.started) return;
                    this.started = true;

                    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        this.shown = this.full;
                        this.done = true;
                        return;
                    }

                    let i = 0;
                    const tick = () => {
                        this.shown = this.full.slice(0, ++i);
                        if (i < this.full.length) {
                            const c = this.full[i - 1];
                            setTimeout(tick, c === '.' || c === ',' ? 170 : (c === ' ' ? 20 : 28));
                        } else {
                            this.done = true;
                        }
                    };
                    setTimeout(tick, 300);
                }
             }"
                x-init="
                const io = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) { start(); io.disconnect(); }
                    });
                }, { threshold: 0.4 });
                io.observe($el);
             ">
                <div class="pl-6 sm:pl-8" style="border-left:3px solid var(--action)" data-reveal>
                    {{-- Screen readers get the whole quote at once; the animated copy is decorative --}}
                    <p class="sr-only">{{ $quote }} — {{ $attribution }}</p>

                    <p aria-hidden="true" class="font-display text-2xl sm:text-3xl leading-snug mb-6 min-h-[10.5rem] sm:min-h-[9.5rem]"><span x-text="shown"></span><span class="type-caret" :class="done && 'is-done'"></span></p>

                    <div aria-hidden="true" class="text-sm transition-opacity duration-700"
                        :class="done ? 'opacity-60' : 'opacity-0'">{{ $attribution }}</div>
                </div>
            </div>

            <!-- CTA -->
            <div class="max-w-6xl px-6 mx-auto pb-28">
                <div class="relative px-10 py-16 text-center rounded-sm" style="background:var(--ink); color:var(--paper)" data-reveal>
                    <span class="crop-mark tl" style="border-color:var(--action)"></span>
                    <span class="crop-mark tr" style="border-color:var(--action)"></span>
                    <span class="crop-mark bl" style="border-color:var(--action)"></span>
                    <span class="crop-mark br" style="border-color:var(--action)"></span>

                    <h2 class="mb-3 text-3xl font-bold font-display">{{ __('Your first critique could be this week.') }}</h2>
                    <p class="mb-8 opacity-70">{{ __('Join free, pick a course, and submit your first piece for review.') }}</p>
                    <a href="{{ route('register') }}" class="inline-flex items-center px-6 py-3 font-medium transition-opacity rounded-sm hover:opacity-85" style="background:var(--paper); color:var(--ink)">
                        {{ __('Create a free account') }}
                    </a>
                </div>
            </div>

    </div>

    <!-- Footer -->
    <footer style="border-top:1px solid var(--mist)">
        <div class="grid max-w-6xl gap-10 px-6 mx-auto py-14 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="max-w-xs text-sm opacity-60">{{ __('A classroom for designers who want to learn by doing.') }}</p>
            </div>
            <div>
                <h4 class="mb-4 text-xs font-semibold tracking-wide uppercase opacity-60">{{ __('Learn') }}</h4>
                <ul class="space-y-2 text-sm">
                    {{-- "All Courses" left the footer: "Courses" in the menu already goes there. --}}
                    <li><a href="{{ route('courses', ['free' => 1]) }}" class="transition-opacity hover:opacity-70">{{ __('Free Courses') }}</a></li>
                    <li><a href="{{ route('learning.index') }}" class="transition-opacity hover:opacity-70">{{ __('Certificates') }}</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-4 text-xs font-semibold tracking-wide uppercase opacity-60">{{ __('Company') }}</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('about') }}" class="transition-opacity hover:opacity-70">{{ __('About') }}</a></li>
                </ul>
            </div>
            <div>
                <h4 class="mb-4 text-xs font-semibold tracking-wide uppercase opacity-60">{{ __('Follow') }}</h4>
                <ul class="space-y-2 text-sm">
                    <li>
                        <a href="{{ config('social.instagram') }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Instagram (opens in a new tab)') }}" class="transition-opacity hover:opacity-70">Instagram</a>
                    </li>
                    <li>
                        <button type="button"
                            aria-haspopup="dialog"
                            aria-label="{{ __('Telegram (show QR code)') }}"
                            x-data
                            x-on:click="$dispatch('open-telegram-qr')"
                            class="transition-opacity pd-qr-trigger hover:opacity-70">Telegram</button>
                        @include('partials._telegram-qr-dialog')
                    </li>
                    <li>
                        <a href="{{ config('social.facebook') }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Facebook (opens in a new tab)') }}" class="transition-opacity hover:opacity-70">Facebook</a>
                    </li>
                </ul>
            </div>
        </div>
        <div style="border-top:1px solid var(--mist)">
            <div class="flex flex-col items-center justify-between max-w-6xl gap-2 px-6 py-6 mx-auto text-xs sm:flex-row opacity-60">
                <span>&copy; Piseth.Design Classroom. All rights reserved.</span>
                <span>{{ __('Made in Siem Reap, Cambodia') }}</span>
            </div>
        </div>
    </footer>

    <script>
        /* ---------- Scroll reveal ----------
           Each [data-reveal] block rises in once, the first time it reaches the
           viewport. html.js-reveal (set in <head>) is what hid them; if this
           script cannot observe, it removes that class so nothing stays hidden. */
        (function() {
            var root = document.documentElement;
            if (!root.classList.contains('js-reveal')) return;

            try {
                var io = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        /* Reveal on entry, and also catch anything already scrolled
                           clean past: a jump to an anchor, End, or a restored scroll
                           position must never leave a section sitting at opacity 0. */
                        if (!entry.isIntersecting && entry.boundingClientRect.top > 0) return;
                        entry.target.classList.add('is-in');
                        io.unobserve(entry.target);
                    });
                }, {
                    rootMargin: '0px 0px -8% 0px',
                    threshold: 0.12
                });
                document.querySelectorAll('[data-reveal]').forEach(function(el) {
                    io.observe(el);
                });
            } catch (e) {
                root.classList.remove('js-reveal');
            }
        })();
    </script>

    {{--
        HERO FAN
        Every card sits at the same spot and is rotated around a pivot far below it, which
        is what bends the row into an arc. "pos" is the card index that is currently in the
        centre. Changing pos slides the whole fan left (pos goes up) or right (pos goes down).

        pos = base (drag / arrows) + sway (automatic left-right) + scroll (page position)

        On load the cards pop up one after another, centre card first, while the arc opens.
    --}}
    <script>
        (function() {
            var fan = document.getElementById('fan');
            if (!fan) return;

            var cards = [].slice.call(fan.querySelectorAll('.fan-card'));
            var faces = cards.map(function(c) {
                return c.querySelector('.fan-face');
            });
            var N = cards.length;
            if (!N) return;

            var caption = document.getElementById('fan-caption');
            var prevBtn = document.getElementById('fan-prev');
            var nextBtn = document.getElementById('fan-next');
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            /* Coarse pointer (touch) or a small viewport: the visitor's thumb already
               drives the fan directly, so the automatic drift is skipped rather than
               fought against — and it's what lets the render loop below come to a
               full stop instead of running every frame forever on a phone. */
            var coarse = window.matchMedia('(pointer: coarse)').matches || window.matchMedia('(max-width: 640px)').matches;
            var still = reduce || coarse;

            /* ---- Tuning knobs ---- */
            var STEP = 10; // degrees between two neighbouring cards (bigger = wider arc)
            var SWAY = still ? 0 : 2.2; // how many cards the fan drifts each way on its own (0 = no auto movement)
            var PERIOD = 16; // seconds for one full left, right, back cycle
            var SCROLL_K = 0; // the fan no longer turns while the page scrolls — it read as spinning, not following
            var VISIBLE = Math.min(4, N / 2 - 0.5); // cards further than this from the centre are hidden

            /* ---- Entrance: the cards slide up and fade in ---- */
            var POP_DUR = 0.9; // seconds each card takes to rise into place
            var POP_STAGGER = 0.09; // delay between cards, centre first and then outward
            var POP_START = 0.1; // seconds before the first card rises
            var POP_RISE = 0.34; // how far below its spot a card starts, in card heights
            var SPREAD_TIME = 1.4; // seconds the arc takes to open up
            var INTRO_END = POP_START + VISIBLE * POP_STAGGER + POP_DUR;
            /* On first load the cards come last in the hero's sequence: after the
               header stage (see "HEADER STAGE" in the CSS). If the
               fan only scrolls into view later, it starts straight away. */
            var HERO_DELAY = reduce ? 0 : 1.25 + (document.documentElement.classList.contains('js-intro') ? 2.05 : 0);
            var loadedAt = performance.now();

            /* ---- State ---- */
            var base = 0; // where the visitor parked the fan
            var target = null; // set while an arrow button is animating to the next card
            var velocity = 0; // leftover speed after a drag, in cards per second
            var clock = 0; // drives the sway
            var rate = 0; // 1 = sway running, 0 = paused (hover / drag / intro); eases up once the pop-up is done
            var paused = false;
            var dragging = false;
            var pointer = null;
            var suppressClick = false;
            var hovered = -1;
            var scrollNow = 0;
            var scrollGoal = 0;
            var introStarted = false;
            var introClock = 0;
            var delay = [];
            var popDone = [];
            var rise = 200; // pixels, measured from the card height
            var spacing = 200; // pixels between two neighbouring cards at the centre
            var shown = cards.map(function() {
                return false;
            });
            var lastNear = -1;
            var raf = 0;
            var last = 0;
            var onScreen = true;

            function clamp(v, a, b) {
                return Math.max(a, Math.min(b, v));
            }

            /* Fast start, long soft landing: the "slide up" of the entrance */
            function outQuart(t) {
                var u = 1 - t;
                return 1 - u * u * u * u;
            }

            function beginIntro() {
                if (introStarted) return;
                introStarted = true;
                /* A negative clock holds the cards back until the hero sequence reaches them */
                introClock = reduce ? 99 : -Math.max(0, HERO_DELAY - (performance.now() - loadedAt) / 1000);
                start();
            }

            function wrap(d) {
                return d - N * Math.floor((d + N / 2) / N);
            }

            function measure() {
                var w = cards[0].offsetWidth;
                var h = cards[0].offsetHeight;
                /* pivot sits 3.6 card-widths below the card, matching --pivot in the CSS */
                spacing = (w * 3.6 + h / 2) * STEP * Math.PI / 180;
                rise = h * POP_RISE;
                start();
            }

            function readScroll() {
                scrollGoal = clamp(window.scrollY, 0, 1200) * SCROLL_K;
                if (SCROLL_K) start();
            }

            function planPops() {
                /* Cards nearest the centre pop first, the outer ones follow */
                for (var i = 0; i < N; i++) {
                    var a = Math.abs(wrap(i - (base + scrollNow)));
                    delay[i] = POP_START + Math.min(a, VISIBLE) * POP_STAGGER;
                    popDone[i] = false;
                }
            }

            function render() {
                var pos = base + SWAY * Math.sin(clock * 2 * Math.PI / PERIOD) + scrollNow;
                var spreadT = clamp((introClock - 0.1) / SPREAD_TIME, 0, 1);
                var spread = 0.3 + 0.7 * (1 - Math.pow(1 - spreadT, 3));
                var nearest = 0;
                var nearestAbs = 1e9;

                for (var i = 0; i < N; i++) {
                    var d = wrap(i - pos);
                    var a = Math.abs(d);
                    var el = cards[i];
                    var p = introStarted ? clamp((introClock - delay[i]) / POP_DUR, 0, 1) : 0;

                    if (a > VISIBLE || p <= 0) {
                        if (shown[i]) {
                            el.style.visibility = 'hidden';
                            shown[i] = false;
                        }
                        continue;
                    }
                    if (!shown[i]) {
                        el.style.visibility = 'visible';
                        shown[i] = true;
                    }

                    el.style.transform = 'rotate(' + (d * STEP * spread).toFixed(3) + 'deg)';
                    el.style.opacity = (clamp((VISIBLE - a) / 1.1, 0, 1) * clamp(p * 1.7, 0, 1)).toFixed(3);
                    el.style.zIndex = (i === hovered) ? 400 : 200 - Math.round(a * 10);

                    if (p < 1) {
                        var eb = outQuart(p);
                        faces[i].style.transform = 'translateY(' + ((1 - eb) * rise).toFixed(1) + 'px) scale(' + (0.94 + 0.06 * eb).toFixed(3) + ')';
                        popDone[i] = false;
                    } else if (!popDone[i]) {
                        faces[i].style.transform = ''; /* hand control back to the CSS hover effect */
                        popDone[i] = true;
                    }

                    if (a < nearestAbs) {
                        nearestAbs = a;
                        nearest = i;
                    }
                }

                if (nearest !== lastNear) {
                    lastNear = nearest;
                    if (caption) caption.textContent = cards[nearest].getAttribute('data-title') || '';
                }
            }

            function frame(now) {
                raf = 0;
                var dt = Math.min(0.05, (now - last) / 1000);
                last = now;

                if (introStarted) introClock += dt;

                if (!dragging) {
                    if (target !== null) {
                        base += (target - base) * (1 - Math.exp(-dt * 7));
                        if (Math.abs(target - base) < 0.002) {
                            base = target;
                            target = null;
                        }
                    } else if (Math.abs(velocity) > 0.01) {
                        base += velocity * dt;
                        velocity *= Math.exp(-dt * 3.2);
                    } else {
                        velocity = 0;
                    }
                }

                /* Sway waits for the pop-up to finish, and eases to a stop while hovering or dragging */
                var swayReady = introClock > INTRO_END - 0.2;
                var wantRate = (paused || dragging || !swayReady) ? 0 : 1;
                rate += (wantRate - rate) * (1 - Math.exp(-dt * 4));
                clock += dt * rate;

                scrollNow += (scrollGoal - scrollNow) * (1 - Math.exp(-dt * 5));

                render();

                /* Nothing left to animate: stop rescheduling instead of redrawing forever.
                   Whatever would move the fan again (a drag, an arrow click, sway easing
                   back in after a hover, a scroll change) calls start() to pick it back up. */
                var atRest = !dragging && target === null && Math.abs(velocity) < 0.001 &&
                    rate < 0.001 && Math.abs(scrollGoal - scrollNow) < 0.01 &&
                    (!introStarted || introClock > INTRO_END + 0.3);

                if (onScreen && !document.hidden && !atRest) raf = requestAnimationFrame(frame);
            }

            function start() {
                if (raf) return;
                last = performance.now();
                raf = requestAnimationFrame(frame);
            }

            /* ---- Arrow buttons and keyboard ---- */
            function go(dir) {
                velocity = 0;
                target = Math.round(target !== null ? target : base) + dir;
                start();
            }
            if (prevBtn) prevBtn.addEventListener('click', function() {
                go(-1);
            });
            if (nextBtn) nextBtn.addEventListener('click', function() {
                go(1);
            });
            fan.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowLeft') {
                    go(-1);
                } else if (e.key === 'ArrowRight') {
                    go(1);
                }
            });

            /* ---- Hovered card comes to the front ---- */
            cards.forEach(function(el, i) {
                el.addEventListener('pointerenter', function(e) {
                    if (e.pointerType === 'mouse') hovered = i;
                });
                el.addEventListener('pointerleave', function() {
                    if (hovered === i) hovered = -1;
                });
            });

            /* ---- Drag / swipe ---- */
            fan.addEventListener('pointerdown', function(e) {
                if (e.pointerType === 'mouse' && e.button !== 0) return;
                if (e.target.closest && e.target.closest('.fan-ui')) return;
                pointer = {
                    id: e.pointerId,
                    x: e.clientX,
                    base: base,
                    lastX: e.clientX,
                    lastT: performance.now(),
                    moved: false
                };
                target = null;
                velocity = 0;
            });

            fan.addEventListener('pointermove', function(e) {
                if (!pointer || e.pointerId !== pointer.id) return;
                var dx = e.clientX - pointer.x;

                if (!pointer.moved && Math.abs(dx) > 6) {
                    pointer.moved = true;
                    dragging = true;
                    fan.classList.add('is-dragging');
                    start();
                    try {
                        fan.setPointerCapture(e.pointerId);
                    } catch (err) {}
                }
                if (!pointer.moved) return;

                base = pointer.base - dx / spacing;

                var now = performance.now();
                var dtm = (now - pointer.lastT) / 1000;
                if (dtm > 0) {
                    var v = -(e.clientX - pointer.lastX) / spacing / dtm;
                    velocity = velocity * 0.6 + v * 0.4;
                }
                pointer.lastX = e.clientX;
                pointer.lastT = now;
            });

            function endDrag(e) {
                if (!pointer || (e && e.pointerId !== pointer.id)) return;
                if (pointer.moved) {
                    suppressClick = true; /* a drag must not open the course link under the cursor */
                    setTimeout(function() {
                        suppressClick = false;
                    }, 60);
                    if (performance.now() - pointer.lastT > 80) velocity = 0;
                    velocity = clamp(velocity, -6, 6);
                }
                dragging = false;
                pointer = null;
                fan.classList.remove('is-dragging');
            }
            fan.addEventListener('pointerup', endDrag);
            fan.addEventListener('pointercancel', endDrag);
            fan.addEventListener('lostpointercapture', endDrag);

            fan.addEventListener('click', function(e) {
                if (suppressClick) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }, true);

            /* ---- Pause the automatic sway while someone is looking closely ---- */
            fan.addEventListener('pointerenter', function(e) {
                if (e.pointerType === 'mouse') paused = true;
            });
            fan.addEventListener('pointerleave', function() {
                paused = false;
                start();
            });
            fan.addEventListener('focusin', function() {
                paused = true;
            });
            fan.addEventListener('focusout', function() {
                paused = false;
                start();
            });

            /* ---- Page scroll nudges the fan, resize re-measures ---- */
            window.addEventListener('scroll', readScroll, {
                passive: true
            });
            window.addEventListener('resize', measure, {
                passive: true
            });
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) start();
            });

            /* ---- The pop-up plays the first time the fan is on screen; the loop stops while it is not ---- */
            if ('IntersectionObserver' in window) {
                new IntersectionObserver(function(entries) {
                    onScreen = entries[0].isIntersecting;
                    if (onScreen) {
                        beginIntro();
                        start();
                    }
                }, {
                    threshold: 0.15
                }).observe(fan);
            }
            if (reduce || !('IntersectionObserver' in window)) beginIntro();

            measure();
            readScroll();
            scrollNow = scrollGoal;
            planPops();
            render();
            start();
        })();
    </script>


    <script>
        /* Header stage behaviour. Everything here is decoration: with no
           JavaScript the stage is complete, just still. Skipped entirely for
           reduced motion, apart from fitting the word to its column.
             fit      sizes the name so it stays clear of the portrait's face
                      (0.68 of the layout width on desktop; lower = smaller)
             pointer  writes --mx / --my (eased) for tilt and parallax
             scroll   writes --sy (0..1 while the stage leaves the screen) */
        (function() {
            var stage = document.getElementById('stage');
            var word = document.getElementById('stage-word');
            if (!stage || !word) return;

            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var fine = window.matchMedia('(pointer: fine)').matches;

            /* ---- fit ---- */
            function fit() {
                var inner = stage.querySelector('.stage-inner');
                var narrow = window.matchMedia('(max-width: 900px)').matches;
                var avail = inner.clientWidth - 48;
                var left = stage.querySelector('.stage-left').getBoundingClientRect().width;
                var target = narrow ? avail : Math.min(avail * 0.68, left + 40);
                word.style.fontSize = '100px';
                var w = word.getBoundingClientRect().width;
                if (w > 0) word.style.fontSize = Math.min(100 * target / w, 340) + 'px';
            }
            fit();
            if (document.fonts && document.fonts.ready) document.fonts.ready.then(fit);
            var rt;
            window.addEventListener('resize', function() {
                clearTimeout(rt);
                rt = setTimeout(fit, 80);
            });

            if (reduce) return;

            /* ---- pointer + scroll ---- */
            var tx = 0,
                ty = 0,
                cx = 0,
                cy = 0,
                raf = 0,
                sraf = 0;

            function tick() {
                cx += (tx - cx) * 0.075;
                cy += (ty - cy) * 0.075;
                stage.style.setProperty('--mx', cx.toFixed(3));
                stage.style.setProperty('--my', cy.toFixed(3));
                raf = (Math.abs(tx - cx) > 0.002 || Math.abs(ty - cy) > 0.002) ? requestAnimationFrame(tick) : 0;
            }

            function kick() {
                if (!raf) raf = requestAnimationFrame(tick);
            }

            function scrollUpdate() {
                sraf = 0;
                var r = stage.getBoundingClientRect();
                stage.style.setProperty('--sy', Math.min(1, Math.max(0, -r.top / Math.max(1, r.height))).toFixed(3));
            }

            if (fine) {
                stage.addEventListener('pointermove', function(e) {
                    var r = stage.getBoundingClientRect();
                    var x = e.clientX - r.left,
                        y = e.clientY - r.top;
                    tx = (x / r.width - 0.5) * 2;
                    ty = (y / r.height - 0.5) * 2;
                    kick();
                });
                stage.addEventListener('pointerleave', function() {
                    tx = 0;
                    ty = 0;
                    kick();
                });
            }

            window.addEventListener('scroll', function() {
                if (!sraf) sraf = requestAnimationFrame(scrollUpdate);
            }, {
                passive: true
            });
        })();
    </script>
</body>

</html>