{{-- Student profile (/my-profile), built on the live piseth.design design (components/live-layout). --}}
@php
    $hmKm = app()->getLocale() === 'km';
    $L = fn (string $en, string $km) => $hmKm ? $km : $en;
    $km = $hmKm ? 'hm-km' : '';
    $icon = fn (string $name, string $class = 'i4') => \App\Support\LiveIcon::svg($name, $class);

    $isTeacher = $user->isAdmin();
    $teacherName = $teacher?->name ?: 'Piseth Yoeurn';
    $avatarUrl = $user->avatarUrl();
    $certificates = $user->certificates->sortByDesc(fn ($c) => $c->issued_at?->getTimestamp() ?? 0)->values();
    $latestCertificate = $certificates->first();

    // Month markers for the activity grid: label a column only when its week opens a new month.
    $monthLabels = [];
    $lastMonth = null;
    foreach ($grid as $index => $week) {
        $month = \Illuminate\Support\Carbon::parse($week[0]['date'])->isoFormat('MMM');
        $monthLabels[$index] = $month === $lastMonth ? null : $month;
        $lastMonth = $month;
    }
    $lessonsInGrid = collect($grid)->flatten(1)->sum('count');

    $badgeIcons = [
        'book-open' => 'book', 'check-circle' => 'check-circle', 'layers' => 'layers', 'pulse' => 'trending-up',
        'clock' => 'clock', 'graduation' => 'grad', 'star' => 'star', 'award' => 'award',
    ];

    $tabs = [
        'courses' => [$L('My Courses', 'វគ្គសិក្សារបស់ខ្ញុំ'), $user->enrollments->count()],
        'activity' => [$L('Activity', 'សកម្មភាពសិក្សា'), $posts->count()],
        'assignments' => [$L('Assignments', 'កិច្ចការ & ការកែលម្អ'), $assignments->count()],
        'quizzes' => [$L('Quiz Results', 'លទ្ធផលតេស្ត Quiz'), $bestQuiz->count()],
        'certificates' => [$L('Certificates', 'វិញ្ញាបនបត្រ'), $certificates->count()],
        'saved' => [$L('Saved', 'បានរក្សាទុក'), $saved->count()],
        'payments' => [$L('Payments', 'ការទូទាត់'), $payments->count()],
    ];

    $streak = $stats->currentStreak();
    $photoError = $errors->first('avatar');
    $pfText = [
        'type' => $L('Please choose a JPG, PNG or WebP photo.', 'សូមជ្រើសរូបថត JPG, PNG ឬ WebP។'),
        'size' => $L('The photo may not be larger than 3 MB.', 'រូបថតមិនអាចធំជាង 3 MB ទេ។'),
        'copied' => $L('Copied', 'បានចម្លង'),
    ];
@endphp

<x-live-layout :title="$L('My Profile', 'គណនីរបស់ខ្ញុំ').' · Piseth.Design'" active="profile" main-class="pf-main">
    <x-slot:styles>
        <script>document.documentElement.classList.add('pf-js');</script>
        <style>
            .pf-main { font-family: Inter, 'Noto Sans Khmer', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.5; -webkit-font-smoothing: antialiased; }
            .pf-page { max-width: 72rem; margin: 0 auto; padding: 2rem 1rem 5rem; display: flex; flex-direction: column; gap: 2rem; }
            @media (min-width: 640px) { .pf-page { padding-left: 1.5rem; padding-right: 1.5rem; } }
            @media (min-width: 1024px) { .pf-page { padding-left: 2rem; padding-right: 2rem; } }
            .pf-mono { font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace; }

            /* ---------- profile card ---------- */
            .pf-card { padding: 1.5rem; border-radius: 1.5rem; background: #fff; border: 1px solid rgba(226, 232, 240, .8); box-shadow: 0 1px 3px rgba(0, 0, 0, .1), 0 1px 2px -1px rgba(0, 0, 0, .1); }
            html.dark .pf-card { background: #151B23; border-color: #222936; }
            @media (min-width: 640px) { .pf-card { padding: 2rem; } }
            .pf-top { display: flex; flex-direction: column; justify-content: space-between; gap: 1.5rem; }
            @media (min-width: 640px) { .pf-top { flex-direction: row; align-items: center; } }
            .pf-id { display: flex; flex-direction: column; align-items: flex-start; gap: 1.25rem; }
            @media (min-width: 640px) { .pf-id { flex-direction: row; align-items: center; gap: 1.5rem; } }

            .pf-photo { position: relative; flex-shrink: 0; }
            .pf-pic { position: relative; display: block; width: 6rem; height: 6rem; padding: 0; border: 0; border-radius: 1.5rem; overflow: hidden; background: #0F172A; cursor: pointer; box-shadow: 0 0 0 4px rgba(77, 163, 255, .3), 0 20px 25px -5px rgba(0, 0, 0, .1), 0 8px 10px -6px rgba(0, 0, 0, .1); transition: all .3s ease; }
            @media (min-width: 640px) { .pf-pic { width: 7rem; height: 7rem; } }
            .pf-photo:hover .pf-pic { box-shadow: 0 0 0 4px rgba(77, 163, 255, .6), 0 20px 25px -5px rgba(0, 0, 0, .1), 0 8px 10px -6px rgba(0, 0, 0, .1); transform: scale(1.02); }
            .pf-pic img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s ease; }
            .pf-photo:hover .pf-pic img { transform: scale(1.05); }
            .pf-initials { display: flex; width: 100%; height: 100%; align-items: center; justify-content: center; background: linear-gradient(135deg, #4DA3FF, #1877F2); color: #fff; font-size: 2.25rem; font-weight: 800; letter-spacing: -.02em; }
            .pf-over { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .25rem; padding: .5rem; background: rgba(0, 0, 0, .6); backdrop-filter: blur(2px); color: #fff; text-align: center; opacity: 0; transition: opacity .2s ease; }
            .pf-over svg { width: 1.5rem; height: 1.5rem; color: #4DA3FF; animation: pf-pulse 2s cubic-bezier(.4, 0, .6, 1) infinite; }
            .pf-over span { font-size: 11px; font-weight: 600; line-height: 1.25; }
            .pf-photo:hover .pf-over, .pf-pic:focus-visible .pf-over { opacity: 1; }
            .pf-dot { position: absolute; right: -.25rem; bottom: -.25rem; display: flex; width: 1.25rem; height: 1.25rem; align-items: center; justify-content: center; border-radius: 9999px; background: #10B981; border: 2px solid #fff; box-shadow: 0 0 0 2px rgba(16, 185, 129, .2), 0 4px 6px -1px rgba(0, 0, 0, .1); }
            html.dark .pf-dot { border-color: #151B23; }
            .pf-dot i { width: .5rem; height: .5rem; border-radius: 9999px; background: #fff; animation: pf-pulse 2s cubic-bezier(.4, 0, .6, 1) infinite; }
            .pf-cam { position: absolute; left: -.25rem; bottom: -.5rem; display: flex; padding: .5rem; border-radius: 9999px; background: #fff; color: #1877F2; border: 1px solid #E2E8F0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1); cursor: pointer; transition: background .15s ease; }
            html.dark .pf-cam { background: #1C232D; color: #4DA3FF; border-color: #2E3034; }
            .pf-cam:hover { background: #F8FAFC; }
            html.dark .pf-cam:hover { background: #252E3B; }
            @keyframes pf-pulse { 50% { opacity: .5; } }

            .pf-info { display: flex; flex-direction: column; gap: .375rem; min-width: 0; }
            .pf-nameline { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
            .pf-nameline h1 { margin: 0; font-size: 1.5rem; line-height: 2rem; font-weight: 900; color: #0F172A; }
            html.dark .pf-nameline h1 { color: #fff; }
            @media (min-width: 640px) { .pf-nameline h1 { font-size: 1.875rem; line-height: 2.25rem; } }
            .pf-active { padding: .125rem .625rem; border-radius: 9999px; background: #EAF5FF; border: 1px solid rgba(77, 163, 255, .2); color: #4DA3FF; font-size: .75rem; line-height: 1rem; font-weight: 600; }
            html.dark .pf-active { background: rgba(77, 163, 255, .2); }
            .pf-role { margin: 0; font-size: .75rem; line-height: 1rem; font-weight: 500; color: #64748B; }
            html.dark .pf-role { color: #94A3B8; }
            @media (min-width: 640px) { .pf-role { font-size: .875rem; line-height: 1.25rem; } }
            .pf-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .25rem 1rem; padding-top: .125rem; font-size: .75rem; line-height: 1rem; color: #64748B; }
            .pf-meta > * { display: inline-flex; align-items: center; gap: .25rem; min-width: 0; overflow-wrap: anywhere; }
            .pf-meta svg { color: #94A3B8; flex-shrink: 0; }
            .pf-meta a { color: inherit; text-decoration: none; }
            .pf-meta a:hover { color: #4DA3FF; }
            .pf-bio { margin: .25rem 0 0; max-width: 40rem; font-size: .8125rem; line-height: 1.6; color: #475569; }
            html.dark .pf-bio { color: #CBD5E1; }
            .pf-tools { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; padding-top: .25rem; font-size: .75rem; line-height: 1rem; }
            .pf-tool { display: inline-flex; align-items: center; gap: .375rem; padding: .375rem .75rem; border-radius: .75rem; border: 1px solid rgba(226, 232, 240, .8); background: #F1F5F9; color: #334155; font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; transition: all .15s ease; }
            .pf-tool svg { color: #1877F2; }
            html.dark .pf-tool { background: #1E2530; border-color: #2C3646; color: #E2E8F0; }
            html.dark .pf-tool svg { color: #4DA3FF; }
            .pf-tool:hover { background: #E2E8F0; }
            html.dark .pf-tool:hover { background: #252E3B; }
            .pf-tool--ghost { background: transparent; border-color: transparent; color: #64748B; }
            .pf-tool--ghost svg { color: #4DA3FF; }
            html.dark .pf-tool--ghost { background: transparent; border-color: transparent; color: #94A3B8; }
            .pf-tool--ghost:hover { background: #F1F5F9; color: #1E293B; }
            html.dark .pf-tool--ghost:hover { background: #1E2530; color: #E2E8F0; }
            .pf-err { margin: .25rem 0 0; font-size: .75rem; color: #E11D48; }
            html.dark .pf-err { color: #FB7185; }

            .pf-acts { display: flex; align-items: center; gap: .5rem; align-self: flex-start; }
            @media (min-width: 640px) { .pf-acts { align-self: auto; } }
            .pf-btn { display: inline-flex; align-items: center; gap: .375rem; padding: .625rem 1rem; border: 0; border-radius: .75rem; background: #4DA3FF; color: #fff; font: inherit; font-size: .75rem; line-height: 1rem; font-weight: 600; white-space: nowrap; text-decoration: none; cursor: pointer; box-shadow: 0 1px 2px rgba(0, 0, 0, .05); transition: background .15s ease; }
            .pf-btn:hover { background: #3892F3; }
            .pf-out { display: inline-flex; align-items: center; gap: .375rem; padding: .625rem .875rem; border-radius: .75rem; border: 1px solid rgba(244, 63, 94, .2); background: #FFF1F2; color: #E11D48; font: inherit; font-size: .75rem; line-height: 1rem; font-weight: 600; white-space: nowrap; cursor: pointer; transition: background .15s ease; }
            .pf-out:hover { background: #FFE4E6; }
            html.dark .pf-out { background: rgba(76, 5, 25, .4); color: #FB7185; }
            html.dark .pf-out:hover { background: rgba(136, 19, 55, .6); }

            .pf-stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #F1F5F9; }
            html.dark .pf-stats { border-color: #222936; }
            @media (min-width: 640px) { .pf-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
            .pf-stat { padding: 1rem; border-radius: 1rem; background: #F8FAFC; border: 1px solid #F1F5F9; min-width: 0; }
            html.dark .pf-stat { background: #11161D; border-color: #222936; }
            .pf-stat .k { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #94A3B8; }
            .pf-stat .v { display: block; font-size: 1.5rem; line-height: 2rem; font-weight: 900; color: #0F172A; font-variant-numeric: tabular-nums; }
            html.dark .pf-stat .v { color: #fff; }
            @media (min-width: 640px) { .pf-stat .v { font-size: 1.875rem; line-height: 2.25rem; } }
            .pf-stat .v small { font-size: .55em; font-weight: 700; opacity: .6; margin-left: .125rem; }
            .pf-stat .v.blue { color: #4DA3FF; }
            .pf-stat .s { display: block; font-size: 11px; color: #64748B; }
            .pf-stat .s.green { color: #10B981; font-weight: 600; }
            .pf-stat .s.amber { color: #F59E0B; font-weight: 600; }

            /* ---------- tabs ---------- */
            .pf-body { display: flex; flex-direction: column; gap: 1.5rem; }
            .pf-tabs { align-self: flex-start; max-width: 100%; }
            .pf-count { display: inline-flex; min-width: 1.25rem; height: 1.125rem; margin-left: .375rem; padding: 0 .3125rem; align-items: center; justify-content: center; border-radius: 9999px; background: rgba(100, 116, 139, .14); font-size: 10px; font-weight: 700; font-variant-numeric: tabular-nums; }
            .hm-tab[aria-selected="true"] .pf-count { background: rgba(24, 119, 242, .14); color: #1877F2; }
            html.dark .hm-tab[aria-selected="true"] .pf-count { background: rgba(77, 163, 255, .2); color: #4DA3FF; }
            .pf-js .pf-panel:not(.is-on) { display: none; }
            .pf-panel { outline: none; scroll-margin-top: 6rem; }
            .pf-panel > * + * { margin-top: 1.5rem; }

            /* ---------- course cards ---------- */
            .pf-grid2 { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; }
            @media (min-width: 768px) { .pf-grid2 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            .pf-cc { display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; border-radius: 1.5rem; background: #fff; border: 1px solid rgba(226, 232, 240, .8); box-shadow: 0 1px 2px rgba(0, 0, 0, .05); transition: all .3s ease; }
            html.dark .pf-cc { background: #151B23; border-color: #222936; }
            .pf-cc:hover { border-color: rgba(77, 163, 255, .5); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, .1), 0 8px 10px -6px rgba(0, 0, 0, .1); transform: translateY(-.25rem); }
            .pf-shot { position: relative; display: block; aspect-ratio: 16 / 9; width: 100%; overflow: hidden; background: #020617; text-decoration: none; }
            .pf-shot img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s ease-out; }
            .pf-cc:hover .pf-shot img, .pf-sv:hover .pf-shot img { transform: scale(1.05); }
            .pf-shot::after { content: ''; position: absolute; inset: 0; pointer-events: none; background: linear-gradient(to top, rgba(0, 0, 0, .85), rgba(0, 0, 0, .35), rgba(0, 0, 0, .3)); }
            .pf-chips { position: absolute; z-index: 1; top: .75rem; left: .75rem; right: .75rem; display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
            .pf-chip { padding: .25rem .625rem; border-radius: .5rem; background: rgba(0, 0, 0, .6); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, .2); color: #fff; font-size: 11px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .pf-chip.green { color: #6EE7B7; font-size: .75rem; font-variant-numeric: tabular-nums; flex-shrink: 0; }
            .pf-cap { position: absolute; z-index: 1; left: .75rem; right: .75rem; bottom: .75rem; }
            .pf-cap h3 { margin: 0; font-size: 1.125rem; line-height: 1.5rem; font-weight: 700; color: #fff; text-shadow: 0 1px 2px rgba(0, 0, 0, .3); }
            @media (min-width: 640px) { .pf-cap h3 { font-size: 1.25rem; line-height: 1.75rem; } }
            .pf-cap p { margin: .125rem 0 0; font-size: .75rem; line-height: 1rem; color: #CBD5E1; }
            .pf-ccb { flex: 1; display: flex; flex-direction: column; justify-content: space-between; gap: 1rem; padding: 1.25rem; }
            @media (min-width: 640px) { .pf-ccb { padding: 1.5rem; } }
            .pf-prog { display: flex; align-items: center; justify-content: space-between; margin-bottom: .375rem; font-size: .75rem; line-height: 1rem; color: #64748B; }
            .pf-prog b { color: #4DA3FF; font-variant-numeric: tabular-nums; }
            .pf-bar { width: 100%; height: .5rem; border-radius: 9999px; background: #F1F5F9; overflow: hidden; }
            html.dark .pf-bar { background: #1E293B; }
            .pf-bar i { display: block; height: 100%; border-radius: 9999px; background: #4DA3FF; transition: width .5s ease; }
            .pf-ccf { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding-top: .75rem; border-top: 1px solid #F1F5F9; font-size: .75rem; line-height: 1rem; }
            html.dark .pf-ccf { border-color: #222936; }
            .pf-by { display: inline-flex; align-items: center; gap: .375rem; min-width: 0; color: #64748B; }
            .pf-by img, .pf-by .pd-avatar { width: 1.25rem !important; height: 1.25rem !important; border-radius: 9999px; object-fit: cover; flex-shrink: 0; font-size: 9px !important; }
            .pf-go { display: inline-flex; align-items: center; gap: .25rem; padding: .375rem .75rem; border-radius: .75rem; background: #4DA3FF; color: #fff; font-weight: 600; white-space: nowrap; text-decoration: none; box-shadow: 0 1px 2px rgba(0, 0, 0, .05); transition: background .15s ease; }
            .pf-go:hover { background: #3892F3; }

            /* ---------- empty states ---------- */
            .pf-empty { display: flex; flex-direction: column; align-items: center; gap: .875rem; padding: 3.5rem 1rem; border-radius: 1.5rem; border: 1px dashed var(--line); text-align: center; font-size: .875rem; color: var(--mut); }
            .pf-empty p { margin: 0; max-width: 28rem; }

            /* ---------- activity grid ---------- */
            .pf-box { padding: 1.25rem; border-radius: 1.5rem; background: #fff; border: 1px solid rgba(226, 232, 240, .8); box-shadow: 0 1px 2px rgba(0, 0, 0, .05); }
            html.dark .pf-box { background: #151B23; border-color: #222936; }
            @media (min-width: 640px) { .pf-box { padding: 1.5rem; } }
            .pf-boxhead { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: .5rem; margin-bottom: 1rem; }
            .pf-boxhead h2 { margin: 0; font-size: 1rem; line-height: 1.5rem; font-weight: 800; color: var(--ink); }
            .pf-boxhead p { margin: 0; font-size: .75rem; color: var(--mut); }
            .pf-scroll { overflow-x: auto; padding-bottom: .25rem; }
            .pf-months, .pf-weeks { display: flex; gap: 3px; min-width: max-content; }
            .pf-months { margin-bottom: .375rem; font-size: 10px; text-transform: uppercase; letter-spacing: .06em; color: var(--mut); }
            .pf-months span { width: 11px; white-space: nowrap; }
            .pf-week { display: grid; grid-template-rows: repeat(7, 1fr); gap: 3px; }
            .pf-day { display: inline-block; width: 11px; height: 11px; border-radius: 3px; background: #EEF2F7; }
            html.dark .pf-day { background: #1E2530; }
            .pf-day[data-level="1"] { background: rgba(77, 163, 255, .3); }
            .pf-day[data-level="2"] { background: rgba(77, 163, 255, .55); }
            .pf-day[data-level="3"] { background: rgba(77, 163, 255, .8); }
            .pf-day[data-level="4"] { background: #1877F2; }
            html.dark .pf-day[data-level="4"] { background: #4DA3FF; }
            .pf-day[data-future="1"] { background: transparent !important; }
            .pf-legend { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; margin-top: 1rem; font-size: .75rem; color: var(--mut); }
            .pf-legend p { margin: 0; }
            .pf-legend div { display: flex; align-items: center; gap: .375rem; }

            /* ---------- certificates ---------- */
            .pf-cert { display: flex; flex-direction: column; overflow: hidden; border-radius: 1.5rem; background: #fff; border: 1px solid rgba(226, 232, 240, .8); box-shadow: 0 1px 3px rgba(0, 0, 0, .1), 0 1px 2px -1px rgba(0, 0, 0, .1); }
            html.dark .pf-cert { background: #151B23; border-color: #222936; }
            @media (min-width: 768px) { .pf-cert { flex-direction: row; align-items: stretch; } }
            .pf-seal { position: relative; display: flex; flex-direction: column; justify-content: space-between; flex-shrink: 0; min-height: 190px; padding: 1.25rem; overflow: hidden; background: linear-gradient(to bottom right, #111317, #1C1E24); border-bottom: 1px solid #E2E8F0; text-decoration: none; }
            html.dark .pf-seal { border-color: #222936; }
            @media (min-width: 768px) { .pf-seal { width: 18rem; border-bottom: 0; border-right: 1px solid #E2E8F0; } html.dark .pf-seal { border-right-color: #222936; } }
            @media (min-width: 1024px) { .pf-seal { width: 20rem; } }
            .pf-seal > img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; opacity: .35; mix-blend-mode: luminosity; transition: transform .5s ease; }
            .pf-seal:hover > img { transform: scale(1.05); }
            .pf-seal::after { content: ''; position: absolute; inset: 0; pointer-events: none; background: linear-gradient(to top, rgba(0, 0, 0, .9), rgba(0, 0, 0, .5), transparent); }
            .pf-seal > div { position: relative; z-index: 1; }
            .pf-seal .row { display: flex; align-items: center; justify-content: space-between; color: #FBBF24; }
            .pf-seal .tag { padding: .125rem .5rem; border-radius: .375rem; background: rgba(69, 26, 3, .7); border: 1px solid rgba(245, 158, 11, .3); color: #FBBF24; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
            .pf-seal .no { display: block; font-size: 10px; color: #CBD5E1; }
            .pf-seal h4 { margin: .25rem 0; font-size: .875rem; line-height: 1.25rem; font-weight: 700; color: #fff; }
            .pf-seal .ok { display: inline-flex; align-items: center; gap: .25rem; font-size: 11px; font-weight: 500; color: #34D399; }
            .pf-certb { flex: 1; display: flex; flex-direction: column; justify-content: space-between; gap: 1rem; padding: 1.5rem; }
            @media (min-width: 640px) { .pf-certb { padding: 2rem; } }
            .pf-certb .v { display: inline-flex; align-items: center; gap: .25rem; font-size: .75rem; font-weight: 600; color: #059669; }
            html.dark .pf-certb .v { color: #34D399; }
            .pf-certb h3 { margin: .25rem 0; font-size: 1.125rem; line-height: 1.75rem; font-weight: 700; color: #0F172A; }
            html.dark .pf-certb h3 { color: #fff; }
            @media (min-width: 640px) { .pf-certb h3 { font-size: 1.25rem; } }
            .pf-certb .d { margin: 0; font-size: .8125rem; line-height: 1.6; color: #64748B; }
            .pf-certb .m { margin: .25rem 0 0; font-size: .75rem; color: #94A3B8; }
            .pf-row { display: flex; flex-wrap: wrap; align-items: center; gap: .625rem; }
            .pf-gray { display: inline-flex; align-items: center; gap: .375rem; padding: .625rem 1rem; border: 0; border-radius: .75rem; background: #F1F5F9; color: #334155; font: inherit; font-size: .75rem; line-height: 1rem; font-weight: 600; white-space: nowrap; cursor: pointer; transition: background .15s ease; }
            .pf-gray:hover { background: #E2E8F0; }
            html.dark .pf-gray { background: #1C232D; color: #CBD5E1; }
            html.dark .pf-gray:hover { background: #252E3B; }

            /* ---------- milestones ---------- */
            .pf-badges { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 13rem), 1fr)); gap: .75rem; }
            .pf-badge { display: flex; align-items: center; gap: .75rem; padding: .875rem; border-radius: 1rem; background: #F8FAFC; border: 1px solid #F1F5F9; }
            html.dark .pf-badge { background: #11161D; border-color: #222936; }
            .pf-badge > i { display: inline-flex; flex-shrink: 0; width: 2.25rem; height: 2.25rem; align-items: center; justify-content: center; border-radius: .75rem; background: #EAF5FF; color: #1877F2; }
            html.dark .pf-badge > i { background: rgba(77, 163, 255, .15); color: #4DA3FF; }
            .pf-badge p { margin: 0; font-size: .8125rem; font-weight: 600; color: var(--ink); }
            .pf-badge small { display: block; font-size: 11px; color: var(--mut); }
            .pf-badge.is-locked { border-style: dashed; opacity: .6; }
            .pf-badge.is-locked > i { background: transparent; border: 1px dashed var(--line); color: var(--mut); }

            /* ---------- saved resources ---------- */
            .pf-sv { display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; border-radius: 1rem; background: #fff; border: 1px solid rgba(226, 232, 240, .8); box-shadow: 0 1px 2px rgba(0, 0, 0, .05); transition: all .2s ease; }
            html.dark .pf-sv { background: #151B23; border-color: #222936; }
            .pf-sv:hover { border-color: rgba(77, 163, 255, .5); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, .1), 0 4px 6px -4px rgba(0, 0, 0, .1); }
            .pf-sv .pf-chips { top: .625rem; left: .625rem; right: .625rem; }
            .pf-sv .pf-chip { padding: .125rem .5rem; border-radius: .25rem; font-size: 10px; font-weight: 500; color: rgba(255, 255, 255, .9); }
            .pf-mark { display: flex; padding: .25rem; border-radius: .375rem; background: #4DA3FF; color: #fff; }
            .pf-mark svg { fill: currentColor; }
            .pf-sv .pf-cap { left: .625rem; right: .625rem; bottom: .625rem; }
            .pf-sv .pf-cap h3 { font-size: 1rem; line-height: 1.5rem; }
            .pf-sv .pf-cap p { font-size: 11px; }
            .pf-svf { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem; font-size: .75rem; color: #64748B; }
            html.dark .pf-svf { color: #94A3B8; }
            .pf-svf a { display: inline-flex; align-items: center; gap: .25rem; font-weight: 600; color: #4DA3FF; text-decoration: none; }
            .pf-svf a:hover { text-decoration: underline; }

            /* ---------- payments ---------- */
            .pf-pay { padding: 1rem 1.25rem; border-radius: 1rem; background: #fff; border: 1px solid rgba(226, 232, 240, .8); box-shadow: 0 1px 2px rgba(0, 0, 0, .05); }
            html.dark .pf-pay { background: #151B23; border-color: #222936; }
            .pf-pay + .pf-pay { margin-top: .75rem; }
            .pf-payt { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: .75rem; }
            .pf-payt h4 { margin: 0; font-size: .875rem; font-weight: 700; color: var(--ink); }
            .pf-payt h4 a { color: inherit; text-decoration: none; }
            .pf-payt h4 a:hover { color: #4DA3FF; }
            .pf-payt .when { margin: .25rem 0 0; font-size: .75rem; color: var(--mut); }
            .pf-amt { text-align: right; flex-shrink: 0; }
            .pf-amt strong { display: block; font-size: 1.125rem; font-weight: 800; color: var(--ink); font-variant-numeric: tabular-nums; }
            .pf-st { display: inline-block; margin-top: .125rem; padding: .0625rem .5rem; border-radius: 9999px; font-size: 11px; font-weight: 600; }
            .pf-st.ok { background: rgba(16, 185, 129, .12); color: #059669; }
            html.dark .pf-st.ok { color: #34D399; }
            .pf-st.warn { background: rgba(245, 158, 11, .14); color: #B45309; }
            html.dark .pf-st.warn { color: #FBBF24; }
            .pf-st.bad { background: rgba(100, 116, 139, .14); color: #64748B; }
            html.dark .pf-st.bad { color: #94A3B8; }
            .pf-note { margin: .75rem 0 0; font-size: .75rem; color: var(--mut); }
            .pf-retry { display: inline-flex; margin-top: .75rem; padding: .375rem .75rem; border-radius: .75rem; border: 1px solid var(--line); color: var(--ink); font-size: .75rem; font-weight: 600; text-decoration: none; }
            .pf-retry:hover { border-color: #4DA3FF; color: #4DA3FF; }

            /* ---------- toast ---------- */
            .pf-toast { position: fixed; z-index: 70; left: 50%; bottom: 6rem; transform: translateX(-50%); display: inline-flex; align-items: center; gap: .5rem; padding: .625rem 1rem; border-radius: .75rem; background: #0F172A; color: #fff; font-size: .8125rem; font-weight: 600; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, .2); animation: pf-toast 3.2s ease forwards; }
            .pf-toast svg { color: #34D399; }
            @keyframes pf-toast { 0% { opacity: 0; transform: translate(-50%, .5rem); } 8%, 85% { opacity: 1; transform: translate(-50%, 0); } 100% { opacity: 0; visibility: hidden; } }

            html:lang(km) .hm-body :is(.pf-nameline h1, .pf-cap h3, .pf-certb h3, .pf-seal h4) { line-height: 1.6; }
            html:lang(km) .hm-body :is(.pf-tab, .pf-tool, .pf-btn, .pf-out, .pf-go, .pf-gray) { line-height: 1.4; }
            @media (prefers-reduced-motion: reduce) {
                .pf-over svg, .pf-dot i { animation: none; }
                .pf-cc, .pf-cc:hover { transform: none; }
            }
        </style>
        @include('partials.live._activity-styles')
        @include('partials.live._task-styles')
    </x-slot:styles>

    <div class="pf-page">
        {{-- ================= Profile card ================= --}}
        <section class="pf-card" aria-label="{{ $L('Profile', 'ប្រវត្តិរូប') }}">
            <div class="pf-top">
                <div class="pf-id">
                    <form class="pf-photo" method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data" data-photo-form>
                        @csrf
                        @method('PATCH')
                        <button type="button" class="pf-pic" data-photo-pick title="{{ $L('Click to change profile picture', 'ចុចដើម្បីប្តូររូបថត Profile') }}">
                            @if ($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" width="112" height="112" decoding="async"
                                    onerror="this.replaceWith(Object.assign(document.createElement('span'), { className: 'pf-initials', textContent: @js($user->initials()) }))">
                            @else
                                <span class="pf-initials">{{ $user->initials() }}</span>
                            @endif
                            <span class="pf-over">{!! $icon('camera', 'i5') !!}<span class="{{ $km }}">{{ $L('Change Photo', 'ប្តូររូបថត') }}</span></span>
                        </button>
                        <span class="pf-dot" title="{{ $L('Active', 'សកម្ម') }}"><i></i></span>
                        <button type="button" class="pf-cam" data-photo-pick title="{{ $L('Upload photo', 'ផ្លាស់ប្តូររូបភាព') }}" aria-label="{{ $L('Upload photo', 'ផ្លាស់ប្តូររូបភាព') }}">{!! $icon('camera', 'i35') !!}</button>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" hidden data-photo-input>
                    </form>

                    <div class="pf-info">
                        <div class="pf-nameline">
                            <h1>{{ $user->name }}</h1>
                            <span class="pf-active">{{ $isTeacher ? 'Verified Instructor' : 'Active Account' }}</span>
                        </div>
                        <p class="pf-role">
                            @if ($user->headline){{ $user->headline }} · @endif{{ $isTeacher ? 'Lead Instructor (លោកគ្រូ)' : 'Design Student (និស្សិត)' }}
                        </p>
                        <div class="pf-meta">
                            <span>{!! $icon('mail', 'i35') !!} {{ $user->email }}</span>
                            @if ($user->created_at)
                                <span>{!! $icon('calendar', 'i35') !!} {{ $hmKm ? 'សមាជិកតាំងពី '.$user->created_at->locale('km')->isoFormat('MMMM YYYY') : 'Member since '.$user->created_at->locale('en')->isoFormat('MMMM YYYY') }}</span>
                            @endif
                            @if ($user->location)
                                <span>{!! $icon('map-pin', 'i35') !!} {{ $user->location }}</span>
                            @endif
                            @if ($user->website)
                                <a href="{{ $user->website }}" target="_blank" rel="noopener noreferrer nofollow">{!! $icon('link', 'i35') !!} {{ preg_replace('#^https?://(www\.)?#i', '', rtrim($user->website, '/')) }}</a>
                            @endif
                        </div>
                        @if ($user->bio)
                            <p class="pf-bio">{{ $user->bio }}</p>
                        @endif
                        <div class="pf-tools">
                            <button type="button" class="pf-tool" data-photo-pick>{!! $icon('upload', 'i35') !!}<span class="{{ $km }}">{{ $L('Change Photo', 'ផ្លាស់ប្តូររូបថត') }}</span></button>
                            <a href="{{ route('profile.edit') }}" class="pf-tool pf-tool--ghost">{!! $icon('pencil', 'i35') !!}<span class="{{ $km }}">{{ $L('Edit Profile', 'កែប្រែព័ត៌មាន') }}</span></a>
                        </div>
                        @if ($photoError)
                            <p class="pf-err" role="alert">{{ $photoError }}</p>
                        @endif
                        <p class="pf-err" role="alert" data-photo-err hidden></p>
                    </div>
                </div>

                <div class="pf-acts">
                    <a href="{{ $latestCertificate ? route('certificates.show', $latestCertificate) : '#achievements' }}" class="pf-btn" @unless ($latestCertificate) data-tab-link="certificates" @endunless>
                        {!! $icon('award') !!}<span>View Verified Certificate</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="pf-out" title="{{ $L('Log Out of Student Account', 'ចាកចេញពីគណនី') }}">
                            {!! $icon('logout') !!}<span class="{{ $km }}">{{ $L('Log Out', 'ចាកចេញ') }}</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="pf-stats">
                <div class="pf-stat">
                    <span class="k pf-mono">Courses</span>
                    <span class="v" data-fx-count>{{ $user->enrollments->count() }}</span>
                    <span class="s">Enrolled active</span>
                </div>
                <div class="pf-stat">
                    <span class="k pf-mono">Completed Lessons</span>
                    <span class="v" data-fx-count>{{ $lessonsDone }}</span>
                    <span class="s green">{{ $overallProgress }}% Progress</span>
                </div>
                <div class="pf-stat">
                    <span class="k pf-mono">Assignments</span>
                    <span class="v" data-fx-count>{{ $latestWork->count() }}</span>
                    <span class="s amber">{{ $latestWork->filter(fn ($w) => $w->isReviewed())->count() }} Reviewed</span>
                </div>
                <div class="pf-stat">
                    <span class="k pf-mono">Certificates</span>
                    <span class="v blue" data-fx-count>{{ $certificates->count() }}</span>
                    <span class="s">Issued by {{ $teacherName }}</span>
                </div>
            </div>
        </section>

        {{-- ================= Tabs ================= --}}
        <div class="pf-body">
            <div class="hm-tabs pf-tabs" data-jelly-tabs data-pf-tabs>
                <div class="hm-tabs-track" role="tablist" aria-label="{{ __('Profile sections') }}">
                    <span class="hm-jelly-trail" aria-hidden="true"></span>
                    <span class="hm-jelly" aria-hidden="true"><span class="hm-jelly-blob"></span><span class="hm-sparks"></span></span>
                    @foreach ($tabs as $key => [$label, $count])
                        <button type="button" class="hm-tab {{ $km }}" role="tab" id="pf-tab-{{ $key }}" data-value="{{ $key }}"
                            aria-controls="pf-panel-{{ $key }}" aria-selected="{{ $key === 'courses' ? 'true' : 'false' }}">{{ $label }}<b class="pf-count">{{ $count }}</b></button>
                    @endforeach
                </div>
            </div>

            {{-- ---------- My courses ---------- --}}
            <div class="pf-panel is-on" id="pf-panel-courses" role="tabpanel" aria-labelledby="pf-tab-courses" tabindex="0">
                @if ($user->enrollments->isEmpty())
                    <div class="pf-empty">
                        <p class="{{ $km }}">{{ $L('No courses yet. Pick your first course and it will show up here with your progress.', 'មិនទាន់មានវគ្គសិក្សាទេ។ ជ្រើសរើសវគ្គដំបូងរបស់អ្នក ហើយវឌ្ឍនភាពនឹងបង្ហាញនៅទីនេះ។') }}</p>
                        <a href="{{ route('courses') }}" class="pf-btn">{!! $icon('book') !!}<span class="{{ $km }}">{{ $L('Browse courses', 'មើលវគ្គសិក្សា') }}</span></a>
                    </div>
                @else
                    <div class="pf-grid2">
                        @foreach ($user->enrollments as $enrollment)
                            @php
                                $course = $enrollment->course;
                                if (! $course) continue;
                                $percent = $course->progressPercent($user);
                                $next = $enrollment->continueLesson();
                                $continueUrl = ($next && filled($next->getRouteKey())) ? route('courses.lessons.show', [$course, $next]) : route('courses.show', $course);
                                $titleKm = $course->title_km ?: $course->title_en;
                            @endphp
                            <article class="pf-cc">
                                <a href="{{ $continueUrl }}" class="pf-shot" aria-label="{{ $course->localized('title') }}">
                                    <img src="{{ \App\Support\CoursePicture::for($course, 800) }}" alt="" loading="lazy" decoding="async">
                                    <span class="pf-chips">
                                        <span class="pf-chip pf-mono">{{ $course->category ?: 'Design' }}</span>
                                        <span class="pf-chip green pf-mono">{{ $percent }}% Completed</span>
                                    </span>
                                    <span class="pf-cap">
                                        <h3 class="hm-km">{{ $titleKm }}</h3>
                                        <p class="pf-mono">{{ $course->title_en }}</p>
                                    </span>
                                </a>
                                <div class="pf-ccb">
                                    <div>
                                        <div class="pf-prog"><span class="{{ $km }}">{{ $L('Course Progress', 'វឌ្ឍនភាពសិក្សា') }}</span><b class="pf-mono">{{ $percent }}%</b></div>
                                        <div class="pf-bar" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $L('Course Progress', 'វឌ្ឍនភាពសិក្សា') }}"><i style="width: {{ $percent }}%"></i></div>
                                    </div>
                                    <div class="pf-ccf">
                                        <span class="pf-by">
                                            @if ($teacher)
                                                <x-user-avatar :user="$teacher" :size="20" />
                                            @else
                                                <img src="{{ asset('images/logo-mark-96.png') }}" alt="" width="20" height="20">
                                            @endif
                                            <span>{{ $course->instructor_name ?: $teacherName }}</span>
                                        </span>
                                        <a href="{{ $continueUrl }}" class="pf-go"><span class="{{ $km }}">{{ $percent >= 100 ? $L('Review', 'មើលឡើងវិញ') : $L('Continue', 'បន្តការរៀន') }}</span>{!! $icon('chevron-right', 'i35') !!}</a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ---------- Activity ---------- --}}
            <div class="pf-panel" id="pf-panel-activity" role="tabpanel" aria-labelledby="pf-tab-activity" tabindex="0">
                <section class="pf-box">
                    <div class="pf-boxhead">
                        <h2 class="{{ $km }}">{{ $L('Learning streak', 'ការសិក្សាប្រចាំថ្ងៃ') }}</h2>
                        <p>{{ __(':count lessons finished in the last :weeks weeks', ['count' => $lessonsInGrid, 'weeks' => \App\Support\LearningStats::WEEKS]) }}</p>
                    </div>
                    <div class="pf-scroll">
                        <div class="pf-months" aria-hidden="true">
                            @foreach ($grid as $index => $week)
                                <span>{{ $monthLabels[$index] ?? '' }}</span>
                            @endforeach
                        </div>
                        <div class="pf-weeks" role="img" aria-label="{{ __(':count lessons finished in the last :weeks weeks', ['count' => $lessonsInGrid, 'weeks' => \App\Support\LearningStats::WEEKS]) }}">
                            @foreach ($grid as $week)
                                <div class="pf-week">
                                    @foreach ($week as $day)
                                        <span class="pf-day" data-level="{{ $day['level'] }}" @if ($day['future']) data-future="1" @endif
                                            title="{{ $day['future'] ? '' : trans_choice('{0}No lessons on :date|{1}1 lesson on :date|[2,*]:count lessons on :date', $day['count'], ['count' => $day['count'], 'date' => $day['label']]) }}"></span>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="pf-legend">
                        <p>
                            @if ($streak > 0)
                                {{ trans_choice('{1}On a :count day streak.|[2,*]On a :count day streak.', $streak, ['count' => $streak]) }}
                            @else
                                {{ __('No streak running — finish a lesson today to start one.') }}
                            @endif
                            {{ __('Best run: :count days.', ['count' => $stats->longestStreak()]) }}
                        </p>
                        <div aria-hidden="true">
                            <span>{{ __('Less') }}</span>
                            @foreach ([0, 1, 2, 3, 4] as $level)
                                <span class="pf-day" data-level="{{ $level }}"></span>
                            @endforeach
                            <span>{{ __('More') }}</span>
                        </div>
                    </div>
                </section>

                @if ($posts->isEmpty())
                    <p class="pf-empty {{ $km }}">{{ __('Nothing finished yet. Your lessons will show up here.') }}</p>
                @else
                    <div class="ac-list" data-ac-list>
                        @foreach ($posts as $post)
                            @include('partials.live._activity-post', ['post' => $post])
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ---------- Assignments ---------- --}}
            <div class="pf-panel" id="pf-panel-assignments" role="tabpanel" aria-labelledby="pf-tab-assignments" tabindex="0">
                @if ($assignments->isEmpty())
                    <div class="pf-empty">
                        {!! $icon('file-text', 'i5') !!}
                        <p class="{{ $km }}">{{ $L('No assignments yet. They appear here when Teacher Piseth sets one for your courses.', 'មិនទាន់មានកិច្ចការទេ។ កិច្ចការនឹងបង្ហាញនៅទីនេះ ពេលលោកគ្រូដាក់ឲ្យសម្រាប់វគ្គរបស់អ្នក។') }}</p>
                    </div>
                @else
                    <div class="tk-grid">
                        @foreach ($assignments as $assignment)
                            @include('partials.live._assignment-card', ['assignment' => $assignment, 'submission' => $latestWork->get($assignment->id)])
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ---------- Quiz results ---------- --}}
            <div class="pf-panel" id="pf-panel-quizzes" role="tabpanel" aria-labelledby="pf-tab-quizzes" tabindex="0">
                @if ($quizzes->isEmpty())
                    <div class="pf-empty">
                        {!! $icon('trophy', 'i5') !!}
                        <p class="{{ $km }}">{{ $L('No quizzes yet for your courses.', 'មិនទាន់មានតេស្តសម្រាប់វគ្គរបស់អ្នកទេ។') }}</p>
                    </div>
                @else
                    <div class="tk-grid tk-grid--3">
                        @foreach ($quizzes->sortByDesc(fn ($q) => $bestQuiz->has($q->id)) as $quiz)
                            @include('partials.live._quiz-card', ['quiz' => $quiz, 'attempt' => $bestQuiz->get($quiz->id)])
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ---------- Certificates & milestones ---------- --}}
            <div class="pf-panel" id="pf-panel-certificates" role="tabpanel" aria-labelledby="pf-tab-certificates" tabindex="0">
                <span id="achievements"></span>
                @forelse ($certificates as $certificate)
                    @php $cc = $certificate->course; @endphp
                    <article class="pf-cert">
                        <a href="{{ route('certificates.show', $certificate) }}" class="pf-seal">
                            <img src="https://images.unsplash.com/photo-1589330694653-dad6d3240a2b?w=800&auto=format&fit=crop&q=80" alt="" loading="lazy" decoding="async">
                            <div class="row"><span class="tag pf-mono">Verified Credential</span>{!! $icon('award', 'i5') !!}</div>
                            <div>
                                <span class="no pf-mono">Certificate #{{ $certificate->code }}</span>
                                <h4 class="hm-km">វិញ្ញាបនបត្រ {{ $cc?->title_en ?? $certificate->holder_name }}</h4>
                                <span class="ok">{!! $icon('check-circle', 'i35') !!} 100% Verified</span>
                            </div>
                        </a>
                        <div class="pf-certb">
                            <div>
                                <span class="v pf-mono">{!! $icon('check-circle', 'i35') !!} Verified Digital Credential</span>
                                <h3>{{ $cc?->localized('title') }}@if ($cc && $cc->durationMinutes() > 0) ({{ $cc->formattedDuration() }})@endif</h3>
                                <p class="d hm-km">បញ្ជាក់ការបញ្ចប់វគ្គសិក្សាដោយជោគជ័យ ចេញដោយលោកគ្រូ យឿន ពិសិដ្ឋ (Build Bright University)</p>
                                <p class="m pf-mono">Issued to {{ $certificate->holder_name ?: $user->name }} · Completed {{ $certificate->issued_at?->locale('en')->isoFormat('MMM D, YYYY') }}</p>
                            </div>
                            <div class="pf-row">
                                <button type="button" class="pf-gray" data-copy="{{ $certificate->code }}">{!! $icon('copy') !!}<span data-copy-label>{{ $L('Copy Code', 'ចម្លងលេខកូដ') }}</span></button>
                                <a href="{{ route('certificates.show', $certificate) }}" class="pf-btn">{!! $icon('award') !!}<span>{{ $L('View Official Certificate Page', 'មើលទំព័រវិញ្ញាបនបត្រ') }}</span></a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="pf-empty">
                        {!! $icon('award', 'i5') !!}
                        <p class="{{ $km }}">{{ $L('Finish a course to earn your verified certificate from Teacher Piseth Yoeurn.', 'បញ្ចប់វគ្គសិក្សាមួយ ដើម្បីទទួលបានវិញ្ញាបនបត្រពីលោកគ្រូ យឿន ពិសិដ្ឋ។') }}</p>
                    </div>
                @endforelse

                <section class="pf-box">
                    <div class="pf-boxhead">
                        <h2 class="{{ $km }}">{{ $L('Milestones', 'សមិទ្ធផល') }}</h2>
                        <p>{{ $L(count(array_filter($badges, fn ($b) => $b['earned'])).' earned', 'ទទួលបាន '.count(array_filter($badges, fn ($b) => $b['earned']))) }}</p>
                    </div>
                    <div class="pf-badges">
                        @foreach ($badges as $badge)
                            <div class="pf-badge {{ $badge['earned'] ? '' : 'is-locked' }}">
                                <i>{!! $icon($badgeIcons[$badge['icon']] ?? 'award', 'i4') !!}</i>
                                <div style="min-width:0">
                                    <p>{{ __($badge['label']) }}</p>
                                    <small>{{ $badge['earned'] ? __($badge['note']) : __('Not yet: :note', ['note' => __($badge['note'])]) }}</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>

            {{-- ---------- Saved resources ---------- --}}
            <div class="pf-panel" id="pf-panel-saved" role="tabpanel" aria-labelledby="pf-tab-saved" tabindex="0">
                @if ($saved->isEmpty())
                    <div class="pf-empty">
                        {!! $icon('bookmark', 'i5') !!}
                        <p class="{{ $km }}">{{ $L('Nothing saved yet. Bookmark fonts, templates and kits in the Resources library to keep them here.', 'មិនទាន់មានអ្វីរក្សាទុកទេ។ ចុចសម្គាល់ Font, Template និងឯកសារនៅក្នុងបណ្ណាល័យឯកសារ ដើម្បីរក្សាទុកនៅទីនេះ។') }}</p>
                        <a href="{{ route('resources.index') }}" class="pf-btn">{!! $icon('folder') !!}<span class="{{ $km }}">{{ $L('Open Resources', 'បើកបណ្ណាល័យឯកសារ') }}</span></a>
                    </div>
                @else
                    <div class="pf-grid2">
                        @foreach ($saved as $resource)
                            <article class="pf-sv">
                                <a href="{{ route('resources.download', $resource) }}" class="pf-shot" @if ($resource->isLink()) target="_blank" rel="noopener" @endif>
                                    <img src="{{ $resource->pictureSrc() }}" alt="" loading="lazy" decoding="async">
                                    <span class="pf-chips">
                                        <span class="pf-chip pf-mono">{{ $resource->software }} · {{ $resource->type }}</span>
                                        <span class="pf-mark">{!! $icon('bookmark', 'i35') !!}</span>
                                    </span>
                                    <span class="pf-cap">
                                        <h3 class="hm-km">{{ $resource->title_km ?: $resource->title_en }}</h3>
                                        <p class="pf-mono">{{ $resource->title_en }} · {{ $resource->sizeLabel() }}</p>
                                    </span>
                                </a>
                                <div class="pf-svf">
                                    <span class="pf-mono">Size: {{ $resource->sizeLabel() }}</span>
                                    <a href="{{ route('resources.download', $resource) }}" @if ($resource->isLink()) target="_blank" rel="noopener" @endif><span class="{{ $km }}">{{ $L('Download', 'ទាញយក') }}</span>{!! $icon('chevron-right', 'i35') !!}</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ---------- Payments ---------- --}}
            {{-- Read-only on purpose: the student's own receipt book, and the record of why they can open a course. --}}
            <div class="pf-panel" id="pf-panel-payments" role="tabpanel" aria-labelledby="pf-tab-payments" tabindex="0">
                <div>
                    @forelse ($payments as $payment)
                        <div class="pf-pay">
                            <div class="pf-payt">
                                <div style="min-width:0">
                                    <h4>
                                        @if ($payment->course)
                                            <a href="{{ route('courses.show', $payment->course) }}">{{ $payment->course->localized('title') }}</a>
                                        @else
                                            {{-- The course is no longer on sale; the receipt still stands. --}}
                                            {{ $payment->courseTitle() }}
                                        @endif
                                    </h4>
                                    <p class="when">{{ ($payment->paid_at ?? $payment->created_at)?->format('j M Y, H:i') }} · <span class="pf-mono">{{ $payment->invoice_no }}</span></p>
                                </div>
                                <div class="pf-amt">
                                    <strong>{{ $payment->formattedAmount() }}</strong>
                                    <span class="pf-st {{ $payment->statusTone() }}">{{ $payment->statusLabel() }}</span>
                                </div>
                            </div>

                            {{-- Only say something when there is something for them to do. --}}
                            @if ($payment->isPaid())
                                @if ($payment->course)
                                    <p class="pf-note">{{ __('Unlocked — this course is yours to keep.') }}</p>
                                @endif
                            @elseif ($payment->status === 'review')
                                <p class="pf-note">{{ __('We are checking this payment by hand. Contact us with the invoice number above if it stays this way.') }}</p>
                            @elseif ($payment->isRefunded())
                                <p class="pf-note">{{ __('This payment was refunded on :date, so the course was closed again. Your lesson progress is saved if you decide to buy it back.', ['date' => $payment->refunded_at?->format('j M Y')]) }}</p>
                            @elseif ($payment->course && ! $user->isEnrolledIn($payment->course))
                                <a href="{{ route('payments.checkout', $payment->course) }}" class="pf-retry">{{ __('Try paying again') }}</a>
                            @endif
                        </div>
                    @empty
                        <div class="pf-empty">
                            {!! $icon('receipt', 'i5') !!}
                            <p>{{ __('No payments yet. Free courses do not appear here.') }}</p>
                        </div>
                    @endforelse
                </div>

                @if ($payments->isNotEmpty())
                    <p class="pf-note">{{ __('Payment records are kept for accounting and tax purposes.') }}</p>
                @endif
            </div>
        </div>
    </div>

    <x-slot:after>
        @if (session('status') === 'photo-updated')
            <div class="pf-toast" role="status">{!! $icon('check-circle') !!}<span class="{{ $km }}">{{ $L('Profile photo updated', 'បានប្តូររូបថតរួចរាល់') }}</span></div>
        @endif
    </x-slot:after>

    <x-slot:scripts>
        @include('partials.live._activity-likes')
        <script>
            (function () {
                'use strict';
                var text = @json($pfText);

                /* ---- tabs: the jelly tabs announce a change; the URL hash opens one (#assignments, #achievements ...) ---- */
                var bar = document.querySelector('[data-pf-tabs]');
                var alias = { achievements: 'certificates', certificate: 'certificates' };

                function show(name, remember) {
                    var panel = document.getElementById('pf-panel-' + name);
                    if (!panel) return;
                    document.querySelectorAll('.pf-panel').forEach(function (p) { p.classList.toggle('is-on', p === panel); });
                    if (window.fxArm) fxArm();
                    if (remember) { try { history.replaceState(null, '', '#' + name); } catch (e) {} }
                }
                function open(name) {
                    var tab = bar && bar.querySelector('[data-value="' + name + '"]');
                    if (!tab) return false;
                    if (tab.getAttribute('aria-selected') === 'true') show(name, false); else tab.click();
                    tab.scrollIntoView({ block: 'nearest', inline: 'center' });
                    return true;
                }
                if (bar) bar.addEventListener('jelly:change', function (e) { show(e.detail.value, true); });

                function fromHash() {
                    var name = location.hash.replace('#', '');
                    if (name && open(alias[name] || name)) bar.scrollIntoView({ block: 'start' });
                }
                fromHash();
                window.addEventListener('hashchange', fromHash);

                document.querySelectorAll('[data-tab-link]').forEach(function (a) {
                    a.addEventListener('click', function (e) {
                        e.preventDefault();
                        open(a.getAttribute('data-tab-link'));
                        bar.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                });

                /* ---- change photo: pick a file, check it, send it ---- */
                var form = document.querySelector('[data-photo-form]');
                var input = form && form.querySelector('[data-photo-input]');
                var err = document.querySelector('[data-photo-err]');
                if (form && input) {
                    document.querySelectorAll('[data-photo-pick]').forEach(function (b) {
                        b.addEventListener('click', function () { input.click(); });
                    });
                    input.addEventListener('change', function () {
                        var file = input.files && input.files[0];
                        if (!file) return;
                        if (!/^image\/(jpeg|png|webp)$/.test(file.type)) { err.textContent = text.type; err.hidden = false; input.value = ''; return; }
                        if (file.size > 3 * 1024 * 1024) { err.textContent = text.size; err.hidden = false; input.value = ''; return; }
                        err.hidden = true;
                        var img = form.querySelector('.pf-pic img, .pf-pic .pf-initials');
                        if (img && window.URL) {
                            var preview = document.createElement('img');
                            preview.src = URL.createObjectURL(file);
                            preview.alt = '';
                            img.replaceWith(preview);
                        }
                        form.classList.add('is-busy');
                        form.submit();
                    });
                }

                /* ---- copy a certificate code ---- */
                document.addEventListener('click', function (e) {
                    var b = e.target.closest('[data-copy]');
                    if (!b || !navigator.clipboard) return;
                    var label = b.querySelector('[data-copy-label]');
                    var was = label.textContent;
                    navigator.clipboard.writeText(b.getAttribute('data-copy')).then(function () {
                        label.textContent = text.copied;
                        setTimeout(function () { label.textContent = was; }, 2000);
                    });
                });
            })();
        </script>
    </x-slot:scripts>
</x-live-layout>
