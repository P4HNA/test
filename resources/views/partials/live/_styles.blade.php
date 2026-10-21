{{--
    Styles shared by every page built on the live piseth.design design
    (components/live-layout): colours for light and dark, the top bar,
    filter tabs, footer, phone bottom bar, language / theme pill and the
    animations. Page-specific styles go in the page's own "styles" slot.
--}}
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
       TOP BAR (the live site's header; this page only)
       ================================================================== */
    .hm-header { position: sticky; top: 0; z-index: 40; width: 100%; background: rgba(255, 255, 255, .85); -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px); border-bottom: 1px solid var(--line); font-family: Inter, 'Noto Sans Khmer', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.5; -webkit-font-smoothing: antialiased; transition: background-color .2s ease, border-color .2s ease; }
    html.dark .hm-header { background: rgba(28, 28, 30, .85); }
    .hm-hbar { max-width: 80rem; height: 4rem; margin: 0 auto; padding: 0 1rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    @media (min-width: 640px) { .hm-hbar { padding: 0 1.5rem; } }
    @media (min-width: 1024px) { .hm-hbar { padding: 0 2rem; } }
    .hm-hlogo { flex-shrink: 0; display: flex; align-items: center; min-height: 44px; font-size: 1.125rem; line-height: 1.75rem; font-weight: 700; letter-spacing: -.025em; color: var(--ink); text-decoration: none; }
    @media (min-width: 640px) { .hm-hlogo { font-size: 1.25rem; } }
    .hm-hlogo span { color: var(--blue); }
    .hm-hnav { display: none; }
    @media (min-width: 768px) { .hm-hnav { display: flex; align-items: center; gap: 2rem; } }
    .hm-hnav a { position: relative; padding: .375rem 0; font-size: .875rem; line-height: 1.25rem; font-weight: 500; white-space: nowrap; color: var(--mut); text-decoration: none; transition: color .15s ease; }
    .hm-hnav a:hover { color: var(--ink); }
    .hm-hnav a[aria-current="page"] { font-weight: 600; color: var(--blue); }
    .hm-hnav a[aria-current="page"]::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 2px; border-radius: 9999px; background: var(--blue); }
    .hm-hright { position: relative; display: flex; align-items: center; gap: .5rem; }
    @media (min-width: 640px) { .hm-hright { gap: .625rem; } }
    .hm-hbell { position: relative; display: flex; padding: .5rem; border: 0; border-radius: .75rem; background: transparent; color: var(--mut); cursor: pointer; transition: all .15s ease; }
    .hm-hbell:hover, .hm-hbell[aria-expanded="true"] { color: var(--ink); background: #F5F7FA; }
    html.dark .hm-hbell:hover, html.dark .hm-hbell[aria-expanded="true"] { background: #242629; }
    .hm-hbell .dot { position: absolute; top: .375rem; right: .375rem; width: .5rem; height: .5rem; border-radius: 9999px; background: #1877F2; box-shadow: 0 0 0 2px #fff; }
    html.dark .hm-hbell .dot { box-shadow: 0 0 0 2px #1C1C1E; }
    .hm-havatar { display: flex; padding: 2px; border: 0; border-radius: 9999px; background: transparent; cursor: pointer; transition: box-shadow .15s ease; }
    .hm-havatar:hover, .hm-havatar[aria-expanded="true"] { box-shadow: 0 0 0 2px rgba(24, 119, 242, .4); }
    .hm-havatar .pd-avatar { box-shadow: 0 0 0 1px var(--line); }
    .hm-hsignin { display: inline-flex; align-items: center; gap: .375rem; padding: .375rem .75rem; border-radius: .75rem; background: #4DA3FF; color: #fff; font-size: .75rem; line-height: 1rem; font-weight: 600; text-decoration: none; white-space: nowrap; box-shadow: 0 1px 2px rgba(0, 0, 0, .05); transition: background .15s ease; }
    .hm-hsignin:hover { background: #3892F3; }
    .hm-hportal, .hm-hdash { display: none; align-items: center; gap: .375rem; padding: .375rem .75rem; border-radius: .5rem; font-size: .75rem; line-height: 1rem; font-weight: 600; text-decoration: none; white-space: nowrap; transition: background .15s ease; }
    @media (min-width: 1024px) { .hm-hportal, .hm-hdash { display: inline-flex; } }
    .hm-hportal { color: #4DA3FF; background: #EAF5FF; border: 1px solid rgba(77, 163, 255, .3); }
    .hm-hportal:hover { background: #D9EDFF; }
    html.dark .hm-hportal { background: rgba(77, 163, 255, .15); }
    html.dark .hm-hportal:hover { background: rgba(77, 163, 255, .25); }
    .hm-hdash { color: #059669; background: #ECFDF5; border: 1px solid rgba(16, 185, 129, .3); }
    .hm-hdash:hover { background: #D1FAE5; }
    html.dark .hm-hdash { color: #34D399; background: rgba(2, 44, 34, .3); }
    html.dark .hm-hdash:hover { background: rgba(2, 44, 34, .5); }
    .hm-hpanel { position: absolute; top: calc(100% + .5rem); right: 0; z-index: 50; width: min(16rem, calc(100vw - 2rem)); overflow: hidden; border-radius: .75rem; background: #fff; border: 1px solid #E2E8F0; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, .1), 0 8px 10px -6px rgba(0, 0, 0, .1); transform-origin: top right; animation: hm-zoom-in .15s ease-out both; }
    html.dark .hm-hpanel { background: #151B23; border-color: #222936; }
    .hm-hpanel--bell { width: min(18rem, calc(100vw - 2rem)); }
    .hm-hpanel-head { padding: .875rem; border-bottom: 1px solid #F1F5F9; }
    html.dark .hm-hpanel-head { border-color: #222936; }
    .hm-hrow { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .hm-hname { margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .75rem; line-height: 1rem; font-weight: 600; color: var(--strong); }
    .hm-hmail { margin: .125rem 0 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 11px; color: var(--soft); }
    .hm-hsub { margin: .25rem 0 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 10px; font-weight: 500; color: #4DA3FF; }
.hm-hrole { flex-shrink: 0; padding: .125rem .5rem; border-radius: 9999px; font-size: 9px; font-weight: 700; text-transform: uppercase; background: #ECFDF5; color: #059669; border: 1px solid rgba(16, 185, 129, .2); }
    html.dark .hm-hrole { background: rgba(2, 44, 34, .4); color: #34D399; }
    .hm-hrole--teacher, html.dark .hm-hrole--teacher { background: rgba(77, 163, 255, .15); color: #4DA3FF; border-color: rgba(77, 163, 255, .3); }
    .hm-hmenu { padding: .25rem; font-size: .75rem; line-height: 1rem; }
    .hm-hmenu form { margin: 0; }
    .hm-hitem { width: 100%; display: flex; align-items: center; gap: .5rem; padding: .5rem .75rem; border: 0; border-radius: .5rem; background: transparent; font: inherit; text-align: left; text-decoration: none; color: #334155; cursor: pointer; transition: background .15s ease; }
    html.dark .hm-hitem { color: #CBD5E1; }
    .hm-hitem:hover { background: #F1F5F9; }
    html.dark .hm-hitem:hover { background: #1C232D; }
    .hm-hitem svg { color: #4DA3FF; }
    .hm-hitem--emerald svg { color: #10B981; }
    .hm-hitem--muted svg { color: #94A3B8; }
    .hm-hitem--strong { font-weight: 600; }
    .hm-hitem--split { justify-content: space-between; }
    .hm-hitem--split > span:first-child { display: flex; align-items: center; gap: .5rem; }
    .hm-hitem .hint { font-size: 10px; font-weight: 600; color: #4DA3FF; }
    .hm-hitem .hint--mono { font-weight: 400; color: #94A3B8; }
    .hm-hitem .hint--chip { flex-shrink: 0; padding: .125rem .375rem; border-radius: .25rem; background: rgba(77, 163, 255, .15); font-weight: 700; }
    .hm-hitem--danger, html.dark .hm-hitem--danger { font-weight: 600; color: #E11D48; }
    html.dark .hm-hitem--danger { color: #FB7185; }
    .hm-hitem--danger svg { color: #F43F5E; }
    .hm-hitem--danger:hover { background: #FFF1F2; }
    html.dark .hm-hitem--danger:hover { background: rgba(76, 5, 25, .4); }
    .hm-hsep { margin-top: .25rem; padding-top: .25rem; border-top: 1px solid #F1F5F9; }
    html.dark .hm-hsep { border-color: #222936; }
    .hm-hbrand { display: flex; align-items: center; gap: .625rem; }
    .hm-hbrand img { width: 1.5rem; height: 1.5rem; flex-shrink: 0; }
    .hm-hnote { display: block; padding: .875rem; text-decoration: none; transition: background .15s ease; }
    .hm-hnote:hover { background: #F8FAFC; }
    html.dark .hm-hnote:hover { background: #1C232D; }
    .hm-hnote .pill { display: inline-block; padding: .125rem .5rem; border-radius: 9999px; background: rgba(77, 163, 255, .15); font-size: 10px; font-weight: 700; color: #4DA3FF; }
    .hm-hnote .t { display: block; margin-top: .375rem; font-size: .75rem; line-height: 1rem; font-weight: 600; color: var(--strong); }
    .hm-hnote .m { display: block; margin-top: .125rem; font-size: 11px; line-height: 1.5; color: var(--soft); }
    .hm-hnote .go { display: inline-flex; align-items: center; gap: .25rem; margin-top: .5rem; font-size: 11px; font-weight: 600; color: var(--blue); }
    .hm-hempty { margin: 0; padding: 1.25rem .875rem; text-align: center; font-size: .75rem; color: var(--soft); }
    html:lang(km) .hm-body :is(.hm-hnav a, .hm-hsignin, .hm-hportal, .hm-hdash, .hm-hitem, .hm-hname, .hm-hempty) { line-height: 1.5; }
    html:lang(km) .hm-body .hm-hlogo { letter-spacing: -.025em; }

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
