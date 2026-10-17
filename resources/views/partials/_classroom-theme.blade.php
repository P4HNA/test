{{-- Shared paper/proof tokens and type. Extra page CSS can follow this include. --}}
<script>
    (function() {
        /* Same first theme as the homepage (dark), so going from the homepage
           to a course never flips the page; the toggle's choice always wins. */
        var saved = null;
        try { saved = localStorage.getItem('pd-theme'); } catch (e) {}
        var theme = saved || 'dark';
        if (theme === 'dark') document.documentElement.classList.add('dark');
    })();
</script>
@include('partials._head-shared')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
{{-- Kantumruy Pro is the Khmer face the whole site now sets (see the
     font-family rules below). Archivo carries 700 and 800 as well: the top bar
     sets its wordmark in 800, and without that weight the browser fakes it --
     the lockup comes out a few pixels wider and the nav row lands in a
     different place on classroom pages than on the homepage. --}}
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800&family=Archivo+Expanded:wght@700;800;900&family=Caveat:wght@500;600&family=Kantumruy+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
    :root {
        --paper: #FFFFFF;
        --surface: #F4F4F6;
        --ink: #0A0A0A;
        --graphite: #5E5E5E;
        --mist: #E6E6EA;
        /* Accent: the homepage's blue. --action fills buttons (white text on
           it is 4.8:1); --action-ink is for blue text and links (4.8:1 or more
           on paper and surface). --redline is the older name for the accent,
           kept so every page that used it follows along. */
        --action: #0866FF;
        --action-ink: #0062EB;
        --action-soft: #EAF4FF;
        --danger: #DC2626;
        --redline: var(--action-ink);
        --blue: #0866FF;
        /* Form-error text: red, 4.5:1 or more on --paper/--surface. */
        --error: #B42318;
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
        --redline: var(--action-ink);
        --blue: #3D85FF;
        --error: #FF8A73;
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

    /* ============================================================
       THEME SWITCH ANIMATION
       A circle of the incoming theme's paper colour expands from the
       toggle button and washes over the whole page. Same effect as
       the homepage, shared here so every page matches it.
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

    :focus-visible {
        outline: 2px solid var(--redline);
        outline-offset: 3px;
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
        border-top: 1.5px solid var(--redline);
        border-left: 1.5px solid var(--redline);
    }

    .crop-mark.tr {
        top: -8px;
        right: -8px;
        border-top: 1.5px solid var(--redline);
        border-right: 1.5px solid var(--redline);
    }

    .crop-mark.bl {
        bottom: -8px;
        left: -8px;
        border-bottom: 1.5px solid var(--redline);
        border-left: 1.5px solid var(--redline);
    }

    .crop-mark.br {
        bottom: -8px;
        right: -8px;
        border-bottom: 1.5px solid var(--redline);
        border-right: 1.5px solid var(--redline);
    }

    .pin {
        position: absolute;
        top: -9px;
        left: 24px;
        width: 13px;
        height: 13px;
        border-radius: 50%;
        background: var(--redline);
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

    /* The card inverts on mouse hover and on keyboard focus. Its text takes the
       card's colour (color: inherit), so nothing inside vanishes when it flips. */
    @media (hover: hover) and (pointer: fine) {
        .course-tile:hover {
            background: var(--ink) !important;
            color: var(--paper) !important;
            border-color: transparent !important;
            transform: rotate(0deg) translateY(-3px);
        }
    }

    .course-tile:focus-visible {
        background: var(--ink) !important;
        color: var(--paper) !important;
        border-color: transparent !important;
        transform: rotate(0deg);
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
        background: var(--ink);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .3s ease;
    }

    .nav-link:hover::after,
    .nav-link.is-active::after,
    .nav-link:focus-visible::after {
        transform: scaleX(1);
    }

    .pd-sheet {
        background: var(--paper);
        border: 1px solid var(--mist);
    }

    /* Avatar styling moved to resources/css/app.css: it is used by the top bar,
       which the homepage also renders, and the homepage does not include this
       partial -- so keeping it here left the avatar square on / only. */

    .pd-btn-ink {
        background: var(--ink);
        color: var(--paper);
    }

    .pd-btn-ink:hover {
        opacity: .85;
    }

    .pd-input {
        width: 100%;
        background: transparent;
        border: 0;
        border-bottom: 1px solid var(--mist);
        padding: .5rem 0;
        color: var(--ink);
    }

    .pd-input:focus {
        outline: none;
        border-bottom-color: var(--redline);
    }

    .pd-input[aria-invalid="true"] {
        border-bottom: 2px solid var(--error);
    }

    .pd-field-error {
        color: var(--error);
    }

    .pd-btn-wait:disabled {
        opacity: .55;
        cursor: not-allowed;
    }

    @media (prefers-reduced-motion: reduce) {
        * {
            transition: none !important;
            animation: none !important;
        }
    }
</style>