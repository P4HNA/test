{{-- One assignment (/assignments/{uuid}): the brief, the work handed in, and the teacher's review. Live piseth.design design. --}}
@php
    $hmKm = app()->getLocale() === 'km';
    $L = fn (string $en, string $km) => $hmKm ? $km : $en;
    $km = $hmKm ? 'hm-km' : '';
    $icon = fn (string $name, string $class = 'i4') => \App\Support\LiveIcon::svg($name, $class);
    $when = fn ($date) => $date ? $date->locale($hmKm ? 'km' : 'en')->calendar() : '';

    $course = $assignment->course;
    $status = $current?->status;
    $statusTone = $current ? $current->statusTone() : 'gray';
    $statusLabel = $current
        ? ($hmKm ? \App\Models\AssignmentSubmission::STATUSES[$status][1].' ('.\App\Models\AssignmentSubmission::STATUSES[$status][0].')' : $current->statusLabel('en'))
        : $L('Not Submitted', 'មិនទាន់បញ្ជូន (Not Submitted)');
    $statusIcon = ['green' => 'check-circle', 'amber' => 'clock', 'blue' => 'eye', 'gray' => 'upload'][$statusTone] ?? 'upload';
    $canHandIn = $status !== 'approved';
    $reviewed = $current && $current->isReviewed();
    $nextVersion = ($current?->version ?? 0) + 1;
    $requirements = $assignment->requirementList();
    $grade = $current?->grade !== null ? rtrim(rtrim((string) $current->grade, '0'), '.') : null;
    $teacherName = $current?->reviewer?->name ?? 'Piseth Yoeurn';
    $asText = [
        'big' => $L('That file is larger than 30 MB. Export a smaller version or share a link instead.', 'ឯកសារធំជាង 30 MB។ សូមនាំចេញទំហំតូចជាងនេះ ឬចែករំលែកជាតំណភ្ជាប់។'),
        'type' => $L('Upload a JPG, PNG, WebP, PDF, ZIP, PSD, AI or FIG file.', 'សូមបញ្ជូនឯកសារ JPG, PNG, WebP, PDF, ZIP, PSD, AI ឬ FIG។'),
        'sending' => $L('Uploading…', 'កំពុងបញ្ជូន…'),
    ];
@endphp

<x-live-layout :title="'Assignment '.$assignment->code().' · '.$assignment->title_en.' · Piseth.Design'" main-class="ad-main">
    <x-slot:styles>
        <style>
            .ad-main { font-family: Inter, 'Noto Sans Khmer', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.5; -webkit-font-smoothing: antialiased; background: #F8FAFC; }
            html.dark .ad-main { background: #0B0F17; }
            .ad-page { max-width: 60rem; margin: 0 auto; padding: 2rem 1rem 6rem; display: flex; flex-direction: column; gap: 2rem; }
            @media (min-width: 640px) { .ad-page { padding-left: 1.5rem; padding-right: 1.5rem; } }
            .ad-back { display: inline-flex; align-items: center; gap: .5rem; align-self: flex-start; font-size: .8125rem; font-weight: 600; color: #475569; text-decoration: none; transition: color .15s ease, gap .2s ease; }
            html.dark .ad-back { color: #94A3B8; }
            .ad-back:hover { color: #1877F2; gap: .75rem; }
            .ad-card { overflow: hidden; border-radius: 1.5rem; background: #fff; border: 1px solid rgba(226, 232, 240, .8); box-shadow: 0 1px 3px rgba(0, 0, 0, .06), 0 10px 30px -18px rgba(15, 23, 42, .25); }
            html.dark .ad-card { background: #151B23; border-color: #222936; }

            .ad-hero { position: relative; height: 15rem; overflow: hidden; background: #020617; }
            @media (min-width: 640px) { .ad-hero { height: 20rem; } }
            .ad-hero > img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; animation: ad-ken 22s ease-in-out infinite alternate; }
            @keyframes ad-ken { from { transform: scale(1.02); } to { transform: scale(1.12) translate(-2%, -1%); } }
            .ad-hero::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, rgba(2, 6, 23, .92), rgba(2, 6, 23, .25) 55%, rgba(2, 6, 23, .15)); }
            .ad-hero .sweep { position: absolute; z-index: 1; top: 0; bottom: 0; left: 0; width: 35%; background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .14), transparent); animation: hm-sheen 6s ease-in-out 1s infinite; pointer-events: none; }
            .ad-chips { position: absolute; z-index: 2; top: 1rem; left: 1rem; right: 1rem; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; }
            @media (min-width: 640px) { .ad-chips { top: 1.25rem; left: 1.5rem; right: 1.5rem; } }
            .ad-chips > div { display: flex; flex-wrap: wrap; gap: .5rem; }
            .ad-chip { padding: .25rem .75rem; border-radius: .5rem; background: rgba(0, 0, 0, .6); border: 1px solid rgba(255, 255, 255, .2); color: #fff; font-size: .75rem; font-weight: 600; backdrop-filter: blur(12px); }
            .ad-status { display: inline-flex; align-items: center; gap: .375rem; padding: .3125rem .875rem; border-radius: 9999px; background: #fff; border: 1px solid; font-size: .75rem; font-weight: 600; box-shadow: 0 4px 14px rgba(0, 0, 0, .15); }
            .ad-status[data-tone="amber"] { color: #B45309; border-color: rgba(245, 158, 11, .4); background: #FFFBEB; }
            .ad-status[data-tone="green"] { color: #047857; border-color: rgba(16, 185, 129, .4); background: #ECFDF5; }
            .ad-status[data-tone="blue"] { color: #1D4ED8; border-color: rgba(59, 130, 246, .4); background: #EFF6FF; }
            .ad-status[data-tone="gray"] { color: #334155; border-color: rgba(148, 163, 184, .4); background: #F8FAFC; }
            .ad-title { position: absolute; z-index: 2; left: 1rem; right: 1rem; bottom: 1rem; }
            @media (min-width: 640px) { .ad-title { left: 1.5rem; right: 1.5rem; bottom: 1.25rem; } }
            .ad-title h1 { margin: 0; font-size: 1.75rem; line-height: 1.15; font-weight: 800; color: #fff; text-shadow: 0 2px 12px rgba(0, 0, 0, .35); text-wrap: balance; }
            @media (min-width: 640px) { .ad-title h1 { font-size: 2.125rem; } }
            .ad-title p { margin: .25rem 0 0; font-size: .875rem; font-weight: 500; color: #7DD3FC; }

            .ad-body { padding: 1.5rem 1.25rem 2rem; }
            @media (min-width: 640px) { .ad-body { padding: 2rem; } }
            .ad-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .375rem .75rem; padding-bottom: 1.25rem; border-bottom: 1px solid #F1F5F9; font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: .75rem; color: #64748B; }
            html.dark .ad-meta { border-color: #222936; color: #94A3B8; }
            html:lang(km) .ad-meta { font-family: inherit; }
            .ad-meta .late { color: #E11D48; font-weight: 600; }
            .ad-cols { display: grid; grid-template-columns: minmax(0, 1fr); gap: 2rem; padding-top: 1.75rem; }
            @media (min-width: 900px) { .ad-cols { grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr); } }
            .ad-h { margin: 0 0 .625rem; font-size: .75rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #0F172A; }
            html.dark .ad-h { color: #F1F5F9; }
            html:lang(km) .ad-h { letter-spacing: 0; }
            .ad-brief { margin: 0; font-size: .9375rem; line-height: 1.7; color: #334155; white-space: pre-line; }
            html.dark .ad-brief { color: #CBD5E1; }
            .ad-req { list-style: none; margin: 0; padding: 0; display: grid; gap: .5rem; }
            .ad-req li { display: flex; align-items: flex-start; gap: .625rem; font-size: .8125rem; line-height: 1.55; color: #334155; }
            html.dark .ad-req li { color: #CBD5E1; }
            .ad-req svg { flex-shrink: 0; margin-top: .125rem; color: #4DA3FF; }
            .ad-btns { display: flex; flex-wrap: wrap; gap: .625rem; margin-top: 1.75rem; }
            .ad-btn { display: inline-flex; align-items: center; gap: .5rem; padding: .6875rem 1.125rem; border: 0; border-radius: .875rem; background: #4DA3FF; color: #fff; font: inherit; font-size: .8125rem; font-weight: 700; text-decoration: none; cursor: pointer; box-shadow: 0 6px 16px -6px rgba(77, 163, 255, .7); transition: background .15s ease, transform .15s ease; }
            .ad-btn:hover { background: #3892F3; transform: translateY(-1px); }
            .ad-btn:disabled { opacity: .6; cursor: progress; transform: none; }
            .ad-btn--ghost { background: #F1F5F9; color: #0F172A; box-shadow: none; }
            html.dark .ad-btn--ghost { background: #1E2530; color: #F1F5F9; }
            .ad-btn--ghost:hover { background: #E2E8F0; }
            html.dark .ad-btn--ghost:hover { background: #252E3B; }
            .ad-btn--orange { background: #EA580C; box-shadow: 0 6px 16px -6px rgba(234, 88, 12, .7); }
            .ad-btn--orange:hover { background: #C2410C; }

            .ad-side { padding: 1.25rem; border-radius: 1.25rem; background: #F8FAFC; border: 1px solid #E2E8F0; display: flex; flex-direction: column; gap: 1rem; align-self: start; }
            html.dark .ad-side { background: #11161D; border-color: #222936; }
            .ad-side-h { display: flex; align-items: center; justify-content: space-between; gap: .5rem; font-size: .8125rem; font-weight: 700; color: #0F172A; }
            html.dark .ad-side-h { color: #F1F5F9; }
            .ad-side-h span { font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 11px; font-weight: 500; color: #94A3B8; }
            .ad-file { padding: .875rem; border-radius: 1rem; background: #fff; border: 1px solid #E2E8F0; }
            html.dark .ad-file { background: #151B23; border-color: #2E3034; }
            .ad-file-row { display: flex; align-items: center; gap: .75rem; min-width: 0; }
            .ad-file-row > i { display: inline-flex; flex-shrink: 0; width: 2.5rem; height: 2.5rem; align-items: center; justify-content: center; border-radius: .75rem; background: #EAF5FF; color: #1877F2; }
            html.dark .ad-file-row > i { background: rgba(77, 163, 255, .15); color: #4DA3FF; }
            .ad-file-row img { width: 2.5rem; height: 2.5rem; flex-shrink: 0; border-radius: .75rem; object-fit: cover; }
            .ad-file-row strong { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .8125rem; color: #0F172A; }
            html.dark .ad-file-row strong { color: #F1F5F9; }
            .ad-file-row small { display: block; font-size: 11px; color: #94A3B8; }
            .ad-file-foot { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-top: .75rem; padding-top: .75rem; border-top: 1px solid #F1F5F9; font-size: .75rem; }
            html.dark .ad-file-foot { border-color: #222936; }
            .ad-file-foot a, .ad-file-foot button { display: inline-flex; align-items: center; gap: .375rem; padding: 0; border: 0; background: none; font: inherit; font-weight: 600; color: #1877F2; text-decoration: none; cursor: pointer; }
            html.dark .ad-file-foot a, html.dark .ad-file-foot button { color: #4DA3FF; }
            .ad-file-foot > span { font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 11px; color: #94A3B8; }
            .ad-note { margin: .75rem 0 0; padding: .625rem .75rem; border-radius: .75rem; background: #F8FAFC; font-size: .75rem; line-height: 1.55; color: #475569; }
            html.dark .ad-note { background: #0B0F17; color: #CBD5E1; }
            .ad-none { display: flex; flex-direction: column; align-items: center; gap: .5rem; padding: 1.5rem 1rem; border-radius: 1rem; border: 1.5px dashed #CBD5E1; text-align: center; font-size: .8125rem; color: #64748B; }
            html.dark .ad-none { border-color: #334155; color: #94A3B8; }
            .ad-none svg { color: #94A3B8; }
            .ad-hist { list-style: none; margin: 0; padding: 0; display: grid; gap: .5rem; }
            .ad-hist li { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .5rem .625rem; border-radius: .625rem; background: #fff; border: 1px solid #E2E8F0; font-size: .75rem; color: #475569; }
            html.dark .ad-hist li { background: #151B23; border-color: #2E3034; color: #CBD5E1; }
            .ad-hist b { font-family: 'JetBrains Mono', ui-monospace, monospace; color: #0F172A; }
            html.dark .ad-hist b { color: #fff; }
            .ad-dot { display: inline-flex; align-items: center; gap: .375rem; }
            .ad-dot::before { content: ''; width: .5rem; height: .5rem; border-radius: 9999px; background: var(--d, #94A3B8); }

            /* hand-in panel */
            .ad-hand { margin-top: 1.75rem; padding: 1.25rem; border-radius: 1.25rem; background: linear-gradient(135deg, rgba(77, 163, 255, .08), rgba(168, 85, 247, .06)); border: 1px solid rgba(77, 163, 255, .25); }
            .ad-hand[hidden] { display: none; }
            .ad-hand h3 { margin: 0 0 .25rem; font-size: 1rem; font-weight: 800; color: var(--ink); }
            .ad-hand > p { margin: 0 0 1rem; font-size: .8125rem; color: var(--mut); }
            .ad-drop { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .5rem; padding: 1.75rem 1rem; border-radius: 1rem; border: 2px dashed rgba(77, 163, 255, .45); background: rgba(255, 255, 255, .7); text-align: center; cursor: pointer; transition: all .2s ease; }
            html.dark .ad-drop { background: rgba(11, 15, 23, .6); }
            .ad-drop:hover, .ad-drop.is-over { border-color: #1877F2; background: rgba(234, 245, 255, .9); transform: scale(1.01); }
            html.dark .ad-drop:hover, html.dark .ad-drop.is-over { background: rgba(77, 163, 255, .12); }
            .ad-drop input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
            .ad-drop .ico { display: inline-flex; width: 3rem; height: 3rem; align-items: center; justify-content: center; border-radius: 1rem; background: #fff; color: #1877F2; box-shadow: 0 8px 20px -8px rgba(24, 119, 242, .6); animation: hm-float-y 3s ease-in-out infinite; }
            html.dark .ad-drop .ico { background: #1C232D; color: #4DA3FF; }
            .ad-drop strong { font-size: .875rem; color: var(--ink); }
            .ad-drop small { font-size: .75rem; color: var(--mut); }
            .ad-picked { display: none; align-items: center; gap: .5rem; margin-top: .75rem; padding: .5rem .75rem; border-radius: .75rem; background: #fff; border: 1px solid #E2E8F0; font-size: .8125rem; color: #0F172A; }
            html.dark .ad-picked { background: #151B23; border-color: #2E3034; color: #F1F5F9; }
            .ad-picked.is-on { display: flex; }
            .ad-picked svg { color: #10B981; flex-shrink: 0; }
            .ad-picked span { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .ad-fields { display: grid; gap: .875rem; margin-top: 1rem; }
            .ad-fields label { display: block; margin-bottom: .375rem; font-size: .75rem; font-weight: 600; color: var(--ink); }
            .ad-input { width: 100%; padding: .625rem .875rem; border-radius: .75rem; border: 1px solid #E2E8F0; background: #fff; color: #0F172A; font: inherit; font-size: .875rem; }
            html.dark .ad-input { background: #0B0F17; border-color: #334155; color: #fff; }
            .ad-input:focus { outline: 2px solid rgba(77, 163, 255, .5); outline-offset: 1px; border-color: #4DA3FF; }
            .ad-err { margin: .5rem 0 0; font-size: .75rem; font-weight: 600; color: #E11D48; }
            html.dark .ad-err { color: #FB7185; }
            .ad-hand-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; margin-top: 1.25rem; }
            .ad-hand-foot small { font-size: .75rem; color: var(--mut); }

            /* review */
            .ad-rev { padding: 1.5rem 1.25rem; }
            @media (min-width: 640px) { .ad-rev { padding: 2rem; } }
            .ad-rev-h { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; padding-bottom: 1.25rem; border-bottom: 1px solid #F1F5F9; }
            html.dark .ad-rev-h { border-color: #222936; }
            .ad-rev-h h2 { display: flex; align-items: center; gap: .5rem; margin: 0; font-size: .9375rem; font-weight: 800; letter-spacing: .03em; text-transform: uppercase; color: var(--ink); }
            html:lang(km) .ad-rev-h h2 { letter-spacing: 0; }
            .ad-rev-h h2 svg { color: #4DA3FF; }
            .ad-grade { padding: .25rem .625rem; border-radius: .5rem; background: #FFFBEB; border: 1px solid rgba(245, 158, 11, .35); color: #D97706; font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: .8125rem; font-weight: 700; }
            html.dark .ad-grade { background: rgba(245, 158, 11, .12); color: #FBBF24; }
            .ad-who { display: flex; gap: 1rem; padding-top: 1.25rem; }
            .ad-who .pd-avatar { border-radius: 1rem !important; box-shadow: 0 0 0 3px rgba(77, 163, 255, .2); }
            .ad-who strong { display: block; font-size: .9375rem; color: var(--ink); }
            .ad-who small { display: block; font-size: .8125rem; color: var(--mut); }
            .ad-said { margin-top: .875rem; padding: 1rem 1.125rem; border-radius: 1rem; background: #F8FAFC; border: 1px solid #E2E8F0; font-size: .9375rem; line-height: 1.65; color: #1E293B; }
            html.dark .ad-said { background: #11161D; border-color: #222936; color: #E2E8F0; }
            .ad-said q { display: block; margin-top: .5rem; font-size: .8125rem; color: #64748B; quotes: '“' '”'; }
            html.dark .ad-said q { color: #94A3B8; }
            .ad-rev-foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; margin-top: 1.5rem; font-size: .8125rem; color: var(--mut); }
            .ad-wait { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1rem; padding: 1.25rem 1.5rem; }
            .ad-wait p { margin: 0; flex: 1; min-width: 14rem; font-size: .875rem; color: var(--mut); }
            .ad-win { display: flex; align-items: center; gap: 1rem; padding: 1.25rem 1.5rem; background: linear-gradient(135deg, rgba(16, 185, 129, .12), rgba(77, 163, 255, .08)); }
            .ad-win i { display: inline-flex; flex-shrink: 0; width: 3rem; height: 3rem; align-items: center; justify-content: center; border-radius: 1rem; background: #10B981; color: #fff; box-shadow: 0 10px 24px -10px rgba(16, 185, 129, .9); animation: hm-pop-in .6s cubic-bezier(.34, 1.56, .64, 1) both; }
            .ad-win strong { display: block; font-size: 1rem; color: var(--ink); }
            .ad-win span { font-size: .8125rem; color: var(--mut); }

            .ad-light { position: fixed; z-index: 80; inset: 0; display: flex; align-items: center; justify-content: center; padding: 1.5rem; background: rgba(2, 6, 23, .85); backdrop-filter: blur(8px); }
            .ad-light[hidden] { display: none; }
            .ad-light img { max-width: min(100%, 56rem); max-height: 85vh; border-radius: 1rem; box-shadow: 0 30px 60px rgba(0, 0, 0, .5); animation: hm-zoom-in .25s ease both; }
            .ad-light button { position: absolute; top: 1rem; right: 1rem; display: flex; width: 2.5rem; height: 2.5rem; align-items: center; justify-content: center; border: 0; border-radius: 9999px; background: rgba(255, 255, 255, .15); color: #fff; cursor: pointer; }
            .ad-toast { position: fixed; z-index: 70; left: 50%; bottom: 6rem; transform: translateX(-50%); display: inline-flex; align-items: center; gap: .5rem; max-width: calc(100% - 2rem); padding: .75rem 1rem; border-radius: .875rem; background: #0F172A; color: #fff; font-size: .8125rem; font-weight: 600; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, .3); animation: pf-toast 4s ease forwards; }
            .ad-toast svg { color: #34D399; flex-shrink: 0; }
            @keyframes pf-toast { 0% { opacity: 0; transform: translate(-50%, .5rem); } 8%, 85% { opacity: 1; transform: translate(-50%, 0); } 100% { opacity: 0; visibility: hidden; } }
            html:lang(km) .hm-body :is(.ad-title h1) { line-height: 1.5; }
            @media (prefers-reduced-motion: reduce) { .ad-hero > img, .ad-hero .sweep, .ad-drop .ico { animation: none; } }
        </style>
    </x-slot:styles>

    <div class="ad-page">
        <a href="{{ route('assignments.index') }}" class="ad-back">{!! $icon('chevron-left') !!}<span class="{{ $km }}">{{ $L('All assignments', 'កិច្ចការទាំងអស់') }}</span></a>

        <article class="ad-card" data-fx>
            <div class="ad-hero">
                <img src="{{ $assignment->pictureSrc() }}" alt="" decoding="async">
                <span class="sweep" aria-hidden="true"></span>
                <div class="ad-chips">
                    <div>
                        <span class="ad-chip tk-mono">Assignment {{ $assignment->code() }}</span>
                        <span class="ad-chip">{{ $course->category ?: $course->localized('title') }}</span>
                    </div>
                    <span class="ad-status {{ $km }}" data-tone="{{ $statusTone }}" id="ad-status">{!! $icon($statusIcon, 'i35') !!}{{ $statusLabel }}</span>
                </div>
                <div class="ad-title">
                    <h1>{{ $assignment->title_en }}</h1>
                    @if (filled($assignment->title_km))<p class="hm-km">{{ $assignment->title_km }}</p>@endif
                </div>
            </div>

            <div class="ad-body">
                <div class="ad-meta">
                    @if ($assignment->due_at)
                        <span class="{{ ! $current && $assignment->due_at->isPast() ? 'late' : '' }}">{{ $L('Due', 'កាលកំណត់') }}: {{ $assignment->due_at->locale($hmKm ? 'km' : 'en')->diffForHumans() }} · {{ $assignment->due_at->locale('en')->isoFormat('MMM D, YYYY') }}</span>
                    @else
                        <span>{{ $L('No due date', 'គ្មានកាលកំណត់') }}</span>
                    @endif
                    @if (filled($assignment->format))<span aria-hidden="true">·</span><span>{{ $L('Format', 'ទម្រង់') }}: {{ $assignment->format }}</span>@endif
                    <span aria-hidden="true">·</span><span>{{ $course->localized('title') }}</span>
                </div>

                <div class="ad-cols">
                    <div>
                        @if (filled($assignment->localized('brief')))
                            <h2 class="ad-h">{{ $L('Project description', 'ការពិពណ៌នាគម្រោង') }}</h2>
                            <p class="ad-brief">{{ $assignment->localized('brief') }}</p>
                        @endif
                        @if ($requirements)
                            <h2 class="ad-h" style="margin-top: 1.75rem">{{ $L('Technical requirements', 'លក្ខខណ្ឌបច្ចេកទេស') }}</h2>
                            <ul class="ad-req">
                                @foreach ($requirements as $line)
                                    <li data-fx>{!! $icon('check-circle', 'i35') !!}<span>{{ $line }}</span></li>
                                @endforeach
                            </ul>
                        @endif
                        <div class="ad-btns">
                            @if (filled($assignment->brief_path))
                                <a href="{{ route('assignments.brief', $assignment) }}" class="ad-btn ad-btn--ghost fx-btn">{!! $icon('download') !!}<span class="{{ $km }}">{{ $L('Download Brief', 'ទាញយកឯកសារណែនាំ') }}</span></a>
                            @endif
                            @if ($canHandIn)
                                <button type="button" class="ad-btn fx-btn" data-open-hand>{!! $icon('upload') !!}<span class="{{ $km }}">{{ $current ? $L('Upload version '.$nextVersion, 'បញ្ជូនកំណែទី '.$nextVersion) : $L('Upload Assignment', 'បញ្ជូនកិច្ចការ') }}</span></button>
                            @endif
                        </div>

                        @if ($canHandIn)
                            <form id="hand-in" class="ad-hand" method="POST" action="{{ route('assignments.submit', $assignment) }}" enctype="multipart/form-data" @unless ($errors->any() || ! $current) hidden @endunless data-hand>
                                @csrf
                                <h3 class="{{ $km }}">{{ $current ? $L('Hand in version '.$nextVersion, 'បញ្ជូនកំណែទី '.$nextVersion) : $L('Hand in your work', 'បញ្ជូនស្នាដៃរបស់អ្នក') }}</h3>
                                <p class="{{ $km }}">{{ $L('Drop your final export here. Teacher Piseth is notified and reviews it.', 'ទម្លាក់ឯកសារចុងក្រោយរបស់អ្នកនៅទីនេះ។ លោកគ្រូ ពិសិដ្ឋ នឹងពិនិត្យវា។') }}</p>
                                <label class="ad-drop" data-drop>
                                    <input type="file" name="work" accept=".jpg,.jpeg,.png,.webp,.pdf,.zip,.psd,.ai,.fig" data-work>
                                    <span class="ico">{!! $icon('upload', 'i5') !!}</span>
                                    <strong class="{{ $km }}">{{ $L('Drag your file here or tap to choose', 'អូសឯកសារមកទីនេះ ឬចុចដើម្បីជ្រើសរើស') }}</strong>
                                    <small>JPG · PNG · PDF · ZIP · PSD · AI · FIG — {{ $L('up to 30 MB', 'រហូតដល់ 30 MB') }}</small>
                                </label>
                                <div class="ad-picked" data-picked>{!! $icon('check-circle', 'i35') !!}<span data-picked-name></span><small data-picked-size></small></div>
                                @error('work')<p class="ad-err" role="alert">{{ $message }}</p>@enderror
                                <p class="ad-err" role="alert" data-work-err hidden></p>
                                <div class="ad-fields">
                                    <div>
                                        <label for="ad-link" class="{{ $km }}">{{ $L('Or a link to your work (Behance, Figma, Google Drive)', 'ឬតំណភ្ជាប់ទៅស្នាដៃ (Behance, Figma, Google Drive)') }}</label>
                                        <input id="ad-link" name="link" type="url" inputmode="url" class="ad-input" value="{{ old('link') }}" placeholder="https://">
                                        @error('link')<p class="ad-err">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="ad-note" class="{{ $km }}">{{ $L('Concept rationale (2–3 sentences)', 'គំនិតនៃការរចនា (២–៣ ប្រយោគ)') }}</label>
                                        <textarea id="ad-note" name="note" rows="3" maxlength="1000" class="ad-input" style="resize:vertical">{{ old('note') }}</textarea>
                                    </div>
                                </div>
                                <div class="ad-hand-foot">
                                    <small class="{{ $km }}">{{ $L('Each upload is kept as a new version.', 'ការបញ្ជូននីមួយៗត្រូវបានរក្សាទុកជាកំណែថ្មី។') }}</small>
                                    <button type="submit" class="ad-btn fx-btn" data-send>{!! $icon('upload') !!}<span class="{{ $km }}" data-send-label>{{ $current ? $L('Submit version '.$nextVersion, 'ផ្ញើកំណែទី '.$nextVersion) : $L('Submit Assignment', 'ផ្ញើកិច្ចការ') }}</span></button>
                                </div>
                            </form>
                        @endif
                    </div>

                    <aside class="ad-side" data-fx>
                        <div class="ad-side-h"><strong class="{{ $km }}">{{ $L('Submitted File', 'ឯកសារដែលបានបញ្ជូន') }}</strong>@if ($assignment->due_at)<span>{{ $assignment->due_at->locale('en')->isoFormat('MMM D, YYYY') }}</span>@endif</div>
                        @if ($current)
                            <div class="ad-file">
                                <div class="ad-file-row">
                                    @if ($current->file_path && $current->isImage())
                                        <img src="{{ route('assignments.work', $current) }}" alt="" loading="lazy">
                                    @else
                                        <i>{!! $icon($current->link_url && ! $current->file_path ? 'link' : 'file-text') !!}</i>
                                    @endif
                                    <div style="min-width:0">
                                        <strong>{{ $current->file_name ?: preg_replace('#^https?://(www\.)?#', '', (string) $current->link_url) }}</strong>
                                        <small>{{ $current->file_path ? $current->sizeLabel().' · ' : '' }}{{ $when($current->created_at) }}</small>
                                    </div>
                                </div>
                                <div class="ad-file-foot">
                                    @if ($current->file_path && $current->isImage())
                                        <button type="button" data-light="{{ route('assignments.work', $current) }}">{!! $icon('eye', 'i35') !!}<span class="{{ $km }}">{{ $L('Preview Artwork', 'មើលស្នាដៃ') }}</span></button>
                                    @elseif ($current->file_path)
                                        <a href="{{ route('assignments.work', [$current, 'download' => 1]) }}">{!! $icon('download', 'i35') !!}<span class="{{ $km }}">{{ $L('Download', 'ទាញយក') }}</span></a>
                                    @else
                                        <a href="{{ $current->link_url }}" target="_blank" rel="noopener noreferrer">{!! $icon('external', 'i35') !!}<span class="{{ $km }}">{{ $L('Open link', 'បើកតំណ') }}</span></a>
                                    @endif
                                    <span>v{{ $current->version }}</span>
                                </div>
                                @if (filled($current->note))<p class="ad-note">{{ $current->note }}</p>@endif
                            </div>
                        @else
                            <div class="ad-none">{!! $icon('image', 'i5') !!}<span class="{{ $km }}">{{ $L('Nothing handed in yet.', 'មិនទាន់បានបញ្ជូនអ្វីទេ។') }}</span></div>
                        @endif

                        @if ($versions->count() > 1)
                            <div>
                                <div class="ad-side-h" style="margin-bottom:.5rem"><strong class="{{ $km }}">{{ $L('Version history', 'ប្រវត្តិកំណែ') }}</strong></div>
                                <ol class="ad-hist">
                                    @foreach ($versions as $v)
                                        @php $tone = ['green' => '#10B981', 'amber' => '#F59E0B', 'blue' => '#3B82F6'][$v->statusTone()] ?? '#94A3B8'; @endphp
                                        <li><b>v{{ $v->version }}</b><span class="ad-dot {{ $km }}" style="--d: {{ $tone }}">{{ $v->statusLabel() }}</span><span>{{ $v->created_at->locale('en')->isoFormat('MMM D') }}</span></li>
                                    @endforeach
                                </ol>
                            </div>
                        @endif
                    </aside>
                </div>
            </div>
        </article>

        @if ($status === 'approved')
            <section class="ad-card ad-win" data-fx data-win>
                <i>{!! $icon('trophy', 'i5') !!}</i>
                <div><strong class="{{ $km }}">{{ $L('Approved — great work!', 'បានអនុម័ត — ការងារល្អណាស់!') }}</strong><span class="{{ $km }}">{{ $L('This assignment is complete and counts toward your certificate.', 'កិច្ចការនេះបានបញ្ចប់ ហើយរាប់បញ្ចូលសម្រាប់វិញ្ញាបនបត្ររបស់អ្នក។') }}</span></div>
            </section>
        @elseif ($current && in_array($status, ['submitted', 'review'], true))
            <section class="ad-card ad-wait" data-fx>
                <span class="fx-live {{ $km }}"><i></i>{{ $status === 'review' ? $L('Under review', 'កំពុងពិនិត្យ') : $L('Handed in', 'បានបញ្ជូន') }}</span>
                <p class="{{ $km }}">{{ $L('Your work is with Teacher Piseth. You will see the grade and critique here as soon as it is reviewed.', 'ស្នាដៃរបស់អ្នកស្ថិតនៅជាមួយលោកគ្រូ ពិសិដ្ឋ។ ពិន្ទុ និងមតិកែលម្អនឹងបង្ហាញនៅទីនេះភ្លាមៗពេលពិនិត្យរួច។') }}</p>
            </section>
        @endif

        @if ($reviewed)
            <section class="ad-card ad-rev" data-fx aria-labelledby="ad-rev-title">
                <div class="ad-rev-h">
                    <h2 id="ad-rev-title">{!! $icon('sparkles') !!}<span class="{{ $km }}">{{ $L('Teacher review & critique', 'ការកែលម្អពីលោកគ្រូ') }}</span></h2>
                    @if ($grade !== null)<span class="ad-grade">Grade: {{ $grade }} / 10</span>@endif
                </div>
                <div class="ad-who">
                    @if ($current->reviewer)
                        <x-user-avatar :user="$current->reviewer" :size="52" />
                    @else
                        <img src="{{ asset('images/logo-mark-96.png') }}" alt="" width="52" height="52" style="border-radius:1rem">
                    @endif
                    <div style="min-width:0; flex:1">
                        <strong>{{ $L('Teacher', 'លោកគ្រូ') }}: {{ $teacherName }}</strong>
                        <small class="{{ $km }}">{{ $L('University Lecturer & Lead Instructor', 'សាស្ត្រាចារ្យសាកលវិទ្យាល័យ និងគ្រូបង្រៀនផ្ទាល់') }} · {{ $when($current->reviewed_at) }}</small>
                        <div class="ad-said">
                            @if (filled($current->feedback_en))<span><b>{{ $L('Feedback', 'មតិកែលម្អ') }}:</b> {{ $current->feedback_en }}</span>@endif
                            @if (filled($current->feedback_km))<q class="hm-km">{{ $current->feedback_km }}</q>@endif
                        </div>
                    </div>
                </div>
                <div class="ad-rev-foot">
                    <span class="{{ $km }}">{{ $L('Version history', 'ប្រវត្តិកំណែ') }}: {{ $versions->reverse()->map(fn ($v) => 'v'.$v->version.' '.$v->created_at->locale('en')->isoFormat('MMM D'))->implode(' · ') }}@if ($status === 'revision') · v{{ $nextVersion }} {{ $L('pending', 'កំពុងរង់ចាំ') }}@endif</span>
                    @if ($status === 'revision')
                        <button type="button" class="ad-btn ad-btn--orange fx-btn" data-open-hand>{!! $icon('upload') !!}<span class="{{ $km }}">{{ $L('Resubmit Assignment v'.$nextVersion, 'ផ្ញើការងារកែសម្រួល v'.$nextVersion) }}</span></button>
                    @endif
                </div>
            </section>
        @endif
    </div>

    <x-slot:after>
        <div class="ad-light" data-lightbox hidden role="dialog" aria-modal="true" aria-label="{{ $L('Artwork preview', 'មើលស្នាដៃ') }}">
            <img alt="" data-lightbox-img>
            <button type="button" data-lightbox-close aria-label="{{ $L('Close', 'បិទ') }}">{!! $icon('x') !!}</button>
        </div>
        @if (session('status') === 'work-submitted')
            <div class="ad-toast" role="status">{!! $icon('check-circle') !!}<span class="{{ $km }}">{{ $L('Handed in. Teacher Piseth will review it soon.', 'បានបញ្ជូនរួចរាល់។ លោកគ្រូ ពិសិដ្ឋ នឹងពិនិត្យឆាប់ៗ។') }}</span></div>
        @endif
    </x-slot:after>

    <x-slot:scripts>
        <script>
            (function () {
                'use strict';
                var text = @json($asText);
                var hand = document.querySelector('[data-hand]');

                document.querySelectorAll('[data-open-hand]').forEach(function (b) {
                    b.addEventListener('click', function () {
                        if (!hand) return;
                        hand.hidden = false;
                        hand.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
                        var input = hand.querySelector('[data-work]');
                        setTimeout(function () { input && input.focus({ preventScroll: true }); }, 350);
                    });
                });

                if (hand) {
                    var drop = hand.querySelector('[data-drop]');
                    var input = hand.querySelector('[data-work]');
                    var picked = hand.querySelector('[data-picked]');
                    var err = hand.querySelector('[data-work-err]');
                    var ok = /\.(jpe?g|png|webp|pdf|zip|psd|ai|fig)$/i;

                    function size(b) { return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; }
                    function show() {
                        var f = input.files && input.files[0];
                        err.hidden = true;
                        if (!f) { picked.classList.remove('is-on'); return; }
                        if (!ok.test(f.name)) { err.textContent = text.type; err.hidden = false; input.value = ''; picked.classList.remove('is-on'); return; }
                        if (f.size > 30 * 1024 * 1024) { err.textContent = text.big; err.hidden = false; input.value = ''; picked.classList.remove('is-on'); return; }
                        picked.querySelector('[data-picked-name]').textContent = f.name;
                        picked.querySelector('[data-picked-size]').textContent = size(f.size);
                        picked.classList.add('is-on');
                        if (window.fxBurst) fxBurst(picked.querySelector('svg'), '#10B981');
                    }
                    input.addEventListener('change', show);
                    ['dragenter', 'dragover'].forEach(function (t) { drop.addEventListener(t, function (e) { e.preventDefault(); drop.classList.add('is-over'); }); });
                    ['dragleave', 'drop'].forEach(function (t) { drop.addEventListener(t, function () { drop.classList.remove('is-over'); }); });
                    drop.addEventListener('drop', function (e) {
                        e.preventDefault();
                        if (e.dataTransfer && e.dataTransfer.files.length) { input.files = e.dataTransfer.files; show(); }
                    });
                    hand.addEventListener('submit', function () {
                        var b = hand.querySelector('[data-send]');
                        b.disabled = true;
                        hand.querySelector('[data-send-label]').textContent = text.sending;
                    });
                }

                /* artwork preview */
                var box = document.querySelector('[data-lightbox]');
                document.querySelectorAll('[data-light]').forEach(function (b) {
                    b.addEventListener('click', function () {
                        box.querySelector('[data-lightbox-img]').src = b.getAttribute('data-light');
                        box.hidden = false;
                        box.querySelector('[data-lightbox-close]').focus();
                    });
                });
                function close() { box.hidden = true; }
                box.addEventListener('click', function (e) { if (e.target === box || e.target.closest('[data-lightbox-close]')) close(); });
                document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !box.hidden) close(); });

                /* a little celebration for approved work */
                var win = document.querySelector('[data-win] i');
                if (win && window.fxBurst) setTimeout(function () { fxBurst(win, '#10B981'); fxBurst(document.getElementById('ad-status'), '#FBBF24'); }, 450);
            })();
        </script>
    </x-slot:scripts>
</x-live-layout>
