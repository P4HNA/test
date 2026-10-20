<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Piseth.Design Classroom</title>
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
    {{-- Archivo + Kantumruy Pro: the shared top bar. Inter, Noto Sans Khmer, JetBrains Mono: this page. --}}
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800&family=Archivo+Expanded:wght@700;800;900&family=Kantumruy+Pro:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&family=Noto+Sans+Khmer:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* ------------------------------------------------------------------
           Variables used by the shared top bar, promotion bar and Telegram
           dialog (same values as every other page, so they look identical).
           ------------------------------------------------------------------ */
        :root {
            --paper: #FFFFFF;
            --surface: #F4F4F6;
            --ink: #0A0A0A;
            --graphite: #5E5E5E;
            --mist: #E6E6EA;
            --action: #0866FF;
            --action-ink: #0062EB;
            --action-soft: #EAF4FF;
            --danger: #DC2626;
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
            transition: background-color .45s ease, color .45s ease;
        }

        .font-display { font-family: 'Archivo Expanded', 'Kantumruy Pro', system-ui, sans-serif; }
        [x-cloak] { display: none !important; }

        /* Theme switch: a circle of the incoming colour washes over the page. */
        .theme-ripple {
            position: fixed; z-index: 90; width: 0; height: 0; border-radius: 50%;
            pointer-events: none; transform: translate(-50%, -50%);
            animation: theme-ripple-grow .62s cubic-bezier(.4, 0, .2, 1) forwards;
        }
        @keyframes theme-ripple-grow {
            0% { width: 0; height: 0; opacity: 1; }
            62% { opacity: 1; }
            100% { width: var(--r); height: var(--r); opacity: 0; }
        }

        /* On the homepage the language / theme switch lives in the floating
           pill at the bottom-right (.hm-prefs), so the copy in the shared top
           bar is hidden here only. Every other page keeps it. */
        .hm-body #site-nav .sn-cluster { display: none !important; }

        /* ==================================================================
           HOME — design tokens (light, then dark)
           ================================================================== */
        .hm-body {
            --h-bg: #F5F7FA;
            --h-card: #FFFFFF;
            --h-subtle: #F8FAFC;
            --h-chip: #F1F5F9;
            --h-text: #1C1C1E;
            --h-muted: #65676B;
            --h-quote: #3A3B3F;
            --h-line: #E4E6EB;
            --h-line-soft: rgba(226, 232, 240, .8);
            --h-blue: #1877F2;
            --h-sky: #4DA3FF;
            --h-sky-hover: #3892F3;
            --h-navy: #0071E3;
            --h-shadow: 0 1px 2px rgba(0, 0, 0, .05);
            --h-avatar-a: #E2E8F0;
            --h-avatar-b: #CBD5E1;
            --h-avatar-fg: #FFFFFF;
            background: var(--h-bg);
            color: var(--h-text);
        }

        html.dark .hm-body {
            --h-bg: #0B0B0D;
            --h-card: #1C1C1E;
            --h-subtle: #151B23;
            --h-chip: #151B23;
            --h-text: #F5F5F7;
            --h-muted: #A1A1A6;
            --h-quote: #C7C7CC;
            --h-line: #2E3034;
            --h-line-soft: #222936;
            --h-avatar-a: #2A313C;
            --h-avatar-b: #1A2029;
            --h-avatar-fg: #4B5563;
        }

        .hm-main, .hm-footer, .hm-prefs, .hm-modal, .hm-toast {
            font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Noto Sans Khmer', sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .hm-km { font-family: 'Noto Sans Khmer', Inter, sans-serif; }
        .hm-mono { font-family: 'JetBrains Mono', 'Noto Sans Khmer', ui-monospace, SFMono-Regular, Menlo, monospace; }
        .hm-wrap { max-width: 80rem; margin: 0 auto; padding: 0 1rem; }
        @media (min-width: 640px) { .hm-wrap { padding: 0 1.5rem; } }
        @media (min-width: 1024px) { .hm-wrap { padding: 0 2rem; } }
        .hm-section { padding: 3rem 0; scroll-margin-top: 5rem; }
        @media (min-width: 640px) { .hm-section { padding: 4rem 0; } }
        .hm-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .18em; color: var(--h-sky); margin: 0; }
        @media (min-width: 640px) { .hm-label { font-size: 12px; } }
        .hm-h2 { margin: .75rem 0 0; font-size: 1.5rem; font-weight: 900; letter-spacing: -.02em; line-height: 1.2; color: var(--h-text); }
        @media (min-width: 640px) { .hm-h2 { font-size: 2.25rem; } }
        html:lang(km) .hm-h2 { line-height: 1.45; }
        .hm-desc { margin: .75rem 0 0; font-size: .875rem; line-height: 1.65; color: var(--h-muted); max-width: 42rem; }
        @media (min-width: 640px) { .hm-desc { font-size: 1rem; } }
        .hm-head { display: flex; flex-direction: column; gap: 1.5rem; margin-bottom: 2rem; }
        @media (min-width: 1024px) { .hm-head { flex-direction: row; align-items: flex-end; justify-content: space-between; margin-bottom: 2.5rem; } }
        .hm-head > div:first-child { min-width: 0; }
        .hm-ico { width: 1.25rem; height: 1.25rem; flex-shrink: 0; }
        .hm-ico-sm { width: 1rem; height: 1rem; flex-shrink: 0; }
        .hm-ico-xs { width: .875rem; height: .875rem; flex-shrink: 0; }
        .hm-sr { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }

        /* ---------- blank profile ---------- */
        .hm-avatar { position: relative; flex-shrink: 0; overflow: hidden; display: flex; align-items: flex-end; justify-content: center; border-radius: 9999px; background: linear-gradient(to bottom, var(--h-avatar-a), var(--h-avatar-b)); color: var(--h-avatar-fg); }
        .hm-avatar svg { width: 82%; height: 82%; }
        .hm-avatar--square { border-radius: 1rem; }

        /* ---------- buttons ---------- */
        .hm-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 46px; padding: .75rem 1.5rem; border-radius: .75rem; font-size: .875rem; font-weight: 600; text-decoration: none; transition: all .2s ease; cursor: pointer; }
        .hm-btn-primary { color: #fff; background: var(--h-sky); box-shadow: 0 4px 6px -1px rgba(77, 163, 255, .2); }
        .hm-btn-primary:hover { background: var(--h-sky-hover); }
        .hm-btn-ghost { color: #1E293B; background: #F1F5F9; border: 1px solid rgba(226, 232, 240, .8); }
        .hm-btn-ghost:hover { background: #E2E8F0; }
        html.dark .hm-btn-ghost { color: #E2E8F0; background: #151B23; border-color: #222936; }
        html.dark .hm-btn-ghost:hover { background: #1C232D; }
        .hm-arrow { transition: transform .2s ease; }
        .hm-btn:hover .hm-arrow, .hm-link:hover .hm-arrow { transform: translateX(4px); }
        .hm-link { display: inline-flex; align-items: center; gap: .25rem; font-size: .875rem; font-weight: 600; color: var(--h-blue); text-decoration: none; }
        html.dark .hm-link { color: var(--h-sky); }

        /* ==================================================================
           HERO
           ================================================================== */
        .hm-hero { position: relative; overflow: hidden; padding: 2rem 0 3rem; }
        @media (min-width: 640px) { .hm-hero { padding-top: 3.5rem; } }
        @media (min-width: 1024px) { .hm-hero { padding-top: 4.5rem; } }
        .hm-hero-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 2.5rem; align-items: center; }
        @media (min-width: 1024px) { .hm-hero-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 3rem; } }
        .hm-hero-copy > * + * { margin-top: 1.5rem; }
        .hm-status { display: flex; align-items: center; gap: .5rem; font-size: 12px; font-weight: 500; color: var(--h-sky); margin: 0; }
        .hm-dot { position: relative; display: inline-flex; width: .5rem; height: .5rem; flex-shrink: 0; }
        .hm-dot::before, .hm-dot::after { content: ""; position: absolute; inset: 0; border-radius: 9999px; background: var(--h-sky); }
        .hm-dot::before { animation: hm-ping 1.6s cubic-bezier(0, 0, .2, 1) infinite; opacity: .6; }
        .hm-h1 { margin: 0; font-size: 1.875rem; font-weight: 900; letter-spacing: -.025em; line-height: 1.15; color: var(--h-text); }
        @media (min-width: 640px) { .hm-h1 { font-size: 3rem; } }
        @media (min-width: 1024px) { .hm-h1 { font-size: 3.75rem; } }
        html:lang(km) .hm-h1 { line-height: 1.4; }
        .hm-h1 span { color: var(--h-sky); }
        .hm-lead { margin: 0; max-width: 36rem; font-size: 1rem; line-height: 1.65; color: var(--h-muted); }
        @media (min-width: 640px) { .hm-lead { font-size: 1.125rem; } }
        html:lang(km) .hm-lead { line-height: 2; }
        .hm-ctas { display: flex; flex-direction: column; gap: .75rem; padding-top: .5rem; }
        @media (min-width: 640px) { .hm-ctas { flex-direction: row; align-items: center; } }

        /* ---------- design window ---------- */
        .hm-art { position: relative; }
        @media (min-width: 1024px) { .hm-art { margin: 4rem 0; } }
        .hm-glow { position: absolute; border-radius: 9999px; filter: blur(64px); pointer-events: none; }
        .hm-glow-a { width: 18rem; height: 18rem; top: -2rem; right: -2rem; background: rgba(24, 119, 242, .15); }
        .hm-glow-b { width: 14rem; height: 14rem; bottom: -2.5rem; left: -2.5rem; background: rgba(34, 211, 238, .1); }
        .hm-window { position: relative; background: var(--h-card); border: 1px solid var(--h-line); border-radius: 1rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, .1), 0 8px 10px -6px rgba(0, 0, 0, .1); overflow: hidden; }
        .hm-titlebar { display: flex; align-items: center; gap: .75rem; padding: .75rem 1rem; border-bottom: 1px solid var(--h-line); background: var(--h-subtle); }
        .hm-lights { display: flex; gap: .375rem; flex-shrink: 0; }
        .hm-lights i { width: .75rem; height: .75rem; border-radius: 9999px; display: block; }
        .hm-file { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 12px; color: var(--h-muted); }
        .hm-badge { flex-shrink: 0; display: inline-flex; align-items: center; gap: .375rem; padding: .25rem .625rem; border-radius: .5rem; background: var(--h-card); border: 1px solid var(--h-line); font-size: 12px; font-weight: 600; color: var(--h-blue); }
        html.dark .hm-badge { color: var(--h-sky); }
        .hm-canvas-pad { padding: .75rem; }
        @media (min-width: 640px) { .hm-canvas-pad { padding: 1rem; } }
        .hm-canvas { position: relative; overflow: hidden; background: #111317; border: 1px solid #2E3034; border-radius: .75rem; padding: 1.5rem 1.25rem 1rem; }
        @media (min-width: 640px) { .hm-canvas { padding-left: 1.5rem; padding-right: 1.5rem; } }
        .hm-grid-lines { position: absolute; inset: 0; pointer-events: none; background-image: linear-gradient(to right, #ffffff08 1px, transparent 1px), linear-gradient(to bottom, #ffffff08 1px, transparent 1px); background-size: 24px 24px; }
        .hm-canvas-grid { position: relative; display: grid; grid-template-columns: minmax(0, 1fr); align-items: center; gap: .5rem; }
        @media (min-width: 1024px) { .hm-canvas-grid { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 1rem; } }
        .hm-canvas-copy { text-align: center; }
        @media (min-width: 1024px) { .hm-canvas-copy { text-align: left; } }
        .hm-canvas-title { margin: 0; font-size: 1.875rem; font-weight: 900; letter-spacing: -.025em; line-height: 1.05; color: #fff; }
        @media (min-width: 640px) { .hm-canvas-title { font-size: 2.25rem; } }
        .hm-canvas-sub { margin: .5rem 0 0; font-size: .875rem; color: #CBD5E1; }
        /* These two are English in both languages, so they keep their tight Latin spacing. */
        html:lang(km) .hm-body .hm-canvas-title { line-height: 1.05; }
        html:lang(km) .hm-body .hm-canvas-sub { line-height: 1.5; }
        .hm-tags { margin-top: .75rem; display: flex; flex-wrap: wrap; justify-content: center; gap: .375rem; }
        @media (min-width: 1024px) { .hm-tags { flex-direction: column; align-items: flex-start; } }
        .hm-tag { padding: .125rem .5rem; border-radius: .375rem; font-size: 11px; }
        .hm-canvas-foot { position: relative; margin-top: .25rem; padding-top: .75rem; border-top: 1px solid rgba(255, 255, 255, .05); display: flex; justify-content: flex-end; }
        .hm-canvas-foot a { color: var(--h-sky); }
        .hm-window-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem .75rem; padding: .875rem 1rem; border-top: 1px solid var(--h-line); }
        @media (min-width: 640px) { .hm-window-foot { padding: .875rem 1.25rem; } }
        .hm-py { flex-shrink: 0; width: 2rem; height: 2rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; color: var(--h-blue); background: rgba(24, 119, 242, .1); }
        .hm-window-foot p { margin: 0; font-size: .875rem; color: var(--h-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .hm-window-foot b { color: var(--h-text); font-weight: 600; }

        /* ---------- 3D stage ---------- */
        .hm-stage { position: relative; display: flex; align-items: center; justify-content: center; width: 100%; height: 15rem; perspective: 1000px; }
        @media (min-width: 640px) { .hm-stage { height: 16rem; } }
        .hm-stage-glow { position: absolute; inset: 1.5rem; border-radius: 9999px; filter: blur(40px); background: linear-gradient(to top right, rgba(24, 119, 242, .35), rgba(99, 102, 241, .25), rgba(34, 211, 238, .25)); animation: pulse-glow-3d 4s ease-in-out infinite; }
        .hm-star { position: absolute; width: 4px; height: 4px; border-radius: 9999px; background: #A5F3FC; box-shadow: 0 0 6px #67E8F9; animation: hm-twinkle 3s ease-in-out infinite; }
        .hm-ring-tilt { position: absolute; width: 14rem; height: 14rem; border-radius: 9999px; border: 1px solid rgba(129, 140, 248, .2); display: flex; justify-content: center; align-items: flex-start; animation: hm-orbit-reverse 26s linear infinite; }
        .hm-ring-tilt i { margin-top: -3px; width: 6px; height: 6px; border-radius: 9999px; background: #A5B4FC; box-shadow: 0 0 10px #A5B4FC; }
        .hm-orbit { position: absolute; width: 12rem; height: 12rem; border-radius: 9999px; border: 1px dashed rgba(77, 163, 255, .4); display: flex; justify-content: center; align-items: flex-start; animation: float-3d-orbit 18s linear infinite; }
        .hm-orbit i { margin-top: -6px; width: 12px; height: 12px; border-radius: 9999px; background: #22D3EE; box-shadow: 0 0 14px #22D3EE; }
        .hm-scene { position: relative; width: 14rem; height: 14rem; display: flex; align-items: center; justify-content: center; transform-style: preserve-3d; }
        .hm-cube-shadow { position: absolute; bottom: 1.75rem; width: 6rem; height: 1rem; border-radius: 9999px; background: rgba(24, 119, 242, .45); filter: blur(12px); animation: hm-shadow-breathe 6s ease-in-out infinite; }
        .hm-cube { position: relative; width: 120px; height: 120px; border-radius: 24px; overflow: hidden; border: 1px solid rgba(255, 255, 255, .15); background: linear-gradient(to bottom right, rgba(43, 58, 92, .95), #151C2B, #0A0D13); box-shadow: 0 24px 50px -12px rgba(24, 119, 242, .55), inset 0 1px 0 rgba(255, 255, 255, .18); transform-style: preserve-3d; animation: float-3d-main 6s ease-in-out infinite; }
        .hm-cube-grid { position: absolute; inset: 0; background-image: linear-gradient(to right, #ffffff12 1px, transparent 1px), linear-gradient(to bottom, #ffffff12 1px, transparent 1px); background-size: 14px 14px; }
        .hm-cube-cool { position: absolute; inset: 0; background: radial-gradient(circle at 30% 20%, rgba(77, 163, 255, .35), transparent 60%); }
        .hm-cube-warm { position: absolute; inset: 0; background: radial-gradient(circle at 14% 10%, rgba(255, 220, 110, .6), rgba(255, 190, 60, .22) 38%, transparent 68%); animation: hm-warm 3s ease-in-out infinite; }
        .hm-cube-rim { position: absolute; inset: 0; border-radius: 24px; box-shadow: inset 3px 3px 14px rgba(255, 214, 90, .45); animation: hm-warm 3s ease-in-out infinite; }
        .hm-cube-sheen { position: absolute; top: 0; bottom: 0; left: 0; width: 33%; background: linear-gradient(to right, transparent, rgba(255, 255, 255, .25), transparent); animation: hm-sheen 4.8s ease-in-out 1s infinite; }
        .hm-cube-face { position: relative; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .5rem; }
        .hm-cube-face svg { width: 2.75rem; height: 2.75rem; color: #6CB6FF; filter: drop-shadow(0 0 12px rgba(77, 163, 255, .85)); }
        .hm-cube-face span { font-size: 10px; font-weight: 700; letter-spacing: .18em; color: #A5F3FC; text-shadow: 0 0 10px rgba(34, 211, 238, .8), 0 0 14px rgba(255, 214, 90, .45); }
        .hm-sat { position: absolute; transform-style: preserve-3d; }
        .hm-sat-sun { top: 1.5rem; left: 1.5rem; z-index: 10; animation: float-3d-icon-b 3.8s ease-in-out .2s infinite; }
        .hm-sat-figma { top: .5rem; right: 1rem; animation: float-3d-icon-a 4.2s ease-in-out infinite; }
        .hm-sat-ps { bottom: .75rem; left: 1rem; animation: float-3d-icon-b 4.4s ease-in-out .6s infinite; }
        .hm-sat-ai { bottom: 1.25rem; right: 1.5rem; animation: float-3d-icon-c 5.4s ease-in-out .8s infinite; }
        .hm-tile { display: flex; align-items: center; justify-content: center; border-radius: .75rem; font-weight: 800; font-size: 1rem; }
        .hm-tile-sun { position: relative; width: 2.5rem; height: 2.5rem; background: linear-gradient(to bottom right, #FFE27A, #F5B400); box-shadow: 0 8px 24px -4px rgba(245, 180, 0, .75), 0 0 28px rgba(255, 214, 90, .65); }
        .hm-tile-figma { width: 3rem; height: 3rem; background: #1E1E1E; border: 1px solid rgba(255, 255, 255, .15); box-shadow: 0 10px 24px -6px rgba(0, 0, 0, .8); }
        .hm-tile-ps { width: 3rem; height: 3rem; background: #001E36; border: 2px solid rgba(49, 168, 255, .7); box-shadow: 0 0 20px rgba(49, 168, 255, .5); color: #31A8FF; }
        .hm-tile-ai { width: 2.75rem; height: 2.75rem; background: #330000; border: 2px solid rgba(255, 154, 0, .7); box-shadow: 0 0 20px rgba(255, 154, 0, .5); color: #FF9A00; }
        /* the yellow tile is a little sun: rays, halo, a beam onto the cube, light motes */
        .hm-sun-beam { position: absolute; left: 50%; top: 50%; z-index: -1; width: 136px; height: 112px; transform-origin: left center; transform: translateY(-50%) rotate(44deg); clip-path: polygon(0% 42%, 100% 0%, 100% 100%, 0% 58%); background: linear-gradient(90deg, rgba(255, 240, 170, .95) 0%, rgba(255, 214, 90, .55) 30%, rgba(255, 196, 60, .18) 70%, rgba(255, 196, 60, 0) 100%); filter: blur(5px); pointer-events: none; animation: hm-beam 3s ease-in-out infinite; }
        .hm-sun-motes { position: absolute; left: 50%; top: 50%; z-index: -1; width: 136px; height: 0; transform-origin: left center; transform: rotate(44deg); pointer-events: none; }
        .hm-sun-motes i { position: absolute; left: 0; width: 6px; height: 6px; border-radius: 9999px; background: #FFF3B0; box-shadow: 0 0 8px #FFD54A; animation: hm-mote 2.7s linear infinite; }
        .hm-sun-rays { position: absolute; inset: -1.75rem; z-index: -1; border-radius: 9999px; pointer-events: none; background: repeating-conic-gradient(rgba(255, 214, 90, .45) 0deg 5deg, rgba(255, 214, 90, 0) 5deg 22deg); -webkit-mask-image: radial-gradient(circle, #000 28%, transparent 70%); mask-image: radial-gradient(circle, #000 28%, transparent 70%); animation: hm-sun-spin 16s linear infinite; }
        .hm-sun-halo { position: absolute; inset: -1rem; z-index: -1; border-radius: 9999px; background: rgba(255, 213, 74, .5); filter: blur(24px); pointer-events: none; animation: hm-sun-pulse 3s ease-in-out infinite; }

        /* ---------- floating cards (desktop) ---------- */
        .hm-floats { display: none; }
        @media (min-width: 1024px) { .hm-floats { display: block; } }
        .hm-float { position: absolute; z-index: 20; }
        .hm-float-progress { top: -56px; left: -2.5rem; animation: hm-float-y 5.5s ease-in-out infinite; }
        .hm-float-cert { top: 66px; right: -2rem; animation: hm-float-y-sm 4.6s ease-in-out .6s infinite; }
        .hm-float-project { bottom: 4.25rem; left: -3rem; animation: hm-float-y 6.2s ease-in-out 1.1s infinite; }
        .hm-float-learning { bottom: -60px; right: -1.5rem; animation: hm-float-y-sm 5s ease-in-out .3s infinite; }
        .hm-fcard { display: flex; align-items: center; gap: .75rem; padding: .5rem 1rem .5rem .5rem; border-radius: 1rem; background: color-mix(in srgb, var(--h-card) 95%, transparent); backdrop-filter: blur(12px); border: 1px solid var(--h-line); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, .1), 0 8px 10px -6px rgba(0, 0, 0, .1); }
        html.dark .hm-fcard { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, .5); }
        .hm-fcard small { display: block; font-size: 10px; color: var(--h-muted); }
        .hm-fcard strong { display: block; font-size: 12px; font-weight: 700; color: var(--h-text); }
        .hm-ring { position: relative; display: flex; align-items: center; justify-content: center; }
        .hm-ring svg { width: 2.75rem; height: 2.75rem; transform: rotate(-90deg); }
        .hm-ring circle.track { stroke: #E2E8F0; }
        html.dark .hm-ring circle.track { stroke: #2E3034; }
        .hm-ring circle.bar { animation: hm-ring-draw 1.6s cubic-bezier(.22, 1, .36, 1) .3s both; }
        .hm-ring b { position: absolute; font-size: 10px; font-weight: 700; color: var(--h-text); }
        .hm-play { position: relative; width: 2.5rem; height: 2.5rem; flex-shrink: 0; border-radius: .75rem; background: var(--h-blue); color: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px -1px rgba(24, 119, 242, .4); }
        .hm-play::before { content: ""; position: absolute; inset: 0; border-radius: .75rem; background: var(--h-blue); animation: hm-ping-soft 2.2s ease-out infinite; }
        .hm-play svg { position: relative; }
        .hm-live { display: flex; align-items: center; gap: .375rem; font-size: 10px; font-weight: 600; color: var(--h-sky); }
        .hm-live::before { content: ""; width: 6px; height: 6px; border-radius: 9999px; background: var(--h-sky); animation: hm-pulse 2s ease-in-out infinite; }

        /* ==================================================================
           JELLY FILTER TABS
           ================================================================== */
        .hm-tabs { max-width: 100%; overflow-x: auto; padding: .25rem; border-radius: 1rem; background: var(--h-card); border: 1px solid var(--h-line-soft); box-shadow: var(--h-shadow); scrollbar-width: none; }
        html.dark .hm-tabs { background: #151B23; border-color: #222936; }
        .hm-tabs::-webkit-scrollbar { display: none; }
        .hm-tabs-track { position: relative; display: flex; width: max-content; align-items: center; gap: .25rem; }
        .hm-tab { position: relative; z-index: 1; flex-shrink: 0; padding: .5rem 1rem; border: 0; border-radius: .75rem; background: transparent; font: inherit; font-size: .875rem; font-weight: 600; white-space: nowrap; color: var(--h-muted); cursor: pointer; transition: color .3s ease; }
        .hm-tab:hover { color: var(--h-text); }
        .hm-tab[aria-selected="true"] { color: #fff; }
        .hm-tab span { display: inline-block; }
        .hm-tab span.is-pop { animation: hm-label-pop 420ms cubic-bezier(.34, 1.56, .64, 1) both; }
        .hm-tab:focus-visible { outline: 2px solid var(--h-sky); outline-offset: 1px; }
        .hm-jelly, .hm-jelly-trail { position: absolute; top: 0; bottom: 0; left: 0; pointer-events: none; }
        .hm-jelly { transition: transform 580ms cubic-bezier(.34, 1.56, .64, 1), width 580ms cubic-bezier(.34, 1.56, .64, 1); }
        .hm-jelly-trail { border-radius: .75rem; background: rgba(77, 163, 255, .4); filter: blur(12px); transition: transform 860ms cubic-bezier(.22, 1, .36, 1), width 860ms cubic-bezier(.22, 1, .36, 1); }
        .hm-jelly-blob { position: relative; display: block; width: 100%; height: 100%; overflow: hidden; border-radius: .75rem; background: linear-gradient(to bottom, #5AAEFF, #1877F2); box-shadow: 0 8px 22px -6px rgba(24, 119, 242, .7), inset 0 1px 0 rgba(255, 255, 255, .45); }
        .hm-jelly-blob::before { content: ""; position: absolute; left: 6px; right: 6px; top: 2px; height: 45%; border-radius: 10px 10px 0 0; background: linear-gradient(to bottom, rgba(255, 255, 255, .4), transparent); }
        .hm-jelly-blob::after { content: ""; position: absolute; top: 0; bottom: 0; left: 0; width: 33%; opacity: 0; background: linear-gradient(to right, transparent, rgba(255, 255, 255, .7), transparent); }
        .hm-jelly-blob.is-jelly { animation: hm-jelly 620ms cubic-bezier(.25, .9, .3, 1.2) both; }
        .hm-jelly-blob.is-jelly::after { animation: hm-shimmer 720ms ease-out 120ms both; }
        .hm-sparks i { position: absolute; left: 50%; top: 50%; margin: -3px 0 0 -3px; width: 6px; height: 6px; border-radius: 9999px; background: #9FD0FF; box-shadow: 0 0 8px #4DA3FF; animation: hm-spark 560ms ease-out 380ms both; }
        .hm-tabs.is-static .hm-jelly, .hm-tabs.is-static .hm-jelly-trail { transition: none; }

        /* ==================================================================
           COURSE CARDS
           ================================================================== */
        .hm-course-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; }
        @media (min-width: 768px) { .hm-course-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { .hm-course-grid { gap: 2rem; } }
        .hm-card { position: relative; display: flex; flex-direction: column; overflow: hidden; background: var(--h-card); border: 1px solid var(--h-line); border-radius: 1rem; box-shadow: var(--h-shadow); transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease; }
        .hm-card:hover { transform: translateY(-4px); border-color: rgba(24, 119, 242, .5); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, .1), 0 8px 10px -6px rgba(0, 0, 0, .1); }
        html.dark .hm-card:hover { border-color: rgba(69, 149, 255, .5); }
        .hm-card[hidden] { display: none; }
        .hm-card.is-in { animation: hm-pop-in 420ms cubic-bezier(.22, 1, .36, 1) both; }
        .hm-cover { position: relative; aspect-ratio: 16 / 9; overflow: hidden; background: #111317; }
        .hm-cover img, .hm-cover .hm-cover-fallback { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transition: transform .7s ease-out; }
        .hm-card:hover .hm-cover img, .hm-card:hover .hm-cover .hm-cover-fallback { transform: scale(1.05); }
        .hm-cover-fallback { display: flex; align-items: center; justify-content: center; background: radial-gradient(circle at 70% 30%, color-mix(in srgb, var(--mark-fg) 35%, transparent), transparent 60%), var(--mark-bg); }
        .hm-cover-fallback span { font-family: 'Archivo Expanded', Inter, sans-serif; font-size: 4.5rem; line-height: 1; font-weight: 900; color: var(--mark-fg); opacity: .9; }
        @media (max-width: 1279px) {
            .hm-course-grid .hm-cover { aspect-ratio: 16 / 10; }
            .hm-cover-fallback { align-items: flex-start; padding-top: 2.75rem; }
            .hm-cover-fallback span { font-size: 3.25rem; }
        }
        @media (max-width: 400px) { .hm-cover-fallback span { opacity: .45; } }
        .hm-scrim { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0, 0, 0, .9), rgba(0, 0, 0, .45), rgba(0, 0, 0, .35)); }
        .hm-cover-top { position: absolute; top: 0; left: 0; right: 0; padding: 1rem; display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; }
        .hm-sw { display: inline-flex; align-items: center; justify-content: center; min-width: 26px; height: 22px; padding: 0 .375rem; border-radius: .375rem; font-size: 10px; font-weight: 700; line-height: 1; }
        .hm-cover-tools { display: flex; align-items: center; gap: .5rem; flex-shrink: 0; }
        .hm-cat { display: none; padding: .25rem .625rem; border-radius: 9999px; font-size: 11px; font-weight: 600; color: #fff; background: rgba(255, 255, 255, .15); border: 1px solid rgba(255, 255, 255, .2); backdrop-filter: blur(4px); }
        @media (min-width: 640px) { .hm-cat { display: inline-flex; } }
        .hm-heart { width: 2.25rem; height: 2.25rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; color: #fff; background: rgba(0, 0, 0, .3); border: 1px solid rgba(255, 255, 255, .25); backdrop-filter: blur(4px); cursor: pointer; transition: all .2s ease; }
        .hm-heart:hover { background: rgba(0, 0, 0, .5); }
        .hm-heart:active { transform: scale(.9); }
        .hm-heart[aria-pressed="true"] { background: #F43F5E; border-color: #FB7185; box-shadow: 0 10px 15px -3px rgba(244, 63, 94, .4); }
        .hm-heart[aria-pressed="true"] svg { fill: currentColor; }
        .hm-heart:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }
        .hm-cover-bottom { position: absolute; left: 0; right: 0; bottom: 0; padding: 1rem; }
        @media (min-width: 640px) { .hm-cover-bottom { padding: 1.25rem; } }
        .hm-cover-bottom h3 { margin: 0; font-size: 1.125rem; font-weight: 800; line-height: 1.25; color: #fff; }
        @media (min-width: 640px) { .hm-cover-bottom h3 { font-size: 1.25rem; } }
        html:lang(km) .hm-cover-bottom h3 { line-height: 1.5; }
        .hm-cover-bottom p { margin: .25rem 0 0; font-size: 11px; color: #CBD5E1; }
        .hm-meta { margin-top: .625rem; display: flex; flex-wrap: wrap; align-items: center; gap: .25rem .75rem; font-size: 12px; color: #E2E8F0; }
        .hm-meta span { display: inline-flex; align-items: center; gap: .25rem; }
        .hm-card-body { display: flex; flex-direction: column; gap: 1rem; flex: 1; padding: 1rem; }
        @media (min-width: 640px) { .hm-card-body { padding: 1.25rem; } }
        .hm-person { display: flex; align-items: center; gap: .75rem; min-width: 0; }
        .hm-person p { margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .hm-person .name { font-size: .875rem; font-weight: 600; color: var(--h-text); }
        .hm-person .role { font-size: 12px; color: var(--h-muted); }
        .hm-buy { margin-top: auto; display: flex; align-items: flex-end; justify-content: space-between; gap: .75rem; padding-top: 1rem; border-top: 1px solid var(--h-line); }
        .hm-buy small { display: block; font-size: 12px; color: var(--h-muted); }
        .hm-price { display: flex; align-items: baseline; gap: .5rem; margin: 0; }
        .hm-price b { font-size: 1.5rem; font-weight: 900; color: var(--h-text); }
        .hm-price s { font-size: .875rem; color: var(--h-muted); }
        .hm-off { font-size: 10px; font-weight: 700; padding: .125rem .375rem; border-radius: .375rem; background: rgba(16, 185, 129, .1); color: #059669; }
        html.dark .hm-off { color: #34D399; }
        .hm-enroll { flex-shrink: 0; display: inline-flex; align-items: center; gap: .5rem; min-height: 42px; padding: 0 1rem; border-radius: .75rem; background: var(--h-blue); color: #fff; font-size: .875rem; font-weight: 600; text-decoration: none; box-shadow: 0 4px 6px -1px rgba(24, 119, 242, .25); transition: background .2s ease; }
        .hm-enroll:hover { background: var(--h-navy); }
        .hm-enroll:hover .hm-arrow { transform: translateX(4px); }
        .hm-empty { padding: 4rem 0; text-align: center; color: var(--h-muted); }

        /* ==================================================================
           INSTRUCTOR
           ================================================================== */
        .hm-teacher { border-radius: 1.5rem; background: var(--h-card); border: 1px solid var(--h-line); padding: 1.5rem; box-shadow: var(--h-shadow); }
        @media (min-width: 640px) { .hm-teacher { padding: 2.5rem; } }
        @media (min-width: 1024px) { .hm-teacher { padding: 3rem; } }
        .hm-teacher-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 2.5rem; }
        @media (min-width: 1024px) { .hm-teacher-grid { grid-template-columns: minmax(0, 4fr) minmax(0, 8fr); gap: 3rem; } }
        .hm-teacher-left { display: flex; flex-direction: column; align-items: center; }
        .hm-portrait { position: relative; }
        .hm-portrait .hm-avatar { width: 14rem; height: 14rem; border: 2px solid #E2E8F0; }
        html.dark .hm-portrait .hm-avatar { border-color: #2B3545; }
        @media (min-width: 640px) { .hm-portrait .hm-avatar { width: 16rem; height: 16rem; } }
        .hm-lecturer { position: absolute; bottom: -1.25rem; left: 50%; transform: translateX(-50%); width: max-content; max-width: 260px; display: flex; align-items: center; gap: .625rem; padding: .5rem .75rem; border-radius: .75rem; background: var(--h-card); border: 1px solid var(--h-line); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, .1); }
        html.dark .hm-lecturer { background: #151B23; }
        @media (min-width: 640px) { .hm-lecturer { left: auto; right: -1.5rem; transform: none; } }
        .hm-lecturer .ic { width: 2rem; height: 2rem; flex-shrink: 0; border-radius: .5rem; display: flex; align-items: center; justify-content: center; color: var(--h-blue); background: rgba(24, 119, 242, .1); }
        .hm-lecturer b { display: block; font-size: 12px; color: var(--h-text); }
        .hm-lecturer span { display: block; font-size: 11px; color: var(--h-muted); }
        .hm-stats { margin-top: 2.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; width: 100%; max-width: 20rem; }
        .hm-stat { border-radius: 1rem; border: 1px solid var(--h-line); background: var(--h-subtle); padding: 1rem; text-align: center; }
        .hm-stat b { display: block; font-size: 1.25rem; font-weight: 700; color: var(--h-text); }
        .hm-stat span { display: block; margin-top: .125rem; font-size: 12px; color: var(--h-muted); }
        .hm-teacher-right > * + * { margin-top: 1.5rem; }
        .hm-teacher-name { margin: .5rem 0 0; font-size: 1.5rem; font-weight: 900; letter-spacing: -.02em; line-height: 1.45; color: var(--h-text); }
        @media (min-width: 640px) { .hm-teacher-name { font-size: 2.25rem; } }
        .hm-teacher-name span { color: var(--h-blue); }
        html.dark .hm-teacher-name span { color: var(--h-sky); }
        .hm-role { margin: .5rem 0 0; font-size: .875rem; color: var(--h-muted); }
        @media (min-width: 640px) { .hm-role { font-size: 1rem; } }
        .hm-quote { margin: 0; border-left: 2px solid var(--h-sky); padding-left: 1rem; font-style: italic; font-size: 15px; line-height: 1.65; color: var(--h-quote); }
        html:lang(km) .hm-quote { line-height: 2; }
        .hm-skills-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: .75rem; }
        .hm-skills-head h3 { margin: 0; display: flex; align-items: center; gap: .5rem; font-size: 1rem; font-weight: 700; color: var(--h-text); }
        .hm-pill-count { flex-shrink: 0; font-size: 11px; font-weight: 600; padding: .25rem .625rem; border-radius: 9999px; color: var(--h-blue); background: rgba(24, 119, 242, .1); }
        .hm-skills { list-style: none; margin: 0; padding: 0; display: grid; grid-template-columns: minmax(0, 1fr); gap: .625rem; }
        @media (min-width: 640px) { .hm-skills { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        .hm-skill { display: flex; align-items: center; justify-content: space-between; gap: .625rem; padding: .625rem .75rem; border-radius: 1rem; background: #F8FAFC; border: 1px solid rgba(226, 232, 240, .8); transition: all .2s ease; }
        .hm-skill:hover { background: #fff; border-color: rgba(77, 163, 255, .5); box-shadow: var(--h-shadow); }
        html.dark .hm-skill { background: #151B23; border-color: #222936; }
        html.dark .hm-skill:hover { background: #1C232D; border-color: rgba(77, 163, 255, .5); }
        .hm-skill .t { display: flex; align-items: center; gap: .625rem; min-width: 0; font-size: .875rem; font-weight: 500; line-height: 1.35; color: var(--h-text); }
        .hm-skill .e { font-size: 1.125rem; line-height: 1; transition: transform .2s ease; }
        .hm-skill:hover .e { transform: scale(1.1); }
        .hm-skill .g { flex-shrink: 0; font-size: 10px; font-weight: 600; padding: .125rem .5rem; border-radius: .375rem; background: #fff; border: 1px solid #E2E8F0; color: var(--h-muted); }
        html.dark .hm-skill .g { background: #0B0F14; border-color: #222936; }
        .hm-trust-row { padding-top: 1.25rem; border-top: 1px solid var(--h-line); display: flex; flex-direction: column; gap: 1rem; }
        @media (min-width: 1280px) { .hm-trust-row { flex-direction: row; align-items: center; justify-content: space-between; } }
        .hm-trust { list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: .5rem 1.25rem; }
        .hm-trust li { display: flex; align-items: center; gap: .375rem; font-size: .875rem; color: var(--h-muted); }
        .hm-trust svg { color: var(--h-blue); }
        html.dark .hm-trust svg { color: var(--h-sky); }

        /* ==================================================================
           SHOWCASE
           ================================================================== */
        .hm-show-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; }
        @media (min-width: 640px) { .hm-show-grid { gap: 2rem; } }
        @media (min-width: 768px) { .hm-show-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { .hm-show-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        .hm-show { cursor: pointer; text-align: left; }
        .hm-show:focus-visible { outline: 2px solid var(--h-sky); outline-offset: 2px; }
        .hm-show .hm-cover { aspect-ratio: 16 / 10; }
        .hm-show-shade { position: absolute; top: 0; left: 0; right: 0; height: 5rem; background: linear-gradient(to bottom, rgba(0, 0, 0, .5), transparent); }
        .hm-show-top { position: absolute; z-index: 2; top: 0; left: 0; right: 0; padding: .875rem; display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; }
        .hm-award { display: inline-flex; align-items: center; gap: .375rem; padding: .25rem .625rem; border-radius: .5rem; background: rgba(0, 0, 0, .6); border: 1px solid rgba(255, 255, 255, .15); backdrop-filter: blur(4px); font-size: 11px; font-weight: 600; color: #FCD34D; }
        .hm-like { display: inline-flex; align-items: center; gap: .375rem; height: 2rem; padding: 0 .625rem; border-radius: 9999px; border: 1px solid rgba(255, 255, 255, .25); background: rgba(0, 0, 0, .35); backdrop-filter: blur(4px); color: #fff; font: inherit; font-size: 12px; font-weight: 600; cursor: pointer; transition: all .2s ease; }
        .hm-like:hover { background: rgba(0, 0, 0, .55); }
        .hm-like:active { transform: scale(.9); }
        .hm-like[aria-pressed="true"] { background: #F43F5E; border-color: #FB7185; box-shadow: 0 10px 15px -3px rgba(244, 63, 94, .4); }
        .hm-like[aria-pressed="true"] svg { fill: currentColor; }
        .hm-show-hover { position: absolute; inset: 0; z-index: 1; pointer-events: none; display: flex; align-items: flex-end; justify-content: space-between; gap: .5rem; padding: .875rem; opacity: 0; background: linear-gradient(to top, rgba(0, 0, 0, .85), rgba(0, 0, 0, .4), transparent); transition: opacity .3s ease; }
        .hm-show:hover .hm-show-hover, .hm-show:focus-visible .hm-show-hover { opacity: 1; }
        .hm-inspect { display: inline-flex; align-items: center; gap: .375rem; padding: .5rem .75rem; border-radius: .75rem; background: #fff; color: #1C1C1E; font-size: 12px; font-weight: 700; transform: translateY(8px); transition: transform .3s ease; }
        .hm-show:hover .hm-inspect { transform: translateY(0); }
        .hm-grade { padding: .375rem .625rem; border-radius: .5rem; background: var(--h-blue); color: #fff; font-size: 11px; font-weight: 700; }
        .hm-show-body { display: flex; flex-direction: column; gap: .75rem; flex: 1; padding: 1rem; }
        @media (min-width: 640px) { .hm-show-body { padding: 1.25rem; } }
        .hm-course-tag { margin: 0; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .14em; color: var(--h-sky); }
        .hm-show-body h3 { margin: 0; font-size: 1.0625rem; font-weight: 700; line-height: 1.35; color: var(--h-text); }
        .hm-show-body .kh { margin: .125rem 0 0; font-size: .875rem; color: var(--h-muted); }
        .hm-clamp { margin: 0; font-size: .875rem; line-height: 1.6; color: var(--h-muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .hm-show-foot { margin-top: auto; padding-top: .75rem; border-top: 1px solid var(--h-line); display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
        .hm-student-img { width: 2.25rem; height: 2.25rem; border-radius: 9999px; object-fit: cover; flex-shrink: 0; }
        .hm-tools { display: flex; align-items: center; gap: .25rem; flex-shrink: 0; }
        .hm-tool { padding: .125rem .375rem; border-radius: .375rem; font-size: 10px; background: var(--h-chip); border: 1px solid var(--h-line-soft); color: var(--h-muted); }

        /* ---------- project modal ---------- */
        .hm-modal { position: fixed; inset: 0; z-index: 70; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(0, 0, 0, .8); backdrop-filter: blur(12px); animation: hm-fade-in .2s ease-out both; }
        .hm-modal[hidden] { display: none; }
        @media (min-width: 640px) { .hm-modal { padding: 1.5rem; } }
        .hm-dialog { display: flex; flex-direction: column; width: 100%; max-width: 56rem; max-height: 90vh; overflow: hidden; border-radius: 1.5rem; background: #fff; border: 1px solid #E2E8F0; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .25); color: var(--h-text); animation: hm-zoom-in .2s cubic-bezier(.16, 1, .3, 1) both; }
        html.dark .hm-dialog { background: #151B23; border-color: #2E3034; }
        .hm-dialog-head { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; padding: 1rem 1.25rem; border-bottom: 1px solid var(--h-line); }
        .hm-dialog-head h2 { margin: 0; font-size: 1.125rem; font-weight: 700; line-height: 1.35; }
        .hm-dialog-sub { margin-top: .25rem; display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; font-size: .875rem; color: var(--h-muted); }
        .hm-verified { display: inline-flex; align-items: center; gap: .25rem; padding: .125rem .5rem; border-radius: 9999px; background: rgba(16, 185, 129, .1); color: #059669; font-size: 11px; font-weight: 600; }
        html.dark .hm-verified { color: #34D399; }
        .hm-dialog-actions { display: flex; align-items: center; gap: .5rem; flex-shrink: 0; }
        .hm-share { display: inline-flex; align-items: center; gap: .375rem; height: 2.25rem; padding: 0 .75rem; border-radius: .75rem; border: 1px solid var(--h-line); background: var(--h-chip); color: var(--h-text); font: inherit; font-size: 12px; font-weight: 600; cursor: pointer; transition: all .2s ease; }
        .hm-share.is-copied { background: #10B981; border-color: #10B981; color: #fff; }
        .hm-close { width: 2.25rem; height: 2.25rem; border-radius: .75rem; border: 0; background: transparent; color: var(--h-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .hm-close:hover { background: var(--h-chip); color: var(--h-text); }
        .hm-dialog-body { overflow-y: auto; overscroll-behavior: contain; padding: 1.25rem; }
        @media (min-width: 640px) { .hm-dialog-body { padding: 1.5rem; } }
        .hm-dialog-body > * + * { margin-top: 1.5rem; }
        .hm-shot { position: relative; aspect-ratio: 16 / 9; border-radius: 1rem; overflow: hidden; background: #111317; border: 1px solid var(--h-line); }
        .hm-shot img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .hm-shot .hm-grade { position: absolute; right: .75rem; bottom: .75rem; font-size: 12px; }
        .hm-dialog-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
        @media (min-width: 768px) { .hm-dialog-grid { grid-template-columns: 1fr 2fr; } }
        .hm-idcard { border-radius: 1rem; border: 1px solid var(--h-line); background: var(--h-subtle); padding: 1rem; }
        html.dark .hm-idcard { background: #11161D; }
        .hm-dl { margin: 1rem 0 0; display: grid; gap: .625rem; font-size: .875rem; }
        .hm-dl div { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .hm-dl dt { color: var(--h-muted); }
        .hm-dl dd { margin: 0; font-weight: 600; }
        .hm-dl .award { display: inline-flex; align-items: center; gap: .25rem; color: #D97706; }
        html.dark .hm-dl .award { color: #FCD34D; }
        .hm-mini-like { display: inline-flex; align-items: center; gap: .25rem; padding: .125rem .5rem; border-radius: 9999px; border: 0; font: inherit; font-size: 12px; font-weight: 600; cursor: pointer; background: rgba(244, 63, 94, .1); color: #E11D48; }
        .hm-mini-like[aria-pressed="true"] { background: #F43F5E; color: #fff; }
        .hm-mini-like[aria-pressed="true"] svg { fill: currentColor; }
        .hm-small-head { margin: 0 0 .5rem; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--h-muted); }
        .hm-concept h3 { margin: 0 0 .5rem; font-size: .875rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--h-sky); }
        .hm-concept p { margin: 0; font-size: 15px; line-height: 1.65; color: var(--h-quote); }
        .hm-critique { margin-top: 1.25rem; border-radius: 1rem; border: 1px solid rgba(24, 119, 242, .25); background: rgba(24, 119, 242, .06); padding: 1rem 1.25rem; }
        html.dark .hm-critique { background: rgba(24, 119, 242, .1); }
        .hm-critique-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
        .hm-critique-head b { font-size: 1.25rem; font-weight: 700; color: var(--h-blue); }
        html.dark .hm-critique-head b { color: var(--h-sky); }
        .hm-critique p.q { margin: .75rem 0 0; font-style: italic; font-size: 15px; line-height: 1.65; color: var(--h-text); }

        /* ==================================================================
           FOOTER
           ================================================================== */
        .hm-footer { border-top: 1px solid var(--h-line); background: #fff; padding: 3rem 0; transition: background-color .3s ease; }
        html.dark .hm-footer { background: #0B0F14; border-color: #1E2530; }
        @media (min-width: 1024px) { .hm-footer { padding: 4rem 0; } }
        .hm-footer-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 2rem; }
        @media (min-width: 640px) { .hm-footer-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { .hm-footer-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 3rem; } }
        .hm-footer-grid > div > * + * { margin-top: .75rem; }
        .hm-wordmark { margin: 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -.025em; color: var(--h-text); }
        .hm-wordmark span { color: var(--h-blue); }
        html.dark .hm-wordmark span { color: var(--h-sky); }
        .hm-footer p { margin: 0; font-size: .875rem; line-height: 1.65; color: var(--h-muted); }
        .hm-footer .motto { color: var(--h-sky); }
        .hm-fhead { margin: 0 0 1rem; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--h-text); }
        .hm-flinks { list-style: none; margin: 0; padding: 0; display: grid; gap: .75rem; }
        .hm-flinks a, .hm-flinks button { padding: 0; border: 0; background: none; font: inherit; font-size: .875rem; color: var(--h-muted); text-decoration: none; text-align: left; cursor: pointer; transition: color .2s ease; }
        .hm-flinks a:hover, .hm-flinks button:hover { color: var(--h-blue); }
        html.dark .hm-flinks a:hover, html.dark .hm-flinks button:hover { color: var(--h-sky); }
        .hm-footer-bar { margin-top: 2.5rem; padding-top: 1.5rem; border-top: 1px solid var(--h-line); display: flex; flex-direction: column; gap: .75rem; }
        html.dark .hm-footer-bar { border-color: #1E2530; }
        @media (min-width: 640px) { .hm-footer-bar { flex-direction: row; align-items: center; justify-content: space-between; } }
        .hm-footer-bar p { font-size: 12px; }

        /* ---------- floating language / theme pill ---------- */
        .hm-prefs { position: fixed; z-index: 45; right: max(.75rem, env(safe-area-inset-right)); bottom: max(1rem, env(safe-area-inset-bottom)); display: flex; align-items: center; padding: .25rem; border-radius: 9999px; background: color-mix(in srgb, var(--h-card) 90%, transparent); backdrop-filter: blur(12px); border: 1px solid var(--h-line); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, .1); }
        @media (min-width: 640px) { .hm-prefs { right: 1.25rem; bottom: 1.5rem; } }
        html.dark .hm-prefs { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, .4); }
        .hm-prefs form { display: flex; }
        .hm-pref { display: inline-flex; align-items: center; gap: .375rem; height: 2.25rem; padding: 0 .75rem; border: 0; border-radius: 9999px; background: transparent; font: inherit; font-size: 12px; font-weight: 600; color: var(--h-text); cursor: pointer; transition: background .2s ease; }
        .hm-pref:hover { background: #F1F5F9; }
        html.dark .hm-pref:hover { background: #262A31; }
        .hm-pref:focus-visible { outline: 2px solid var(--h-sky); outline-offset: 1px; }
        .hm-pref-divider { width: 1px; height: 1.25rem; background: var(--h-line); }
        .hm-flag { width: 1.25rem; height: 13px; border-radius: 3px; box-shadow: 0 1px 2px rgba(0, 0, 0, .15); }
        .hm-spin-in { animation: hm-icon-spin 420ms cubic-bezier(.34, 1.56, .64, 1) both; }

        .hm-toast { position: fixed; z-index: 80; left: 50%; bottom: 5.5rem; transform: translateX(-50%); display: inline-flex; align-items: center; gap: .5rem; padding: .625rem 1rem; border-radius: 9999px; background: #1C1C1E; color: #fff; font-size: .875rem; font-weight: 600; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, .2); animation: hm-toast-in 220ms ease-out both; }
        .hm-toast[hidden] { display: none; }
        html.dark .hm-toast { background: #F5F5F7; color: #1C1C1E; }

        /* ==================================================================
           KEYFRAMES
           ================================================================== */
        @keyframes float-3d-main { 0%, 100% { transform: translateY(0) rotateX(8deg) rotateY(-10deg) scale(1); } 50% { transform: translateY(-10px) rotateX(14deg) rotateY(-4deg) scale(1.02); } }
        @keyframes float-3d-orbit { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        @keyframes float-3d-icon-a { 0%, 100% { transform: translateY(0) rotate(4deg) translateZ(20px); } 50% { transform: translateY(-8px) rotate(-3deg) translateZ(35px); } }
        @keyframes float-3d-icon-b { 0%, 100% { transform: translateY(0) rotate(-8deg) translateZ(15px); } 50% { transform: translateY(9px) rotate(3deg) translateZ(25px); } }
        @keyframes float-3d-icon-c { 0%, 100% { transform: translateY(0) rotate(10deg) translateZ(25px); } 50% { transform: translateY(-6px) rotate(16deg) translateZ(40px); } }
        @keyframes pulse-glow-3d { 0%, 100% { opacity: .35; transform: scale(.92); } 50% { opacity: .75; transform: scale(1.1); } }
        @keyframes hm-float-y { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-10px); } }
        @keyframes hm-float-y-sm { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
        @keyframes hm-sheen { 0% { transform: translateX(-160%) skewX(-18deg); } 55%, 100% { transform: translateX(320%) skewX(-18deg); } }
        @keyframes hm-twinkle { 0%, 100% { opacity: .15; transform: scale(.6); } 50% { opacity: 1; transform: scale(1); } }
        @keyframes hm-shadow-breathe { 0%, 100% { transform: scale(1); opacity: .55; } 50% { transform: scale(.8); opacity: .28; } }
        @keyframes hm-orbit-reverse { from { transform: rotateX(68deg) rotate(360deg); } to { transform: rotateX(68deg) rotate(0deg); } }
        @keyframes hm-ring-draw { from { stroke-dashoffset: 113.1; } }
        @keyframes hm-ping { 75%, 100% { transform: scale(2); opacity: 0; } }
        @keyframes hm-ping-soft { 0% { transform: scale(1); opacity: .6; } 100% { transform: scale(1.9); opacity: 0; } }
        @keyframes hm-pulse { 50% { opacity: .4; } }
        @keyframes hm-sun-spin { to { transform: rotate(360deg); } }
        @keyframes hm-sun-pulse { 0%, 100% { opacity: .55; transform: scale(.9); } 50% { opacity: 1; transform: scale(1.15); } }
        @keyframes hm-beam { 0%, 100% { opacity: .7; } 50% { opacity: 1; } }
        @keyframes hm-warm { 0%, 100% { opacity: .6; } 50% { opacity: 1; } }
        @keyframes hm-mote { 0% { transform: translateX(14px) scale(.6); opacity: 0; } 18% { opacity: 1; } 100% { transform: translateX(118px) scale(1); opacity: 0; } }
        @keyframes hm-jelly { 0% { transform: scale(1, 1); } 20% { transform: scale(1.22, .78); } 40% { transform: scale(.88, 1.12); } 58% { transform: scale(1.07, .94); } 76% { transform: scale(.98, 1.03); } 100% { transform: scale(1, 1); } }
        @keyframes hm-shimmer { 0% { opacity: 1; transform: translateX(-120%) skewX(-20deg); } 100% { opacity: 1; transform: translateX(340%) skewX(-20deg); } }
        @keyframes hm-label-pop { 0% { transform: scale(1); } 40% { transform: scale(1.09); } 100% { transform: scale(1); } }
        @keyframes hm-spark { 0% { transform: translate(0, 0) scale(1); opacity: 1; } 100% { transform: translate(var(--dx), var(--dy)) scale(0); opacity: 0; } }
        @keyframes hm-icon-spin { from { transform: rotate(-120deg) scale(.4); opacity: 0; } to { transform: rotate(0) scale(1); opacity: 1; } }
        @keyframes hm-pop-in { from { opacity: 0; transform: translateY(12px) scale(.98); } to { opacity: 1; transform: none; } }
        @keyframes hm-fade-in { from { opacity: 0; } to { opacity: 1; } }
        @keyframes hm-zoom-in { from { opacity: 0; transform: scale(.95); } to { opacity: 1; transform: scale(1); } }
        @keyframes hm-toast-in { from { opacity: 0; transform: translate(-50%, 12px); } to { opacity: 1; transform: translate(-50%, 0); } }

        @media (prefers-reduced-motion: reduce) {
            .hm-body *, .hm-body *::before, .hm-body *::after {
                animation-duration: .001ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .001ms !important;
            }
        }
    </style>
</head>

@php
    $hmKm = app()->getLocale() === 'km';
    // Home copy in both languages, kept on this page so no shared file changes.
    $L = fn (string $en, string $km) => $hmKm ? $km : $en;

    // Adobe-style colours for a course's short mark (Ps, Ai, Id ...).
    $hmMarks = [
        'ps' => ['#001E36', '#31A8FF'], 'ai' => ['#330000', '#FF9A00'], 'id' => ['#49021F', '#FF3366'],
        'pr' => ['#00005B', '#9999FF'], 'ae' => ['#00005B', '#9999FF'], 'mo' => ['#00005B', '#9999FF'],
        'xd' => ['#470137', '#FF61F6'], 'fg' => ['#1E1E1E', '#0ACF83'], 'au' => ['#00242B', '#00E4BB'],
        'dw' => ['#072401', '#4AFF99'],
    ];
    $hmCycle = array_keys($hmMarks);

    $hmCourses = collect($courses ?? [])->values();
    $hmCategories = $hmCourses->pluck('category')->filter()->unique()->values();

    $hmSkills = [
        ['🎨', 'ការរចនាម៉ាកសញ្ញា & Branding', 'Branding'],
        ['🎬', 'Motion Graphics & Animation', 'Motion'],
        ['📱', 'UI/UX Design & Figma Systems', 'UI/UX'],
        ['📢', 'Social Media Campaign Visuals', 'Social Media'],
        ['📦', 'Packaging & Print Production', 'Packaging'],
        ['🎥', 'Video & Visual Content Editing', 'Production'],
        ['🤖', 'AI for Design & Studio Workflows', 'AI Design'],
        ['🎓', 'ការបង្រៀន Graphic & Motion Design', 'Teaching'],
        ['💡', 'Design Thinking & Creative Direction', 'Strategy'],
        ['⚡', 'Adobe Photoshop, Illustrator, InDesign, AE', 'Software'],
    ];

    $hmProjects = [
        [
            'id' => 'sw-1', 'studentName' => 'Dara Somnang', 'studentNameKh' => 'ដារ៉ា សំណាង',
            'studentAvatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80',
            'course' => 'Graphic Design', 'title' => 'Angkor Heritage Minimalist Identity', 'titleKh' => 'អត្តសញ្ញាណប្រណិតភាពអង្គរទំនើប',
            'image' => 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=800&auto=format&fit=crop&q=80',
            'likes' => 142, 'badge' => "Teacher's Choice", 'tools' => ['Illustrator', 'InDesign', 'Photoshop'], 'grade' => '9.8 / 10',
            'description' => 'A modern minimalist brand identity and packaging system celebrating classical Angkorian architectural proportions and traditional Khmer stone carvings.',
            'teacherCritique' => 'Outstanding optical balance between the modern sans-serif type and traditional Khmer display font. Grid proportion is exemplary.',
        ],
        [
            'id' => 'sw-2', 'studentName' => 'Bopha Vann', 'studentNameKh' => 'បុប្ផា វ៉ាន់',
            'studentAvatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200&auto=format&fit=crop&q=80',
            'course' => 'UX/UI Design', 'title' => 'Phnom Penh Urban Transit UI Kit', 'titleKh' => 'ប្រព័ន្ធកក់សំបុត្រ និង UI Kit ធ្វើដំណើររាជធានី',
            'image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800&auto=format&fit=crop&q=80',
            'likes' => 198, 'badge' => 'Featured Project', 'tools' => ['Figma', 'FigJam', 'Protopie'], 'grade' => '9.6 / 10',
            'description' => 'Clean mobile ticketing and real-time bus navigation application designed for Phnom Penh commuters with full Khmer localized typography and dark mode support.',
            'teacherCritique' => 'Superb design token structure and auto-layout discipline. Micro-interactions for route tracking are smooth and intuitive.',
        ],
        [
            'id' => 'sw-3', 'studentName' => 'Rithy Kem', 'studentNameKh' => 'រិទ្ធី កែម',
            'studentAvatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&auto=format&fit=crop&q=80',
            'course' => 'Motion Graphics', 'title' => 'Kinetic Khmer Calligraphy Showreel', 'titleKh' => 'ចលនាអក្សរផ្ចង់ខ្មែរ Kinetic Calligraphy',
            'image' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=800&auto=format&fit=crop&q=80',
            'likes' => 215, 'badge' => 'Top Honors', 'tools' => ['After Effects', 'Cinema 4D', 'Illustrator'], 'grade' => '9.9 / 10',
            'description' => 'Dynamic 3D typography motion reel exploring traditional Khmer calligraphy morphing into contemporary geometric spatial forms with fluid rhythm.',
            'teacherCritique' => 'Masterful easing curves and secondary overshoot physics. Setting a new benchmark for motion typography in Cambodia.',
        ],
        [
            'id' => 'sw-4', 'studentName' => 'Channary Pich', 'studentNameKh' => 'ចាន់ណារី ពេជ្រ',
            'studentAvatar' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=200&auto=format&fit=crop&q=80',
            'course' => 'Graphic Design', 'title' => 'Mondulkiri Specialty Coffee Packaging', 'titleKh' => 'ការរចនាកញ្ចប់កាហ្វេពិសេសមណ្ឌលគិរី',
            'image' => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&auto=format&fit=crop&q=80',
            'likes' => 167, 'badge' => 'Distinction', 'tools' => ['Illustrator', 'Photoshop'], 'grade' => '9.5 / 10',
            'description' => 'Sustainable eco-friendly packaging series capturing the misty highlands of Mondulkiri with custom illustrated botanicals and tactile gold foil accents.',
            'teacherCritique' => 'The hierarchy between brand mark, origin story, and brew specs is masterfully balanced. Print-ready execution.',
        ],
    ];

    $hmTeacher = 'Piseth Yoeurn';
    $hmTeacherKh = 'យឿន ពិសិដ្ឋ';
    $hmStartUrl = auth()->check() ? route('learning.index') : route('register');

    // Words the page script shows (share button, toast, like button).
    $hmText = [
        'copied' => $L('Copied Link!', 'បានចម្លងតំណ!'),
        'share' => $L('Share', 'ចែករំលែក'),
        'copiedToast' => $L('Project link copied to clipboard', 'បានចម្លងតំណគម្រោងរួចរាល់'),
        'copyFailed' => $L('Could not copy the link', 'មិនអាចចម្លងតំណបានទេ'),
        'like' => $L('Like this project', 'ចូលចិត្តគម្រោងនេះ'),
        'unlike' => $L('Unlike this project', 'ឈប់ចូលចិត្ត'),
    ];
@endphp

<body class="antialiased hm-body">

    <div
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

        {{-- The live promotion sits above the menu; the menu is the bar shared with every page. --}}
        @include('partials._promo')

        @include('partials._site-nav')

        <main class="hm-main">

            {{-- ============================== HERO ============================== --}}
            <section class="hm-hero" aria-labelledby="hm-hero-title">
                <div class="hm-wrap hm-hero-grid">
                    <div class="hm-hero-copy">
                        <p class="hm-status"><span class="hm-dot" aria-hidden="true"></span>{{ $L('Modern Cambodian Design Academy · Led by Lecturer Piseth Yoeurn', 'Modern Cambodian Design Academy · បង្រៀនដោយលោកគ្រូ ពិសិដ្ឋ') }}</p>

                        <h1 id="hm-hero-title" class="hm-h1">
                            {{ $L('Master Design Fundamentals.', 'រៀន Design ពីមូលដ្ឋានគ្រឹះ') }}<br>
                            <span>{{ $L('Craft Valuable Creations.', 'ដល់ការបង្កើតស្នាដៃពិតៗ') }}</span>
                        </h1>

                        <p class="hm-lead">{{ $L('Learn Graphic Design, UX/UI, Motion Graphics, and studio creative workflows through hands-on practice led by university lecturer Piseth Yoeurn.', 'រៀន Graphic Design, UX/UI, Motion Graphics និងជំនាញ Creative ដោយផ្តោតលើការអនុវត្តជាក់ស្តែង ជាមួយសាស្ត្រាចារ្យ យឿន ពិសិដ្ឋ។') }}</p>

                        <div class="hm-ctas">
                            <a href="{{ $hmStartUrl }}" class="hm-btn hm-btn-primary">
                                {{ $L('Start Learning', 'ចាប់ផ្ដើមរៀន') }}
                                <svg class="hm-ico-sm hm-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 5l7 7-7 7" /></svg>
                            </a>
                            <a href="#hm-courses" class="hm-btn hm-btn-ghost">{{ $L('Explore Courses', 'មើលវគ្គសិក្សា') }}</a>
                        </div>
                    </div>

                    {{-- macOS-style design window with the 3D studio scene --}}
                    <div class="hm-art">
                        <div class="hm-glow hm-glow-a" aria-hidden="true"></div>
                        <div class="hm-glow hm-glow-b" aria-hidden="true"></div>

                        <div class="hm-floats" aria-hidden="true">
                            <div class="hm-float hm-float-progress">
                                <div class="hm-fcard">
                                    <span class="hm-ring">
                                        <svg viewBox="0 0 44 44"><circle class="track" cx="22" cy="22" r="18" fill="none" stroke-width="4" /><circle class="bar" cx="22" cy="22" r="18" fill="none" stroke-width="4" stroke-linecap="round" stroke="#4DA3FF" stroke-dasharray="113.1" stroke-dashoffset="24.9" /></svg>
                                        <b class="hm-mono">78%</b>
                                    </span>
                                    <span><small>{{ $L('Overall Progress', 'វឌ្ឍនភាពសរុប') }}</small><strong>Design Course</strong></span>
                                </div>
                            </div>
                            <div class="hm-float hm-float-cert">
                                <div class="hm-fcard" style="gap:.5rem; padding:.5rem .75rem; border-radius:.75rem">
                                    <svg class="hm-ico-sm" style="color:#4DA3FF" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6" /><path d="M15.48 12.89 17 22l-5-3-5 3 1.52-9.11" /></svg>
                                    <strong>Design Course Certified</strong>
                                    <svg class="hm-ico-sm" style="color:#10B981" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="m9 12 2 2 4-4" /></svg>
                                </div>
                            </div>
                            <div class="hm-float hm-float-project">
                                <div class="hm-fcard" style="padding:.625rem .75rem; border-radius:.75rem">
                                    <span class="hm-mono" style="font-size:.875rem; font-weight:700; color:#FF9A00">#01</span>
                                    <span><strong>{{ $L('Design Course Project', 'គម្រោង Design Course') }}</strong><small style="color:#E8890C">{{ $L('Teacher Feedback Received', 'បានទទួលមតិពីលោកគ្រូ') }}</small></span>
                                </div>
                            </div>
                            <div class="hm-float hm-float-learning">
                                <div class="hm-fcard" style="padding:.625rem 1rem .625rem .625rem; border-radius:.75rem">
                                    <span class="hm-play"><svg class="hm-ico-sm" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5.14v13.72a1 1 0 0 0 1.52.85l10.6-6.86a1 1 0 0 0 0-1.7L9.52 4.29A1 1 0 0 0 8 5.14z" /></svg></span>
                                    <span style="min-width:0">
                                        <span class="hm-live">{{ $L('Currently Learning', 'កំពុងរៀន') }}</span>
                                        <strong style="max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap">Design Course: Visual Hierarchy</strong>
                                        <small>{{ $L('38 mins remaining', 'នៅសល់ 38 នាទី') }}</small>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="hm-window">
                            <div class="hm-titlebar">
                                <span class="hm-lights" aria-hidden="true"><i style="background:#FF5F56"></i><i style="background:#FFBD2E"></i><i style="background:#27C93F"></i></span>
                                <span class="hm-file hm-mono">design_course_curriculum.fig</span>
                                <span class="hm-badge">
                                    <svg class="hm-ico-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 3l1.9 5.6 5.6 1.9-5.6 1.9L11 18l-1.9-5.6-5.6-1.9 5.6-1.9z" /><path d="M19 3v4M17 5h4" /></svg>
                                    Design Course
                                </span>
                            </div>

                            <div class="hm-canvas-pad">
                                <div class="hm-canvas">
                                    <div class="hm-grid-lines" aria-hidden="true"></div>
                                    <div class="hm-canvas-grid">
                                        <div class="hm-canvas-copy">
                                            <p class="hm-canvas-title">Design Course</p>
                                            <p class="hm-canvas-sub">Graphic Design · UX/UI · Motion Graphics</p>
                                            <div class="hm-tags">
                                                <span class="hm-tag hm-mono" style="background:#001E36; color:#31A8FF; border:1px solid rgba(49,168,255,.3)">Adobe Photoshop</span>
                                                <span class="hm-tag hm-mono" style="background:#332200; color:#FFB547; border:1px solid rgba(255,181,71,.3)">Adobe Illustrator</span>
                                                <span class="hm-tag hm-mono" style="background:#1F1335; color:#C79BFF; border:1px solid rgba(199,155,255,.3)">UX/UI Design · Figma</span>
                                            </div>
                                        </div>

                                        <div class="hm-stage" aria-hidden="true">
                                            <div class="hm-stage-glow"></div>
                                            <span class="hm-star" style="top:12%; left:18%"></span>
                                            <span class="hm-star" style="top:22%; right:14%; animation-delay:.9s"></span>
                                            <span class="hm-star" style="bottom:18%; left:10%; animation-delay:1.6s"></span>
                                            <span class="hm-star" style="bottom:10%; right:24%; animation-delay:.4s"></span>
                                            <span class="hm-star" style="top:48%; right:6%; animation-delay:2.2s"></span>
                                            <div class="hm-ring-tilt"><i></i></div>
                                            <div class="hm-orbit"><i></i></div>

                                            <div class="hm-scene">
                                                <div class="hm-cube-shadow"></div>
                                                <div class="hm-cube">
                                                    <div class="hm-cube-grid"></div>
                                                    <div class="hm-cube-cool"></div>
                                                    <div class="hm-cube-warm"></div>
                                                    <div class="hm-cube-rim"></div>
                                                    <div class="hm-cube-sheen"></div>
                                                    <div class="hm-cube-face">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19 7-7 3 3-7 7-3-3z" /><path d="m18 13-1.5-7.5L2 2l3.5 14.5L13 18l5-5z" /><path d="m2 2 7.586 7.586" /><circle cx="11" cy="11" r="2" /></svg>
                                                        <span class="hm-mono">3D STUDIO</span>
                                                    </div>
                                                </div>

                                                {{-- the yellow tile is a small sun lighting the cube --}}
                                                <div class="hm-sat hm-sat-sun">
                                                    <span class="hm-sun-beam"></span>
                                                    <span class="hm-sun-motes"><i style="top:-12px"></i><i style="top:-3px; animation-delay:.9s"></i><i style="top:6px; animation-delay:1.8s"></i></span>
                                                    <span class="hm-sun-rays"></span>
                                                    <span class="hm-sun-halo"></span>
                                                    <div class="hm-tile hm-tile-sun"><svg viewBox="0 0 24 24" width="20" height="20"><path fill="#3B2A00" d="M12 2l2.1 6.4L20.5 10.5l-6.4 2.1L12 19l-2.1-6.4L3.5 10.5l6.4-2.1z" /></svg></div>
                                                </div>
                                                <div class="hm-sat hm-sat-figma">
                                                    <div class="hm-tile hm-tile-figma">
                                                        <svg viewBox="0 0 38 57" width="18" height="26"><path fill="#1ABCFE" d="M19 28.5a9.5 9.5 0 1 1 19 0 9.5 9.5 0 0 1-19 0z" /><path fill="#0ACF83" d="M0 47.5A9.5 9.5 0 0 1 9.5 38H19v9.5a9.5 9.5 0 1 1-19 0z" /><path fill="#FF7262" d="M19 0v19h9.5a9.5 9.5 0 1 0 0-19H19z" /><path fill="#F24E1E" d="M0 9.5A9.5 9.5 0 0 0 9.5 19H19V0H9.5A9.5 9.5 0 0 0 0 9.5z" /><path fill="#A259FF" d="M0 28.5A9.5 9.5 0 0 0 9.5 38H19V19H9.5A9.5 9.5 0 0 0 0 28.5z" /></svg>
                                                    </div>
                                                </div>
                                                <div class="hm-sat hm-sat-ps"><div class="hm-tile hm-tile-ps">Ps</div></div>
                                                <div class="hm-sat hm-sat-ai"><div class="hm-tile hm-tile-ai">Ai</div></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="hm-canvas-foot">
                                        <a href="{{ route('courses') }}" class="hm-link">
                                            {{ $L('Open Design Course', 'បើក Design Course') }}
                                            <svg class="hm-ico-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M8 7h9v9" /></svg>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="hm-window-foot">
                                <div class="hm-person">
                                    <span class="hm-py hm-mono">PY</span>
                                    <p>{{ $L('Instructor:', 'គ្រូបង្រៀន:') }} <b>{{ $hmTeacher }}</b></p>
                                </div>
                                <a href="{{ route('courses') }}" class="hm-link" style="flex-shrink:0">
                                    {{ $L('Design Course Curriculum', 'កម្មវិធីសិក្សា') }}
                                    <svg class="hm-ico-sm hm-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 5l7 7-7 7" /></svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ============================== COURSES (real courses from the database) ============================== --}}
            <section id="hm-courses" class="hm-section" aria-labelledby="hm-courses-title">
                <div class="hm-wrap">
                    <div class="hm-head">
                        <div>
                            <p class="hm-label hm-mono">Featured Curriculums</p>
                            <h2 id="hm-courses-title" class="hm-h2">{{ $L('Featured Design Curriculums', 'វគ្គសិក្សាជំនាញរចនាជ្រើសរើសពិសេស') }}</h2>
                            <p class="hm-desc">{{ $L('Industry-aligned programs in Graphic Design, UI/UX and Motion Graphics, built around what the job market needs.', 'កម្មវិធីសិក្សាផ្តោតលើជំនាញ Graphic Design, UI/UX, និង Motion Graphics ស្របតាមតម្រូវការទីផ្សារការងារ។') }}</p>
                        </div>
                        @if ($hmCategories->count() > 1)
                            <div class="hm-tabs" data-jelly-tabs data-target="#hm-course-grid [data-category]">
                                <div class="hm-tabs-track" role="tablist" aria-label="{{ $L('Filter courses by category', 'ជ្រើសរើសប្រភេទវគ្គសិក្សា') }}">
                                    <span class="hm-jelly-trail" aria-hidden="true"></span>
                                    <span class="hm-jelly" aria-hidden="true"><span class="hm-jelly-blob"></span><span class="hm-sparks"></span></span>
                                    <button type="button" role="tab" class="hm-tab" aria-selected="true" data-value="__all"><span>{{ $L('All', 'ទាំងអស់') }}</span></button>
                                    @foreach ($hmCategories as $cat)
                                        <button type="button" role="tab" class="hm-tab" aria-selected="false" tabindex="-1" data-value="{{ $cat }}"><span>{{ __($cat) }}</span></button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    @if ($hmCourses->isEmpty())
                        <p class="hm-empty">{{ $L('New courses are on the way. Check back soon.', 'វគ្គសិក្សាថ្មីនឹងមកដល់ឆាប់ៗនេះ។') }}</p>
                    @else
                        <div id="hm-course-grid" class="hm-course-grid">
                            @foreach ($hmCourses as $course)
                                @php
                                    $mark = trim((string) $course->mark);
                                    $pick = $hmMarks[strtolower($mark)] ?? $hmMarks[$hmCycle[$loop->index % count($hmCycle)]];
                                    $thumb = $course->thumbnailSrc();
                                    $title = $course->localized('title');
                                    $subtitle = $course->localized('subtitle');
                                    $onSale = ! $course->isFree() && $course->isOnSale();
                                    $off = ($onSale && (float) $course->price > 0) ? (int) round((1 - $course->salePrice() / (float) $course->price) * 100) : 0;
                                @endphp
                                <article class="hm-card" data-category="{{ $course->category }}">
                                    <div class="hm-cover" style="--mark-bg: {{ $pick[0] }}; --mark-fg: {{ $pick[1] }}">
                                        @if ($thumb)
                                            <img src="{{ $thumb }}" alt="{{ $title }}" loading="lazy" decoding="async">
                                        @else
                                            <div class="hm-cover-fallback" aria-hidden="true"><span>{{ $mark ?: 'PD' }}</span></div>
                                        @endif
                                        <div class="hm-scrim"></div>

                                        <div class="hm-cover-top">
                                            @if ($mark !== '')
                                                <span class="hm-sw hm-mono" style="background: {{ $pick[0] }}; color: {{ $pick[1] }}; border: 1px solid {{ $pick[1] }}4D">{{ $mark }}</span>
                                            @else
                                                <span></span>
                                            @endif
                                            <div class="hm-cover-tools">
                                                @if ($course->category)
                                                    <span class="hm-cat">{{ __($course->category) }}</span>
                                                @endif
                                                <button type="button" class="hm-heart" data-wish="{{ $course->slug }}" aria-pressed="false"
                                                    aria-label="{{ $L('Add to wishlist', 'រក្សាទុកក្នុងបញ្ជីចូលចិត្ត') }}"
                                                    data-label-on="{{ $L('Remove from wishlist', 'ដកចេញពីបញ្ជីចូលចិត្ត') }}"
                                                    data-label-off="{{ $L('Add to wishlist', 'រក្សាទុកក្នុងបញ្ជីចូលចិត្ត') }}">
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" /></svg>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="hm-cover-bottom">
                                            <h3>{{ $title }}</h3>
                                            @if ($subtitle !== '')
                                                <p class="hm-mono">{{ $subtitle }}</p>
                                            @endif
                                            <div class="hm-meta">
                                                @if ($course->level)
                                                    <span>
                                                        <svg class="hm-ico-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 20h18M6 20V10M12 20V4M18 20v-7" /></svg>
                                                        {{ __($course->level) }}
                                                    </span>
                                                @endif
                                                <span>
                                                    <svg class="hm-ico-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 2 10 5-10 5L2 7z" /><path d="m2 17 10 5 10-5" /><path d="m2 12 10 5 10-5" /></svg>
                                                    <span class="hm-mono">{{ __(':count lessons', ['count' => $course->lessons_count]) }}</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="hm-card-body">
                                        <div class="hm-person">
                                            <span class="hm-avatar" style="width:2.5rem; height:2.5rem" role="img" aria-label="{{ $course->instructor_name ?: $hmTeacher }}">
                                                <svg viewBox="0 0 64 64" fill="currentColor" aria-hidden="true"><circle cx="32" cy="23" r="12.5" /><path d="M7 64c0-14.5 11.2-25 25-25s25 10.5 25 25z" /></svg>
                                            </span>
                                            <div style="min-width:0">
                                                <p class="name">{{ $hmKm ? $hmTeacherKh : ($course->instructor_name ?: $hmTeacher) }}</p>
                                                <p class="role">{{ $course->instructor_title ?: $L('Senior Visual & Motion Designer', 'Senior Visual & Motion Designer') }}</p>
                                            </div>
                                        </div>

                                        <div class="hm-buy">
                                            <div>
                                                <small>{{ $L('Tuition', 'តម្លៃសិក្សា') }}</small>
                                                <p class="hm-price">
                                                    @if ($course->isFree())
                                                        <b>{{ __('Free') }}</b>
                                                    @elseif ($onSale)
                                                        <b>{{ $course->salePriceLabel() }}</b>
                                                        <s>{{ $course->priceLabel() }}</s>
                                                        @if ($off > 0)
                                                            <span class="hm-off hm-mono">-{{ $off }}%</span>
                                                        @endif
                                                    @else
                                                        <b>{{ $course->priceLabel() }}</b>
                                                    @endif
                                                </p>
                                            </div>
                                            <a href="{{ route('courses.show', $course) }}" class="hm-enroll">
                                                {{ $L('Enroll Now', 'ចុះឈ្មោះរៀន') }}
                                                <svg class="hm-ico-sm hm-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 5l7 7-7 7" /></svg>
                                            </a>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            {{-- ============================== INSTRUCTOR ============================== --}}
            <section id="hm-instructor" class="hm-section" aria-labelledby="hm-instructor-title">
                <div class="hm-wrap">
                    <div class="hm-teacher">
                        <div class="hm-teacher-grid">
                            <div class="hm-teacher-left">
                                <div class="hm-portrait">
                                    <span class="hm-avatar hm-avatar--square" role="img" aria-label="{{ $hmTeacher }}">
                                        <svg viewBox="0 0 64 64" fill="currentColor" aria-hidden="true"><circle cx="32" cy="23" r="12.5" /><path d="M7 64c0-14.5 11.2-25 25-25s25 10.5 25 25z" /></svg>
                                    </span>
                                    <div class="hm-lecturer">
                                        <span class="ic"><svg class="hm-ico-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 10 12 5 2 10l10 5 10-5z" /><path d="M6 12v5c3 3 9 3 12 0v-5" /></svg></span>
                                        <span><b>{{ $L('University Lecturer', 'សាស្ត្រាចារ្យសាកលវិទ្យាល័យ') }}</b><span>Build Bright University, Siem Reap</span></span>
                                    </div>
                                </div>
                                <div class="hm-stats">
                                    <div class="hm-stat"><b class="hm-mono">10+ Years</b><span>{{ $L('Design Experience', 'បទពិសោធន៍ Design') }}</span></div>
                                    <div class="hm-stat"><b class="hm-mono">1,400+</b><span>{{ $L('Graduated Students', 'និស្សិតបានសិក្សា') }}</span></div>
                                </div>
                            </div>

                            <div class="hm-teacher-right">
                                <div>
                                    <p class="hm-label hm-mono">Meet Your Instructor <span style="color:var(--h-muted); text-transform:none; letter-spacing:0">· {{ $L('10+ years of experience', 'បទពិសោធន៍ជាង ១០ ឆ្នាំ') }}</span></p>
                                    <h2 id="hm-instructor-title" class="hm-teacher-name"><span class="hm-km" style="color:inherit">គ្រូបង្រៀន {{ $hmTeacherKh }}</span> <span>({{ $hmTeacher }})</span></h2>
                                    <p class="hm-role">{{ $L('Senior Visual & Motion Designer · Lecturer at Build Bright University', 'Senior Visual & Motion Designer · សាស្ត្រាចារ្យនៅសាកលវិទ្យាល័យ Build Bright') }}</p>
                                </div>

                                <blockquote class="hm-quote">“{{ $L('Design is not merely mastering software. It is layout balance, typography, emotion and discipline, used to solve real problems. I created piseth.design to share real studio experience with young Cambodian creatives.', 'ការរចនាមិនមែនត្រឹមតែការប្រើ Tool នោះទេ ប៉ុន្តែជាការគិតស៊ីជម្រៅពី Layout, Grid, Typography និង Emotion ដើម្បីដោះស្រាយបញ្ហាពិតប្រាកដ។ ខ្ញុំបង្កើត piseth.design ដើម្បីចែករំលែកបទពិសោធន៍ជាក់ស្តែងដល់ប្អូនៗនិស្សិតខ្មែរ។') }}”</blockquote>

                                <div>
                                    <div class="hm-skills-head">
                                        <h3>
                                            <svg class="hm-ico-sm" style="color:var(--h-sky)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 3l1.9 5.6 5.6 1.9-5.6 1.9L11 18l-1.9-5.6-5.6-1.9 5.6-1.9z" /><path d="M19 3v4M17 5h4" /></svg>
                                            {{ $L('10 Core Specializations', 'ជំនាញឯកទេស (My Professional Skills)') }}
                                        </h3>
                                        <span class="hm-pill-count hm-mono">10 Specializations</span>
                                    </div>
                                    <ul class="hm-skills">
                                        @foreach ($hmSkills as [$emoji, $skill, $tag])
                                            <li class="hm-skill">
                                                <span class="t"><span class="e" aria-hidden="true">{{ $emoji }}</span><span>{{ $skill }}</span></span>
                                                <span class="g hm-mono">{{ $tag }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="hm-trust-row">
                                    <ul class="hm-trust">
                                        @foreach (['Direct 1-on-1 Feedback', 'Agency Client Briefs', 'Khmer Typography Expertise'] as $item)
                                            <li><svg class="hm-ico-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" /><path d="m9 12 2 2 4-4" /></svg>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                    <a href="{{ route('about') }}" class="hm-link" style="white-space:nowrap">
                                        {{ $L('Full Teaching Philosophy & Story', 'ស្វែងយល់បន្ថែមពីទស្សនវិជ្ជាបង្រៀន') }}
                                        <svg class="hm-ico-sm hm-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ============================== STUDENT SHOWCASE ============================== --}}
            <section id="hm-showcase" class="hm-section" aria-labelledby="hm-showcase-title">
                <div class="hm-wrap">
                    <div class="hm-head">
                        <div>
                            <p class="hm-label hm-mono">Student Work</p>
                            <h2 id="hm-showcase-title" class="hm-h2">{{ $L('Student Creative Showcase', 'ស្នាដៃសិស្សឆ្នើម (Student Creative Showcase)') }}</h2>
                            <p class="hm-desc">{{ $L("Real design projects by Cambodian students, held to Piseth Yoeurn's teaching standards. Tap a project to inspect it.", 'គម្រោងរចនាជាក់ស្តែងអនុវត្តដោយនិស្សិតកម្ពុជា តាមស្តង់ដារបង្រៀនរបស់លោកគ្រូ យឿន ពិសិដ្ឋ។ ចុចលើរូបដើម្បីពិនិត្យលម្អិត។') }}</p>
                        </div>
                        <div class="hm-tabs" data-jelly-tabs data-target="#hm-show-grid [data-category]">
                            <div class="hm-tabs-track" role="tablist" aria-label="{{ $L('Filter projects by course', 'ជ្រើសរើសប្រភេទស្នាដៃ') }}">
                                <span class="hm-jelly-trail" aria-hidden="true"></span>
                                <span class="hm-jelly" aria-hidden="true"><span class="hm-jelly-blob"></span><span class="hm-sparks"></span></span>
                                <button type="button" role="tab" class="hm-tab" aria-selected="true" data-value="__all"><span>{{ $L('All', 'ទាំងអស់') }}</span></button>
                                @foreach (['Graphic Design', 'UX/UI Design', 'Motion Graphics'] as $cat)
                                    <button type="button" role="tab" class="hm-tab" aria-selected="false" tabindex="-1" data-value="{{ $cat }}"><span>{{ $cat }}</span></button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div id="hm-show-grid" class="hm-show-grid">
                        @foreach ($hmProjects as $p)
                            <article class="hm-card hm-show" data-category="{{ $p['course'] }}" data-project="{{ $p['id'] }}" role="button" tabindex="0"
                                aria-label="{{ $L('Inspect Details', 'ពិនិត្យស្នាដៃលម្អិត') }}: {{ $p['title'] }}">
                                <div class="hm-cover">
                                    <img src="{{ $p['image'] }}" alt="{{ $p['title'] }}" loading="lazy" decoding="async">
                                    <div class="hm-show-shade"></div>
                                    <div class="hm-show-top">
                                        <span class="hm-award hm-mono">
                                            <svg class="hm-ico-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="6" /><path d="M15.48 12.89 17 22l-5-3-5 3 1.52-9.11" /></svg>
                                            {{ $p['badge'] }}
                                        </span>
                                        <button type="button" class="hm-like" data-like="{{ $p['id'] }}" data-base="{{ $p['likes'] }}" aria-pressed="false"
                                            aria-label="{{ $L('Like this project', 'ចូលចិត្តគម្រោងនេះ') }}">
                                            <svg class="hm-ico-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" /></svg>
                                            <span class="hm-mono" data-count>{{ $p['likes'] }}</span>
                                        </button>
                                    </div>
                                    <div class="hm-show-hover">
                                        <span class="hm-inspect">
                                            <svg class="hm-ico-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" /><circle cx="12" cy="12" r="3" /></svg>
                                            {{ $L('Inspect Details', 'ពិនិត្យស្នាដៃលម្អិត') }}
                                        </span>
                                        <span class="hm-grade hm-mono">{{ $L('Grade', 'ពិន្ទុ') }} {{ $p['grade'] }}</span>
                                    </div>
                                </div>
                                <div class="hm-show-body">
                                    <p class="hm-course-tag hm-mono">{{ $p['course'] }}</p>
                                    <div>
                                        <h3>{{ $p['title'] }}</h3>
                                        <p class="kh hm-km">{{ $p['titleKh'] }}</p>
                                    </div>
                                    <p class="hm-clamp">{{ $p['description'] }}</p>
                                    <div class="hm-show-foot">
                                        <div class="hm-person">
                                            <img class="hm-student-img" src="{{ $p['studentAvatar'] }}" alt="{{ $p['studentName'] }}" loading="lazy" decoding="async">
                                            <div style="min-width:0">
                                                <p class="name">{{ $p['studentName'] }}</p>
                                                <p class="role hm-km" style="font-size:11px">{{ $p['studentNameKh'] }}</p>
                                            </div>
                                        </div>
                                        <div class="hm-tools">
                                            @foreach (array_slice($p['tools'], 0, 2) as $tool)
                                                <span class="hm-tool hm-mono">{{ $tool }}</span>
                                            @endforeach
                                            @if (count($p['tools']) > 2)
                                                <span class="hm-mono" style="font-size:10px; color:var(--h-muted)">+{{ count($p['tools']) - 2 }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        </main>

        {{-- ============================== FOOTER ============================== --}}
        <footer class="hm-footer">
            <div class="hm-wrap hm-footer-grid">
                <div>
                    <p class="hm-wordmark">piseth<span>.design</span></p>
                    <p>{{ $L('Modern creative design academy founded by university lecturer Piseth Yoeurn. Educating Cambodian creators to craft international-standard design.', 'Modern creative design academy founded by university lecturer Piseth Yoeurn. Educating Cambodian creators to craft international-standard design.') }}</p>
                    <p class="motto hm-km">រៀន Design ឲ្យច្បាស់ បង្កើតស្នាដៃឲ្យមានតម្លៃ។</p>
                </div>

                <div>
                    <p class="hm-fhead">{{ $L('Courses', 'Courses · វគ្គសិក្សា') }}</p>
                    <ul class="hm-flinks">
                        @foreach ($hmCourses->take(4) as $course)
                            <li><a href="{{ route('courses.show', $course) }}">{{ $course->localized('title') }}</a></li>
                        @endforeach
                        <li><a href="{{ route('courses', ['free' => 1]) }}">{{ __('Free Courses') }}</a></li>
                    </ul>
                </div>

                <div>
                    <p class="hm-fhead">{{ $L('Student Platform', 'Student Platform') }}</p>
                    <ul class="hm-flinks">
                        <li><a href="{{ route('learning.index') }}">{{ __('My Learning') }}</a></li>
                        <li><a href="{{ route('learning.index') }}">{{ __('Certificates') }}</a></li>
                        <li><a href="{{ route('learning.profile') }}">{{ __('Profile') }}</a></li>
                        <li><a href="{{ route('about') }}">{{ __('About') }}</a></li>
                    </ul>
                </div>

                <div>
                    <p class="hm-fhead">{{ $L('Teacher & Community', 'Teacher & Community') }}</p>
                    <p>{{ $L('Lecturer at Build Bright University, Siem Reap. Curriculum built on real agency and client work.', 'សាស្ត្រាចារ្យនៅ Build Bright University, Siem Reap។ កម្មវិធីសិក្សាផ្អែកលើការងារជាក់ស្តែងជាមួយអតិថិជន។') }}</p>
                    <ul class="hm-flinks">
                        <li>
                            <button type="button"
                                aria-haspopup="dialog"
                                aria-label="{{ __('Telegram (show QR code)') }}"
                                x-data
                                x-on:click="$dispatch('open-telegram-qr')"
                                class="pd-qr-trigger">Telegram</button>
                            @include('partials._telegram-qr-dialog')
                        </li>
                        <li><a href="{{ config('social.facebook') }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Facebook (opens in a new tab)') }}">Facebook</a></li>
                        <li><a href="{{ config('social.instagram') }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Instagram (opens in a new tab)') }}">Instagram</a></li>
                    </ul>
                </div>
            </div>

            <div class="hm-wrap">
                <div class="hm-footer-bar">
                    <p>&copy; {{ date('Y') }} piseth.design. All rights reserved.</p>
                    <p>{{ __('Made in Siem Reap, Cambodia') }}</p>
                </div>
            </div>
        </footer>

        {{-- Language and theme: one pill in the bottom-right corner. --}}
        <div class="hm-prefs">
            <form method="POST" action="{{ route('locale') }}">
                @csrf
                <input type="hidden" name="locale" value="{{ $hmKm ? 'en' : 'km' }}">
                <button type="submit" class="hm-pref" aria-label="{{ $hmKm ? 'Switch language to English' : 'Switch language to Khmer' }}">
                    @if ($hmKm)
                        <svg class="hm-flag" viewBox="0 0 30 20" aria-hidden="true"><rect width="30" height="20" fill="#032EA1" /><rect y="5" width="30" height="10" fill="#E00025" /><path fill="#fff" d="M9 13.4h12v1.2H9zM10.2 11.6h9.6v1.8h-9.6zM11.6 9.4h1.2v2.2h-1.2zM14.4 7.4h1.2v4.2h-1.2zM17.2 9.4h1.2v2.2h-1.2z" /></svg>
                        <span class="hm-km">ខ្មែរ</span>
                    @else
                        <svg class="hm-flag" viewBox="0 0 60 30" aria-hidden="true"><clipPath id="hm-uk-clip"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z" /></clipPath><path d="M0,0 v30 h60 v-30 z" fill="#012169" /><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6" /><path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#hm-uk-clip)" stroke="#C8102E" stroke-width="4" /><path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10" /><path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6" /></svg>
                        <span>EN</span>
                    @endif
                </button>
            </form>
            <span class="hm-pref-divider" aria-hidden="true"></span>
            <button type="button" class="hm-pref" @click="toggleTheme($event)"
                :aria-label="dark ? '{{ __('Switch to light mode') }}' : '{{ __('Switch to dark mode') }}'" :aria-pressed="dark">
                <svg class="hm-ico-sm hm-spin-in" style="color:#4DA3FF" x-show="dark" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" /></svg>
                <svg class="hm-ico-sm hm-spin-in" style="color:#F59E0B" x-show="!dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4" /><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" /></svg>
                <span x-text="dark ? 'Dark' : 'Light'">Dark</span>
            </button>
        </div>

        {{-- Project inspection dialog (filled by the script below) --}}
        <div class="hm-modal" id="hm-modal" hidden>
            <div class="hm-dialog" role="dialog" aria-modal="true" aria-labelledby="hm-modal-title">
                <div class="hm-dialog-head">
                    <div style="min-width:0">
                        <h2 id="hm-modal-title" data-f="title"></h2>
                        <div class="hm-dialog-sub">
                            <span class="hm-km" data-f="titleKh"></span>
                            <span class="hm-verified">
                                <svg class="hm-ico-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z" /><path d="m9 12 2 2 4-4" /></svg>
                                {{ $L('Verified Student', 'និស្សិតផ្ទៀងផ្ទាត់') }}
                            </span>
                        </div>
                    </div>
                    <div class="hm-dialog-actions">
                        <button type="button" class="hm-share" data-share aria-live="polite">
                            <svg class="hm-ico-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" /><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" /></svg>
                            <span data-share-label>{{ $L('Share', 'ចែករំលែក') }}</span>
                        </button>
                        <button type="button" class="hm-close" data-close aria-label="{{ $L('Close project details', 'បិទ') }}">
                            <svg class="hm-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" /></svg>
                        </button>
                    </div>
                </div>
                <div class="hm-dialog-body">
                    <div class="hm-shot">
                        <img data-f="image" alt="">
                        <span class="hm-grade hm-mono">{{ $L('Grade', 'ពិន្ទុ') }} <span data-f="grade"></span></span>
                    </div>
                    <div class="hm-dialog-grid">
                        <aside>
                            <div class="hm-idcard">
                                <div class="hm-person">
                                    <img class="hm-student-img" style="width:3rem; height:3rem" data-f="studentAvatar" alt="">
                                    <div style="min-width:0">
                                        <p class="name" data-f="studentName"></p>
                                        <p class="role hm-km" data-f="studentNameKh"></p>
                                    </div>
                                </div>
                                <dl class="hm-dl">
                                    <div><dt>{{ $L('Award', 'ពានរង្វាន់') }}</dt><dd class="award" data-f="badge"></dd></div>
                                    <div><dt>{{ $L('Curriculum', 'វគ្គសិក្សា') }}</dt><dd data-f="course"></dd></div>
                                    <div>
                                        <dt>{{ $L('Likes', 'ចូលចិត្ត') }}</dt>
                                        <dd>
                                            <button type="button" class="hm-mini-like" data-modal-like aria-pressed="false" aria-label="{{ $L('Like this project', 'ចូលចិត្តគម្រោងនេះ') }}">
                                                <svg class="hm-ico-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" /></svg>
                                                <span class="hm-mono" data-modal-count></span>
                                            </button>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                            <p class="hm-small-head" style="margin-top:1rem">{{ $L('Software Stack', 'កម្មវិធីប្រើប្រាស់') }}</p>
                            <div class="hm-tools" data-f="tools" style="flex-wrap:wrap"></div>
                        </aside>
                        <div class="hm-concept">
                            <h3>{{ $L('Project Concept', 'គំនិតគម្រោង') }}</h3>
                            <p data-f="description"></p>
                            <div class="hm-critique">
                                <div class="hm-critique-head">
                                    <div class="hm-person">
                                        <span class="hm-avatar" style="width:2.5rem; height:2.5rem" role="img" aria-label="{{ $hmTeacher }}">
                                            <svg viewBox="0 0 64 64" fill="currentColor" aria-hidden="true"><circle cx="32" cy="23" r="12.5" /><path d="M7 64c0-14.5 11.2-25 25-25s25 10.5 25 25z" /></svg>
                                        </span>
                                        <div style="min-width:0">
                                            <p class="name">{{ $L('Official Teacher Critique', 'មតិវាយតម្លៃពីលោកគ្រូ') }}</p>
                                            <p class="role {{ $hmKm ? 'hm-km' : '' }}">{{ $hmKm ? $hmTeacherKh : $hmTeacher }}</p>
                                        </div>
                                    </div>
                                    <b class="hm-mono" data-f="grade"></b>
                                </div>
                                <p class="q">“<span data-f="teacherCritique"></span>”</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="hm-toast" id="hm-toast" role="status" aria-live="polite" hidden>
            <svg class="hm-ico-sm" style="color:#34D399" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10" /><path d="m9 12 2 2 4-4" /></svg>
            <span data-toast-text></span>
        </div>
    </div>

    <script>
        window.hmProjects = @json(collect($hmProjects)->keyBy('id'));
        window.hmText = @json($hmText);
    </script>

    <script>
        (function () {
            'use strict';
            var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var store = {
                get: function (k, fb) { try { var v = localStorage.getItem(k); return v === null ? fb : JSON.parse(v); } catch (e) { return fb; } },
                set: function (k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
            };
            function restart(el, cls) { if (!el) return; el.classList.remove(cls); void el.offsetWidth; el.classList.add(cls); }

            /* ---------- jelly filter tabs ----------
               The pill springs to the clicked tab with overshoot, squashes and
               stretches as it lands, a blurred trail follows it, a gloss shimmer
               sweeps across and a few sparks pop out. */
            var SPARKS = [[-29, -20], [29, -20], [-38, 3], [38, 3], [-19, 22], [19, 22]];
            document.querySelectorAll('[data-jelly-tabs]').forEach(function (root) {
                var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));
                var pill = root.querySelector('.hm-jelly');
                var trail = root.querySelector('.hm-jelly-trail');
                var blob = root.querySelector('.hm-jelly-blob');
                var sparks = root.querySelector('.hm-sparks');
                var items = function () { return document.querySelectorAll(root.getAttribute('data-target')); };
                var active = 0;

                function place(animate) {
                    var t = tabs[active];
                    if (!t) return;
                    if (!animate) root.classList.add('is-static');
                    [pill, trail].forEach(function (el) {
                        el.style.width = t.offsetWidth + 'px';
                        el.style.transform = 'translateX(' + t.offsetLeft + 'px)';
                    });
                    if (!animate) { void root.offsetWidth; root.classList.remove('is-static'); }
                }

                function burst() {
                    sparks.innerHTML = '';
                    if (reduce) return;
                    SPARKS.forEach(function (d) {
                        var s = document.createElement('i');
                        s.style.setProperty('--dx', d[0] + 'px');
                        s.style.setProperty('--dy', d[1] + 'px');
                        sparks.appendChild(s);
                    });
                }

                function filter(value) {
                    items().forEach(function (el) {
                        var show = value === '__all' || el.getAttribute('data-category') === value;
                        if (show && el.hidden) { el.hidden = false; restart(el, 'is-in'); }
                        else if (!show) { el.hidden = true; }
                    });
                }

                function select(i, focus) {
                    if (i === active) return;
                    tabs[active].setAttribute('aria-selected', 'false');
                    tabs[active].tabIndex = -1;
                    active = i;
                    var t = tabs[i];
                    t.setAttribute('aria-selected', 'true');
                    t.tabIndex = 0;
                    if (focus) t.focus();
                    place(true);
                    restart(blob, 'is-jelly');
                    restart(t.querySelector('span'), 'is-pop');
                    burst();
                    filter(t.getAttribute('data-value'));
                    if (root.scrollWidth > root.clientWidth) {
                        root.scrollTo({ left: t.offsetLeft - (root.clientWidth - t.offsetWidth) / 2, behavior: reduce ? 'auto' : 'smooth' });
                    }
                }

                tabs.forEach(function (t, i) { t.addEventListener('click', function () { select(i); }); });
                root.addEventListener('keydown', function (e) {
                    if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
                    e.preventDefault();
                    select((active + (e.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length, true);
                });

                place(false);
                if (window.ResizeObserver) new ResizeObserver(function () { place(false); }).observe(root.querySelector('.hm-tabs-track'));
                if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { place(false); });
            });

            /* ---------- wishlist (this browser only) ---------- */
            var wish = store.get('pd-wishlist', []);
            if (!Array.isArray(wish)) wish = [];
            document.querySelectorAll('[data-wish]').forEach(function (btn) {
                var id = btn.getAttribute('data-wish');
                function paint() {
                    var on = wish.indexOf(id) !== -1;
                    btn.setAttribute('aria-pressed', on ? 'true' : 'false');
                    btn.setAttribute('aria-label', btn.getAttribute(on ? 'data-label-on' : 'data-label-off'));
                }
                paint();
                btn.addEventListener('click', function () {
                    wish = wish.indexOf(id) !== -1 ? wish.filter(function (x) { return x !== id; }) : wish.concat(id);
                    store.set('pd-wishlist', wish);
                    paint();
                });
            });

            /* ---------- likes (this browser only) ---------- */
            var liked = store.get('pd-liked', []);
            if (!Array.isArray(liked)) liked = [];
            var projects = window.hmProjects || {};
            function likeCount(id) { return (projects[id] ? projects[id].likes : 0) + (liked.indexOf(id) !== -1 ? 1 : 0); }
            function paintLikes() {
                document.querySelectorAll('[data-like]').forEach(function (btn) {
                    var id = btn.getAttribute('data-like');
                    var on = liked.indexOf(id) !== -1;
                    btn.setAttribute('aria-pressed', on ? 'true' : 'false');
                    btn.setAttribute('aria-label', (on ? hmText.unlike : hmText.like) + ' (' + likeCount(id) + ')');
                    btn.querySelector('[data-count]').textContent = likeCount(id);
                });
                if (openId) {
                    var on = liked.indexOf(openId) !== -1;
                    modalLike.setAttribute('aria-pressed', on ? 'true' : 'false');
                    modal.querySelector('[data-modal-count]').textContent = likeCount(openId);
                }
            }
            function toggleLike(id) {
                liked = liked.indexOf(id) !== -1 ? liked.filter(function (x) { return x !== id; }) : liked.concat(id);
                store.set('pd-liked', liked);
                paintLikes();
            }
            document.querySelectorAll('[data-like]').forEach(function (btn) {
                btn.addEventListener('click', function (e) { e.stopPropagation(); toggleLike(btn.getAttribute('data-like')); });
                btn.addEventListener('keydown', function (e) { e.stopPropagation(); });
            });

            /* ---------- project dialog ---------- */
            var modal = document.getElementById('hm-modal');
            var modalLike = modal.querySelector('[data-modal-like]');
            var shareBtn = modal.querySelector('[data-share]');
            var shareLabel = modal.querySelector('[data-share-label]');
            var toast = document.getElementById('hm-toast');
            var openId = null, lastFocus = null, shareTimer = 0, toastTimer = 0;

            function fill(p) {
                modal.querySelectorAll('[data-f]').forEach(function (el) {
                    var key = el.getAttribute('data-f');
                    if (key === 'image' || key === 'studentAvatar') {
                        el.src = p[key];
                        el.alt = key === 'image' ? p.title : p.studentName;
                    } else if (key === 'tools') {
                        el.innerHTML = '';
                        p.tools.forEach(function (tool) {
                            var s = document.createElement('span');
                            s.className = 'hm-tool hm-mono';
                            s.textContent = tool;
                            el.appendChild(s);
                        });
                    } else {
                        el.textContent = p[key];
                    }
                });
            }

            function open(id) {
                var p = projects[id];
                if (!p) return;
                openId = id;
                lastFocus = document.activeElement;
                fill(p);
                paintLikes();
                modal.hidden = false;
                document.documentElement.style.overflow = 'hidden';
                modal.querySelector('[data-close]').focus();
            }

            function close() {
                if (!openId) return;
                modal.hidden = true;
                openId = null;
                document.documentElement.style.overflow = '';
                if (location.hash.indexOf('#showcase-') === 0) history.replaceState(null, '', location.pathname + location.search);
                if (lastFocus && lastFocus.focus) lastFocus.focus();
            }

            document.querySelectorAll('[data-project]').forEach(function (card) {
                card.addEventListener('click', function () { open(card.getAttribute('data-project')); });
                card.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(card.getAttribute('data-project')); }
                });
            });
            modal.addEventListener('mousedown', function (e) { if (e.target === modal) close(); });
            modal.querySelector('[data-close]').addEventListener('click', close);
            modalLike.addEventListener('click', function () { if (openId) toggleLike(openId); });
            document.addEventListener('keydown', function (e) {
                if (!openId) return;
                if (e.key === 'Escape') { close(); return; }
                if (e.key === 'Tab') {
                    var nodes = modal.querySelectorAll('button, [href], [tabindex]:not([tabindex="-1"])');
                    var first = nodes[0], last = nodes[nodes.length - 1];
                    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                }
            });

            function showToast(text) {
                toast.querySelector('[data-toast-text]').textContent = text;
                toast.hidden = false;
                restart(toast, 'hm-toast');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(function () { toast.hidden = true; }, 2400);
            }

            function copy(text) {
                if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(text).then(function () { return true; }, function () { return legacy(text); });
                return Promise.resolve(legacy(text));
            }
            function legacy(text) {
                try {
                    var a = document.createElement('textarea');
                    a.value = text; a.setAttribute('readonly', ''); a.style.position = 'fixed'; a.style.opacity = '0';
                    document.body.appendChild(a); a.select();
                    var ok = document.execCommand('copy');
                    document.body.removeChild(a);
                    return ok;
                } catch (e) { return false; }
            }

            shareBtn.addEventListener('click', function () {
                if (!openId) return;
                var url = location.origin + location.pathname + location.search + '#showcase-' + openId;
                copy(url).then(function (ok) {
                    shareBtn.classList.toggle('is-copied', ok);
                    shareLabel.textContent = ok ? hmText.copied : hmText.share;
                    showToast(ok ? hmText.copiedToast : hmText.copyFailed);
                    clearTimeout(shareTimer);
                    shareTimer = setTimeout(function () { shareBtn.classList.remove('is-copied'); shareLabel.textContent = hmText.share; }, 2000);
                });
            });

            paintLikes();

            /* A shared link (#showcase-sw-1) opens that project. */
            var m = location.hash.match(/^#showcase-(.+)$/);
            if (m && projects[m[1]]) open(m[1]);
        })();
    </script>
</body>

</html>
