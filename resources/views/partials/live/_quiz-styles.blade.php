{{-- Quiz page and quiz results (.qz-*). --}}
<style>
    .qz-main { font-family: Inter, 'Noto Sans Khmer', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.5; -webkit-font-smoothing: antialiased; background: #F8FAFC; }
    html.dark .qz-main { background: #0B0F17; }
    .qz-page { max-width: 48rem; margin: 0 auto; padding: 2rem 1rem 6rem; display: flex; flex-direction: column; gap: 1.5rem; }
    @media (min-width: 640px) { .qz-page { padding-left: 1.5rem; padding-right: 1.5rem; } }
    .qz-back { display: inline-flex; align-items: center; gap: .5rem; align-self: flex-start; font-size: .8125rem; font-weight: 600; color: #475569; text-decoration: none; transition: color .15s ease, gap .2s ease; }
    html.dark .qz-back { color: #94A3B8; }
    .qz-back:hover { color: #1877F2; gap: .75rem; }
    .qz-card { overflow: hidden; border-radius: 1.5rem; background: #fff; border: 1px solid rgba(226, 232, 240, .8); box-shadow: 0 1px 3px rgba(0, 0, 0, .06), 0 10px 30px -18px rgba(15, 23, 42, .25); }
    html.dark .qz-card { background: #151B23; border-color: #222936; }
    .qz-hero { position: relative; height: 12rem; overflow: hidden; background: #020617; }
    @media (min-width: 640px) { .qz-hero { height: 15rem; } }
    .qz-hero > img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; animation: ad-ken 22s ease-in-out infinite alternate; }
    @keyframes ad-ken { from { transform: scale(1.02); } to { transform: scale(1.12) translate(-2%, -1%); } }
    .qz-hero::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, rgba(2, 6, 23, .9), rgba(2, 6, 23, .25) 60%, rgba(2, 6, 23, .2)); }
    .qz-chips { position: absolute; z-index: 2; top: 1rem; left: 1rem; right: 1rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; }
    @media (min-width: 640px) { .qz-chips { left: 1.5rem; right: 1.5rem; } }
    .qz-chips > div { display: flex; flex-wrap: wrap; gap: .5rem; }
    .qz-chip { display: inline-flex; align-items: center; gap: .375rem; padding: .25rem .75rem; border-radius: .5rem; background: rgba(0, 0, 0, .6); border: 1px solid rgba(255, 255, 255, .2); color: #fff; font-size: .75rem; font-weight: 600; backdrop-filter: blur(12px); }
    .qz-chip svg { color: #4DA3FF; }
    .qz-mono { font-family: 'JetBrains Mono', ui-monospace, monospace; }
    .qz-title { position: absolute; z-index: 2; left: 1rem; right: 1rem; bottom: 1rem; }
    @media (min-width: 640px) { .qz-title { left: 1.5rem; right: 1.5rem; } }
    .qz-title h1 { margin: 0; font-size: 1.75rem; line-height: 1.15; font-weight: 800; color: #fff; text-shadow: 0 2px 12px rgba(0, 0, 0, .35); }
    @media (min-width: 640px) { .qz-title h1 { font-size: 2rem; } }
    .qz-title p { margin: .25rem 0 0; font-size: .875rem; color: #7DD3FC; }
    .qz-body { padding: 1.5rem 1.25rem 1.75rem; }
    @media (min-width: 640px) { .qz-body { padding: 2rem; } }
    .qz-prog { display: flex; justify-content: space-between; margin-bottom: .5rem; font-size: .75rem; color: #64748B; }
    .qz-prog b { font-family: 'JetBrains Mono', ui-monospace, monospace; font-weight: 500; }
    .qz-bar { height: .375rem; border-radius: 9999px; background: #F1F5F9; overflow: hidden; }
    html.dark .qz-bar { background: #1E293B; }
    .qz-bar i { display: block; height: 100%; width: 0; border-radius: inherit; background: linear-gradient(90deg, #4DA3FF, #818CF8); transition: width .6s cubic-bezier(.22, 1, .36, 1); }
    .qz-q { margin-top: 1.75rem; }
    .qz-js .qz-q:not(.is-on) { display: none; }
    .qz-q.is-on { animation: qz-in .45s cubic-bezier(.22, 1, .36, 1) both; }
    @keyframes qz-in { from { opacity: 0; transform: translateX(18px); } to { opacity: 1; transform: none; } }
    .qz-label { margin: 0; font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: .75rem; letter-spacing: .05em; text-transform: uppercase; color: #94A3B8; }
    .qz-q h2 { margin: .375rem 0 0; font-size: 1.1875rem; line-height: 1.4; font-weight: 800; color: #0F172A; }
    html.dark .qz-q h2 { color: #F8FAFC; }
    .qz-q h2 + p { margin: .5rem 0 0; font-size: .9375rem; color: #64748B; }
    .qz-opts { display: grid; gap: .625rem; margin-top: 1.25rem; }
    .qz-opt { position: relative; display: flex; align-items: center; gap: .875rem; padding: .875rem 1rem; border-radius: 1rem; background: #F8FAFC; border: 1px solid #E2E8F0; color: #1E293B; font-size: .9375rem; cursor: pointer; transition: all .2s ease; }
    html.dark .qz-opt { background: #11161D; border-color: #2E3034; color: #E2E8F0; }
    .qz-opt:hover { border-color: rgba(77, 163, 255, .6); background: #fff; transform: translateX(2px); }
    html.dark .qz-opt:hover { background: #1A2230; }
    .qz-opt input { position: absolute; opacity: 0; pointer-events: none; }
    .qz-opt .k { display: inline-flex; flex-shrink: 0; width: 1.5rem; height: 1.5rem; align-items: center; justify-content: center; border-radius: 9999px; border: 1px solid #CBD5E1; font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 11px; color: #64748B; transition: all .2s ease; }
    .qz-opt:has(input:checked) { border-color: #4DA3FF; background: #EAF5FF; box-shadow: 0 0 0 3px rgba(77, 163, 255, .18); }
    html.dark .qz-opt:has(input:checked) { background: rgba(77, 163, 255, .14); }
    .qz-opt:has(input:checked) .k { background: #4DA3FF; border-color: #4DA3FF; color: #fff; }
    .qz-opt:has(input:focus-visible) { outline: 2px solid #4DA3FF; outline-offset: 2px; }
    .qz-opt.is-right { border-color: #10B981 !important; background: #ECFDF5 !important; box-shadow: none !important; }
    html.dark .qz-opt.is-right { background: rgba(16, 185, 129, .14) !important; }
    .qz-opt.is-right .k { background: #10B981 !important; border-color: #10B981 !important; color: #fff !important; }
    .qz-opt.is-wrong { border-color: #F43F5E !important; background: #FFF1F2 !important; box-shadow: none !important; animation: qz-shake .4s ease; }
    html.dark .qz-opt.is-wrong { background: rgba(244, 63, 94, .12) !important; }
    .qz-opt.is-wrong .k { background: #F43F5E !important; border-color: #F43F5E !important; color: #fff !important; }
    .qz-locked .qz-opt { cursor: default; transform: none !important; }
    @keyframes qz-shake { 20%, 60% { transform: translateX(-4px); } 40%, 80% { transform: translateX(4px); } }
    .qz-why { display: none; margin-top: 1rem; padding: .875rem 1rem; border-radius: 1rem; font-size: .875rem; line-height: 1.6; }
    .qz-why.is-on { display: block; animation: hm-pop-in .4s ease both; }
    .qz-why.ok { background: #ECFDF5; color: #065F46; border: 1px solid rgba(16, 185, 129, .3); }
    .qz-why.no { background: #FFF7ED; color: #9A3412; border: 1px solid rgba(249, 115, 22, .3); }
    html.dark .qz-why.ok { background: rgba(16, 185, 129, .12); color: #A7F3D0; }
    html.dark .qz-why.no { background: rgba(249, 115, 22, .12); color: #FED7AA; }
    .qz-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid #F1F5F9; }
    html.dark .qz-foot { border-color: #222936; }
    .qz-foot small { font-size: .8125rem; color: #94A3B8; }
    .qz-btn { display: inline-flex; align-items: center; gap: .5rem; padding: .75rem 1.375rem; border: 0; border-radius: .875rem; background: #4DA3FF; color: #fff; font: inherit; font-size: .875rem; font-weight: 700; text-decoration: none; cursor: pointer; box-shadow: 0 6px 16px -6px rgba(77, 163, 255, .7); transition: background .15s ease, opacity .15s ease, transform .15s ease; }
    .qz-btn:hover { background: #3892F3; transform: translateY(-1px); }
    .qz-btn:disabled { opacity: .45; cursor: not-allowed; transform: none; box-shadow: none; }
    .qz-btn--ghost { background: #F1F5F9; color: #0F172A; box-shadow: none; }
    html.dark .qz-btn--ghost { background: #1E2530; color: #F1F5F9; }
    .qz-btn--ghost:hover { background: #E2E8F0; }
    html.dark .qz-btn--ghost:hover { background: #252E3B; }

    /* score */
    .qz-done { display: flex; flex-direction: column; align-items: center; gap: 1rem; padding: 2.5rem 1.25rem; text-align: center; }
    .qz-done[hidden] { display: none; }
    .qz-ring { position: relative; width: 10rem; height: 10rem; }
    .qz-ring svg { width: 100%; height: 100%; transform: rotate(-90deg); }
    .qz-ring circle { fill: none; stroke-width: 10; }
    .qz-ring .bg { stroke: #E2E8F0; }
    html.dark .qz-ring .bg { stroke: #1E293B; }
    .qz-ring .fg { stroke: url(#qzGrad); stroke-linecap: round; stroke-dasharray: 289; stroke-dashoffset: 289; transition: stroke-dashoffset 1.4s cubic-bezier(.22, 1, .36, 1) .2s; }
    .qz-ring b { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 2.25rem; font-weight: 900; color: var(--ink); font-variant-numeric: tabular-nums; }
    .qz-ring b small { font-size: .75rem; font-weight: 600; color: var(--mut); }
    .qz-done h2 { margin: 0; font-size: 1.5rem; font-weight: 900; color: var(--ink); }
    .qz-done p { margin: 0; max-width: 28rem; font-size: .9375rem; color: var(--mut); }
    .qz-done .row { display: flex; flex-wrap: wrap; justify-content: center; gap: .625rem; margin-top: .5rem; }
    .qz-pass { display: inline-flex; align-items: center; gap: .375rem; padding: .25rem .75rem; border-radius: 9999px; font-size: .75rem; font-weight: 700; }
    .qz-pass.ok { background: rgba(16, 185, 129, .12); color: #059669; }
    .qz-pass.no { background: rgba(245, 158, 11, .14); color: #B45309; }
    html.dark .qz-pass.ok { color: #34D399; }
    html.dark .qz-pass.no { color: #FBBF24; }

    /* results list */
    .qz-review { display: grid; gap: .875rem; }
    .qz-item { padding: 1.125rem 1.25rem; border-radius: 1.25rem; background: #fff; border: 1px solid rgba(226, 232, 240, .8); }
    html.dark .qz-item { background: #151B23; border-color: #222936; }
    .qz-item.is-right { border-left: 4px solid #10B981; }
    .qz-item.is-wrong { border-left: 4px solid #F43F5E; }
    .qz-item h3 { margin: .25rem 0 .75rem; font-size: 1rem; line-height: 1.45; font-weight: 700; color: var(--ink); }
    .qz-item ul { list-style: none; margin: 0; padding: 0; display: grid; gap: .375rem; }
    .qz-item li { display: flex; align-items: center; gap: .625rem; padding: .5rem .75rem; border-radius: .75rem; font-size: .875rem; color: var(--mut); }
    .qz-item li.right { background: #ECFDF5; color: #065F46; font-weight: 600; }
    .qz-item li.picked:not(.right) { background: #FFF1F2; color: #9F1239; text-decoration: line-through; }
    html.dark .qz-item li.right { background: rgba(16, 185, 129, .12); color: #A7F3D0; }
    html.dark .qz-item li.picked:not(.right) { background: rgba(244, 63, 94, .12); color: #FDA4AF; }
    .qz-item li .k { display: inline-flex; flex-shrink: 0; width: 1.375rem; height: 1.375rem; align-items: center; justify-content: center; border-radius: 9999px; border: 1px solid currentColor; font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 10px; text-decoration: none; }
    .qz-item .why { margin: .75rem 0 0; font-size: .8125rem; line-height: 1.6; color: var(--mut); }
    html:lang(km) .hm-body :is(.qz-title h1, .qz-q h2, .qz-done h2) { line-height: 1.6; }
    @media (prefers-reduced-motion: reduce) { .qz-hero > img, .qz-q.is-on, .qz-opt.is-wrong { animation: none; } }
</style>
