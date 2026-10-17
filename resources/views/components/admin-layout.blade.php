@props(['title' => 'Dashboard', 'active' => 'dashboard', 'live' => 20000])

@php
    $nav = [
        'Main' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'grid'],
        ],
        'Manage' => [
            ['key' => 'courses', 'label' => 'Courses', 'route' => 'admin.courses', 'icon' => 'book'],
            ['key' => 'videos', 'label' => 'Videos', 'route' => 'admin.videos', 'icon' => 'video'],
            ['key' => 'thumbnails', 'label' => 'Course pictures', 'route' => 'admin.thumbnails', 'icon' => 'image'],
            ['key' => 'users', 'label' => 'Students', 'route' => 'admin.users', 'icon' => 'users'],
            ['key' => 'certificates', 'label' => 'Certificates', 'route' => 'admin.certificates', 'icon' => 'award'],
        ],
        'Finance' => [
            ['key' => 'payments', 'label' => 'Payments', 'route' => 'admin.payments', 'icon' => 'card'],
            // Discounts change what checkout charges, so they sit next to the money.
            ['key' => 'promotions', 'label' => 'Promotions', 'route' => 'admin.promotions', 'icon' => 'tag'],
        ],
    ];
    $me = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} - Piseth.Design Admin</title>

    {{-- Same theme key, fonts and design system as the rest of the product --}}
    @include('partials._head')

    <style>
        /* ============================================================
           Admin console, built on the shared design system
           (public/css/ui.css). The admin's short token names below are
           aliases of the product tokens, so a colour changes in one place
           for the whole product and light/dark carries over.
           ============================================================ */
        :root {
            --card: var(--surface);
            --soft: var(--surface-2);
            --muted: var(--text-2);
            --line: var(--border);
            --green: var(--success-text);
            --amber: var(--warning-text);
            --blue: var(--text);
            /* Categorical chart colours: distinct hues that hold up in both themes. */
            --c1: var(--accent);
            --c2: #EA580C;   /* the accent is blue now, so the second series is orange */
            --c3: #059669;
            --c4: #7C3AED;
            --c5: #A8A29E;
        }

        html.dark {
            --c2: #FB923C;
            --c3: #34D399;
            --c4: #A78BFA;
            --c5: #78716C;
        }

        [x-cloak] { display: none !important; }

        /* ---------- Shell ---------- */
        .ad-side {
            position: fixed; top: 0; bottom: 0; left: 0; z-index: 50;
            display: flex; flex-direction: column; width: 256px;
            background: var(--surface); border-right: 1px solid var(--line);
            transform: translateX(-100%); transition: transform var(--dur-4) var(--ease-drawer);
        }
        .ad-side.is-open { transform: none; box-shadow: var(--shadow-lg); }
        .ad-scrim { position: fixed; inset: 0; z-index: 45; background: var(--overlay); }
        .ad-main { min-height: 100vh; }
        .ad-brand {
            display: flex; align-items: center; gap: 10px; height: 64px; padding: 0 18px;
            border-bottom: 1px solid var(--line); color: var(--text); text-decoration: none;
            font-size: 15px; font-weight: 650; letter-spacing: -.015em;
        }
        .ad-nav-scroll { flex: 1; overflow-y: auto; padding: 12px; }
        .ad-nav-label { margin: 0; padding: 14px 10px 6px; font-size: 12px; font-weight: 600; color: var(--text-3); }
        .ad-nav {
            display: flex; align-items: center; gap: 10px; min-height: 38px; padding: 0 10px; border-radius: var(--radius);
            font-size: 14px; font-weight: 500; color: var(--muted); text-decoration: none;
            transition: background-color var(--dur-2) ease, color var(--dur-2) ease;
        }
        .ad-nav svg { color: var(--text-3); transition: color var(--dur-2) ease; }
        .ad-nav.is-active { background: var(--soft); color: var(--text); font-weight: 600; }
        .ad-nav.is-active svg { color: var(--accent); }
        .ad-side-foot { padding: 12px; border-top: 1px solid var(--line); }

        @media (hover: hover) and (pointer: fine) {
            .ad-nav:hover { background: var(--soft); color: var(--text); }
            .ad-nav:hover svg { color: var(--text-2); }
        }

        .ad-top {
            position: sticky; top: 0; z-index: 30; display: flex; align-items: center; gap: 12px;
            height: 64px; padding: 0 16px;
            background: color-mix(in srgb, var(--bg) 86%, transparent);
            -webkit-backdrop-filter: saturate(1.5) blur(12px); backdrop-filter: saturate(1.5) blur(12px);
            border-bottom: 1px solid var(--line);
        }
        .ad-content { max-width: 1360px; margin: 0 auto; padding: 24px 16px 56px; }
        .ad-search { display: none; position: relative; flex: 1; max-width: 400px; }
        .ad-search .ad-input { padding-left: 38px; height: 38px; background: var(--soft); border-color: transparent; box-shadow: none; }
        .ad-search .ad-input:focus { background: var(--card); border-color: var(--accent); }
        .ad-search-icon { position: absolute; left: 12px; top: 10px; color: var(--text-3); pointer-events: none; }

        @media (min-width: 640px) { .ad-search { display: block; } .ad-top { padding: 0 20px; } .ad-content { padding: 24px 20px 56px; } }
        @media (min-width: 1024px) {
            .ad-side { transform: none; }
            .ad-scrim, .ad-mob { display: none !important; }
            .ad-main { padding-left: 256px; }
            .ad-top { padding: 0 32px; }
            .ad-content { padding: 32px 32px 64px; }
        }

        /* ---------- Building blocks ---------- */
        /* min-width:0 lets a card inside a grid shrink, so wide tables scroll inside it instead of widening the page. */
        .ad-card { min-width: 0; background: var(--card); border: 1px solid var(--line); border-radius: var(--radius-lg); box-shadow: var(--shadow-xs); }
        html.dark .ad-card { box-shadow: none; }
        .ad-card-h { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 18px 20px 0; }
        .ad-card-b { padding: 16px 20px 20px; }
        .ad-h1 { margin: 0; font-family: var(--font-sans); font-stretch: 106%; font-weight: 700; font-size: clamp(1.45rem, 2.2vw, 1.9rem); letter-spacing: -.03em; line-height: 1.15; }
        html:lang(km) .ad-h1 { font-stretch: 100%; letter-spacing: 0; line-height: 1.4; }
        .ad-h2 { margin: 0; font-size: 15px; font-weight: 600; letter-spacing: -.01em; }
        .ad-muted { color: var(--muted); }
        .ad-sm { font-size: 13px; }
        .ad-xs { font-size: 12px; }

        .ad-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 36px; padding: 0 14px;
            border: 1px solid var(--border-strong); border-radius: var(--radius); background: var(--card); color: var(--text);
            font: inherit; font-size: 14px; font-weight: 550; text-decoration: none; white-space: nowrap; cursor: pointer;
            box-shadow: var(--shadow-xs);
            transition: background-color var(--dur-2) ease, border-color var(--dur-2) ease, color var(--dur-2) ease, transform var(--dur-1) var(--ease-out);
            -webkit-tap-highlight-color: transparent;
        }
        .ad-btn:active:not([disabled]) { transform: scale(var(--press-scale)); }
        .ad-btn-primary { background: var(--primary); border-color: var(--primary); color: var(--on-primary); }
        .ad-btn-danger { color: var(--danger-text); border-color: var(--danger-border); }
        .ad-btn-sm { height: 32px; padding: 0 10px; font-size: 13px; border-radius: 7px; }
        .ad-icon-btn { width: 36px; padding: 0; box-shadow: none; border-color: transparent; background: transparent; color: var(--text-2); }
        .ad-icon-btn.ad-btn-danger { color: var(--danger-text); }
        .ad-btn[disabled] { opacity: .5; cursor: not-allowed; }

        @media (hover: hover) and (pointer: fine) {
            .ad-btn:hover:not([disabled]) { background: var(--soft); }
            .ad-btn-primary:hover:not([disabled]) { background: var(--primary-hover); border-color: var(--primary-hover); }
            .ad-btn-danger:hover:not([disabled]) { background: var(--danger-soft); }
            .ad-icon-btn:hover { color: var(--text); }
        }

        .ad-input, .ad-select, .ad-textarea {
            width: 100%; height: 40px; padding: 0 12px; border: 1px solid var(--border-strong); border-radius: var(--radius);
            background-color: var(--card); color: var(--text); font: inherit; font-size: 14px; box-shadow: var(--shadow-xs);
            transition: border-color var(--dur-2) ease, box-shadow var(--dur-2) ease;
        }
        .ad-select { padding-right: 36px; }
        .ad-textarea { height: auto; min-height: 96px; padding: 10px 12px; resize: vertical; line-height: 1.55; }
        .ad-input:focus, .ad-select:focus, .ad-textarea:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--ring-soft); }
        .ad-input::placeholder, .ad-textarea::placeholder { color: var(--text-3); opacity: 1; }
        @media (max-width: 640px) { .ad-input, .ad-select, .ad-textarea { font-size: 16px; } }
        .ad-label { display: block; margin-bottom: 6px; font-size: 13.5px; font-weight: 550; }
        .ad-hint { margin: 6px 0 0; font-size: 12.5px; color: var(--text-3); }
        .ad-error { margin: 6px 0 0; font-size: 13px; font-weight: 500; color: var(--danger-text); }

        /* Course picture drop zone. These live in CSS rather than inline styles because
           Alpine rewrites the inline style attribute: x-show strips `display` when it
           reveals an element, and a string :style binding replaces the attribute whole. */
        .ad-drop {
            position: relative; display: grid; place-items: center; aspect-ratio: 16/9; overflow: hidden;
            border: 2px dashed var(--line); border-radius: 12px; background: var(--soft);
            cursor: pointer; text-align: center;
        }
        .ad-drop.is-dragging { border-color: var(--accent); background: var(--accent-soft); }
        .ad-drop-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .ad-drop-empty { display: grid; justify-items: center; gap: 8px; padding: 16px; }
        .ad-drop-meta { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; }

        .ad-badge {
            display: inline-flex; align-items: center; gap: 6px; height: 22px; padding: 0 8px; border-radius: var(--radius-sm);
            font-size: 12px; font-weight: 550; background: var(--soft); border: 1px solid transparent;
            color: var(--muted); white-space: nowrap;
        }
        .ad-badge.ok { background: var(--success-soft); border-color: var(--success-border); color: var(--success-text); }
        .ad-badge.accent { background: var(--accent-soft); border-color: var(--accent-border); color: var(--accent-text); }
        .ad-badge.blue { background: transparent; border-color: var(--border-strong); color: var(--muted); }
        .ad-badge.warn { background: var(--warning-soft); border-color: var(--warning-border); color: var(--warning-text); }
        .ad-badge.bad { background: var(--danger-soft); border-color: var(--danger-border); color: var(--danger-text); }
        .ad-badge.dot::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

        .ad-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .ad-table th { padding: 10px 20px; text-align: left; font-size: 12.5px; font-weight: 600; color: var(--text-3); background: var(--bg-subtle); border-bottom: 1px solid var(--line); white-space: nowrap; }
        .ad-table td { padding: 14px 20px; border-bottom: 1px solid var(--line); vertical-align: middle; }
        /* Data cells stay on one line; the one descriptive column per table
           (course, person, lesson) opts in to wrapping with a sensible width. */
        .ad-table td { white-space: nowrap; }
        .ad-table td.ad-col-main { min-width: 220px; white-space: normal; }
        /* Inner cells a little tighter; the outer edges keep the card's 20px so text lines up with card headers. */
        .ad-table th, .ad-table td { padding-left: 14px; padding-right: 14px; }
        .ad-table th:first-child, .ad-table td:first-child { padding-left: 20px; }
        .ad-table th:last-child, .ad-table td:last-child { padding-right: 20px; }
        .ad-table .ad-num { text-align: right; font-variant-numeric: tabular-nums; }
        .ad-table tr:last-child td { border-bottom: 0; }
        .ad-table tbody tr { transition: background-color var(--dur-2) ease; }
        @media (hover: hover) and (pointer: fine) { .ad-table tbody tr:hover { background: var(--bg-subtle); } }
        /* The main cell of a row opens the record (student, course). The title
           underlines on hover so the row reads as a link without shouting. */
        .ad-row-link { display: flex; align-items: center; gap: 12px; color: inherit; text-decoration: none; border-radius: var(--radius); }
        .ad-row-link:focus-visible { outline: 2px solid var(--ring); outline-offset: 4px; }
        .ad-row-title { display: block; font-weight: 600; text-decoration: underline; text-decoration-color: transparent; text-underline-offset: 3px; transition: text-decoration-color var(--dur-2) ease; }
        @media (hover: hover) and (pointer: fine) { .ad-row-link:hover .ad-row-title { text-decoration-color: currentColor; } }

        /* Dashboard "Needs attention" rows: icon, one sentence, the fix. */
        .ad-attn { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 12px; padding: 10px 12px; border-radius: 10px; }
        .ad-attn + .ad-attn { border-top: 1px solid var(--line); border-radius: 0; }
        .ad-attn-icon { display: grid; place-items: center; flex: none; width: 32px; height: 32px; border-radius: var(--radius); background: var(--soft); color: var(--text-2); }
        .ad-attn-icon.is-warn { background: var(--warning-soft); color: var(--warning-text); }
        .ad-attn-icon.is-ok { background: var(--success-soft); color: var(--success-text); }
        @media (max-width: 639px) { .ad-attn > .ad-btn { margin-left: 44px; } }

        /* A quiet text button for a secondary, reversible action in a table
           (Publish / Hide). 32px tall so it is still an easy target. */
        .ad-link-btn { display: inline-flex; align-items: center; height: 32px; padding: 0 2px; border: 0; border-radius: 6px; background: none; color: var(--text-2); font: inherit; font-size: 13px; font-weight: 600; text-decoration: underline; text-decoration-color: var(--border-strong); text-underline-offset: 3px; cursor: pointer; transition: color var(--dur-2) ease, text-decoration-color var(--dur-2) ease, transform 160ms cubic-bezier(.23, 1, .32, 1); }
        .ad-link-btn:active { transform: scale(var(--press-scale)); }
        .ad-link-btn:focus-visible { outline: 2px solid var(--ring); outline-offset: 2px; }
        @media (hover: hover) and (pointer: fine) { .ad-link-btn:hover { color: var(--text); text-decoration-color: currentColor; } }

        /* A problem worth fixing, with the fix one click away. Icon + words, never colour alone. */
        .ad-warn-line { display: flex; width: max-content; align-items: center; gap: 5px; margin-top: 6px; font-size: 12px; font-weight: 600; color: var(--warning-text); text-decoration: none; white-space: nowrap; border-radius: 4px; }
        .ad-warn-line:focus-visible { outline: 2px solid var(--ring); outline-offset: 2px; }
        @media (hover: hover) and (pointer: fine) { .ad-warn-line:hover { text-decoration: underline; text-underline-offset: 3px; } }

        /* Radio choice with a title and a one-line explanation. */
        .ad-choice { display: flex; align-items: flex-start; gap: 10px; padding: 10px 12px; border: 1px solid var(--line); border-radius: var(--radius); cursor: pointer; transition: border-color var(--dur-2) ease, background-color var(--dur-2) ease; }
        .ad-choice input { margin-top: 3px; accent-color: var(--accent); }
        .ad-choice:has(input:checked) { border-color: var(--accent); background: var(--accent-soft); }
        .ad-choice:has(input:focus-visible) { outline: 2px solid var(--ring); outline-offset: 2px; }

        /* A group of radio choices: no browser box, the legend reads as a label. */
        .ad-fieldset { margin: 0; padding: 0; border: 0; min-width: 0; }
        .ad-fieldset > legend { padding: 0; }

        /* Promotions: a small copy of the bar at the top of the homepage. */
        /* Mirrors the Course Library banner (partials/_course-promo-banner): the offer in a blue block, then the text and a button. */
        .promo-preview { display: grid; grid-template-columns: 76px minmax(0, 1fr); overflow: hidden; border-radius: 4px; background: color-mix(in srgb, #0866FF 9%, var(--card)); border: 1px solid color-mix(in srgb, #0866FF 22%, var(--line)); font-size: 13.5px; }
        .promo-preview-pill { display: grid; place-items: center; padding: 10px 6px; background: #0866FF; color: #FFFFFF; font-size: 19px; font-weight: 800; letter-spacing: -.02em; text-align: center; }
        .promo-preview-text { display: flex; flex-direction: column; gap: 3px; min-width: 0; padding: 12px 14px 4px; overflow-wrap: anywhere; }
        .promo-preview-cta { justify-self: start; grid-column: 2; margin: 6px 14px 12px; padding: 6px 12px; border-radius: 4px; background: #0866FF; color: #FFFFFF; font-weight: 650; }

        /* "Back to the list" link above a record page. */
        .ad-back { display: inline-flex; align-items: center; gap: 6px; width: max-content; font-size: 13px; font-weight: 550; color: var(--muted); text-decoration: none; border-radius: 6px; transition: color var(--dur-2) ease; }
        .ad-back:focus-visible { outline: 2px solid var(--ring); outline-offset: 3px; }
        @media (hover: hover) and (pointer: fine) { .ad-back:hover { color: var(--text); } }

        /* One course a student can open: mark, title and progress, then the action. */
        .ad-access-row { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 12px 14px; padding: 14px 12px; border-bottom: 1px solid var(--line); }
        .ad-access-row:last-child { border-bottom: 0; }
        @media (max-width: 639px) { .ad-access-row > form { margin-left: 54px; } }

        /* position:relative keeps visually-hidden (absolute) labels inside the scroll box. */
        .ad-scroll-x { position: relative; overflow-x: auto; }
        .ad-avatar { display: grid; place-items: center; flex: none; width: 36px; height: 36px; border-radius: 50%; background: var(--accent-soft); color: var(--accent-text); font-size: 13px; font-weight: 650; text-transform: uppercase; }
        .ad-mark { display: grid; place-items: center; flex: none; width: 40px; height: 40px; border-radius: 10px; font-family: var(--font-sans); font-stretch: 110%; font-weight: 700; font-size: 14px; letter-spacing: -.02em; box-shadow: inset 0 1px 0 rgba(255, 255, 255, .2); }

        /* KPI figures carry the page: large, tabular, quietly labelled. */
        .ad-kpi-value { margin-top: 8px; font-family: var(--font-sans); font-stretch: 106%; font-weight: 700; font-size: clamp(1.9rem, 3.4vw, 2.4rem); letter-spacing: -.035em; line-height: 1; font-variant-numeric: tabular-nums; }
        .ad-kpi-icon { display: grid; place-items: center; width: 32px; height: 32px; flex: none; border-radius: var(--radius); background: var(--soft); border: 1px solid var(--line); color: var(--text-2); }
        .ad-chip { display: inline-flex; align-items: center; gap: 3px; padding: 2px 6px; border-radius: var(--radius-xs); font-size: 12px; font-weight: 650; background: none; }
        .ad-chip.up { color: var(--success-text); background: var(--success-soft); }
        .ad-chip.down { color: var(--danger-text); background: var(--danger-soft); }
        /* Confirm dialog (replaces the browser's own alert box) and the online-admins list. */
        .ad-confirm { max-width: 440px; }
        .ad-confirm-icon { display: grid; place-items: center; flex: none; width: 40px; height: 40px; border-radius: 10px; background: var(--accent-soft); color: var(--accent-text); }
        .ad-confirm.is-danger .ad-confirm-icon { background: var(--danger-soft); color: var(--danger-text); }
        .ad-confirm-msg { margin: 6px 0 0; font-size: 14px; line-height: 1.55; color: var(--text-2); white-space: pre-line; overflow-wrap: anywhere; }
        .ad-btn-danger-solid { background: var(--danger-solid); border-color: var(--danger-solid); color: #FFFFFF; }
        @media (hover: hover) and (pointer: fine) { .ad-btn-danger-solid:hover:not([disabled]) { background: var(--danger-solid-hover); border-color: var(--danger-solid-hover); } }
        .ad-pop-enter { transition: opacity .18s cubic-bezier(.23, 1, .32, 1), transform .2s cubic-bezier(.23, 1, .32, 1); }
        .ad-pop-leave { transition: opacity .12s ease-out, transform .12s ease-out; }
        .ad-pop-from { opacity: 0; transform: translateY(6px) scale(.97); }
        .ad-pop-to { opacity: 1; transform: none; }
        @media (prefers-reduced-motion: reduce) { .ad-pop-enter, .ad-pop-leave { transition: opacity .12s linear; } .ad-pop-from { transform: none; } }
        button.ad-live { font: inherit; font-size: 13px; font-weight: 550; cursor: pointer; transition: background-color var(--dur-1) ease, border-color var(--dur-1) ease; -webkit-tap-highlight-color: transparent; }
        button.ad-live:active { transform: scale(var(--press-scale)); }
        @media (hover: hover) and (pointer: fine) { button.ad-live:hover { background: var(--soft); border-color: var(--border-strong); } }
        .ad-live-short { display: none; }
        @media (max-width: 480px) { .ad-live-long { display: none; } .ad-live-short { display: inline; } button.ad-live { padding: 0 10px; } }
        button.ad-live:focus-visible { outline: 2px solid var(--ring); outline-offset: 2px; }

        .ad-spark { width: 96px; height: 32px; color: var(--accent); }
        @keyframes ad-flash { from { background: var(--accent-soft); } to { background: var(--card); } }
        .ad-flash { animation: ad-flash 1.4s ease-out; }

        .ad-live { display: inline-flex; align-items: center; gap: 8px; height: 32px; padding: 0 12px; border: 1px solid var(--line); border-radius: 99px; background: var(--card); font-size: 13px; font-weight: 550; white-space: nowrap; color: var(--text-2); }
        .ad-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--text-3); }
        .ad-dot.is-live { background: var(--success); animation: ad-pulse 1.8s infinite; }
        .ad-dot.is-off { background: var(--danger); }
        @keyframes ad-pulse {
            0% { box-shadow: 0 0 0 0 color-mix(in srgb, var(--success) 50%, transparent); }
            70% { box-shadow: 0 0 0 8px transparent; }
            100% { box-shadow: 0 0 0 0 transparent; }
        }

        /* Floating layers grow from their trigger (top-right). */
        .ad-menu {
            position: absolute; right: 0; top: calc(100% + 8px); z-index: 60; width: min(360px, calc(100vw - 24px));
            overflow: hidden; background: var(--card); border: 1px solid var(--line); border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md); transform-origin: top right;
        }
        .ad-menu-item { display: flex; align-items: center; gap: 10px; width: 100%; padding: 10px 16px; border: 0; background: none; color: var(--text); font: inherit; font-size: 14px; text-align: left; text-decoration: none; cursor: pointer; transition: background-color var(--dur-1) ease; }
        .ad-menu-item svg { color: var(--text-3); }
        @media (hover: hover) and (pointer: fine) { .ad-menu-item:hover { background: var(--soft); } }
        html.dark .ad-bell-dot { color: #0A0A0A; }  /* white on the light dark-mode blue is only 3.5:1 */
        .ad-bell-dot { position: absolute; top: 0; right: 0; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 99px; background: var(--accent); color: #FFFFFF; font-size: 11px; font-weight: 700; line-height: 18px; text-align: center; box-shadow: 0 0 0 2px var(--bg); }

        .ad-modal-back { position: fixed; inset: 0; z-index: 80; display: grid; place-items: center; padding: 16px; background: var(--overlay); }
        .ad-modal { width: 100%; max-width: 460px; max-height: calc(100vh - 32px); overflow: auto; background: var(--card); border: 1px solid var(--line); border-radius: 14px; box-shadow: var(--shadow-lg); }

        .ad-toast { position: fixed; right: 20px; bottom: 20px; left: 20px; z-index: 90; display: flex; align-items: flex-start; gap: 10px; max-width: 400px; margin-left: auto; padding: 12px 12px 12px 14px; border: 1px solid var(--line); border-radius: var(--radius-lg); background: var(--card); box-shadow: var(--shadow-lg); font-size: 14px; }
        .ad-toast.ok > span:first-child { color: var(--success-text); }
        .ad-toast.err > span:first-child { color: var(--danger-text); }

        .ad-bar { height: 6px; border-radius: 99px; background: var(--surface-3); overflow: hidden; }
        .ad-bar > span { display: block; height: 100%; border-radius: 99px; background: var(--accent); transition: width var(--dur-4) var(--ease-out); }

        /* ---------- Charts: one accent series, one ink series ---------- */
        .ad-chart { position: relative; width: 100%; }
        .ad-chart svg { display: block; max-width: 100%; }
        .ch-grid { stroke: var(--line); stroke-width: 1; }
        .ch-axis { fill: var(--text-3); font-size: 11px; }
        .ch-line { fill: none; stroke: var(--accent); stroke-width: 2.25; stroke-linecap: round; stroke-linejoin: round; }
        .ch-dot { fill: var(--card); stroke: var(--accent); stroke-width: 2.25; }
        .ch-line2 { fill: none; stroke: var(--blue); stroke-width: 2.25; stroke-linecap: round; stroke-linejoin: round; opacity: .8; }
        .ch-dot2 { fill: var(--card); stroke: var(--blue); stroke-width: 2.25; }
        .ch-guide { stroke: var(--text-3); stroke-dasharray: 3 4; opacity: .6; }
        .ch-stop-a { stop-color: var(--accent); stop-opacity: .18; }
        .ch-stop-b { stop-color: var(--accent); stop-opacity: 0; }
        .ch-donut-bg { fill: none; stroke: var(--soft); stroke-width: 18; }
        .ch-center { fill: var(--text); font-family: var(--font-sans); font-stretch: 106%; font-weight: 700; font-size: 26px; letter-spacing: -.03em; }
        .ad-tip { position: absolute; z-index: 5; padding: 6px 10px; border-radius: var(--radius); background: var(--primary); color: var(--on-primary); font-size: 12px; font-weight: 600; white-space: nowrap; pointer-events: none; transform: translate(-50%, -130%); box-shadow: var(--shadow-md); }

        /* ---------- Layout helpers (so the admin never depends on a Tailwind rebuild) ---------- */
        .ad-grid { display: grid; gap: 20px; }
        .ad-grid-16 { gap: 16px; }
        .ad-grid > * { min-width: 0; }
        .ad-only-sm { display: none; }
        .ad-i16 { width: 16px; height: 16px; }
        .ad-i18 { width: 18px; height: 18px; }
        .ad-i24 { width: 24px; height: 24px; }
        @media (min-width: 640px) {
            .ad-cols-sm-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .ad-cols-sm-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .ad-only-sm { display: block; }
        }
        @media (min-width: 1280px) {
            .ad-cols-xl-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .ad-cols-xl-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .ad-span-xl-2 { grid-column: span 2 / span 2; }
        }

        /* ---------- Video drop zone ---------- */
        /* Segmented filter (Students roles, Payment statuses): one control, one pattern. */
        .ad-seg { display: inline-flex; flex-wrap: wrap; gap: 2px; padding: 3px; border: 1px solid var(--line); border-radius: 10px; background: var(--soft); }
        .ad-seg a { display: inline-flex; align-items: center; gap: 6px; height: 30px; padding: 0 10px; border-radius: 7px; color: var(--text-2); font-size: 13px; font-weight: 550; text-decoration: none; transition: background-color var(--dur-2) ease, color var(--dur-2) ease; }
        .ad-seg a span { color: var(--text-3); font-variant-numeric: tabular-nums; }
        .ad-seg a[aria-current="page"] { background: var(--card); color: var(--text); box-shadow: var(--shadow-xs), 0 0 0 1px var(--line); }
        @media (hover: hover) and (pointer: fine) { .ad-seg a:not([aria-current="page"]):hover { color: var(--text); } }

        /* Styled file picker: the native input stays (keyboard, drag-and-drop,
           screen readers) but is visually replaced by a button + file name. */
        .ad-file { position: relative; display: flex; align-items: center; gap: 10px; min-height: 40px; padding: 4px 12px 4px 4px; border: 1px dashed var(--border-strong); border-radius: var(--radius); background: var(--bg-subtle); cursor: pointer; transition: border-color var(--dur-2) ease, background-color var(--dur-2) ease; }
        .ad-file input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        .ad-file .ad-btn { pointer-events: none; flex: none; }
        .ad-file-name { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 13px; color: var(--text-3); }
        .ad-file.has-file { border-style: solid; border-color: var(--accent); background: var(--accent-soft); }
        .ad-file.has-file .ad-file-name { color: var(--text); }
        .ad-file:focus-within { outline: 2px solid var(--ring); outline-offset: 2px; }
        @media (hover: hover) and (pointer: fine) { .ad-file:hover { border-color: var(--text-3); } }

        .ad-callout { display: flex; gap: 10px; align-items: flex-start; padding: 12px 16px; border: 1px solid var(--line); border-radius: var(--radius-lg); background: var(--soft); font-size: 13.5px; line-height: 1.55; }
        .ad-callout.warn { background: var(--warning-soft); border-color: var(--warning-border); }
        .ad-callout.warn > svg { color: var(--warning-text); flex: none; margin-top: 1px; }

        .ad-drop {
            display: grid; justify-items: center; gap: 6px; padding: 28px 16px;
            border: 1.5px dashed var(--border-strong); border-radius: var(--radius-lg); text-align: center; cursor: pointer;
            color: var(--muted); background: var(--bg-subtle); transition: border-color var(--dur-2) ease, background-color var(--dur-2) ease;
        }
        .ad-drop.is-drag { border-color: var(--accent); background: var(--accent-soft); }
        @media (hover: hover) and (pointer: fine) { .ad-drop:hover { border-color: var(--accent); background: var(--accent-soft); } }

        /* Reduced motion: drop the moving parts (drawer slide, pulse ring,
           press scale); colour and opacity feedback stay. */
        @media (prefers-reduced-motion: reduce) {
            .ad-side { transition: none; }
            .ad-dot.is-live { animation: none; }
            .ad-btn:active:not([disabled]) { transform: none; }
        }
    </style>
</head>

<body>
    <div x-data="adminShell()"
        x-on:admin-live.window="onLive($event.detail)"
        x-on:admin-live-status.window="live.ok = $event.detail.ok"
        x-on:keydown.escape.window="menu = null; sidebar = false">

        {{-- Screen readers still hear the mode change --}}
        <div class="sr-only" role="status" aria-live="polite" x-text="announce"></div>

        <div class="ad-scrim" x-show="sidebar" x-cloak @click="sidebar = false"></div>

        {{-- ================= Sidebar ================= --}}
        <aside class="ad-side" :class="{ 'is-open': sidebar }" aria-label="Admin navigation">
            <div class="ad-brand">
                <x-brand :href="route('admin.dashboard')" :size="30" label="Piseth.Design admin dashboard" />
                <span class="ad-badge accent">Admin</span>
            </div>

            <nav class="ad-nav-scroll">
                @foreach ($nav as $group => $items)
                <p class="ad-nav-label">{{ $group }}</p>
                @foreach ($items as $item)
                <a href="{{ route($item['route']) }}" class="ad-nav {{ $active === $item['key'] ? 'is-active' : '' }}" @if ($active===$item['key']) aria-current="page" @endif>
                    <x-admin.icon :name="$item['icon']" class="ad-i18" />
                    <span>{{ $item['label'] }}</span>
                </a>
                @endforeach
                @endforeach
            </nav>

            <div class="ad-side-foot">
                {{-- Same tab, like every other link in here: the admin is part of
                     the site, not a separate app. The website's top bar carries
                     an "Admin" link straight back. --}}
                <a href="{{ url('/') }}" class="ad-nav">
                    <x-admin.icon name="arrow-left" class="ad-i18" />
                    <span>Back to website</span>
                </a>
            </div>
        </aside>

        <div class="ad-main">
            {{-- ================= Top bar ================= --}}
            <header class="ad-top">
                <button type="button" class="ad-btn ad-icon-btn ad-mob" @click="sidebar = true" aria-label="Open menu">
                    <x-admin.icon name="menu" />
                </button>

                <form action="{{ route('admin.users') }}" method="GET" class="ad-search" role="search">
                    <x-admin.icon name="search" class="ad-search-icon ad-i18" />
                    <input type="search" name="q" class="ad-input" placeholder="Search students by name or email"
                        value="{{ request()->routeIs('admin.users') ? request('q') : '' }}" aria-label="Search students">
                </form>

                <div style="margin-left:auto; display:flex; align-items:center; gap:10px">
                    {{-- Which admins are signed in right now --}}
                    <div style="position:relative" @click.outside="menu === 'online' && (menu = null)">
                        <button type="button" class="ad-live" @click="toggle('online')" :aria-expanded="menu === 'online'" aria-haspopup="true" :aria-label="liveText + '. Show which admins are online'"
                            :title="live.ok ? 'Connected. Click to see which admins are online.' : 'Not connected'">
                            <span class="ad-dot" :class="live.ok === null ? '' : (live.ok ? 'is-live' : 'is-off')"></span>
                            <span class="ad-live-long" x-text="liveText">Connecting</span>
                            <span class="ad-live-short" x-text="live.admins.length && live.ok ? live.admins.length : liveText"></span>
                        </button>
                        <div class="ad-menu" x-show="menu === 'online'" x-cloak style="min-width:280px">
                            <div style="padding:14px 16px; border-bottom:1px solid var(--line)">
                                <p class="ad-h2">Admins online</p>
                                <p class="ad-muted ad-xs" style="margin:2px 0 0">Signed in and active in the last 5 minutes.</p>
                            </div>
                            <div style="max-height:320px; overflow:auto">
                                <template x-for="a in live.admins" :key="a.id">
                                    <div class="ad-menu-item" style="cursor:default">
                                        <span class="ad-avatar" x-text="a.name.charAt(0).toUpperCase()"></span>
                                        <span style="min-width:0; flex:1">
                                            <span style="display:block; font-weight:600; overflow:hidden; text-overflow:ellipsis" x-text="a.name + (a.id === meId ? ' (you)' : '')"></span>
                                            <span class="ad-muted ad-xs" style="display:block; overflow:hidden; text-overflow:ellipsis" x-text="a.email"></span>
                                        </span>
                                        <span class="ad-xs" style="white-space:nowrap; color:var(--success-text)" x-show="a.when === 'Active now'">
                                            <span class="ad-dot is-live" style="display:inline-block; margin-right:4px"></span>Now
                                        </span>
                                        <span class="ad-muted ad-xs" style="white-space:nowrap" x-show="a.when !== 'Active now'" x-text="a.when"></span>
                                    </div>
                                </template>
                                <p class="ad-muted ad-sm" style="padding:24px 16px; text-align:center" x-show="!live.admins.length">
                                    <span x-text="live.ok ? 'No admins are online right now.' : 'Not connected, so this list is out of date.'"></span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="ad-btn ad-icon-btn pd-theme-toggle" @click="toggleTheme()" aria-label="Dark mode" :aria-pressed="dark.toString()" aria-pressed="false">
                        <x-icon name="moon" class="i-moon" />
                        <x-icon name="sun" class="i-sun" />
                    </button>

                    {{-- Notifications --}}
                    <div style="position:relative" @click.outside="menu === 'bell' && (menu = null)">
                        <button type="button" class="ad-btn ad-icon-btn" style="position:relative" @click="toggle('bell')" aria-label="Notifications" :aria-expanded="menu === 'bell'">
                            <x-admin.icon name="bell" class="ad-i18" />
                            <span class="ad-bell-dot" x-show="unread > 0" x-text="unread" x-cloak></span>
                        </button>
                        <div class="ad-menu" x-show="menu === 'bell'" x-cloak>
                            <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 16px; border-bottom:1px solid var(--line)">
                                <p class="ad-h2">New students</p>
                                <a href="{{ route('admin.users') }}" class="ad-sm" style="color:var(--accent); font-weight:600; text-decoration:none">View all</a>
                            </div>
                            <div style="max-height:340px; overflow:auto">
                                <template x-for="r in live.recent" :key="r.id">
                                    <a :href="'{{ route('admin.users') }}?q=' + encodeURIComponent(r.email)" class="ad-menu-item" style="align-items:flex-start">
                                        <span class="ad-avatar" x-text="r.name.charAt(0).toUpperCase()"></span>
                                        <span style="min-width:0; flex:1">
                                            <span style="display:block; font-weight:600" x-text="r.name"></span>
                                            <span class="ad-muted ad-xs" style="display:block; overflow:hidden; text-overflow:ellipsis" x-text="r.email"></span>
                                        </span>
                                        <span class="ad-muted ad-xs" style="white-space:nowrap" x-text="r.when"></span>
                                        <span x-show="r.ts > prevSeen" class="ad-dot" style="background:var(--accent); margin-top:6px" title="New"></span>
                                    </a>
                                </template>
                                <p class="ad-muted ad-sm" style="padding:24px 16px; text-align:center" x-show="!live.recent.length">No students have joined yet.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Account --}}
                    <div style="position:relative" @click.outside="menu === 'user' && (menu = null)">
                        <button type="button" class="ad-avatar" style="border:1px solid var(--line); cursor:pointer; color:var(--ink)" @click="toggle('user')" aria-label="Account menu" :aria-expanded="menu === 'user'">
                            {{ Str::upper(Str::substr($me->name, 0, 1)) }}
                        </button>
                        <div class="ad-menu" x-show="menu === 'user'" x-cloak style="width:240px">
                            <div style="padding:14px 16px; border-bottom:1px solid var(--line)">
                                <p class="ad-h2">{{ $me->name }}</p>
                                <p class="ad-muted ad-xs" style="margin:2px 0 0; overflow:hidden; text-overflow:ellipsis">{{ $me->email }}</p>
                            </div>
                            <a href="{{ url('/') }}" class="ad-menu-item"><x-admin.icon name="external" class="ad-i18" /> Back to website</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="ad-menu-item"><x-admin.icon name="logout" class="ad-i18" /> Log out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="ad-content">
                {{ $slot }}
            </main>
        </div>

        {{-- ================= Toast (after a save, or when something failed) ================= --}}
        @if (session('status') || session('error'))
        <div class="ad-toast {{ session('error') ? 'err' : 'ok' }}" role="status"
            x-data="{ show: true }" x-init="setTimeout(() => show = false, 7000)" x-show="show" x-transition.opacity>
            <span style="color:{{ session('error') ? 'var(--danger)' : 'var(--green)' }}">
                <x-admin.icon :name="session('error') ? 'alert' : 'check'" class="ad-i18" />
            </span>
            <span style="flex:1">{{ session('error') ?? session('status') }}</span>
            <button type="button" class="ad-muted" style="border:0; background:none; cursor:pointer" @click="show = false" aria-label="Dismiss">
                <x-admin.icon name="x" class="ad-i16" />
            </button>
        </div>
        @endif
    </div>

    <script>
        /* One shared poller: the dashboard, the notification bell and the "Live" pill all listen to it. */
        window.AdminLive = (function() {
            var url = @json(route('admin.live'));
            var days = 14;
            var timer = null;
            var busy = false;
            var again = false;

            function emit(name, detail) {
                window.dispatchEvent(new CustomEvent(name, { detail: detail }));
            }

            async function refresh() {
                if (document.hidden) return;
                if (busy) { again = true; return; }
                busy = true;
                try {
                    var res = await fetch(url + '?days=' + days, {
                        credentials: 'same-origin',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (res.status === 401 || res.status === 419) { window.location.reload(); return; }
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    var data = await res.json();
                    emit('admin-live-status', { ok: true });
                    emit('admin-live', data);
                } catch (e) {
                    emit('admin-live-status', { ok: false });
                } finally {
                    busy = false;
                    if (again) { again = false; refresh(); }
                }
            }

            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) refresh();
            });

            return {
                refresh: refresh,
                setDays: function(n) { days = n; refresh(); },
                start: function(ms) {
                    clearInterval(timer);
                    refresh();
                    timer = setInterval(refresh, ms);
                }
            };
        })();

        function adminShell() {
            var SEEN = 'pd-admin-seen';
            return {
                sidebar: false,
                menu: null,
                dark: document.documentElement.classList.contains('dark'),
                announce: '',
                live: { ok: null, online: null, admins: [], recent: [] },
                meId: {{ (int) auth()->id() }},
                seen: Number((function() { try { return localStorage.getItem(SEEN); } catch (e) { return 0; } })() || 0),
                prevSeen: 0,
                serverNow: 0,

                get liveText() {
                    if (this.live.ok === null) return 'Connecting';
                    if (!this.live.ok) return 'Offline';
                    if (this.live.online === null) return 'Live';
                    var n = this.live.admins.length;
                    return n + (n === 1 ? ' admin online' : ' admins online');
                },
                get unread() {
                    var seen = this.seen;
                    return this.live.recent.filter(function(r) { return r.ts > seen; }).length;
                },

                toggleTheme() {
                    this.dark = window.pdToggleTheme();
                    this.announce = this.dark ? 'Dark mode enabled' : 'Light mode enabled';
                    setTimeout(() => { this.announce = ''; }, 2000);
                },
                onLive(d) {
                    this.live.online = d.online ? d.online.users : null;
                    this.live.admins = d.online && d.online.admins ? d.online.admins : [];
                    this.live.recent = d.recent || [];
                    this.serverNow = Math.floor(Date.parse(d.server_time) / 1000) || 0;
                    if (!this.seen && this.serverNow) this.markSeen();   // first visit: start counting from now
                },
                markSeen() {
                    this.seen = this.serverNow;
                    try { localStorage.setItem(SEEN, String(this.seen)); } catch (e) {}
                },
                toggle(name) {
                    this.menu = this.menu === name ? null : name;
                    if (this.menu === 'bell') {
                        this.prevSeen = this.seen;
                        this.markSeen();
                    }
                }
            };
        }

        AdminLive.start({{ (int) $live }});
    </script>


    {{-- One confirm dialog for every form marked data-confirm (replaces the browser's own box). --}}
    <div x-data="adConfirm()" x-init="init()" @keydown.escape.window="open && cancel()">
        <div class="ad-modal-back" x-show="open" x-cloak x-transition.opacity.duration.150ms @click.self="cancel()">
            <div class="ad-modal ad-confirm" :class="tone === 'danger' ? 'is-danger' : ''" role="alertdialog" aria-modal="true"
                aria-labelledby="ad-confirm-title" aria-describedby="ad-confirm-msg" @keydown.tab="trap($event)"
                x-show="open" x-transition:enter="ad-pop-enter" x-transition:enter-start="ad-pop-from" x-transition:enter-end="ad-pop-to"
                x-transition:leave="ad-pop-leave" x-transition:leave-start="ad-pop-to" x-transition:leave-end="ad-pop-from">
                <div style="display:flex; align-items:flex-start; gap:14px; padding:22px 22px 6px">
                    <span class="ad-confirm-icon" aria-hidden="true">
                        <svg x-show="tone === 'danger'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/><path d="M12 9v4M12 17h.01"/></svg>
                        <svg x-show="tone !== 'danger'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                    </span>
                    <div style="min-width:0">
                        <h2 id="ad-confirm-title" class="ad-h2" style="font-size:17px" x-text="title"></h2>
                        <p id="ad-confirm-msg" class="ad-confirm-msg" x-text="message"></p>
                    </div>
                </div>
                <div style="display:flex; flex-wrap:wrap; justify-content:flex-end; gap:10px; padding:18px 22px 22px">
                    <button type="button" class="ad-btn" x-ref="cancel" @click="cancel()">Cancel</button>
                    <button type="button" class="ad-btn" :class="tone === 'danger' ? 'ad-btn-danger-solid' : 'ad-btn-primary'" x-ref="ok" @click="accept()" x-text="okLabel"></button>
                </div>
            </div>
        </div>
    </div>

    <script>
        /* Any <form data-confirm="..."> asks first. Optional: data-confirm-title,
           data-confirm-ok (button text) and data-confirm-tone ("danger" or "primary").
           The submit is held, the dialog opens, and the confirm button sends the form
           exactly as it would have gone. Escape or Cancel keeps everything as it was. */
        function adConfirm() {
            return {
                open: false, title: '', message: '', okLabel: 'Confirm', tone: 'danger',
                form: null, submitter: null, opener: null,
                init() {
                    var self = this;
                    document.addEventListener('submit', function (e) {
                        var f = e.target;
                        if (!f.hasAttribute || !f.hasAttribute('data-confirm') || f.dataset.confirmed === '1') return;
                        e.preventDefault();
                        self.show(f, e.submitter || null);
                    }, true);
                },
                show(f, submitter) {
                    this.form = f; this.submitter = submitter; this.opener = document.activeElement;
                    this.message = f.getAttribute('data-confirm');
                    this.title = f.getAttribute('data-confirm-title') || 'Are you sure?';
                    this.okLabel = f.getAttribute('data-confirm-ok') || 'Confirm';
                    this.tone = f.getAttribute('data-confirm-tone') || 'danger';
                    this.open = true;
                    // The safe choice has focus first, so Enter cannot delete anything by accident.
                    this.$nextTick(() => this.$refs.cancel.focus());
                },
                accept() {
                    var f = this.form, s = this.submitter;
                    this.open = false;
                    if (!f) return;
                    f.dataset.confirmed = '1';
                    if (f.requestSubmit) { if (s && s.form === f) { f.requestSubmit(s); } else { f.requestSubmit(); } } else { f.submit(); }
                },
                cancel() {
                    this.open = false;
                    var o = this.opener; this.form = null;
                    if (o && o.focus) setTimeout(function () { o.focus(); }, 0);
                },
                trap(e) {
                    var items = [this.$refs.cancel, this.$refs.ok];
                    var i = items.indexOf(document.activeElement);
                    if (e.shiftKey && i <= 0) { e.preventDefault(); items[1].focus(); }
                    else if (!e.shiftKey && i === 1) { e.preventDefault(); items[0].focus(); }
                },
            };
        }
    </script>

    @stack('scripts')
</body>

</html>