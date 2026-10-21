{{-- Assignment and quiz cards (.tk-*), shared by the Assignments page and the Profile tabs. --}}
<style>
    .tk-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.25rem; }
    @media (min-width: 768px) { .tk-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .tk-grid--3 { grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 640px) { .tk-grid--3 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .tk-grid--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .tk-card { display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; border-radius: 1rem; background: #fff; border: 1px solid rgba(226, 232, 240, .8); box-shadow: 0 1px 2px rgba(0, 0, 0, .05); color: inherit; text-decoration: none; transition: transform .3s cubic-bezier(.22, 1, .36, 1), box-shadow .3s ease, border-color .3s ease; }
    html.dark .tk-card { background: #151B23; border-color: #222936; }
    .tk-card:hover { border-color: rgba(77, 163, 255, .5); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, .1), 0 8px 10px -6px rgba(0, 0, 0, .1); transform: translateY(-.25rem); }
    .tk-card[hidden] { display: none; }
    .tk-pic { position: relative; display: block; aspect-ratio: 16 / 9; overflow: hidden; background: #020617; }
    .tk-pic img { width: 100%; height: 100%; object-fit: cover; transition: transform .6s cubic-bezier(.22, 1, .36, 1); }
    .tk-card:hover .tk-pic img { transform: scale(1.06); }
    .tk-pic::after { content: ''; position: absolute; inset: 0; pointer-events: none; background: linear-gradient(to top, rgba(0, 0, 0, .85), rgba(0, 0, 0, .3), rgba(0, 0, 0, .2)); }
    .tk-top { position: absolute; z-index: 2; top: .75rem; left: .75rem; right: .75rem; display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .tk-num { padding: .125rem .625rem; border-radius: .5rem; background: rgba(0, 0, 0, .6); border: 1px solid rgba(255, 255, 255, .2); color: #fff; font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: .75rem; font-weight: 700; backdrop-filter: blur(12px); white-space: nowrap; }
    .tk-num--sm { font-size: 10px; font-weight: 500; color: rgba(255, 255, 255, .9); }
    .tk-pill { display: inline-flex; align-items: center; gap: .25rem; padding: .125rem .625rem; border-radius: .5rem; border: 1px solid; font-size: 11px; font-weight: 600; color: #fff; backdrop-filter: blur(12px); white-space: nowrap; }
    .tk-pill[data-tone="green"] { background: rgba(16, 185, 129, .8); border-color: rgba(52, 211, 153, .4); }
    .tk-pill[data-tone="amber"] { background: rgba(245, 158, 11, .85); border-color: rgba(251, 191, 36, .4); }
    .tk-pill[data-tone="blue"] { background: rgba(59, 130, 246, .8); border-color: rgba(96, 165, 250, .4); }
    .tk-pill[data-tone="gray"] { background: rgba(15, 23, 42, .65); border-color: rgba(255, 255, 255, .2); }
    .tk-pill[data-tone="red"] { background: rgba(225, 29, 72, .85); border-color: rgba(251, 113, 133, .4); }
    .tk-pill.is-pass { background: rgba(2, 44, 34, .8); border-color: rgba(16, 185, 129, .3); color: #6EE7B7; font-weight: 700; }
    .tk-cap { position: absolute; z-index: 2; left: .75rem; right: .75rem; bottom: .625rem; display: flex; align-items: flex-end; justify-content: space-between; gap: .75rem; }
    .tk-cap h3 { margin: 0; font-size: 1rem; line-height: 1.4; font-weight: 700; color: #fff; text-shadow: 0 1px 2px rgba(0, 0, 0, .3); }
    .tk-cap small { display: block; margin-top: .125rem; font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 11px; color: #CBD5E1; }
    html:lang(km) .tk-cap small.hm-km { font-family: inherit; }
    .tk-score { flex-shrink: 0; font-size: 1.5rem; font-weight: 900; color: #fff; font-variant-numeric: tabular-nums; text-shadow: 0 4px 12px rgba(0, 0, 0, .4); }
    .tk-body { flex: 1; display: flex; flex-direction: column; justify-content: space-between; gap: .75rem; padding: 1rem 1.25rem 1.25rem; }
    .tk-body p { margin: 0; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; font-size: .75rem; line-height: 1.6; color: #475569; }
    html.dark .tk-body p { color: #CBD5E1; }
    .tk-fb { padding: .75rem; border-radius: .75rem; background: #F8FAFC; border: 1px solid #F1F5F9; font-size: .75rem; color: #475569; }
    html.dark .tk-fb { background: #11161D; border-color: #222936; color: #CBD5E1; }
    .tk-fb div { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .tk-fb strong { color: #0F172A; font-weight: 600; }
    html.dark .tk-fb strong { color: #fff; }
    .tk-fb b { font-family: 'JetBrains Mono', ui-monospace, monospace; color: #10B981; }
    .tk-fb em { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-top: .25rem; font-size: 11px; color: #64748B; }
    html.dark .tk-fb em { color: #94A3B8; }
    .tk-foot { display: flex; align-items: center; justify-content: space-between; gap: .75rem; font-size: .75rem; color: #64748B; }
    html.dark .tk-foot { color: #94A3B8; }
    .tk-foot .go { display: inline-flex; align-items: center; gap: .25rem; font-weight: 600; color: #4DA3FF; }
    .tk-card:hover .tk-foot .go { text-decoration: underline; }
    .tk-foot .late { color: #E11D48; font-weight: 600; }
    html.dark .tk-foot .late { color: #FB7185; }
    .tk-mono { font-family: 'JetBrains Mono', ui-monospace, monospace; }
    html:lang(km) .hm-body :is(.tk-cap h3) { line-height: 1.6; }
</style>
