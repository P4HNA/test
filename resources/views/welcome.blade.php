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
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800&family=Archivo+Expanded:wght@700;800;900&family=Kantumruy+Pro:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&family=Noto+Sans+Khmer:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

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
           HOME — colours (light, then dark)
           ================================================================== */
        .hm-body {
            --pg: #F5F7FA;
            --card: #FFFFFF;
            --card2: #FFFFFF;
            --line: #E4E6EB;
            --line2: rgba(226, 232, 240, .8);
            --line3: #F1F5F9;
            --ink: #1C1C1E;
            --mut: #65676B;
            --blue: #1877F2;
            --blue2: #1877F2;
            --sky: #4DA3FF;
            --strong: #0F172A;
            --body: #475569;
            --soft: #64748B;
            --faint: #94A3B8;
            --chip: #F1F5F9;
            --chip2: #F8FAFC;
            --chip-hov: #FFFFFF;
            --tab-on: #FFFFFF;
            --hov: #F5F7FA;
            --s800: #1E293B;
            --s700: #334155;
            --sep: #CBD5E1;
            --av-a: #E2E8F0;
            --av-b: #CBD5E1;
            --av-fg: #FFFFFF;
            --sh-lg: 0 10px 15px -3px rgba(15, 23, 42, .05), 0 4px 6px -4px rgba(15, 23, 42, .05);
            --sh-xl: 0 20px 25px -5px rgba(15, 23, 42, .05), 0 8px 10px -6px rgba(15, 23, 42, .05);
            background: var(--pg);
            color: var(--ink);
        }

        html.dark .hm-body {
            --pg: #0B0B0D;
            --card: #1C1C1E;
            --card2: #151B23;
            --line: #2E3034;
            --line2: #222936;
            --line3: #222936;
            --ink: #F5F5F7;
            --mut: #A1A1A6;
            --blue: #4595FF;
            --blue2: #4DA3FF;
            --strong: #FFFFFF;
            --body: #CBD5E1;
            --soft: #94A3B8;
            --chip: #151B23;
            --chip2: #151B23;
            --chip-hov: #1C232D;
            --tab-on: #1C232D;
            --hov: #242629;
            --s800: #E2E8F0;
            --s700: #CBD5E1;
            --sep: #334155;
            --av-a: #2A313C;
            --av-b: #1A2029;
            --av-fg: #4B5563;
            --sh-lg: 0 10px 15px -3px rgba(0, 0, 0, .4), 0 4px 6px -4px rgba(0, 0, 0, .4);
            --sh-xl: 0 20px 25px -5px rgba(0, 0, 0, .4), 0 8px 10px -6px rgba(0, 0, 0, .4);
        }

        .hm-main, .hm-footer, .hm-prefs, .hm-modal, .hm-bnav {
            font-family: Inter, 'Noto Sans Khmer', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        .hm-km { font-family: 'Noto Sans Khmer', Inter, sans-serif; }
        .hm-mono { font-family: 'JetBrains Mono', 'Noto Sans Khmer', ui-monospace, SFMono-Regular, Menlo, monospace; }
        .hm-wrap { max-width: 80rem; margin: 0 auto; padding: 0 1rem; }
        @media (min-width: 640px) { .hm-wrap { padding: 0 1.5rem; } }
        @media (min-width: 1024px) { .hm-wrap { padding: 0 2rem; } }
        .hm-sr { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
        .hm-main a { text-decoration: none; }
        .i3 { width: .75rem; height: .75rem; flex-shrink: 0; }
        .i35 { width: .875rem; height: .875rem; flex-shrink: 0; }
        .i4 { width: 1rem; height: 1rem; flex-shrink: 0; }
        .i5 { width: 1.25rem; height: 1.25rem; flex-shrink: 0; }

        /* sections are spaced like the live site: 5rem, 6rem from 640px */
        .hm-main { display: flex; flex-direction: column; gap: 5rem; padding-bottom: 5rem; }
        @media (min-width: 640px) { .hm-main { gap: 6rem; } }
        .hm-main > section { width: 100%; min-width: 0; scroll-margin-top: 5rem; }

        /* ---------- blank profile ---------- */
        .hm-avatar { position: relative; flex-shrink: 0; overflow: hidden; display: flex; align-items: flex-end; justify-content: center; border-radius: 9999px; background: linear-gradient(to bottom, var(--av-a), var(--av-b)); color: var(--av-fg); }
        .hm-avatar svg { width: 82%; height: 82%; }

        /* ---------- section headings ---------- */
        .hm-head { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem; }
        @media (min-width: 768px) { .hm-head { flex-direction: row; align-items: flex-end; justify-content: space-between; } }
        .hm-head > div:first-child { min-width: 0; }
        .hm-label { display: flex; align-items: center; gap: .5rem; margin: 0 0 .25rem; font-size: .75rem; line-height: 1rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--sky); }
        .hm-h2 { margin: 0; font-size: 1.5rem; line-height: 2rem; font-weight: 700; color: var(--strong); }
        @media (min-width: 640px) { .hm-h2 { font-size: 1.875rem; line-height: 2.25rem; } }
        .hm-desc { margin: .25rem 0 0; max-width: 36rem; font-size: .75rem; line-height: 1rem; color: var(--soft); }
        @media (min-width: 640px) { .hm-desc { font-size: .875rem; line-height: 1.25rem; } }

        /* ==================================================================
           HERO
           ================================================================== */
        .hm-hero { position: relative; overflow: hidden; padding-top: 2rem; }
        @media (min-width: 640px) { .hm-hero { padding-top: 3.5rem; } }
        @media (min-width: 1024px) { .hm-hero { padding-top: 4.5rem; } }
        @media (min-width: 1280px) { .hm-hero { padding-bottom: 2.5rem; } }
        .hm-hero-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 3rem; align-items: center; }
        @media (min-width: 1024px) { .hm-hero-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 2rem; } }
        .hm-hero-copy { display: flex; flex-direction: column; gap: 1.5rem; }
        .hm-status { display: flex; align-items: center; gap: .5rem; font-size: .75rem; line-height: 1rem; font-weight: 500; color: var(--sky); }
        .hm-dot { width: .5rem; height: .5rem; flex-shrink: 0; border-radius: 9999px; background: var(--sky); animation: hm-pulse 2s cubic-bezier(.4, 0, .6, 1) infinite; }
        .hm-status-sep { color: var(--sep); }
        .hm-status-by { color: var(--soft); }
        .hm-h1 { margin: 0; font-size: 1.5rem; line-height: 1.25; font-weight: 900; letter-spacing: -.025em; color: var(--strong); }
        @media (min-width: 640px) { .hm-h1 { font-size: 2.25rem; line-height: 1.2; } }
        @media (min-width: 1024px) { .hm-h1 { font-size: 3rem; } }
        .hm-h1 span { color: var(--sky); }
        .hm-lead { margin: 0; max-width: 36rem; font-size: .875rem; line-height: 1.625; color: var(--body); }
        @media (min-width: 640px) { .hm-lead { font-size: 1rem; } }
        @media (min-width: 1024px) { .hm-lead { font-size: 1.125rem; } }
        .hm-ctas { display: flex; flex-direction: column; align-items: stretch; gap: .75rem; padding-top: .5rem; }
        @media (min-width: 640px) { .hm-ctas { flex-direction: row; align-items: center; } }
        .hm-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 46px; padding: .75rem 1.5rem; border-radius: .75rem; font-size: .875rem; line-height: 1.25rem; font-weight: 600; transition: all .15s ease; cursor: pointer; }
        .hm-btn-primary { color: #fff; background: #4DA3FF; box-shadow: 0 4px 6px -1px rgba(77, 163, 255, .2), 0 2px 4px -2px rgba(77, 163, 255, .2); }
        .hm-btn-primary:hover { background: #3892F3; }
        .hm-btn-primary svg { transition: transform .15s ease; }
        .hm-btn-primary:hover svg { transform: translateX(.25rem); }
        .hm-btn-ghost { color: #1E293B; background: #F1F5F9; border: 1px solid rgba(226, 232, 240, .8); }
        .hm-btn-ghost:hover { background: #E2E8F0; }
        html.dark .hm-btn-ghost { color: #E2E8F0; background: #151B23; border-color: #222936; }
        html.dark .hm-btn-ghost:hover { background: #1C232D; }

        /* ---------- design window ---------- */
        .hm-art { position: relative; width: 100%; max-width: 42rem; margin: 0 auto; display: flex; align-items: center; justify-content: center; padding: 1.5rem 0; user-select: none; }
        @media (min-width: 1024px) { .hm-art { max-width: none; height: 540px; padding: 0; } }
        @media (min-width: 1280px) { .hm-art { height: 600px; } }
        .hm-glow { position: absolute; border-radius: 9999px; pointer-events: none; }
        .hm-glow-a { width: 18rem; height: 18rem; top: -2rem; right: -2rem; background: rgba(24, 119, 242, .15); filter: blur(64px); }
        @media (min-width: 1024px) { .hm-glow-a { width: 24rem; height: 24rem; } }
        .hm-glow-b { width: 15rem; height: 15rem; bottom: -1.5rem; left: -2rem; background: rgba(59, 130, 246, .1); filter: blur(40px); }
        .hm-art-inner { position: relative; width: 100%; max-width: 32rem; margin: 0 auto; }
        .hm-window { position: relative; width: 100%; overflow: hidden; background: var(--card); border: 1px solid var(--line); border-radius: 1rem; box-shadow: var(--sh-xl); transition: all .3s ease; }
        .hm-titlebar { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .625rem .875rem; border-bottom: 1px solid var(--line); background: #F5F7FA; }
        html.dark .hm-titlebar { background: #242629; }
        @media (min-width: 640px) { .hm-titlebar { padding: .75rem 1rem; } }
        .hm-tb-left { display: flex; align-items: center; gap: .5rem; min-width: 0; }
        .hm-tb-left i { width: .625rem; height: .625rem; border-radius: 9999px; flex-shrink: 0; }
        .hm-file { margin-left: .5rem; max-width: 130px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .75rem; line-height: 1rem; letter-spacing: -.025em; color: var(--mut); }
        @media (min-width: 640px) { .hm-file { max-width: none; } }
        .hm-badge { flex-shrink: 0; display: flex; align-items: center; gap: .375rem; padding: .125rem .625rem; border-radius: .375rem; background: var(--card); border: 1px solid var(--line); box-shadow: 0 1px 2px rgba(0, 0, 0, .05); font-size: 11px; font-weight: 600; color: var(--blue); }
        .hm-canvas-pad { padding: .75rem; background: #F5F7FA; }
        html.dark .hm-canvas-pad { background: #0B0B0D; }
        @media (min-width: 640px) { .hm-canvas-pad { padding: 1.25rem; } }
        .hm-canvas { position: relative; min-height: 320px; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; padding: 1rem; border-radius: .75rem; background: #111317; color: #fff; border: 1px solid #2E3034; box-shadow: inset 0 2px 4px rgba(0, 0, 0, .05); cursor: pointer; transition: border-color .15s ease; }
        .hm-canvas:hover { border-color: rgba(24, 119, 242, .6); }
        @media (min-width: 640px) { .hm-canvas { min-height: 0; aspect-ratio: 4 / 3; padding: 1.5rem; } }
        .hm-grid-lines { position: absolute; inset: 0; pointer-events: none; background-image: linear-gradient(to right, #ffffff08 1px, transparent 1px), linear-gradient(to bottom, #ffffff08 1px, transparent 1px); background-size: 24px 24px; }
        .hm-canvas-row { position: relative; z-index: 10; margin: auto 0; padding: .75rem 0; display: flex; flex-direction: column; align-items: center; justify-content: space-between; gap: 1.25rem; text-align: center; }
        @media (min-width: 640px) { .hm-canvas-row { flex-direction: row; gap: 1rem; text-align: left; } }
        .hm-canvas-copy { display: flex; flex-direction: column; align-items: center; gap: .5rem; min-width: 0; }
        @media (min-width: 640px) { .hm-canvas-copy { align-items: flex-start; } }
        .hm-canvas-title { font-size: 1.5rem; line-height: 2rem; font-weight: 900; letter-spacing: -.025em; color: rgba(255, 255, 255, .95); }
        @media (min-width: 640px) { .hm-canvas-title { font-size: 1.875rem; line-height: 2.25rem; } }
        @media (min-width: 1024px) { .hm-canvas-title { font-size: 2.25rem; line-height: 2.5rem; } }
        .hm-canvas-sub { margin: 0; font-size: .75rem; line-height: 1rem; font-weight: 500; color: #CBD5E1; }
        .hm-tags { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .375rem; padding-top: .25rem; }
        @media (min-width: 640px) { .hm-tags { justify-content: flex-start; } }
        .hm-tag { padding: .125rem .5rem; border-radius: .25rem; font-size: 10px; }
        .hm-tag-b { background: rgba(59, 130, 246, .2); color: #93C5FD; border: 1px solid rgba(59, 130, 246, .3); }
        .hm-tag-a { background: rgba(245, 158, 11, .2); color: #FCD34D; border: 1px solid rgba(245, 158, 11, .3); }
        .hm-tag-p { background: rgba(168, 85, 247, .2); color: #D8B4FE; border: 1px solid rgba(168, 85, 247, .3); }
        .hm-canvas-foot { position: relative; z-index: 10; display: flex; align-items: center; justify-content: flex-end; padding-top: .75rem; border-top: 1px solid rgba(30, 41, 59, .8); font-size: 11px; color: #94A3B8; }
        .hm-canvas-foot span { display: flex; align-items: center; gap: .25rem; font-weight: 600; color: var(--blue); transition: transform .15s ease; }
        .hm-canvas:hover .hm-canvas-foot span { transform: translateX(.25rem); }
        .hm-wfoot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; padding: .75rem 1.25rem; background: var(--card); border-top: 1px solid var(--line); }
        .hm-wfoot-l { display: flex; align-items: center; gap: .5rem; }
        .hm-py { width: 1.5rem; height: 1.5rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; background: #E7F3FF; font-size: 10px; font-weight: 700; color: var(--blue); }
        html.dark .hm-py { background: rgba(69, 149, 255, .15); }
        .hm-wfoot-t { font-size: .75rem; line-height: 1rem; color: var(--mut); }
        .hm-wfoot-t strong { font-weight: 500; color: var(--ink); }
        .hm-wfoot-link { display: flex; align-items: center; gap: .25rem; font-size: .75rem; line-height: 1rem; font-weight: 600; color: var(--blue); }
        .hm-wfoot-link:hover { text-decoration: underline !important; }

        /* ---------- 3D stage ---------- */
        .hm-stage { position: relative; flex-shrink: 0; display: flex; align-items: center; justify-content: center; width: 100%; height: 15rem; perspective: 1000px; transition: transform .5s ease; }
        @media (min-width: 640px) { .hm-stage { width: 14rem; height: 14rem; } }
        .hm-canvas:hover .hm-stage { transform: scale(1.05); }
        .hm-stage-glow { position: absolute; inset: 1.5rem; border-radius: 9999px; filter: blur(40px); background: linear-gradient(to top right, rgba(24, 119, 242, .35), rgba(99, 102, 241, .25), rgba(34, 211, 238, .25)); animation: pulse-glow-3d 4s ease-in-out infinite; }
        .hm-star { position: absolute; width: 4px; height: 4px; border-radius: 9999px; background: #A5F3FC; box-shadow: 0 0 6px #67E8F9; animation: hm-twinkle 3s ease-in-out infinite; }
        .hm-ring-tilt { position: absolute; width: 14rem; height: 14rem; border-radius: 9999px; border: 1px solid rgba(129, 140, 248, .2); display: flex; justify-content: center; align-items: flex-start; animation: hm-orbit-reverse 26s linear infinite; }
        .hm-ring-tilt i { margin-top: -3px; width: 6px; height: 6px; border-radius: 9999px; background: #A5B4FC; box-shadow: 0 0 10px #A5B4FC; }
        .hm-orbit { position: absolute; width: 12rem; height: 12rem; border-radius: 9999px; border: 1px dashed rgba(24, 119, 242, .4); display: flex; justify-content: center; align-items: flex-start; animation: float-3d-orbit 18s linear infinite; }
        .hm-orbit i { margin-top: -5px; width: 10px; height: 10px; border-radius: 9999px; background: #22D3EE; box-shadow: 0 0 12px #22D3EE; }
        .hm-scene { position: relative; width: 14rem; height: 14rem; display: flex; align-items: center; justify-content: center; transform-style: preserve-3d; }
        .hm-cube-shadow { position: absolute; bottom: 1.75rem; width: 6rem; height: 1rem; border-radius: 9999px; background: rgba(24, 119, 242, .45); filter: blur(12px); animation: hm-shadow-breathe 6s ease-in-out infinite; }
        .hm-cube { position: relative; width: 120px; height: 120px; border-radius: 24px; overflow: hidden; border: 1px solid rgba(96, 165, 250, .4); background: linear-gradient(to bottom right, #1E293B, #0F172A, #000); box-shadow: 0 20px 35px -8px rgba(0, 0, 0, .85), inset 0 1px 1px rgba(255, 255, 255, .3); transform-style: preserve-3d; animation: float-3d-main 6s ease-in-out infinite; }
        .hm-cube-grid { position: absolute; inset: 0; background-image: linear-gradient(to right, #38bdf818 1px, transparent 1px), linear-gradient(to bottom, #38bdf818 1px, transparent 1px); background-size: 12px 12px; }
        .hm-cube-sheen { position: absolute; top: 0; bottom: 0; left: 0; width: 33%; background: linear-gradient(to right, transparent, rgba(255, 255, 255, .22), transparent); animation: hm-sheen 4.8s ease-in-out 1s infinite; }
        /* Sunlight from the yellow tile. It lives INSIDE the cube (which clips
           its children), so the light lands on the "3D STUDIO" face and can
           never spill past the cube. */
        .hm-cube-beam { position: absolute; inset: 0; pointer-events: none; background: conic-gradient(from 90deg at 0 0, rgba(255, 214, 90, 0) 0deg, rgba(255, 228, 130, .5) 24deg, rgba(255, 240, 170, .7) 45deg, rgba(255, 228, 130, .5) 66deg, rgba(255, 214, 90, 0) 90deg); -webkit-mask-image: radial-gradient(circle at 0 0, #000 18%, rgba(0, 0, 0, .55) 55%, transparent 92%); mask-image: radial-gradient(circle at 0 0, #000 18%, rgba(0, 0, 0, .55) 55%, transparent 92%); mix-blend-mode: screen; animation: hm-beam 3s ease-in-out infinite; }
        .hm-cube-hit { position: absolute; inset: 0; pointer-events: none; background: radial-gradient(circle at 18% 14%, rgba(255, 226, 120, .55), rgba(255, 196, 60, .18) 34%, transparent 60%), radial-gradient(ellipse 60% 34% at 50% 74%, rgba(255, 214, 90, .28), transparent 70%); animation: hm-beam 3s ease-in-out infinite; }
        .hm-cube-motes { position: absolute; left: 0; top: 0; width: 160px; height: 0; transform-origin: 0 0; transform: rotate(45deg); pointer-events: none; }
        .hm-cube-motes i { position: absolute; left: 0; width: 5px; height: 5px; border-radius: 9999px; background: #FFF3B0; box-shadow: 0 0 8px #FFD54A; animation: hm-mote 2.7s linear infinite; }
        .hm-cube-face { position: relative; z-index: 10; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .hm-cube-face svg { width: 3rem; height: 3rem; filter: drop-shadow(0 4px 16px rgba(56, 189, 248, .7)); }
        .hm-cube-face span { margin-top: .25rem; font-size: 9px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: #A5F3FC; text-shadow: 0 0 10px rgba(34, 211, 238, .7), 0 0 12px rgba(255, 214, 90, .55); }
        .hm-cube-corner { position: absolute; top: -1.5rem; left: -1.5rem; width: 5rem; height: 5rem; background: rgba(255, 255, 255, .1); transform: rotate(45deg); filter: blur(4px); pointer-events: none; }
        .hm-sat { position: absolute; z-index: 20; transform-style: preserve-3d; }
        .hm-sat-sun { top: 1.5rem; left: 1.5rem; animation: float-3d-icon-b 3.8s ease-in-out .2s infinite; }
        .hm-sat-figma { top: .5rem; right: 1rem; animation: float-3d-icon-a 4.2s ease-in-out infinite; }
        .hm-sat-ps { bottom: .75rem; left: 1rem; animation: float-3d-icon-b 4.8s ease-in-out .4s infinite; }
        .hm-sat-ai { bottom: 1.25rem; right: 1.5rem; animation: float-3d-icon-c 5.4s ease-in-out .8s infinite; }
        .hm-tile { display: flex; align-items: center; justify-content: center; border-radius: .75rem; font-weight: 900; letter-spacing: -.025em; transition: transform .15s ease; }
        .hm-tile:hover { transform: scale(1.1); }
        .hm-tile-sun { position: relative; width: 2.5rem; height: 2.5rem; border-radius: .5rem; background: linear-gradient(to top right, #FBBF24, #FEF08A); color: #451A03; border: 1px solid #FCD34D; box-shadow: 0 4px 12px rgba(251, 191, 36, .6), inset 0 1px 1px rgba(255, 255, 255, .8), 0 0 26px rgba(255, 214, 90, .65); font-size: 1rem; font-weight: 700; }
        .hm-tile-figma { width: 3rem; height: 3rem; padding: .375rem; background: #1E1E1E; border: 1px solid rgba(255, 255, 255, .2); box-shadow: 0 10px 20px rgba(0, 0, 0, .7), inset 0 1px 1px rgba(255, 255, 255, .25); }
        .hm-tile-ps { width: 2.75rem; height: 2.75rem; font-size: .875rem; color: #31A8FF; background: linear-gradient(to bottom, #001E36, #000E1C); border: 1px solid rgba(49, 168, 255, .8); box-shadow: 0 10px 20px rgba(0, 14, 28, .8), inset 0 1px 1px rgba(49, 168, 255, .6); }
        .hm-tile-ps span { filter: drop-shadow(0 2px 8px rgba(49, 168, 255, .9)); }
        .hm-tile-ai { width: 2.5rem; height: 2.5rem; font-size: .75rem; color: #FF9A00; background: linear-gradient(to bottom, #330000, #1A0000); border: 1px solid rgba(255, 154, 0, .8); box-shadow: 0 10px 20px rgba(26, 0, 0, .8), inset 0 1px 1px rgba(255, 154, 0, .6); }
        .hm-tile-ai span { filter: drop-shadow(0 2px 8px rgba(255, 154, 0, .9)); }
        /* the sun's own glow: rays and a halo around the tile only */
        .hm-sun-rays { position: absolute; inset: -1.5rem; z-index: -1; border-radius: 9999px; pointer-events: none; background: repeating-conic-gradient(rgba(255, 214, 90, .45) 0deg 5deg, rgba(255, 214, 90, 0) 5deg 22deg); -webkit-mask-image: radial-gradient(circle, #000 28%, transparent 70%); mask-image: radial-gradient(circle, #000 28%, transparent 70%); animation: hm-sun-spin 16s linear infinite; }
        .hm-sun-halo { position: absolute; inset: -.875rem; z-index: -1; border-radius: 9999px; background: rgba(255, 213, 74, .5); filter: blur(20px); pointer-events: none; animation: hm-sun-pulse 3s ease-in-out infinite; }

        /* ---------- floating cards (1280px and wider, like the live site) ---------- */
        .hm-floats { display: none; }
        @media (min-width: 1280px) { .hm-floats { display: block; } }
        .hm-float { position: absolute; z-index: 20; }
        .hm-float-progress { top: -56px; left: -2.5rem; animation: hm-float-y 5.5s ease-in-out infinite; }
        .hm-float-cert { top: 66px; right: -2rem; animation: hm-float-y-sm 4.6s ease-in-out .6s infinite; }
        .hm-float-project { bottom: 4.25rem; left: -3rem; animation: hm-float-y 6.2s ease-in-out 1.1s infinite; }
        .hm-float-learning { bottom: -78px; right: -1.5rem; animation: hm-float-y-sm 5s ease-in-out .3s infinite; }
        .hm-fcard { display: flex; align-items: center; gap: .75rem; padding: .875rem; border-radius: 1rem; background: var(--card); border: 1px solid var(--line); box-shadow: var(--sh-lg); }
        .hm-fcard small { display: block; font-size: 11px; font-weight: 500; color: var(--mut); }
        .hm-fcard strong { display: block; font-size: .75rem; line-height: 1rem; font-weight: 600; color: var(--ink); }
        .hm-ring { position: relative; width: 2.75rem; height: 2.75rem; display: flex; align-items: center; justify-content: center; }
        .hm-ring svg { width: 2.75rem; height: 2.75rem; transform: rotate(-90deg); }
        .hm-ring .track { stroke: #F1F5F9; }
        html.dark .hm-ring .track { stroke: #1E293B; }
        .hm-ring .bar { stroke: var(--blue); animation: hm-ring-draw 1.6s cubic-bezier(.22, 1, .36, 1) .3s both; }
        .hm-ring b { position: absolute; font-size: 11px; font-weight: 700; color: var(--ink); font-variant-numeric: tabular-nums; }
        .hm-fcard-cert { gap: .5rem; padding: .375rem .75rem; border-radius: 9999px; }
        .hm-fcard-cert strong { font-weight: 500; }
        .hm-fcard-project { gap: .625rem; padding: .625rem .875rem; }
        .hm-no1 { width: 2rem; height: 2rem; flex-shrink: 0; border-radius: .75rem; display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 600; background: #FFFBEB; color: #D97706; }
        html.dark .hm-no1 { background: rgba(69, 26, 3, .4); color: #FBBF24; }
        .hm-amber { display: block; font-size: 11px; font-weight: 500; color: #D97706; }
        html.dark .hm-amber { color: #FBBF24; }
        .hm-play { width: 2.5rem; height: 2.5rem; flex-shrink: 0; border-radius: .75rem; background: #1877F2; color: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px rgba(0, 0, 0, .05); }
        .hm-live { display: flex; align-items: center; gap: .375rem; font-size: 10px; font-weight: 500; color: var(--blue); }
        .hm-live::before { content: ""; width: 6px; height: 6px; border-radius: 9999px; background: #1877F2; animation: hm-pulse 2s cubic-bezier(.4, 0, .6, 1) infinite; }
        .hm-fcard .hm-clamp1 { max-width: 170px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .hm-fcard .hm-mins { display: flex; align-items: center; gap: .25rem; font-size: 11px; color: var(--mut); }

        /* ==================================================================
           JELLY FILTER TABS (styled like the live tabs, moves like jelly)
           ================================================================== */
        .hm-tabs { align-self: flex-start; max-width: 100%; overflow-x: auto; padding: .375rem; border-radius: 1rem; background: var(--chip); border: 1px solid var(--line2); box-shadow: 0 1px 2px rgba(0, 0, 0, .05); scrollbar-width: none; }
        @media (min-width: 768px) { .hm-tabs { align-self: auto; } }
        .hm-tabs::-webkit-scrollbar { display: none; }
        .hm-tabs--sm { padding: .25rem; }
        .hm-tabs-track { position: relative; display: flex; width: max-content; align-items: center; gap: .25rem; }
        .hm-tabs--sm .hm-tabs-track { gap: .375rem; }
        .hm-tab { position: relative; z-index: 1; flex-shrink: 0; display: flex; align-items: center; justify-content: center; min-height: 40px; padding: .5rem 1rem; border: 0; border-radius: .75rem; background: transparent; font: inherit; font-size: .75rem; line-height: 1rem; font-weight: 600; white-space: nowrap; color: #475569; cursor: pointer; transition: color .3s ease; }
        html.dark .hm-tab { color: #94A3B8; }
        .hm-tabs--sm .hm-tab { min-height: 0; padding: .375rem .875rem; }
        .hm-tab:hover { color: var(--strong); }
        .hm-tab[aria-selected="true"] { color: var(--blue); }
        html.dark .hm-tab[aria-selected="true"] { color: #4DA3FF; }
        .hm-tab span { display: inline-block; }
        .hm-tab span.is-pop { animation: hm-label-pop 420ms cubic-bezier(.34, 1.56, .64, 1) both; }
        .hm-tab:focus-visible { outline: 2px solid var(--sky); outline-offset: 1px; }
        .hm-jelly, .hm-jelly-trail { position: absolute; top: 0; bottom: 0; left: 0; pointer-events: none; }
        .hm-jelly { transition: transform 580ms cubic-bezier(.34, 1.56, .64, 1), width 580ms cubic-bezier(.34, 1.56, .64, 1); }
        .hm-jelly-trail { border-radius: .75rem; background: rgba(77, 163, 255, .28); filter: blur(10px); transition: transform 860ms cubic-bezier(.22, 1, .36, 1), width 860ms cubic-bezier(.22, 1, .36, 1); }
        .hm-jelly-blob { position: relative; display: block; width: 100%; height: 100%; overflow: hidden; border-radius: .75rem; background: var(--tab-on); box-shadow: 0 1px 2px rgba(0, 0, 0, .05), 0 6px 16px -6px rgba(24, 119, 242, .35); }
        .hm-jelly-blob::after { content: ""; position: absolute; top: 0; bottom: 0; left: 0; width: 33%; opacity: 0; background: linear-gradient(to right, transparent, rgba(77, 163, 255, .35), transparent); }
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
        .hm-ccard { position: relative; display: flex; flex-direction: column; overflow: hidden; background: var(--card); border: 1px solid var(--line); border-radius: 1rem; box-shadow: 0 1px 2px rgba(0, 0, 0, .05); transition: all .3s ease; }
        .hm-ccard:hover { transform: translateY(-.25rem); border-color: rgba(24, 119, 242, .5); box-shadow: var(--sh-xl); }
        html.dark .hm-ccard:hover { border-color: rgba(69, 149, 255, .5); }
        .hm-ccard[hidden], .hm-scard[hidden] { display: none; }
        .hm-ccard.is-in, .hm-scard.is-in { animation: hm-pop-in 420ms cubic-bezier(.22, 1, .36, 1) both; }
        .hm-ccover { position: relative; aspect-ratio: 16 / 9; width: 100%; overflow: hidden; padding: 1.25rem; display: flex; flex-direction: column; justify-content: space-between; background: #020617; border-bottom: 1px solid var(--line); cursor: pointer; }
        .hm-ccover img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; transition: transform .7s ease-out; }
        .hm-ccard:hover .hm-ccover img { transform: scale(1.05); }
        .hm-ccover-shade { position: absolute; inset: 0; pointer-events: none; background: linear-gradient(to top, rgba(0, 0, 0, .9), rgba(0, 0, 0, .45), rgba(0, 0, 0, .35)); }
        .hm-ccover-dots { position: absolute; inset: 0; pointer-events: none; opacity: .15; background-image: radial-gradient(#ffffff22 1px, transparent 1px); background-size: 16px 16px; }
        .hm-ccover-top, .hm-ccover-mid, .hm-ccover-bot { position: relative; z-index: 10; }
        .hm-ccover-top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .hm-chips { display: flex; align-items: center; gap: .375rem; }
        .hm-chip { width: 1.5rem; height: 1.5rem; border-radius: .375rem; display: flex; align-items: center; justify-content: center; background: rgba(0, 0, 0, .6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, .1); color: #fff; font-size: 10px; font-weight: 700; }
        .hm-ccover-tr { display: flex; align-items: center; gap: .5rem; }
        .hm-cat { padding: .125rem .625rem; border-radius: 9999px; background: rgba(255, 255, 255, .15); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, .15); font-size: 11px; font-weight: 500; color: rgba(255, 255, 255, .9); white-space: nowrap; }
        .hm-heart { width: 1.75rem; height: 1.75rem; flex-shrink: 0; border-radius: 9999px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(12px); background: rgba(0, 0, 0, .5); border: 1px solid rgba(255, 255, 255, .2); color: rgba(255, 255, 255, .8); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1); cursor: pointer; transition: all .15s ease; }
        .hm-heart:hover { background: rgba(0, 0, 0, .75); color: #fff; }
        .hm-heart[aria-pressed="true"] { background: #F43F5E; border-color: transparent; color: #fff; box-shadow: 0 4px 6px -1px rgba(244, 63, 94, .3); }
        .hm-heart[aria-pressed="true"] svg { fill: #fff; transform: scale(1.1); }
        .hm-heart svg { transition: transform .15s ease; }
        .hm-ccover-mid { margin: auto 0; padding: .5rem 0; }
        .hm-ccover-title { margin: 0; font-size: 1.25rem; line-height: 1.75rem; font-weight: 900; color: #fff; filter: drop-shadow(0 1px 1px rgba(0, 0, 0, .05)); }
        @media (min-width: 640px) { .hm-ccover-title { font-size: 1.5rem; line-height: 2rem; } }
        .hm-ccover-tools { margin: .125rem 0 0; font-size: .75rem; line-height: 1rem; color: rgba(255, 255, 255, .7); }
        .hm-ccover-bot { display: flex; align-items: center; justify-content: space-between; gap: .5rem; font-size: 11px; color: rgba(255, 255, 255, .8); }
        .hm-ccover-bot > span { display: flex; align-items: center; gap: .25rem; }
        .hm-ccover-bot strong { font-weight: 600; color: #fff; }
        .hm-ccover-bot .hm-mono { color: rgba(255, 255, 255, .7); }
        .hm-cbody { flex: 1; display: flex; flex-direction: column; justify-content: space-between; gap: 1rem; padding: 1.25rem; }
        @media (min-width: 640px) { .hm-cbody { padding: 1.5rem; } }
        .hm-ctitle { margin: 0; font-size: 1.125rem; line-height: 1.75rem; font-weight: 700; }
        @media (min-width: 640px) { .hm-ctitle { font-size: 1.25rem; } }
        .hm-ctitle a { color: var(--ink); transition: color .15s ease; }
        .hm-ccard:hover .hm-ctitle a { color: var(--blue); }
        .hm-ctools { margin: .125rem 0 0; font-size: .75rem; line-height: 1rem; font-weight: 600; color: var(--blue); }
        .hm-cdesc { margin: .5rem 0 0; font-size: .75rem; line-height: 1.625; color: var(--mut); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .hm-cmeta { margin-top: .875rem; display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; font-size: .75rem; line-height: 1rem; font-weight: 500; color: var(--mut); }
        .hm-cmeta .sep { color: var(--line); }
        .hm-cfoot { display: flex; flex-direction: column; gap: .75rem; padding-top: .75rem; border-top: 1px solid var(--line); }
        .hm-progress-row, .hm-tuition { display: flex; align-items: center; justify-content: space-between; gap: .5rem; font-size: .75rem; line-height: 1rem; color: var(--mut); }
        .hm-progress-row b { font-weight: 700; color: var(--blue); font-variant-numeric: tabular-nums; }
        .hm-bar { margin-top: .375rem; width: 100%; height: .5rem; overflow: hidden; border-radius: 9999px; background: #F5F7FA; border: 1px solid rgba(228, 230, 235, .5); }
        html.dark .hm-bar { background: #242629; border-color: #2E3034; }
        .hm-bar i { display: block; height: 100%; border-radius: 9999px; background: var(--blue); transition: width .5s ease; }
        .hm-price { text-align: right; }
        .hm-price b { font-size: 1rem; line-height: 1.5rem; font-weight: 700; color: var(--ink); font-variant-numeric: tabular-nums; }
        .hm-price s { margin-left: .375rem; font-size: .75rem; color: var(--mut); font-variant-numeric: tabular-nums; }
        .hm-cbtns { display: flex; flex-direction: column; align-items: stretch; gap: .5rem; padding-top: .125rem; }
        @media (min-width: 640px) { .hm-cbtns { flex-direction: row; align-items: center; } }
        .hm-cbtn-ghost, .hm-cbtn { display: flex; align-items: center; justify-content: center; min-height: 44px; border-radius: .75rem; font-size: .75rem; line-height: 1rem; font-weight: 600; transition: all .15s ease; }
        @media (min-width: 640px) { .hm-cbtn-ghost, .hm-cbtn { min-height: 0; } }
        .hm-cbtn-ghost { flex: 1; padding: .625rem .75rem; color: var(--ink); border: 1px solid var(--line); }
        .hm-cbtn-ghost:hover { background: var(--hov); }
        .hm-cbtn { gap: .25rem; padding: .625rem 1rem; color: #fff; background: #1877F2; white-space: nowrap; box-shadow: 0 1px 2px rgba(0, 0, 0, .05); }
        .hm-cbtn:hover { background: #166FE5; }
        .hm-cbtn--play { gap: .375rem; }
        .hm-empty { padding: 4rem 0; text-align: center; color: var(--mut); }

        /* ==================================================================
           INSTRUCTOR
           ================================================================== */
        .hm-teach { position: relative; overflow: hidden; padding: 1.25rem; border-radius: 1.5rem; background: var(--card); border: 1px solid var(--line); box-shadow: 0 1px 3px rgba(0, 0, 0, .1), 0 1px 2px -1px rgba(0, 0, 0, .1); }
        @media (min-width: 640px) { .hm-teach { padding: 2rem; } }
        @media (min-width: 1024px) { .hm-teach { padding: 3rem; } }
        .hm-teach-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 2rem; align-items: start; }
        @media (min-width: 1024px) { .hm-teach-grid { grid-template-columns: minmax(0, 4fr) minmax(0, 8fr); } }
        .hm-teach-left { display: flex; flex-direction: column; align-items: center; gap: 1rem; }
        .hm-portrait { position: relative; }
        .hm-portrait-img { width: 14rem; height: 14rem; overflow: hidden; border-radius: 1rem; border: 2px solid #E2E8F0; box-shadow: var(--sh-xl); display: flex; align-items: flex-end; justify-content: center; background: linear-gradient(to bottom, var(--av-a), var(--av-b)); color: var(--av-fg); }
        html.dark .hm-portrait-img { border-color: #2B3545; }
        @media (min-width: 640px) { .hm-portrait-img { width: 16rem; height: 16rem; } }
        .hm-portrait-img svg { width: 80%; height: 80%; }
        .hm-lect { position: absolute; right: -.75rem; bottom: -.75rem; display: flex; align-items: center; gap: .5rem; padding: .625rem; border-radius: .75rem; background: #fff; border: 1px solid #E2E8F0; box-shadow: var(--sh-lg); text-align: left; }
        html.dark .hm-lect { background: #0B0F14; border-color: #222936; }
        .hm-lect svg { color: var(--sky); }
        .hm-lect .a { display: block; font-size: 11px; font-weight: 700; color: var(--strong); }
        .hm-lect .b { display: block; font-size: 10px; color: var(--faint); }
        .hm-stats { width: 100%; max-width: 260px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; padding-top: .5rem; text-align: center; }
        .hm-stat { padding: .625rem; border-radius: .75rem; background: var(--chip2); border: 1px solid var(--line2); }
        .hm-stat .a { display: block; font-size: .875rem; line-height: 1.25rem; font-weight: 800; color: var(--strong); }
        .hm-stat .b { display: block; font-size: 10px; color: var(--soft); }
        .hm-teach-right { display: flex; flex-direction: column; gap: 1.25rem; text-align: left; }
        .hm-label--row { margin: 0; }
        .hm-label--row .k { color: var(--faint); text-transform: none; }
        .hm-tname { margin: .25rem 0 0; font-size: 1.5rem; line-height: 2rem; font-weight: 700; color: var(--strong); }
        @media (min-width: 640px) { .hm-tname { font-size: 1.875rem; line-height: 2.25rem; } }
        .hm-trole { margin: .25rem 0 0; font-size: .875rem; line-height: 1.25rem; font-weight: 500; color: var(--s700); }
        .hm-tquote { margin: 0; padding-left: 1rem; border-left: 2px solid var(--sky); font-style: italic; font-size: .875rem; line-height: 1.625; color: var(--body); }
        .hm-skills-wrap { display: flex; flex-direction: column; gap: .75rem; padding-top: .5rem; }
        .hm-skills-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding-bottom: .5rem; border-bottom: 1px solid var(--line3); }
        html.dark .hm-skills-head { border-color: #262F3D; }
        .hm-skills-head > div { display: flex; align-items: center; gap: .5rem; min-width: 0; }
        .hm-skills-head svg { color: var(--sky); }
        .hm-skills-head h4 { margin: 0; font-size: .875rem; line-height: 1.25rem; font-weight: 700; color: var(--strong); }
        .hm-spec { flex-shrink: 0; padding: .125rem .625rem; border-radius: 9999px; background: #EAF5FF; border: 1px solid rgba(77, 163, 255, .2); font-size: 11px; font-weight: 600; color: #1877F2; }
        html.dark .hm-spec { background: rgba(77, 163, 255, .15); color: #4DA3FF; }
        .hm-skills { display: grid; grid-template-columns: minmax(0, 1fr); gap: .625rem; padding-top: .25rem; }
        @media (min-width: 640px) { .hm-skills { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        .hm-skill { display: flex; align-items: center; justify-content: space-between; gap: .625rem; padding: .625rem .75rem; border-radius: 1rem; background: #F8FAFC; border: 1px solid rgba(226, 232, 240, .8); box-shadow: 0 1px rgba(0, 0, 0, .05); transition: all .2s ease; }
        .hm-skill:hover { background: #fff; border-color: rgba(77, 163, 255, .5); box-shadow: 0 1px 2px rgba(0, 0, 0, .05); }
        html.dark .hm-skill { background: #151B23; border-color: #222936; }
        html.dark .hm-skill:hover { background: #1C232D; border-color: rgba(77, 163, 255, .4); }
        .hm-skill > div { display: flex; align-items: center; gap: .625rem; min-width: 0; }
        .hm-skill .e { flex-shrink: 0; font-size: 1.125rem; line-height: 1.75rem; user-select: none; transition: transform .2s ease; }
        .hm-skill:hover .e { transform: scale(1.1); }
        .hm-skill .t { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .75rem; line-height: 1rem; font-weight: 600; color: var(--s800); transition: color .2s ease; }
        @media (min-width: 640px) { .hm-skill .t { font-size: 13px; } }
        .hm-skill:hover .t { color: #1877F2; }
        html.dark .hm-skill:hover .t { color: #4DA3FF; }
        .hm-skill .g { display: none; flex-shrink: 0; padding: .125rem .5rem; border-radius: .375rem; background: #fff; border: 1px solid rgba(226, 232, 240, .6); font-size: 10px; font-weight: 500; text-transform: uppercase; letter-spacing: .05em; color: var(--faint); }
        html.dark .hm-skill .g { background: #0B0F14; border-color: #262F3D; }
        @media (min-width: 768px) { .hm-skill .g { display: inline-block; } }
        .hm-trust-row { display: flex; flex-direction: column; justify-content: space-between; gap: .75rem; padding-top: .75rem; border-top: 1px solid var(--line3); }
        @media (min-width: 640px) { .hm-trust-row { flex-direction: row; align-items: center; } }
        .hm-trust { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; font-size: .75rem; line-height: 1rem; color: #475569; }
        html.dark .hm-trust { color: #94A3B8; }
        .hm-trust > span { display: flex; align-items: center; gap: .375rem; }
        .hm-trust svg { color: var(--sky); }
        .hm-tlink { flex-shrink: 0; align-self: flex-start; display: flex; align-items: center; gap: .25rem; font-size: .75rem; line-height: 1rem; font-weight: 600; color: var(--blue2); }
        @media (min-width: 640px) { .hm-tlink { align-self: auto; } }
        .hm-tlink:hover { text-decoration: underline !important; }

        /* ==================================================================
           SHOWCASE
           ================================================================== */
        .hm-show-sec { display: flex; flex-direction: column; gap: 1.5rem; }
        .hm-show-sec .hm-head { margin-bottom: 0; }
        .hm-show-title { margin: 0; font-size: 1.25rem; line-height: 1.75rem; font-weight: 900; color: var(--strong); }
        @media (min-width: 640px) { .hm-show-title { font-size: 1.875rem; line-height: 2.25rem; } }
        .hm-show-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; }
        @media (min-width: 640px) { .hm-show-grid { gap: 2rem; } }
        @media (min-width: 768px) { .hm-show-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { .hm-show-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        .hm-scard { display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; border-radius: 1.5rem; background: var(--card2); border: 1px solid var(--line2); box-shadow: 0 1px 2px rgba(0, 0, 0, .05); cursor: pointer; transition: all .3s ease; }
        .hm-scard:hover { border-color: rgba(77, 163, 255, .4); box-shadow: var(--sh-xl); }
        .hm-scard:focus-visible { outline: 2px solid var(--sky); outline-offset: 2px; }
        .hm-simg { position: relative; aspect-ratio: 16 / 10; overflow: hidden; background: #0F172A; }
        .hm-simg img { width: 100%; height: 100%; object-fit: cover; transition: transform .7s ease; }
        .hm-scard:hover .hm-simg img { transform: scale(1.05); }
        .hm-shover { position: absolute; inset: 0; display: flex; align-items: flex-end; padding: 1rem; opacity: 0; background: linear-gradient(to top, rgba(0, 0, 0, .8), rgba(0, 0, 0, .2), transparent); transition: opacity .15s ease; pointer-events: none; }
        .hm-scard:hover .hm-shover, .hm-scard:focus-visible .hm-shover { opacity: 1; }
        .hm-shover > div { width: 100%; display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .hm-inspect { display: flex; align-items: center; gap: .375rem; padding: .25rem .75rem; border-radius: 9999px; background: rgba(255, 255, 255, .2); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, .3); font-size: .75rem; line-height: 1rem; font-weight: 600; color: #fff; }
        .hm-sgrade { padding: .125rem .625rem; border-radius: .375rem; background: rgba(24, 119, 242, .8); backdrop-filter: blur(12px); font-size: .75rem; line-height: 1rem; font-weight: 700; color: rgba(255, 255, 255, .9); }
        .hm-sbadge { position: absolute; top: .75rem; left: .75rem; display: flex; align-items: center; gap: .25rem; padding: .25rem .625rem; border-radius: .5rem; background: rgba(15, 23, 42, .8); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, .1); box-shadow: 0 1px 3px rgba(0, 0, 0, .1); font-size: 10px; font-weight: 600; color: #fff; }
        .hm-sbadge svg { color: #FBBF24; }
        .hm-slike { position: absolute; top: .75rem; right: .75rem; width: 2rem; height: 2rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(12px); background: rgba(0, 0, 0, .4); border: 1px solid rgba(255, 255, 255, .2); color: rgba(255, 255, 255, .9); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1); cursor: pointer; transition: all .15s ease; }
        .hm-slike:hover { background: rgba(0, 0, 0, .6); }
        .hm-slike[aria-pressed="true"] { background: #F43F5E; border-color: transparent; color: #fff; }
        .hm-slike[aria-pressed="true"] svg { fill: #fff; }
        .hm-sbody { display: flex; flex-direction: column; gap: .75rem; padding: 1.25rem; }
        .hm-srow { display: flex; align-items: center; justify-content: space-between; font-size: .75rem; line-height: 1rem; }
        .hm-scourse { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .025em; color: var(--sky); }
        .hm-scount { display: flex; align-items: center; gap: .25rem; font-size: .75rem; color: var(--soft); }
        .hm-scount svg { color: var(--faint); }
        .hm-scount.is-on svg { color: #F43F5E; fill: #F43F5E; }
        .hm-stitle { margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 1rem; line-height: 1.375; font-weight: 700; color: var(--strong); transition: color .15s ease; }
        .hm-scard:hover .hm-stitle { color: var(--sky); }
        .hm-skh { margin: .125rem 0 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .75rem; line-height: 1rem; color: var(--soft); }
        .hm-sdesc { margin: 0; font-size: .75rem; line-height: 1.625; color: var(--body); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .hm-sfoot { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .5rem 1.25rem 1.25rem; border-top: 1px solid var(--line3); }
        .hm-sstudent { display: flex; align-items: center; gap: .625rem; min-width: 0; }
        .hm-sstudent img { width: 1.75rem; height: 1.75rem; flex-shrink: 0; border-radius: 9999px; object-fit: cover; box-shadow: 0 0 0 1px rgba(77, 163, 255, .4); }
        .hm-sstudent .a { display: block; font-size: .75rem; line-height: 1.25; font-weight: 700; color: var(--s800); }
        .hm-sstudent .b { display: block; font-size: 10px; color: var(--faint); }
        .hm-stools { display: flex; align-items: center; gap: .25rem; flex-shrink: 0; }
        .hm-stool { padding: .125rem .5rem; border-radius: .25rem; background: #F1F5F9; font-size: 10px; font-weight: 500; color: #475569; }
        html.dark .hm-stool { background: #1C232D; color: #CBD5E1; }
        .hm-smore { font-size: 9px; color: var(--faint); }

        /* ---------- project dialog ---------- */
        .hm-modal { position: fixed; inset: 0; z-index: 70; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(0, 0, 0, .8); backdrop-filter: blur(12px); animation: hm-fade-in .2s ease-out both; }
        .hm-modal[hidden] { display: none; }
        @media (min-width: 640px) { .hm-modal { padding: 1.5rem; } }
        .hm-dialog { display: flex; flex-direction: column; width: 100%; max-width: 56rem; max-height: 90vh; overflow: hidden; border-radius: 1.5rem; background: #fff; border: 1px solid #E2E8F0; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .25); color: var(--strong); animation: hm-zoom-in .2s ease-out both; }
        html.dark .hm-dialog { background: #151B23; border-color: #2E3034; }
        .hm-dhead { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem; border-bottom: 1px solid var(--line3); }
        @media (min-width: 640px) { .hm-dhead { padding: 1.25rem; } }
        .hm-dhead-l { display: flex; align-items: center; gap: .75rem; min-width: 0; }
        .hm-dicon { width: 2.5rem; height: 2.5rem; flex-shrink: 0; border-radius: 1rem; display: flex; align-items: center; justify-content: center; background: rgba(77, 163, 255, .1); color: var(--sky); }
        .hm-dkicker { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--sky); }
        .hm-dtitle { margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 1rem; line-height: 1.5rem; font-weight: 700; color: var(--strong); }
        @media (min-width: 640px) { .hm-dtitle { font-size: 1.125rem; line-height: 1.75rem; } }
        .hm-dacts { display: flex; align-items: center; gap: .5rem; flex-shrink: 0; }
        .hm-dbtn { display: flex; align-items: center; gap: .375rem; padding: .5rem; border: 0; border-radius: .75rem; background: transparent; font: inherit; font-size: .75rem; font-weight: 600; color: #475569; cursor: pointer; transition: background .15s ease, color .15s ease; }
        html.dark .hm-dbtn { color: #94A3B8; }
        .hm-dbtn:hover { background: #F1F5F9; }
        html.dark .hm-dbtn:hover { background: #1C232D; }
        .hm-dbtn--close { color: var(--faint); }
        .hm-dbtn--close:hover { color: var(--strong); }
        .hm-share-label { display: none; }
        @media (min-width: 640px) { .hm-share-label { display: inline; } }
        .hm-dbtn .is-ok { color: #10B981; }
        .hm-dbody { overflow-y: auto; overscroll-behavior: contain; padding: 1.25rem; }
        @media (min-width: 640px) { .hm-dbody { padding: 1.5rem; } }
        .hm-dbody { display: flex; flex-direction: column; gap: 1.5rem; }
        .hm-dbody > * { flex-shrink: 0; }
        .hm-dshot { max-height: 500px; overflow: hidden; display: flex; align-items: center; justify-content: center; border-radius: 1rem; background: #020617; border: 1px solid var(--line2); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1); }
        .hm-dshot img { width: 100%; height: 100%; max-height: 480px; object-fit: contain; }
        .hm-dgrid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; padding-top: .5rem; }
        @media (min-width: 768px) { .hm-dgrid { grid-template-columns: minmax(0, 1fr) minmax(0, 2fr); } }
        .hm-dcard { padding: 1rem; border-radius: 1rem; background: #F8FAFC; border: 1px solid var(--line2); }
        html.dark .hm-dcard { background: #11161D; }
        .hm-dcard { display: flex; flex-direction: column; gap: .75rem; }
        .hm-dby { display: flex; align-items: center; gap: .75rem; }
        .hm-dby img { width: 3rem; height: 3rem; border-radius: 9999px; object-fit: cover; box-shadow: 0 0 0 2px #4DA3FF; }
        .hm-dby .k { display: block; font-size: .75rem; color: var(--faint); }
        .hm-dby h4 { margin: 0; font-size: .875rem; font-weight: 700; color: var(--strong); }
        .hm-dby .kh { display: block; font-size: .75rem; color: var(--sky); }
        .hm-dfacts { margin: 0; padding-top: .5rem; border-top: 1px solid var(--line2); font-size: .75rem; color: var(--body); }
        html.dark .hm-dfacts { border-color: #262F3D; }
        .hm-dfacts > div { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .hm-dfacts > div + div { margin-top: .25rem; }
        .hm-dfacts dt { color: var(--faint); }
        .hm-dfacts dd { margin: 0; }
        .hm-dfacts .aw { font-weight: 600; color: #D97706; }
        .hm-dfacts .cu { font-weight: 500; color: var(--strong); }
        .hm-dfacts .lk { font-weight: 700; color: #F43F5E; }
        .hm-dstack-k { display: block; margin-bottom: .375rem; font-size: 11px; color: var(--faint); }
        .hm-dstack { display: flex; flex-wrap: wrap; gap: .375rem; }
        .hm-dstack span { padding: .25rem .625rem; border-radius: .5rem; background: #fff; border: 1px solid #E2E8F0; font-size: 11px; font-weight: 600; color: #334155; }
        html.dark .hm-dstack span { background: #1C232D; border-color: #262F3D; color: #CBD5E1; }
        .hm-dright { display: flex; flex-direction: column; gap: 1rem; }
        .hm-dright h4 { margin: 0; font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--faint); }
        .hm-dconcept { margin: .25rem 0 0; font-size: .875rem; line-height: 1.625; color: var(--s700); }
        html.dark .hm-dconcept { color: #CBD5E1; }
        .hm-dcrit { padding: 1rem; border-radius: 1rem; background: rgba(239, 246, 255, .5); border: 1px solid rgba(77, 163, 255, .3); }
        html.dark .hm-dcrit { background: rgba(24, 35, 53, .4); }
        @media (min-width: 640px) { .hm-dcrit { padding: 1.25rem; } }
        .hm-dcrit { display: flex; flex-direction: column; gap: .75rem; }
        .hm-dcrit-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
        .hm-dcrit-head > div { display: flex; align-items: center; gap: .625rem; }
        .hm-dcrit-head .hm-avatar { width: 2rem; height: 2rem; box-shadow: 0 0 0 2px rgba(77, 163, 255, .6); }
        .hm-dcrit-head .n { display: block; font-size: .75rem; font-weight: 700; color: var(--strong); }
        .hm-dcrit-head .r { display: block; font-size: 10px; color: var(--soft); }
        .hm-dgrade { padding: .25rem .75rem; border-radius: 9999px; background: #ECFDF5; border: 1px solid rgba(16, 185, 129, .3); font-size: .75rem; font-weight: 700; color: #047857; white-space: nowrap; }
        html.dark .hm-dgrade { background: rgba(2, 44, 34, .6); color: #6EE7B7; }
        .hm-dquote { margin: 0; padding-left: .75rem; border-left: 2px solid var(--sky); font-style: italic; font-size: .75rem; line-height: 1.625; color: var(--s700); }
        html.dark .hm-dquote { color: #E2E8F0; }
        @media (min-width: 640px) { .hm-dquote { font-size: .875rem; } }

        /* ==================================================================
           FOOTER
           ================================================================== */
        .hm-footer { border-top: 1px solid rgba(226, 232, 240, .8); background: #fff; transition: background-color .2s ease; }
        html.dark .hm-footer { background: #0B0F14; border-color: #1E2530; }
        .hm-footer-in { padding-top: 3rem; padding-bottom: 3rem; }
        @media (min-width: 1024px) { .hm-footer-in { padding-top: 4rem; padding-bottom: 4rem; } }
        .hm-fgrid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 2rem; }
        @media (min-width: 768px) { .hm-fgrid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { .hm-fgrid { gap: 3rem; } }
        .hm-fcol { display: flex; flex-direction: column; align-items: flex-start; gap: .75rem; }
        .hm-fcol--brand { gap: 1rem; }
        .hm-wordmark { display: block; font-size: 1.25rem; line-height: 1.75rem; font-weight: 700; letter-spacing: -.025em; color: var(--strong); }
        .hm-wordmark span { color: var(--sky); }
        .hm-ftext { margin: 0; font-size: .75rem; line-height: 1.625; color: #475569; }
        html.dark .hm-ftext { color: #94A3B8; }
        .hm-fmotto { margin: 0; font-size: .75rem; line-height: 1rem; color: #94A3B8; }
        html.dark .hm-fmotto { color: #64748B; }
        .hm-fhead { margin: 0; font-size: .75rem; line-height: 1rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--strong); }
        .hm-flinks { list-style: none; margin: 0; padding: 0; font-size: .75rem; line-height: 1rem; color: #475569; }
        html.dark .hm-flinks { color: #94A3B8; }
        .hm-flinks li + li { margin-top: .5rem; }
        .hm-flinks a { color: inherit; text-decoration: none; transition: color .15s ease; }
        .hm-flinks a:hover { color: var(--sky); }
        .hm-fcontact { display: flex; flex-direction: column; align-items: flex-start; padding-top: .25rem; font-size: .75rem; line-height: 1rem; color: #475569; }
        html.dark .hm-fcontact { color: #94A3B8; }
        .hm-fcontact { gap: .375rem; }
        .hm-fcontact .k { font-size: 11px; color: #94A3B8; }
        .hm-fcontact .k + .k, .hm-fcontact * + .k { padding-top: .25rem; }
        .hm-fcontact .v { color: #334155; text-decoration: none; }
        html.dark .hm-fcontact .v { color: #CBD5E1; }
        .hm-fcontact .v:hover { color: var(--sky); }
        .hm-tg { display: inline-flex; align-items: center; gap: .25rem; padding: 0; border: 0; background: none; font: inherit; color: var(--sky); cursor: pointer; }
        .hm-tg:hover { text-decoration: underline; }
        .hm-fsocial { display: flex; align-items: center; gap: .5rem; }
        .hm-fsocial a { color: var(--sky); text-decoration: none; }
        .hm-fsocial a:hover { text-decoration: underline; }
        .hm-fbar { margin-top: 3rem; padding-top: 1.5rem; border-top: 1px solid #F1F5F9; display: flex; flex-direction: column; align-items: center; justify-content: space-between; gap: 1rem; font-size: .75rem; line-height: 1rem; color: #64748B; text-align: center; }
        html.dark .hm-fbar { border-color: #1E2530; }
        @media (min-width: 640px) { .hm-fbar { flex-direction: row; text-align: left; } }
        .hm-fbar p { margin: 0; }
        .hm-fbar div { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .5rem 1rem; font-size: 11px; }
        .hm-fbar a { color: inherit; text-decoration: none; }
        .hm-fbar a:hover { color: var(--sky); }

        /* ---------- phone bottom bar (like the live site) ---------- */
        .hm-bnav { position: fixed; z-index: 40; left: 0; right: 0; bottom: 0; padding: .5rem .5rem max(.5rem, env(safe-area-inset-bottom)); background: rgba(255, 255, 255, .95); backdrop-filter: blur(12px); border-top: 1px solid var(--line); }
        html.dark .hm-bnav { background: rgba(28, 28, 30, .95); }
        .hm-bnav > div { display: flex; align-items: center; justify-content: space-around; font-size: 10px; font-weight: 500; }
        .hm-bnav a { display: flex; flex-direction: column; align-items: center; gap: .25rem; padding: .25rem .75rem; border-radius: .75rem; color: var(--mut); text-decoration: none; transition: color .15s ease; }
        .hm-bnav a[aria-current="page"] { color: var(--blue); font-weight: 600; }
        .hm-shell { padding-bottom: 4.5rem; }
        @media (min-width: 768px) { .hm-bnav { display: none; } .hm-shell { padding-bottom: 0; } }

        /* ---------- floating language / theme pill ---------- */
        .hm-prefs { position: fixed; z-index: 40; right: 1rem; bottom: 5rem; display: flex; align-items: center; gap: .375rem; padding: .25rem; border-radius: 9999px; background: rgba(255, 255, 255, .95); backdrop-filter: blur(12px); border: 1px solid var(--line); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, .1), 0 4px 6px -4px rgba(0, 0, 0, .1); transition: all .15s ease; }
        html.dark .hm-prefs { background: rgba(28, 28, 30, .95); }
        @media (min-width: 640px) { .hm-prefs { right: 1.5rem; gap: .5rem; padding: .375rem; } }
        @media (min-width: 768px) { .hm-prefs { right: 2rem; bottom: 1.5rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, .1), 0 8px 10px -6px rgba(0, 0, 0, .1); } }
        .hm-prefs form { display: flex; margin: 0; }
        .hm-pref { display: inline-flex; align-items: center; gap: .375rem; min-height: 38px; padding: .375rem .75rem; border: 0; border-radius: 9999px; background: transparent; font: inherit; font-size: .75rem; font-weight: 600; color: var(--ink); cursor: pointer; transition: background .15s ease; }
        .hm-pref:hover { background: #F5F7FA; }
        html.dark .hm-pref:hover { background: #242629; }
        .hm-pref:focus-visible { outline: 2px solid var(--sky); outline-offset: 1px; }
        .hm-pref .l { font-size: 11px; }
        @media (min-width: 640px) { .hm-pref .l { font-size: .75rem; } }
        .hm-pref .m { font-weight: 500; }
        .hm-pref-divider { width: 1px; height: 1rem; background: var(--line); }
        .hm-flag { width: 1.125rem; height: 12px; border-radius: 2px; box-shadow: 0 1px 2px rgba(0, 0, 0, .15); }
        .hm-spin-in { animation: hm-icon-spin 420ms cubic-bezier(.34, 1.56, .64, 1) both; }

        /* ---------- Khmer: the shared stylesheet loosens Khmer line heights and
           removes letter spacing site-wide. The live design keeps its own
           values in both languages, so they are put back for this page. */
        html:lang(km) .hm-body .hm-h1 { line-height: 1.25; letter-spacing: -.025em; }
        @media (min-width: 640px) { html:lang(km) .hm-body .hm-h1 { line-height: 1.2; } }
        html:lang(km) .hm-body .hm-lead, html:lang(km) .hm-body .hm-tquote, html:lang(km) .hm-body .hm-cdesc, html:lang(km) .hm-body .hm-sdesc, html:lang(km) .hm-body .hm-ftext { line-height: 1.625; }
        html:lang(km) .hm-body :is(.hm-h2, .hm-tname, .hm-show-title, .hm-ccover-title, .hm-ctitle, .hm-stitle, .hm-skills-head h4, .hm-desc, .hm-trole, .hm-canvas-sub, .hm-ccover-tools, .hm-ctools, .hm-skh, .hm-fmotto, .hm-dconcept) { line-height: 1.5; }
        html:lang(km) .hm-body :is(.hm-label, .hm-fhead, .hm-dkicker, .hm-dright h4, .hm-skill .g) { letter-spacing: .05em; }
        html:lang(km) .hm-body .hm-scourse { letter-spacing: .025em; }
        html:lang(km) .hm-body :is(.hm-canvas-title, .hm-file, .hm-wordmark) { letter-spacing: -.025em; line-height: 1.2; }
        html:lang(km) .hm-body .hm-tab, html:lang(km) .hm-body .hm-pref, html:lang(km) .hm-body .hm-dbtn { line-height: 1rem; }

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
        @keyframes hm-ring-draw { from { stroke-dasharray: 0, 100; } }
        @keyframes hm-pulse { 50% { opacity: .5; } }
        @keyframes hm-sun-spin { to { transform: rotate(360deg); } }
        @keyframes hm-sun-pulse { 0%, 100% { opacity: .55; transform: scale(.9); } 50% { opacity: 1; transform: scale(1.15); } }
        @keyframes hm-beam { 0%, 100% { opacity: .75; } 50% { opacity: 1; } }
        @keyframes hm-mote { 0% { transform: translateX(10px) scale(.6); opacity: 0; } 20% { opacity: 1; } 80% { opacity: .8; } 100% { transform: translateX(118px) scale(1); opacity: 0; } }
        @keyframes hm-jelly { 0% { transform: scale(1, 1); } 20% { transform: scale(1.22, .78); } 40% { transform: scale(.88, 1.12); } 58% { transform: scale(1.07, .94); } 76% { transform: scale(.98, 1.03); } 100% { transform: scale(1, 1); } }
        @keyframes hm-shimmer { 0% { opacity: 1; transform: translateX(-120%) skewX(-20deg); } 100% { opacity: 1; transform: translateX(340%) skewX(-20deg); } }
        @keyframes hm-label-pop { 0% { transform: scale(1); } 40% { transform: scale(1.09); } 100% { transform: scale(1); } }
        @keyframes hm-spark { 0% { transform: translate(0, 0) scale(1); opacity: 1; } 100% { transform: translate(var(--dx), var(--dy)) scale(0); opacity: 0; } }
        @keyframes hm-icon-spin { from { transform: rotate(-120deg) scale(.4); opacity: 0; } to { transform: rotate(0) scale(1); opacity: 1; } }
        @keyframes hm-pop-in { from { opacity: 0; transform: translateY(12px) scale(.98); } to { opacity: 1; transform: none; } }
        @keyframes hm-fade-in { from { opacity: 0; } to { opacity: 1; } }
        @keyframes hm-zoom-in { from { opacity: 0; transform: scale(.95); } to { opacity: 1; transform: scale(1); } }

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

    // Lucide icons used on this page.
    $hmIconPaths = [
        'arrow-right' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'arrow-up-right' => '<path d="M7 7h10v10"/><path d="M7 17 17 7"/>',
        'sparkles' => '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/>',
        'heart' => '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
        'play' => '<polygon points="6 3 20 12 6 21 6 3"/>',
        'grad' => '<path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/>',
        'check-circle' => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'award' => '<path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"/><circle cx="12" cy="8" r="6"/>',
        'eye' => '<path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/>',
        'share' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'x' => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'chart' => '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
        'house' => '<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
        'book' => '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>',
        'folder' => '<path d="m6 14 1.5-2.9A2 2 0 0 1 9.24 10H20a2 2 0 0 1 1.94 2.5l-1.54 6a2 2 0 0 1-1.95 1.5H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H18a2 2 0 0 1 2 2v2"/>',
        'user' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'moon' => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>',
    ];
    $icon = fn (string $name, string $class = 'i4') => '<svg class="'.$class.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$hmIconPaths[$name].'</svg>';
    $blank = '<svg viewBox="0 0 64 64" fill="currentColor" aria-hidden="true"><circle cx="32" cy="23" r="12.5"/><path d="M7 64c0-14.5 11.2-25 25-25s25 10.5 25 25z"/></svg>';

    // ---------- courses: real courses, shown with the live card design ----------
    $hmPhotos = [
        'ps' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=800&auto=format&fit=crop&q=80',
        'ai' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=800&auto=format&fit=crop&q=80',
        'id' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=800&auto=format&fit=crop&q=80',
        'gd' => 'https://images.unsplash.com/photo-1620641788421-7a1c342ea42e?w=800&auto=format&fit=crop&q=80',
        'ux' => 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?w=800&auto=format&fit=crop&q=80',
        'mo' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=800&auto=format&fit=crop&q=80',
    ];
    // course mark => [software chips, software line, photo, filter group]
    $hmSoftware = [
        'ps' => [['Ps'], 'Photoshop', 'ps', 'Graphic Design'],
        'ai' => [['Ai'], 'Illustrator', 'ai', 'Graphic Design'],
        'id' => [['Id'], 'InDesign', 'id', 'Graphic Design'],
        'gd' => [['Ps', 'Ai', 'Id'], 'Photoshop + Illustrator + InDesign', 'gd', 'Graphic Design'],
        'fg' => [['Fg'], 'Figma', 'ux', 'UX/UI Design'],
        'xd' => [['Xd'], 'Adobe XD', 'ux', 'UX/UI Design'],
        'ae' => [['Ae'], 'After Effects', 'mo', 'Motion Graphics'],
        'pr' => [['Pr'], 'Premiere Pro', 'mo', 'Motion Graphics'],
    ];
    $hmGroupOf = function (string $category): string {
        $c = strtolower($category);
        if (preg_match('/ux|ui|figma|xd|web|app/', $c)) return 'UX/UI Design';
        if (preg_match('/motion|after effects|animation|video|premiere/', $c)) return 'Motion Graphics';
        return 'Graphic Design';
    };

    $hmUser = auth()->user();
    $hmUser?->loadMissing('enrollments');

    $hmCards = collect($courses ?? [])->values()->map(function ($course) use ($hmSoftware, $hmPhotos, $hmGroupOf, $hmUser, $L) {
        $mark = trim((string) $course->mark);
        $sw = $hmSoftware[strtolower($mark)] ?? null;
        $category = (string) $course->category;
        $minutes = $course->durationMinutes();
        $enrollment = $hmUser?->enrollmentFor($course);
        $lesson = $enrollment?->continueLesson();
        $description = trim(strip_tags((string) $course->localized('description'))) ?: trim((string) $course->localized('subtitle'));

        return [
            'course' => $course,
            'title' => $course->localized('title'),
            'chips' => $sw[0] ?? array_values(array_filter([$mark])),
            'tools' => $sw[1] ?? ($category ?: 'Design'),
            'photo' => $course->thumbnailSrc() ?: $hmPhotos[$sw[2] ?? 'gd'],
            'group' => $sw[3] ?? $hmGroupOf($category),
            'category' => $category,
            'description' => \Illuminate\Support\Str::limit($description, 240),
            'level' => $course->level ? __($course->level) : '',
            'duration' => $minutes > 0 ? $course->formattedDuration() : '',
            'lessons' => (int) $course->lessons_count,
            'enrolled' => $enrollment !== null,
            'progress' => $enrollment ? $course->progressPercent($hmUser) : 0,
            'continue' => $lesson ? route('courses.lessons.show', [$course, $lesson]) : route('courses.show', $course),
            'url' => route('courses.show', $course),
        ];
    });
    $hmGroups = ['Graphic Design', 'UX/UI Design', 'Motion Graphics'];

    // ---------- teacher ----------
    $hmSkills = [
        ['🎨', 'រចនាក្រាហ្វិក និង Branding', 'Branding'],
        ['🎬', 'Motion Graphics & Animation', 'Motion'],
        ['🖥️', 'UI/UX Design & Figma', 'UI/UX'],
        ['📱', 'Social Media Design', 'Social Media'],
        ['📦', 'Packaging & Product Design', 'Packaging'],
        ['🎥', 'Video & Visual Content', 'Production'],
        ['🤖', 'AI for Design & Creative Workflow', 'AI Design'],
        ['👨‍🏫', 'បង្រៀន Graphic Design និង Motion Design', 'Teaching'],
        ['💡', 'Design Thinking & Creative Direction', 'Strategy'],
        ['💻', 'Adobe Photoshop, Illustrator, InDesign & After Effects', 'Software'],
    ];

    // ---------- student showcase (same projects as the live site) ----------
    $hmProjects = [
        ['id' => 'sw-1', 'studentName' => 'Dara Somnang', 'studentNameKh' => 'ដារ៉ា សំណាង', 'studentAvatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80', 'course' => 'Graphic Design', 'title' => 'Angkor Heritage Minimalist Identity', 'titleKh' => 'ប្រព័ន្ធអត្តសញ្ញាណបេតិកភណ្ឌអង្គរ', 'image' => 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=800&auto=format&fit=crop&q=80', 'likes' => 142, 'badge' => "Teacher's Choice", 'tools' => ['Illustrator', 'InDesign', 'Photoshop'], 'grade' => '9.8 / 10', 'description' => 'A modern minimalist brand identity and packaging system celebrating classical Angkorian architectural proportions and traditional Khmer stone carvings.', 'teacherCritique' => 'Outstanding optical balance between the modern sans-serif type and traditional Khmer display font. Grid proportion is exemplary.'],
        ['id' => 'sw-2', 'studentName' => 'Bopha Vann', 'studentNameKh' => 'វណ្ណ បុប្ផា', 'studentAvatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200&auto=format&fit=crop&q=80', 'course' => 'UX/UI Design', 'title' => 'Phnom Penh Urban Transit UI Kit', 'titleKh' => 'កម្មវិធី និង UI Kit ធ្វើដំណើររាជធានីភ្នំពេញ', 'image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=800&auto=format&fit=crop&q=80', 'likes' => 198, 'badge' => 'Featured Project', 'tools' => ['Figma', 'FigJam', 'Protopie'], 'grade' => '9.6 / 10', 'description' => 'Clean mobile ticketing and real-time bus navigation application designed for Phnom Penh commuters with full Khmer localized typography and dark mode support.', 'teacherCritique' => 'Superb design token structure and auto-layout discipline. Micro-interactions for route tracking are smooth and intuitive.'],
        ['id' => 'sw-3', 'studentName' => 'Rithy Kem', 'studentNameKh' => 'កែម រិទ្ធី', 'studentAvatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&auto=format&fit=crop&q=80', 'course' => 'Motion Graphics', 'title' => 'Kinetic Khmer Calligraphy Showreel', 'titleKh' => 'ចលនាអក្សរឆ្លាក់ខ្មែរ Kinetic Calligraphy', 'image' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=800&auto=format&fit=crop&q=80', 'likes' => 215, 'badge' => 'Top Honors', 'tools' => ['After Effects', 'Cinema 4D', 'Illustrator'], 'grade' => '9.9 / 10', 'description' => 'Dynamic 3D typography motion reel exploring traditional Khmer calligraphy morphing into contemporary geometric spatial forms with fluid rhythm.', 'teacherCritique' => 'Masterful easing curves and secondary overshoot physics. Setting a new benchmark for motion typography in Cambodia.'],
        ['id' => 'sw-4', 'studentName' => 'Channary Pich', 'studentNameKh' => 'ពេជ្រ ចាន់ណារី', 'studentAvatar' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=200&auto=format&fit=crop&q=80', 'course' => 'Graphic Design', 'title' => 'Mondulkiri Specialty Coffee Packaging', 'titleKh' => 'ការរចនាវេចខ្ចប់កាហ្វេពិសេសមណ្ឌលគិរី', 'image' => 'https://images.unsplash.com/photo-1507238691740-187a5b1d37b8?w=800&auto=format&fit=crop&q=80', 'likes' => 167, 'badge' => 'Best Packaging', 'tools' => ['Illustrator', 'Dimension', 'Photoshop'], 'grade' => '9.5 / 10', 'description' => 'Eco-conscious craft paper packaging featuring indigenous botanical motifs and bespoke dual-script roast profile typographic labels.', 'teacherCritique' => 'The tactile texture choice and color palette evoke the authentic highland soil. Highly commercial and shelf-ready.'],
        ['id' => 'sw-5', 'studentName' => 'Sothea Chan', 'studentNameKh' => 'ចាន់ សុធា', 'studentAvatar' => 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=200&auto=format&fit=crop&q=80', 'course' => 'UX/UI Design', 'title' => 'Siem Reap Heritage AR Tourism Guide', 'titleKh' => 'កម្មវិធីណែនាំទេសចរណ៍ AR សៀមរាប', 'image' => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&auto=format&fit=crop&q=80', 'likes' => 184, 'badge' => 'Best Prototype', 'tools' => ['Figma', 'Blender', 'Spline'], 'grade' => '9.7 / 10', 'description' => 'Immersive augmented reality museum and temple navigation experience with multilingual audio tours and interactive 3D artifact inspect.', 'teacherCritique' => 'Impeccable accessibility contrast and spatial UX. The card navigation gestures feel natural on one-handed mobile devices.'],
        ['id' => 'sw-6', 'studentName' => 'Visal Mao', 'studentNameKh' => 'ម៉ៅ វិសាល', 'studentAvatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=200&auto=format&fit=crop&q=80', 'course' => 'Motion Graphics', 'title' => 'Apsara Cyberpunk Kinetic Poster', 'titleKh' => 'Poster ចលនាកូនកាត់ Apsara Cyberpunk', 'image' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=800&auto=format&fit=crop&q=80', 'likes' => 243, 'badge' => 'Best Visual FX', 'tools' => ['After Effects', 'Blender', 'Octane'], 'grade' => '9.9 / 10', 'description' => 'Futuristic animated poster series merging classical Cambodian classical dance hand postures with neon holographic particle simulations.', 'teacherCritique' => 'Stunning artistic vision and audio-visual synchronization. Exemplifies how traditional Cambodian art can evolve into the digital era.'],
    ];
    // liked when the page first loads, as on the live site
    $hmDefaultLiked = ['sw-1', 'sw-3'];

    $hmStartUrl = auth()->check() ? route('learning.index') : route('register');

    // Words the page script shows.
    $hmText = [
        'copied' => 'Copied Link!',
        'share' => 'Share',
        'saveWish' => $L('Save to Wishlist', 'រក្សាទុកក្នុងបញ្ជីចង់រៀន'),
        'removeWish' => $L('Remove from Wishlist', 'ដកចេញពីបញ្ជីចង់រៀន'),
    ];
@endphp

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

        {{-- The live promotion sits above the menu; the menu is the bar shared with every page. --}}
        @include('partials._promo')

        @include('partials._site-nav')

        <main class="hm-main">

            {{-- ============================== HERO ============================== --}}
            <section class="hm-hero" aria-labelledby="hm-hero-title">
                <div class="hm-wrap hm-hero-grid">
                    <div class="hm-hero-copy">
                        <div class="hm-status">
                            <span class="hm-dot" aria-hidden="true"></span>
                            <span>Modern Cambodian Design Academy</span>
                            <span class="hm-status-sep" aria-hidden="true">·</span>
                            <span class="hm-status-by hm-km">{{ $L('Led by Lecturer Piseth Yoeurn', 'បង្រៀនដោយលោកគ្រូ ពិសិដ្ឋ') }}</span>
                        </div>

                        <h1 id="hm-hero-title" class="hm-h1 {{ $hmKm ? 'hm-km' : '' }}">
                            {{ $L('Master Design Fundamentals.', 'រៀន Design ឲ្យច្បាស់') }}<br>
                            <span>{{ $L('Craft Valuable Creations.', 'បង្កើតស្នាដៃឲ្យមានតម្លៃ។') }}</span>
                        </h1>

                        <p class="hm-lead {{ $hmKm ? 'hm-km' : '' }}">{{ $L('Learn Graphic Design, UX/UI, Motion Graphics, and studio creative workflows through hands-on practice led by university lecturer Piseth Yoeurn.', 'រៀន Graphic Design, UX/UI, Motion Graphics និងជំនាញ Creative ដោយផ្តោតលើការអនុវត្តជាក់ស្តែង ជាមួយសាស្ត្រាចារ្យ យឿន ពិសិដ្ឋ។') }}</p>

                        <div class="hm-ctas">
                            <a href="{{ $hmStartUrl }}" class="hm-btn hm-btn-primary">
                                <span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Start Learning', 'ចាប់ផ្ដើមរៀន') }}</span>
                                {!! $icon('arrow-right') !!}
                            </a>
                            <a href="{{ route('courses') }}" class="hm-btn hm-btn-ghost"><span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Explore Courses', 'មើលវគ្គសិក្សា') }}</span></a>
                        </div>
                    </div>

                    {{-- macOS-style design window with the 3D studio scene --}}
                    <div>
                        <div class="hm-art">
                            <div class="hm-glow hm-glow-a" aria-hidden="true"></div>
                            <div class="hm-glow hm-glow-b" aria-hidden="true"></div>

                            <div class="hm-art-inner">
                                <div class="hm-floats" aria-hidden="true">
                                    <div class="hm-float hm-float-progress">
                                        <div class="hm-fcard">
                                            <span class="hm-ring">
                                                <svg viewBox="0 0 36 36"><path class="track" stroke-width="3.5" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" /><path class="bar" stroke-dasharray="78, 100" stroke-width="3.5" stroke-linecap="round" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" /></svg>
                                                <b>78%</b>
                                            </span>
                                            <span><small>Overall Progress</small><strong>Design Course</strong></span>
                                        </div>
                                    </div>
                                    <div class="hm-float hm-float-cert">
                                        <div class="hm-fcard hm-fcard-cert">
                                            <span style="color:var(--blue)">{!! $icon('award', 'i35') !!}</span>
                                            <strong>Design Course Certified</strong>
                                            <span style="color:#10B981">{!! $icon('check-circle', 'i35') !!}</span>
                                        </div>
                                    </div>
                                    <div class="hm-float hm-float-project">
                                        <div class="hm-fcard hm-fcard-project">
                                            <span class="hm-no1">#01</span>
                                            <span><strong>Design Course Project</strong><span class="hm-amber">Teacher Feedback Received</span></span>
                                        </div>
                                    </div>
                                    <div class="hm-float hm-float-learning">
                                        <div class="hm-fcard">
                                            <span class="hm-play"><svg class="i4" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linejoin="round" style="margin-left:2px"><polygon points="6 3 20 12 6 21 6 3" /></svg></span>
                                            <span style="padding-right:.25rem">
                                                <span class="hm-live">Currently Learning</span>
                                                <strong class="hm-clamp1">Design Course: Visual Mastery</strong>
                                                <span class="hm-mins">{!! $icon('clock', 'i3') !!} 38 mins remaining</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="hm-window">
                                    <div class="hm-titlebar">
                                        <div class="hm-tb-left">
                                            <i style="background:#FF5F56"></i><i style="background:#FFBD2E"></i><i style="background:#27C93F"></i>
                                            <span class="hm-file hm-mono">design_course_curriculum.fig</span>
                                        </div>
                                        <div class="hm-badge">{!! $icon('sparkles', 'i3') !!}<span>Design Course</span></div>
                                    </div>

                                    <div class="hm-canvas-pad">
                                        <a href="{{ route('courses') }}" class="hm-canvas" aria-label="Open Design Course">
                                            <div class="hm-grid-lines" aria-hidden="true"></div>
                                            <div class="hm-canvas-row">
                                                <div class="hm-canvas-copy">
                                                    <span class="hm-canvas-title">Design Course</span>
                                                    <p class="hm-canvas-sub">Graphic Design · UX/UI · Motion Graphics</p>
                                                    <div class="hm-tags">
                                                        <span class="hm-tag hm-tag-b hm-mono">Adobe Photoshop</span>
                                                        <span class="hm-tag hm-tag-a hm-mono">Adobe Illustrator</span>
                                                        <span class="hm-tag hm-tag-p hm-mono">UX/UI Design · Figma</span>
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
                                                            {{-- light from the sun tile, kept inside the cube --}}
                                                            <div class="hm-cube-hit"></div>
                                                            <div class="hm-cube-beam"></div>
                                                            <div class="hm-cube-motes"><i style="top:-10px"></i><i style="top:-2px; animation-delay:.9s"></i><i style="top:6px; animation-delay:1.8s"></i></div>
                                                            <div class="hm-cube-sheen"></div>
                                                            <div class="hm-cube-face">
                                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                                                    <defs><linearGradient id="hmPenGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#38BDF8" /><stop offset="50%" stop-color="#818CF8" /><stop offset="100%" stop-color="#1877F2" /></linearGradient></defs>
                                                                    <path d="m12 19 7-7 3 3-7 7-3-3z" stroke="url(#hmPenGrad)" fill="url(#hmPenGrad)" fill-opacity=".25" />
                                                                    <path d="m18 13-1.5-7.5L2 2l3.5 14.5L13 18l5-5z" stroke="url(#hmPenGrad)" />
                                                                    <path d="m2 2 7.586 7.586" stroke="#FFFFFF" />
                                                                    <circle cx="11" cy="11" r="2" fill="#38BDF8" />
                                                                </svg>
                                                                <span class="hm-mono">3D Studio</span>
                                                            </div>
                                                            <div class="hm-cube-corner"></div>
                                                        </div>

                                                        {{-- the yellow tile is a small sun lighting the cube --}}
                                                        <div class="hm-sat hm-sat-sun">
                                                            <span class="hm-sun-rays"></span>
                                                            <span class="hm-sun-halo"></span>
                                                            <div class="hm-tile hm-tile-sun">✦</div>
                                                        </div>
                                                        <div class="hm-sat hm-sat-figma">
                                                            <div class="hm-tile hm-tile-figma">
                                                                <svg viewBox="0 0 38 57" width="20" height="28"><path fill="#1ABCFE" d="M19 28.5a9.5 9.5 0 1 1 19 0 9.5 9.5 0 0 1-19 0z" /><path fill="#0ACF83" d="M0 47.5A9.5 9.5 0 0 1 9.5 38H19v9.5a9.5 9.5 0 1 1-19 0z" /><path fill="#FF7262" d="M19 0v19h9.5a9.5 9.5 0 1 0 0-19H19z" /><path fill="#F24E1E" d="M0 9.5A9.5 9.5 0 0 0 9.5 19H19V0H9.5A9.5 9.5 0 0 0 0 9.5z" /><path fill="#A259FF" d="M0 28.5A9.5 9.5 0 0 0 9.5 38H19V19H9.5A9.5 9.5 0 0 0 0 28.5z" /></svg>
                                                            </div>
                                                        </div>
                                                        <div class="hm-sat hm-sat-ps"><div class="hm-tile hm-tile-ps"><span>Ps</span></div></div>
                                                        <div class="hm-sat hm-sat-ai"><div class="hm-tile hm-tile-ai"><span>Ai</span></div></div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="hm-canvas-foot"><span>Open Design Course {!! $icon('arrow-up-right', 'i35') !!}</span></div>
                                        </a>
                                    </div>

                                    <div class="hm-wfoot">
                                        <div class="hm-wfoot-l">
                                            <span class="hm-py">PY</span>
                                            <span class="hm-wfoot-t">Instructor: <strong>Piseth Yoeurn</strong></span>
                                        </div>
                                        <a href="{{ route('courses') }}" class="hm-wfoot-link">Design Course Curriculum →</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ============================== COURSES (real courses from the database) ============================== --}}
            <section id="hm-courses" class="hm-wrap" aria-labelledby="hm-courses-title">
                <div class="hm-head">
                    <div>
                        <p class="hm-label"><span>Featured Curriculums</span></p>
                        <h2 id="hm-courses-title" class="hm-h2 {{ $hmKm ? 'hm-km' : '' }}">{{ $L('Featured Design Curriculums', 'វគ្គសិក្សាដែលមានសម្រាប់អ្នក') }}</h2>
                        <p class="hm-desc {{ $hmKm ? 'hm-km' : '' }}">{{ $L('Curriculums engineered for professional mastery in graphic design, product UI/UX, and motion graphics.', 'កម្មវិធីសិក្សាផ្តោតលើជំនាញ Graphic Design, UI/UX, និង Motion Graphics ស្របតាមតម្រូវការទីផ្សារការងារ។') }}</p>
                    </div>
                    <div class="hm-tabs" data-jelly-tabs data-target="#hm-course-grid [data-category]">
                        <div class="hm-tabs-track" role="tablist" aria-label="{{ $L('Filter courses', 'ជ្រើសរើសប្រភេទវគ្គសិក្សា') }}">
                            <span class="hm-jelly-trail" aria-hidden="true"></span>
                            <span class="hm-jelly" aria-hidden="true"><span class="hm-jelly-blob"></span><span class="hm-sparks"></span></span>
                            <button type="button" role="tab" class="hm-tab" aria-selected="true" data-value="__all"><span>{{ $L('All', 'ទាំងអស់') }}</span></button>
                            @foreach ($hmGroups as $group)
                                <button type="button" role="tab" class="hm-tab" aria-selected="false" tabindex="-1" data-value="{{ $group }}"><span>{{ $group }}</span></button>
                            @endforeach
                        </div>
                    </div>
                </div>

                @if ($hmCards->isEmpty())
                    <p class="hm-empty">{{ $L('New courses are on the way. Check back soon.', 'វគ្គសិក្សាថ្មីនឹងមកដល់ឆាប់ៗនេះ។') }}</p>
                @else
                    <div id="hm-course-grid" class="hm-course-grid">
                        @foreach ($hmCards as $card)
                            <article class="hm-ccard" data-category="{{ $card['group'] }}">
                                <div class="hm-ccover" data-href="{{ $card['url'] }}">
                                    <img src="{{ $card['photo'] }}" alt="{{ $card['title'] }}" loading="lazy" decoding="async">
                                    <span class="hm-ccover-shade"></span>
                                    <span class="hm-ccover-dots"></span>

                                    <div class="hm-ccover-top">
                                        <div class="hm-chips">
                                            @foreach ($card['chips'] as $chip)
                                                <span class="hm-chip hm-mono">{{ $chip }}</span>
                                            @endforeach
                                        </div>
                                        <div class="hm-ccover-tr">
                                            @if ($card['category'] !== '')
                                                <span class="hm-cat">{{ $card['category'] }}</span>
                                            @endif
                                            <button type="button" class="hm-heart" data-wish="{{ $card['course']->slug }}" aria-pressed="false" aria-label="Toggle Wishlist" title="{{ $hmText['saveWish'] }}">
                                                {!! $icon('heart', 'i35') !!}
                                            </button>
                                        </div>
                                    </div>

                                    <div class="hm-ccover-mid">
                                        <h3 class="hm-ccover-title {{ $hmKm ? 'hm-km' : '' }}">{{ $card['title'] }}</h3>
                                        <p class="hm-ccover-tools hm-mono">{{ $card['tools'] }}</p>
                                    </div>

                                    <div class="hm-ccover-bot">
                                        <span>{!! $icon('chart', 'i35') !!}<strong>{{ $card['level'] ?: $card['group'] }}</strong></span>
                                        <span class="hm-mono">{{ $card['lessons'] }} {{ $L('Lessons', 'មេរៀន') }}</span>
                                    </div>
                                </div>

                                <div class="hm-cbody">
                                    <div>
                                        <h3 class="hm-ctitle {{ $hmKm ? 'hm-km' : '' }}"><a href="{{ $card['url'] }}">{{ $card['title'] }}</a></h3>
                                        <p class="hm-ctools">{{ $card['tools'] }}</p>
                                        @if ($card['description'] !== '')
                                            <p class="hm-cdesc">{{ $card['description'] }}</p>
                                        @endif
                                        <div class="hm-cmeta">
                                            @if ($card['level'] !== '')
                                                <span>{{ $card['level'] }}</span>
                                                <span class="sep" aria-hidden="true">·</span>
                                            @endif
                                            @if ($card['duration'] !== '')
                                                <span>{{ $card['duration'] }}</span>
                                                <span class="sep" aria-hidden="true">·</span>
                                            @endif
                                            <span>{{ $card['lessons'] }} {{ $L('Lessons', 'មេរៀន') }}</span>
                                        </div>
                                    </div>

                                    <div class="hm-cfoot">
                                        @if ($card['enrolled'])
                                            <div>
                                                <div class="hm-progress-row">
                                                    <span>{{ $L('Progress', 'វឌ្ឍនភាពសិក្សា') }}</span>
                                                    <b>{{ $card['progress'] }}%</b>
                                                </div>
                                                <div class="hm-bar"><i style="width: {{ $card['progress'] }}%"></i></div>
                                            </div>
                                        @else
                                            @php $course = $card['course']; @endphp
                                            <div class="hm-tuition">
                                                <span>{{ $L('Tuition', 'តម្លៃសិក្សា') }}</span>
                                                <div class="hm-price">
                                                    @if ($course->isFree())
                                                        <b>{{ __('Free') }}</b>
                                                    @elseif ($course->isOnSale())
                                                        <b>{{ $course->salePriceLabel() }}</b><s>{{ $course->priceLabel() }}</s>
                                                    @else
                                                        <b>{{ $course->priceLabel() }}</b>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif

                                        <div class="hm-cbtns">
                                            <a href="{{ $card['url'] }}" class="hm-cbtn-ghost">{{ $L('View Course', 'ព័ត៌មានលម្អិត') }}</a>
                                            @if ($card['enrolled'])
                                                <a href="{{ $card['continue'] }}" class="hm-cbtn hm-cbtn--play"><svg class="i35" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><polygon points="6 3 20 12 6 21 6 3" /></svg><span>{{ $L('Continue Learning', 'បន្តការរៀន') }}</span></a>
                                            @else
                                                <a href="{{ $card['url'] }}" class="hm-cbtn"><span>{{ $L('Enroll Now', 'ចុះឈ្មោះរៀន') }}</span>{!! $icon('arrow-right', 'i35') !!}</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- ============================== INSTRUCTOR ============================== --}}
            <section id="hm-instructor" class="hm-wrap" aria-labelledby="hm-instructor-title">
                <div class="hm-teach">
                    <div class="hm-teach-grid">
                        <div class="hm-teach-left">
                            <div class="hm-portrait">
                                <div class="hm-portrait-img" role="img" aria-label="Piseth Yoeurn - University Lecturer">{!! $blank !!}</div>
                                <div class="hm-lect">
                                    {!! $icon('grad') !!}
                                    <div><span class="a">University Lecturer</span><span class="b hm-km">សាស្ត្រាចារ្យសាកលវិទ្យាល័យ</span></div>
                                </div>
                            </div>
                            <div class="hm-stats">
                                <div class="hm-stat"><span class="a">10+ Years</span><span class="b hm-km">បទពិសោធន៍ Design</span></div>
                                <div class="hm-stat"><span class="a">1,400+</span><span class="b hm-km">និស្សិតបានសិក្សា</span></div>
                            </div>
                        </div>

                        <div class="hm-teach-right">
                            <div>
                                <p class="hm-label hm-label--row"><span>Meet Your Instructor</span><span aria-hidden="true">·</span><span class="k hm-km">បទពិសោធន៍ជាង ១០ ឆ្នាំ</span></p>
                                <h3 id="hm-instructor-title" class="hm-tname hm-km">លោកគ្រូ យឿន ពិសិដ្ឋ (Piseth Yoeurn)</h3>
                                <p class="hm-trole">Senior Visual &amp; Motion Designer · Lecturer at Build Bright University, Siem Reap</p>
                            </div>

                            <blockquote class="hm-tquote hm-km">"ការរចនាមិនមែនត្រឹមតែការប្រើ Tool នោះទេ ប៉ុន្តែជាការគិតស៊ីជម្រៅពី Layout, Grid, Typography និង Emotion ដើម្បីដោះស្រាយបញ្ហាពិតប្រាកដ។ ខ្ញុំបង្កើត piseth.design ដើម្បីចែករំលែកបទពិសោធន៍ជាក់ស្តែងដល់ប្អូនៗនិស្សិតខ្មែរ។"</blockquote>

                            <div class="hm-skills-wrap">
                                <div class="hm-skills-head">
                                    <div>{!! $icon('sparkles') !!}<h4 class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Core Skills & Creative Capabilities', 'ជំនាញឯកទេស (My Professional Skills)') }}</h4></div>
                                    <span class="hm-spec hm-mono">10 Specializations</span>
                                </div>
                                <div class="hm-skills">
                                    @foreach ($hmSkills as [$emoji, $skill, $tag])
                                        <div class="hm-skill">
                                            <div><span class="e" aria-hidden="true">{{ $emoji }}</span><span class="t">{{ $skill }}</span></div>
                                            <span class="g hm-mono">{{ $tag }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="hm-trust-row">
                                <div class="hm-trust">
                                    @foreach (['Direct 1-on-1 Feedback', 'Agency Client Briefs', 'Khmer Typography Expertise'] as $item)
                                        <span>{!! $icon('check-circle', 'i35') !!}<span>{{ $item }}</span></span>
                                    @endforeach
                                </div>
                                <a href="{{ route('about') }}" class="hm-tlink"><span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Full Teaching Philosophy & Story', 'ស្វែងយល់បន្ថែមពីទស្សនវិជ្ជាបង្រៀន') }}</span>{!! $icon('chevron-right', 'i35') !!}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ============================== STUDENT SHOWCASE ============================== --}}
            <section id="hm-showcase" class="hm-wrap hm-show-sec" aria-labelledby="hm-showcase-title">
                <div class="hm-head">
                    <div>
                        <h3 id="hm-showcase-title" class="hm-show-title hm-km">{{ $L('Student Creative Showcase', 'ស្នាដៃសិស្សឆ្នើម (Student Creative Showcase)') }}</h3>
                        <p class="hm-desc {{ $hmKm ? 'hm-km' : '' }}">{{ $L('Real-world studio deliverables crafted by Cambodian design students with official reviews by Teacher Piseth Yoeurn.', 'គម្រោងរចនាជាក់ស្តែងអនុវត្តដោយនិស្សិតកម្ពុជា តាមស្តង់ដារបង្រៀនរបស់លោកគ្រូ យឿន ពិសិដ្ឋ។ ចុចលើរូបដើម្បីពិនិត្យលម្អិត។') }}</p>
                    </div>
                    <div class="hm-tabs hm-tabs--sm" data-jelly-tabs data-target="#hm-show-grid [data-category]">
                        <div class="hm-tabs-track" role="tablist" aria-label="{{ $L('Filter projects', 'ជ្រើសរើសប្រភេទស្នាដៃ') }}">
                            <span class="hm-jelly-trail" aria-hidden="true"></span>
                            <span class="hm-jelly" aria-hidden="true"><span class="hm-jelly-blob"></span><span class="hm-sparks"></span></span>
                            <button type="button" role="tab" class="hm-tab" aria-selected="true" data-value="__all"><span>{{ $L('All', 'ទាំងអស់') }}</span></button>
                            @foreach ($hmGroups as $group)
                                <button type="button" role="tab" class="hm-tab" aria-selected="false" tabindex="-1" data-value="{{ $group }}"><span>{{ $group }}</span></button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div id="hm-show-grid" class="hm-show-grid">
                    @foreach ($hmProjects as $p)
                        <article class="hm-scard" data-category="{{ $p['course'] }}" data-project="{{ $p['id'] }}" role="button" tabindex="0" aria-label="{{ $L('Inspect Details', 'ពិនិត្យស្នាដៃលម្អិត') }}: {{ $p['title'] }}">
                            <div>
                                <div class="hm-simg">
                                    <img src="{{ $p['image'] }}" alt="{{ $p['title'] }}" loading="lazy" decoding="async">
                                    <div class="hm-shover">
                                        <div>
                                            <span class="hm-inspect">{!! $icon('eye', 'i35') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Inspect Details', 'ពិនិត្យស្នាដៃលម្អិត') }}</span></span>
                                            <span class="hm-sgrade hm-mono">{{ $p['grade'] }}</span>
                                        </div>
                                    </div>
                                    <span class="hm-sbadge hm-mono">{!! $icon('award', 'i3') !!}<span>{{ $p['badge'] }}</span></span>
                                    <button type="button" class="hm-slike" data-like="{{ $p['id'] }}" aria-pressed="{{ in_array($p['id'], $hmDefaultLiked) ? 'true' : 'false' }}" title="Like Project" aria-label="Like Project">{!! $icon('heart') !!}</button>
                                </div>
                                <div class="hm-sbody">
                                    <div class="hm-srow">
                                        <span class="hm-scourse hm-mono">{{ $p['course'] }}</span>
                                        <span class="hm-scount hm-mono {{ in_array($p['id'], $hmDefaultLiked) ? 'is-on' : '' }}" data-count-wrap="{{ $p['id'] }}">{!! $icon('heart', 'i35') !!}<span data-count>{{ $p['likes'] }}</span></span>
                                    </div>
                                    <div>
                                        <h4 class="hm-stitle">{{ $p['title'] }}</h4>
                                        <p class="hm-skh hm-km">{{ $p['titleKh'] }}</p>
                                    </div>
                                    <p class="hm-sdesc">{{ $p['description'] }}</p>
                                </div>
                            </div>
                            <div class="hm-sfoot">
                                <div class="hm-sstudent">
                                    <img src="{{ $p['studentAvatar'] }}" alt="{{ $p['studentName'] }}" loading="lazy" decoding="async">
                                    <div><span class="a">{{ $p['studentName'] }}</span><span class="b hm-km">{{ $p['studentNameKh'] }}</span></div>
                                </div>
                                <div class="hm-stools">
                                    @foreach (array_slice($p['tools'], 0, 2) as $tool)
                                        <span class="hm-stool hm-mono">{{ $tool }}</span>
                                    @endforeach
                                    @if (count($p['tools']) > 2)
                                        <span class="hm-smore hm-mono">+{{ count($p['tools']) - 2 }}</span>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        </main>

        {{-- ============================== FOOTER ============================== --}}
        <footer class="hm-footer">
            <div class="hm-wrap hm-footer-in">
                <div class="hm-fgrid">
                    <div class="hm-fcol hm-fcol--brand">
                        <span class="hm-wordmark">piseth<span>.design</span></span>
                        <p class="hm-ftext">Modern creative design academy founded by university lecturer Piseth Yoeurn. Educating Cambodian creators to craft international-standard design.</p>
                        <p class="hm-fmotto hm-km">រៀន Design ឲ្យច្បាស់ បង្កើតស្នាដៃឲ្យមានតម្លៃ។</p>
                    </div>

                    <div class="hm-fcol">
                        <h4 class="hm-fhead hm-km">Courses · វគ្គសិក្សា</h4>
                        <ul class="hm-flinks">
                            @foreach ($hmCards->take(4) as $card)
                                <li><a href="{{ $card['url'] }}">{{ $card['course']->title_en ?: $card['title'] }}</a></li>
                            @endforeach
                            <li><a href="{{ route('courses', ['free' => 1]) }}">{{ __('Free Courses') }}</a></li>
                        </ul>
                    </div>

                    <div class="hm-fcol">
                        <h4 class="hm-fhead">Student Platform</h4>
                        <ul class="hm-flinks">
                            <li><a href="{{ route('learning.index') }}">Student Dashboard</a></li>
                            <li><a href="{{ route('courses') }}">Video Classroom</a></li>
                            <li><a href="{{ route('learning.index') }}">Certificates</a></li>
                            <li><a href="{{ route('learning.profile') }}">My Profile</a></li>
                        </ul>
                    </div>

                    <div class="hm-fcol">
                        <h4 class="hm-fhead">Teacher &amp; Community</h4>
                        <p class="hm-ftext">Lecturer at Build Bright University, Siem Reap.</p>
                        <div class="hm-fcontact">
                            <span class="k hm-mono">Telegram Community:</span>
                            <button type="button"
                                aria-haspopup="dialog"
                                aria-label="{{ __('Telegram (show QR code)') }}"
                                x-data
                                x-on:click="$dispatch('open-telegram-qr')"
                                class="hm-tg pd-qr-trigger">@@PISETH_YOEURN {!! $icon('arrow-up-right', 'i3') !!}</button>
                            @include('partials._telegram-qr-dialog')
                            <span class="k hm-mono">Direct Mentorship:</span>
                            <a class="v" href="mailto:{{ 'pisethyoeurn69@gmail.com' }}">{{ 'pisethyoeurn69@gmail.com' }}</a>
                            <span class="k hm-mono">Follow:</span>
                            <span class="hm-fsocial">
                                <a href="{{ config('social.facebook') }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Facebook (opens in a new tab)') }}">Facebook</a>
                                <span aria-hidden="true">·</span>
                                <a href="{{ config('social.instagram') }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Instagram (opens in a new tab)') }}">Instagram</a>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="hm-fbar">
                    <p>&copy; {{ date('Y') }} piseth.design. All rights reserved. Phnom Penh, Cambodia.</p>
                    <div>
                        <span>Apple-inspired Minimalist UX</span>
                        <span aria-hidden="true">·</span>
                        <span>Khmer Creative Education</span>
                        <span aria-hidden="true">·</span>
                        <a href="{{ route('about') }}">About Teacher Piseth</a>
                    </div>
                </div>
            </div>
        </footer>

        {{-- Phone bottom bar, like the live site. --}}
        <nav class="hm-bnav" aria-label="{{ $L('Quick links', 'តំណរហ័ស') }}">
            <div>
                <a href="{{ route('home') }}" aria-current="page">{!! $icon('house', 'i5') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Home', 'ទំព័រដើម') }}</span></a>
                <a href="{{ route('courses') }}">{!! $icon('book', 'i5') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Courses', 'វគ្គសិក្សា') }}</span></a>
                <a href="{{ route('learning.index') }}">{!! $icon('folder', 'i5') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Learning', 'ការសិក្សា') }}</span></a>
                <a href="{{ route('about') }}">{!! $icon('sparkles', 'i5') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('About', 'អំពី') }}</span></a>
                <a href="{{ route('learning.profile') }}">{!! $icon('user', 'i5') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Profile', 'គណនី') }}</span></a>
            </div>
        </nav>

        {{-- Language and theme: one pill in the bottom-right corner. --}}
        <div class="hm-prefs">
            <form method="POST" action="{{ route('locale') }}">
                @csrf
                <input type="hidden" name="locale" value="{{ $hmKm ? 'en' : 'km' }}">
                <button type="submit" class="hm-pref" aria-label="{{ $hmKm ? 'Switch to English' : 'ប្តូរទៅភាសាខ្មែរ' }}" title="{{ $hmKm ? 'Switch to English (EN)' : 'ប្តូរទៅភាសាខ្មែរ (KM)' }}">
                    @if ($hmKm)
                        <svg class="hm-flag" viewBox="0 0 30 20" aria-hidden="true"><rect width="30" height="20" fill="#032EA1" /><rect y="5" width="30" height="10" fill="#E00025" /><path fill="#fff" d="M9 13.4h12v1.2H9zM10.2 11.6h9.6v1.8h-9.6zM11.6 9.4h1.2v2.2h-1.2zM14.4 7.4h1.2v4.2h-1.2zM17.2 9.4h1.2v2.2h-1.2z" /></svg>
                        <span class="l hm-km">ខ្មែរ</span>
                    @else
                        <svg class="hm-flag" viewBox="0 0 60 30" aria-hidden="true"><clipPath id="hm-uk-clip"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z" /></clipPath><path d="M0,0 v30 h60 v-30 z" fill="#012169" /><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6" /><path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#hm-uk-clip)" stroke="#C8102E" stroke-width="4" /><path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10" /><path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6" /></svg>
                        <span class="l">EN</span>
                    @endif
                </button>
            </form>
            <span class="hm-pref-divider" aria-hidden="true"></span>
            <button type="button" class="hm-pref" @click="toggleTheme($event)"
                :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'" :title="dark ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
                <span class="hm-spin-in" style="display:flex; color:#4595FF" x-show="dark" x-cloak>{!! $icon('moon', 'i35') !!}</span>
                <span class="hm-spin-in" style="display:flex; color:#F59E0B" x-show="!dark">{!! $icon('sun', 'i35') !!}</span>
                <span class="l m" x-text="dark ? 'Dark' : 'Light'">Dark</span>
            </button>
        </div>

        {{-- Project details dialog (filled by the script below) --}}
        <div class="hm-modal" id="hm-modal" hidden>
            <div class="hm-dialog" role="dialog" aria-modal="true" aria-labelledby="hm-modal-title">
                <div class="hm-dhead">
                    <div class="hm-dhead-l">
                        <div class="hm-dicon">{!! $icon('sparkles', 'i5') !!}</div>
                        <div style="min-width:0">
                            <span class="hm-dkicker hm-mono"><span data-f="course"></span> · Verified Student Deliverable</span>
                            <h3 id="hm-modal-title" class="hm-dtitle" data-f="title"></h3>
                        </div>
                    </div>
                    <div class="hm-dacts">
                        <button type="button" class="hm-dbtn" data-share title="Share Showcase">
                            <span data-share-icon style="display:flex; color:#4DA3FF">{!! $icon('share') !!}</span>
                            <span class="hm-share-label" data-share-label aria-live="polite">Share</span>
                        </button>
                        <button type="button" class="hm-dbtn hm-dbtn--close" data-close aria-label="Close">{!! $icon('x', 'i5') !!}</button>
                    </div>
                </div>
                <div class="hm-dbody">
                    <div class="hm-dshot"><img data-f="image" alt=""></div>
                    <div class="hm-dgrid">
                        <div class="hm-dcard">
                            <div class="hm-dby">
                                <img data-f="studentAvatar" alt="">
                                <div>
                                    <span class="k hm-mono">Designed by</span>
                                    <h4 data-f="studentName"></h4>
                                    <span class="kh hm-km" data-f="studentNameKh"></span>
                                </div>
                            </div>
                            <dl class="hm-dfacts">
                                <div><dt>Honors Award:</dt><dd class="aw hm-mono" data-f="badge"></dd></div>
                                <div><dt>Curriculum:</dt><dd class="cu" data-f="course"></dd></div>
                                <div><dt>Total Likes:</dt><dd class="lk hm-mono"><span data-modal-count></span> Likes</dd></div>
                            </dl>
                            <div>
                                <span class="hm-dstack-k hm-mono">Design Stack:</span>
                                <div class="hm-dstack hm-mono" data-f="tools"></div>
                            </div>
                        </div>
                        <div class="hm-dright">
                            <div>
                                <h4 class="hm-mono">Project Concept &amp; Rationale</h4>
                                <p class="hm-dconcept" data-f="description"></p>
                            </div>
                            <div class="hm-dcrit">
                                <div class="hm-dcrit-head">
                                    <div>
                                        <span class="hm-avatar" role="img" aria-label="Teacher Piseth Yoeurn">{!! $blank !!}</span>
                                        <div><span class="n">Piseth Yoeurn (លោកគ្រូ ពិសិដ្ឋ)</span><span class="r hm-km">សាស្ត្រាចារ្យ · BBU Siem Reap</span></div>
                                    </div>
                                    <span class="hm-dgrade hm-mono">Grade: <span data-f="grade"></span></span>
                                </div>
                                <blockquote class="hm-dquote">"<span data-f="teacherCritique"></span>"</blockquote>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.hmProjects = @json(collect($hmProjects)->keyBy('id'));
        window.hmDefaultLiked = @json($hmDefaultLiked);
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
               stretches as it lands, a soft trail follows it, a shimmer sweeps
               across and a few sparks pop out. */
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

            /* ---------- course cover opens the course ---------- */
            document.querySelectorAll('.hm-ccover[data-href]').forEach(function (cover) {
                cover.addEventListener('click', function (e) {
                    if (e.target.closest('button')) return;
                    window.location.href = cover.getAttribute('data-href');
                });
            });

            /* ---------- wishlist (this browser only) ---------- */
            var wish = store.get('pd-wishlist', []);
            if (!Array.isArray(wish)) wish = [];
            document.querySelectorAll('[data-wish]').forEach(function (btn) {
                var id = btn.getAttribute('data-wish');
                function paint() {
                    var on = wish.indexOf(id) !== -1;
                    btn.setAttribute('aria-pressed', on ? 'true' : 'false');
                    btn.title = on ? hmText.removeWish : hmText.saveWish;
                }
                paint();
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    wish = wish.indexOf(id) !== -1 ? wish.filter(function (x) { return x !== id; }) : wish.concat(id);
                    store.set('pd-wishlist', wish);
                    paint();
                });
            });

            /* ---------- likes (this browser only; two projects start liked, as on the live site) ---------- */
            var projects = window.hmProjects || {};
            var defaults = window.hmDefaultLiked || [];
            var liked = store.get('pd-showcase-likes', null);
            if (!Array.isArray(liked)) liked = defaults.slice();
            function isOn(id) { return liked.indexOf(id) !== -1; }
            function likeCount(id) {
                var base = projects[id] ? projects[id].likes : 0;
                return base + (isOn(id) ? 1 : 0) - (defaults.indexOf(id) !== -1 ? 1 : 0);
            }
            var openId = null;
            var modal = document.getElementById('hm-modal');
            function paintLikes() {
                document.querySelectorAll('[data-like]').forEach(function (btn) {
                    var id = btn.getAttribute('data-like');
                    btn.setAttribute('aria-pressed', isOn(id) ? 'true' : 'false');
                });
                document.querySelectorAll('[data-count-wrap]').forEach(function (wrap) {
                    var id = wrap.getAttribute('data-count-wrap');
                    wrap.classList.toggle('is-on', isOn(id));
                    wrap.querySelector('[data-count]').textContent = likeCount(id);
                });
                if (openId) modal.querySelector('[data-modal-count]').textContent = likeCount(openId);
            }
            document.querySelectorAll('[data-like]').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var id = btn.getAttribute('data-like');
                    liked = isOn(id) ? liked.filter(function (x) { return x !== id; }) : liked.concat(id);
                    store.set('pd-showcase-likes', liked);
                    paintLikes();
                });
                btn.addEventListener('keydown', function (e) { e.stopPropagation(); });
            });

            /* ---------- project dialog ---------- */
            var shareBtn = modal.querySelector('[data-share]');
            var shareLabel = modal.querySelector('[data-share-label]');
            var shareIcon = modal.querySelector('[data-share-icon]');
            var shareIconHtml = shareIcon.innerHTML;
            var lastFocus = null, shareTimer = 0;

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
                    if (!ok) return;
                    shareLabel.textContent = hmText.copied;
                    shareIcon.innerHTML = '<svg class="i4 is-ok" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';
                    clearTimeout(shareTimer);
                    shareTimer = setTimeout(function () { shareLabel.textContent = hmText.share; shareIcon.innerHTML = shareIconHtml; }, 2000);
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
