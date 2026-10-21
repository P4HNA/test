{{-- Course Library (/courses), built on the live piseth.design design (components/live-layout). --}}
@php
    $hmKm = app()->getLocale() === 'km';
    $L = fn (string $en, string $km) => $hmKm ? $km : $en;
    $km = $hmKm ? 'hm-km' : '';
    $icon = fn (string $name, string $class = 'i4') => \App\Support\LiveIcon::svg($name, $class);
    // Khmer pages count in Khmer digits, like the live site.
    $num = fn ($value) => $hmKm ? strtr((string) $value, ['0' => '០', '1' => '១', '2' => '២', '3' => '៣', '4' => '៤', '5' => '៥', '6' => '៦', '7' => '៧', '8' => '៨', '9' => '៩']) : (string) $value;

    $viewer = $user ?? auth()->user();
    $viewer?->loadMissing('enrollments');
    $cards = $courses->values()->map(fn ($course) => \App\Support\CourseCard::make($course, $viewer));
    $cardOf = $cards->keyBy(fn ($card) => $card['course']->id);

    $groupLabel = [
        'Graphic Design' => ['Graphic Design', 'រចនាក្រាហ្វិក'],
        'UX/UI Design' => ['UX/UI Design', 'រចនា UX/UI'],
        'Motion Graphics' => ['Motion Graphics', 'ចលនាក្រាហ្វិក'],
    ];
    $appName = ['PS' => 'Photoshop', 'AI' => 'Illustrator', 'ID' => 'InDesign', 'FG' => 'Figma', 'XD' => 'Adobe XD', 'AE' => 'After Effects', 'PR' => 'Premiere Pro'];
    $minutesLabel = function (int $minutes) use ($L, $num): string {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        if ($h === 0) return $L($minutes.' min', $num($minutes).' នាទី');
        return $L($h.'h'.($m ? ' '.$m.'m' : ''), $num($h).' ម៉ោង'.($m ? ' '.$num($m).' នាទី' : ''));
    };

    // ---------- learning paths: each module of the courses on show ----------
    $paths = $cards->flatMap(function ($card) {
        return $card['course']->modules->filter(fn ($module) => $module->lessons->isNotEmpty())->map(fn ($module) => ['card' => $card, 'module' => $module]);
    })->values();

    // ---------- featured slider: the featured courses, else the first three ----------
    $featured = $cards->filter(fn ($card) => $card['course']->is_featured)->values();
    if ($featured->isEmpty()) {
        $featured = $cards->take(3)->values();
    }

    // ---------- latest lessons, sorted into the live site's topic chips ----------
    $bucketOf = function ($card): string {
        $first = strtoupper($card['chips'][0] ?? '');
        return match (true) {
            $first === 'PS' && count($card['chips']) === 1 => 'photo',
            in_array($first, ['AE', 'PR'], true), $card['group'] === 'Motion Graphics' => 'video',
            default => 'design',
        };
    };
    $buckets = [
        'photo' => ['camera', 'Photo', 'រូបថត'],
        'video' => ['video', 'Video and animation', 'វីដេអូ និងចលនា'],
        'design' => ['pen-tool', 'Design and illustration', 'រចនា និងគំនូរ'],
    ];
    $lessons = $cards->flatMap(function ($card) use ($bucketOf) {
        return $card['course']->modules->flatMap->lessons->map(fn ($lesson) => ['lesson' => $lesson, 'card' => $card, 'bucket' => $bucketOf($card)]);
    })->sortByDesc(fn ($row) => [$row['lesson']->created_at?->getTimestamp() ?? 0, $row['lesson']->id])->values();
    $lessonsByBucket = collect($buckets)->keys()
        ->mapWithKeys(fn ($bucket) => [$bucket => $lessons->where('bucket', $bucket)->take(6)->values()])
        ->filter(fn ($rows) => $rows->isNotEmpty());
    $firstBucket = $lessonsByBucket->keys()->first();

    $hasFilters = $search !== '' || $activeFilterCount > 0;
    $clText = [
        'none' => $L('No matching courses.', 'រកមិនឃើញវគ្គសិក្សាទេ។'),
        'back' => $L('Back to tutorials', 'ត្រឡប់ទៅ Tutorials'),
        'slide' => $L('Go to slide', 'ទៅកាន់ស្លាយ'),
    ];
@endphp

<x-live-layout :title="__('Courses') . ' — Piseth.Design Classroom'" active="courses" main-class="cl-main" :footer-courses="$courses">
    <x-slot:styles>
        <style>
            .cl-main { font-family: Inter, 'Noto Sans Khmer', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.5; -webkit-font-smoothing: antialiased; background: #FDFDFE; padding-bottom: 6rem; }
            html.dark .cl-main { background: #0B0F17; }
            .cl-mono { font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace; }
            .cl-wrap { max-width: 72rem; margin: 0 auto; padding: 0 1rem; }
            @media (min-width: 640px) { .cl-wrap { padding: 0 1.5rem; } }
            @media (min-width: 1024px) { .cl-wrap { padding: 0 2rem; } }
            .cl-hero .cl-wrap { padding: 0; }

            /* ---------- hero ---------- */
            .cl-hero { position: relative; padding: 2rem 1rem 3rem; background: linear-gradient(to bottom, #E7EEFF, #F3EBF9, #FDFDFE); border-bottom: 1px solid rgba(226, 232, 240, .6); }
            html.dark .cl-hero { background: linear-gradient(to bottom, #0B132B, #151226, #0B0F17); border-color: rgba(30, 41, 59, .6); }
            @media (min-width: 640px) { .cl-hero { padding: 3rem 1.5rem; } }
            @media (min-width: 1024px) { .cl-hero { padding-left: 2rem; padding-right: 2rem; } }
            .cl-hero .cl-wrap > * + * { margin-top: 1.5rem; }
            .cl-toprow { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
            .cl-pill { display: inline-flex; align-items: center; gap: .5rem; padding: .375rem .875rem; border-radius: 9999px; background: rgba(255, 255, 255, .95); border: 1px solid rgba(226, 232, 240, .9); color: #1E293B; font: inherit; font-size: .75rem; line-height: 1rem; font-weight: 600; cursor: pointer; box-shadow: 0 1px 1px rgba(0, 0, 0, .04); backdrop-filter: blur(12px); transition: all .15s ease; }
            .cl-pill svg { color: #475569; }
            html.dark .cl-pill { background: rgba(26, 34, 52, .95); border-color: rgba(51, 65, 85, .9); color: #E2E8F0; }
            html.dark .cl-pill svg { color: #CBD5E1; }
            .cl-pill:hover { border-color: #CBD5E1; box-shadow: 0 1px 3px rgba(0, 0, 0, .1); }
            .cl-link { display: inline-flex; align-items: center; gap: .375rem; padding: 0; border: 0; background: none; font: inherit; font-size: .75rem; line-height: 1rem; font-weight: 600; color: #1877F2; cursor: pointer; }
            html.dark .cl-link { color: #4595FF; }
            .cl-link:hover { text-decoration: underline; }
            .cl-hero h1 { margin: 0; padding-top: .5rem; text-align: center; font-size: 1.875rem; line-height: 2.25rem; font-weight: 900; letter-spacing: -.025em; color: #0F172A; }
            html.dark .cl-hero h1 { color: #fff; }
            @media (min-width: 640px) { .cl-hero h1 { padding-top: 1rem; font-size: 2.25rem; line-height: 2.5rem; } }
            @media (min-width: 1024px) { .cl-hero h1 { font-size: 3rem; line-height: 1.1; } }

            .cl-search { position: relative; max-width: 42rem; margin-left: auto; margin-right: auto; padding-top: .5rem; }
            .cl-box { position: relative; display: flex; align-items: center; gap: .25rem; padding: .375rem .375rem .375rem 1rem; border-radius: 1rem; background: #fff; border: 1px solid rgba(226, 232, 240, .9); box-shadow: 0 1px 2px rgba(0, 0, 0, .05); transition: box-shadow .15s ease; }
            html.dark .cl-box { background: #151B23; border-color: rgba(51, 65, 85, .9); }
            .cl-box:hover, .cl-box:focus-within { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1), 0 2px 4px -2px rgba(0, 0, 0, .1); }
            .cl-box input { flex: 1; min-width: 0; padding: .375rem 0; border: 0; background: transparent; font: inherit; font-size: .875rem; color: #0F172A; outline: none; box-shadow: none; }
            html.dark .cl-box input { color: #fff; }
            .cl-box input::placeholder { color: #94A3B8; }
            .cl-box input::-webkit-search-cancel-button { display: none; }
            .cl-ib { position: relative; display: inline-flex; flex-shrink: 0; align-items: center; justify-content: center; width: 2.25rem; height: 2.25rem; padding: 0; border: 0; border-radius: .75rem; background: transparent; color: #94A3B8; cursor: pointer; text-decoration: none; transition: all .15s ease; }
            .cl-ib:hover { background: #F1F5F9; color: #334155; }
            html.dark .cl-ib:hover { background: #1E2530; color: #E2E8F0; }
            .cl-ib[aria-expanded="true"] { background: #EAF5FF; color: #1877F2; }
            html.dark .cl-ib[aria-expanded="true"] { background: rgba(77, 163, 255, .15); color: #4DA3FF; }
            .cl-ib b { position: absolute; top: .125rem; right: .125rem; min-width: 1rem; height: 1rem; padding: 0 .25rem; border-radius: 9999px; background: #1877F2; color: #fff; font-size: 10px; line-height: 1rem; font-weight: 700; }
            .cl-pop { position: absolute; z-index: 40; left: 0; right: 0; top: calc(100% + .5rem); overflow: hidden; border-radius: 1rem; background: #fff; border: 1px solid #E2E8F0; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, .1), 0 8px 10px -6px rgba(0, 0, 0, .1); text-align: left; }
            html.dark .cl-pop { background: #151B23; border-color: #2E3034; }
            .cl-sug a { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .625rem 1rem; color: #0F172A; font-size: .875rem; text-decoration: none; border-bottom: 1px solid #F1F5F9; }
            html.dark .cl-sug a { color: #F1F5F9; border-color: #222936; }
            .cl-sug a:hover, .cl-sug a:focus { background: #F8FAFC; outline: none; }
            html.dark .cl-sug a:hover, html.dark .cl-sug a:focus { background: #1C232D; }
            .cl-sug a span:last-child { flex-shrink: 0; font-size: .75rem; color: #94A3B8; }
            .cl-sug p { margin: 0; padding: .75rem 1rem; font-size: .875rem; color: #64748B; }
            .cl-filters { padding: 1.25rem; display: grid; gap: 1.25rem; }
            @media (min-width: 640px) { .cl-filters { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
            .cl-filters h4 { margin: 0 0 .625rem; font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #94A3B8; }
            .cl-opts { display: flex; flex-wrap: wrap; gap: .375rem; }
            .cl-opt { position: relative; }
            .cl-opt input { position: absolute; opacity: 0; pointer-events: none; }
            .cl-opt span { display: inline-flex; padding: .3125rem .75rem; border-radius: 9999px; border: 1px solid #E2E8F0; background: #fff; color: #334155; font-size: .75rem; font-weight: 600; cursor: pointer; transition: all .15s ease; }
            html.dark .cl-opt span { background: #1A2234; border-color: #334155; color: #CBD5E1; }
            .cl-opt input:checked + span { background: #0F172A; border-color: #0F172A; color: #fff; }
            html.dark .cl-opt input:checked + span { background: #fff; border-color: #fff; color: #0F172A; }
            .cl-opt input:focus-visible + span { outline: 2px solid #4DA3FF; outline-offset: 2px; }
            .cl-range { display: flex; align-items: center; gap: .5rem; }
            .cl-range input { width: 100%; min-width: 0; padding: .4375rem .75rem; border-radius: .75rem; border: 1px solid #E2E8F0; background: #F8FAFC; color: #0F172A; font: inherit; font-size: .8125rem; }
            html.dark .cl-range input { background: #0B0F17; border-color: #334155; color: #fff; }
            .cl-fbtns { display: flex; align-items: center; justify-content: flex-end; gap: .5rem; padding: .875rem 1.25rem; border-top: 1px solid #F1F5F9; background: #F8FAFC; }
            html.dark .cl-fbtns { background: #11161D; border-color: #222936; }
            .cl-btn-dark { display: inline-flex; align-items: center; gap: .375rem; padding: .5rem 1rem; border: 0; border-radius: 9999px; background: #0F172A; color: #fff; font: inherit; font-size: .75rem; font-weight: 700; text-decoration: none; cursor: pointer; transition: background .15s ease; }
            .cl-btn-dark:hover { background: #1E293B; }
            html.dark .cl-btn-dark { background: #fff; color: #0F172A; }
            html.dark .cl-btn-dark:hover { background: #F1F5F9; }
            .cl-btn-line { display: inline-flex; align-items: center; gap: .375rem; padding: .4375rem 1rem; border-radius: 9999px; border: 1px solid #CBD5E1; background: #fff; color: #0F172A; font: inherit; font-size: .75rem; font-weight: 600; text-decoration: none; cursor: pointer; transition: background .15s ease; }
            html.dark .cl-btn-line { background: #1A2234; border-color: #334155; color: #fff; }
            .cl-btn-line:hover { background: #F1F5F9; }
            html.dark .cl-btn-line:hover { background: #1E293B; }

            .cl-active { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .5rem; font-size: .75rem; color: #64748B; }
            .cl-active a { display: inline-flex; align-items: center; gap: .25rem; padding: .1875rem .625rem; border-radius: 9999px; background: rgba(255, 255, 255, .8); border: 1px solid #E2E8F0; color: #334155; font-weight: 600; text-decoration: none; }
            html.dark .cl-active a { background: rgba(26, 34, 52, .8); border-color: #334155; color: #CBD5E1; }
            .cl-active a:hover { border-color: #F43F5E; color: #E11D48; }

            /* ---------- main courses ---------- */
            .cl-mainhead { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; padding: .75rem .25rem 0; }
            .cl-mainhead > div { display: flex; align-items: center; gap: .5rem; }
            .cl-dot { width: .5rem; height: .5rem; border-radius: 9999px; background: #0071E3; animation: cl-pulse 2s cubic-bezier(.4, 0, .6, 1) infinite; }
            html.dark .cl-dot { background: #42A5F5; }
            @keyframes cl-pulse { 50% { opacity: .5; } }
            .cl-mainhead h2 { margin: 0; font-size: .75rem; line-height: 1rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: #1E293B; }
            html.dark .cl-mainhead h2 { color: #E2E8F0; }
            @media (min-width: 640px) { .cl-mainhead h2 { font-size: .875rem; line-height: 1.25rem; } }
            .cl-count { font-size: 11px; font-weight: 500; color: #64748B; }
            html.dark .cl-count { color: #94A3B8; }
            .cl-tabs { margin-top: 1rem; }
            .cl-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; margin-top: 1rem; }
            @media (min-width: 640px) { .cl-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; } }
            @media (min-width: 1024px) { .cl-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
            .cl-card { position: relative; display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; border-radius: 1rem; background: rgba(255, 255, 255, .9); border: 1px solid rgba(226, 232, 240, .8); box-shadow: 0 1px 2px rgba(0, 0, 0, .05); backdrop-filter: blur(24px); transition: all .3s ease; }
            @media (min-width: 640px) { .cl-card { border-radius: 1.5rem; } }
            html.dark .cl-card { background: rgba(20, 26, 35, .9); border-color: rgba(255, 255, 255, .1); }
            .cl-card:hover { border-color: rgba(0, 113, 227, .5); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .25); transform: translateY(-.375rem); }
            html.dark .cl-card:hover { border-color: rgba(66, 165, 245, .5); }
            .cl-card.is-promo { box-shadow: 0 0 0 2px #0866FF, 0 1px 2px rgba(0, 0, 0, .05); }
            .cl-card[hidden] { display: none; }
            .cl-cover { position: relative; aspect-ratio: 16 / 10; overflow: hidden; background: #0F172A; }
            .cl-cover > img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s ease-out; }
            .cl-card:hover .cl-cover > img { transform: scale(1.05); }
            .cl-cover::after { content: ''; position: absolute; inset: 0; pointer-events: none; background: linear-gradient(to top, rgba(0, 0, 0, .8), rgba(0, 0, 0, .2), transparent); }
            .cl-cover .pd-ribbon { z-index: 3; }
            .cl-tl, .cl-tr, .cl-bl, .cl-br { position: absolute; z-index: 2; display: flex; align-items: center; gap: .25rem; }
            .cl-tl { top: .625rem; left: .625rem; }
            .cl-tr { top: .625rem; right: .625rem; }
            .cl-bl { bottom: .625rem; left: .625rem; }
            .cl-br { bottom: .625rem; right: .625rem; }
            .cl-card.is-promo .cl-tr { display: none; }
            .cl-sw { display: inline-flex; width: 1.5rem; height: 1.5rem; align-items: center; justify-content: center; border-radius: .375rem; border: 1px solid rgba(255, 255, 255, .2); background: rgba(15, 23, 42, .9); color: #fff; font-size: 10px; font-weight: 700; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1); }
            .cl-sw[data-app="PS"] { background: #001E36; color: #31A8FF; border-color: rgba(49, 168, 255, .3); }
            .cl-sw[data-app="AI"] { background: #330000; color: #FF9A00; border-color: rgba(255, 154, 0, .3); }
            .cl-sw[data-app="ID"] { background: #49021F; color: #FF3366; border-color: rgba(255, 51, 102, .3); }
            .cl-sw[data-app="FG"] { background: #1E1E1E; color: #0ACF83; border-color: rgba(10, 207, 131, .3); }
            .cl-sw[data-app="XD"] { background: #470137; color: #FF61F6; border-color: rgba(255, 97, 246, .3); }
            .cl-sw[data-app="AE"] { background: #00005B; color: #9999FF; border-color: rgba(153, 153, 255, .3); }
            .cl-sw[data-app="PR"] { background: #00005B; color: #EA77FF; border-color: rgba(234, 119, 255, .3); }
            .cl-dur { padding: .125rem .625rem; border-radius: 9999px; background: rgba(0, 0, 0, .6); border: 1px solid rgba(255, 255, 255, .15); color: #fff; font-size: 10px; font-weight: 700; backdrop-filter: blur(12px); }
            html.dark .cl-dur { background: rgba(0, 0, 0, .75); }
            .cl-tag { padding: .125rem .5rem; border-radius: .5rem; background: rgba(255, 255, 255, .2); border: 1px solid rgba(255, 255, 255, .2); color: rgba(255, 255, 255, .95); font-size: 10px; font-weight: 600; backdrop-filter: blur(12px); }
            .cl-enr { display: inline-flex; align-items: center; gap: .25rem; padding: .125rem .5rem; border-radius: .5rem; background: rgba(2, 44, 34, .8); border: 1px solid rgba(16, 185, 129, .4); color: #6EE7B7; font-size: 10px; font-weight: 600; backdrop-filter: blur(12px); }
            .cl-enr svg { width: .625rem; height: .625rem; }
            .cl-body { flex: 1; display: flex; flex-direction: column; justify-content: space-between; padding: 1rem; }
            @media (min-width: 640px) { .cl-body { padding: 1.25rem; } }
            .cl-body h3 { margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 1rem; line-height: 1.5rem; font-weight: 700; color: #0F172A; transition: color .15s ease; }
            html.dark .cl-body h3 { color: #fff; }
            .cl-card:hover .cl-body h3 { color: #0071E3; }
            html.dark .cl-card:hover .cl-body h3 { color: #42A5F5; }
            .cl-body h3 a { color: inherit; text-decoration: none; }
            .cl-body h3 a::after { content: ''; position: absolute; inset: 0; z-index: 1; }
            .cl-tools { margin: .25rem 0 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .75rem; line-height: 1rem; font-weight: 500; color: #64748B; }
            html.dark .cl-tools { color: #94A3B8; }
            .cl-desc { display: none; margin: .625rem 0 0; font-size: .8125rem; line-height: 1.6; color: #475569; }
            html.dark .cl-desc { color: #CBD5E1; }
            .cl-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .25rem .75rem; margin-top: .75rem; font-size: 11px; color: #64748B; }
            html.dark .cl-meta { color: #94A3B8; }
            .cl-meta span { display: inline-flex; align-items: center; gap: .25rem; }
            .cl-meta svg { color: #94A3B8; }
            .cl-meta .lvl { color: #F59E0B; font-weight: 600; }
            html.dark .cl-meta .lvl { color: #FBBF24; }
            .cl-meta .lvl svg { color: currentColor; }
            .cl-meta .prog { color: #10B981; font-weight: 600; }
            .cl-foot { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-top: .875rem; padding-top: .875rem; border-top: 1px solid #F1F5F9; }
            html.dark .cl-foot { border-color: rgba(255, 255, 255, .05); }
            .cl-price { display: flex; align-items: baseline; gap: .375rem; min-width: 0; font-size: 1rem; font-weight: 800; color: #0F172A; }
            html.dark .cl-price { color: #fff; }
            .cl-price s { font-size: .75rem; font-weight: 400; color: #94A3B8; order: 2; }
            .cl-price .free { color: #10B981; font-family: Inter, 'Noto Sans Khmer', sans-serif; }
            .cl-go { position: relative; z-index: 2; display: inline-flex; flex-shrink: 0; align-items: center; gap: .25rem; padding: .375rem .75rem; border-radius: .75rem; background: #0F172A; color: #fff; font-size: .75rem; line-height: 1rem; font-weight: 600; text-decoration: none; box-shadow: 0 1px 2px rgba(0, 0, 0, .05); transition: all .15s ease; }
            html.dark .cl-go { background: #fff; color: #0F172A; }
            .cl-card:hover .cl-go { background: #0071E3; color: #fff; }
            .cl-go.is-on { background: rgba(0, 113, 227, .1); color: #0071E3; box-shadow: none; }
            html.dark .cl-go.is-on { background: rgba(66, 165, 245, .1); color: #42A5F5; }
            .cl-go.is-on:hover { background: #0071E3; color: #fff; }
            .cl-empty { margin-top: 1rem; padding: 2rem 1rem; border-radius: 1rem; background: rgba(255, 255, 255, .5); border: 1px dashed #E2E8F0; text-align: center; }
            html.dark .cl-empty { background: rgba(255, 255, 255, .05); border-color: #1E293B; }
            .cl-empty p { margin: 0; font-size: .875rem; color: #64748B; }
            .cl-empty a { display: inline-block; margin-top: .5rem; font-size: .75rem; font-weight: 600; color: #0071E3; }

            /* "Browse all courses": the full catalogue, two wide columns with the course write-up. */
            .cl-allhead { display: none; align-items: center; justify-content: space-between; gap: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #E2E8F0; }
            html.dark .cl-allhead { border-color: #1E293B; }
            .cl-allhead h2 { margin: 0; font-size: 1.5rem; line-height: 2rem; font-weight: 700; color: #0F172A; }
            html.dark .cl-allhead h2 { color: #fff; }
            .cl-allhead p { margin: .125rem 0 0; font-size: .75rem; color: #64748B; }
            .is-all .cl-allhead { display: flex; }
            .is-all .cl-mainhead, .is-all .cl-tutorials { display: none; }
            @media (min-width: 768px) { .is-all .cl-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.5rem; } }
            @media (min-width: 1024px) { .is-all .cl-grid { gap: 2rem; } }
            .is-all .cl-desc { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
            .is-all .cl-body h3 { font-size: 1.125rem; }

            /* ---------- shared section bits ---------- */
            .cl-tutorials { padding-top: 4rem; }
            @media (min-width: 640px) { .cl-tutorials { padding-top: 5rem; } }
            .cl-tutorials > .cl-wrap > * + * { margin-top: 4rem; }
            .cl-sechead { text-align: center; }
            .cl-sechead h2 { margin: 0; font-size: 1.5rem; line-height: 2rem; font-weight: 900; letter-spacing: -.025em; color: #0F172A; }
            html.dark .cl-sechead h2 { color: #fff; }
            @media (min-width: 640px) { .cl-sechead h2 { font-size: 1.875rem; line-height: 2.25rem; } }
            .cl-sechead p { max-width: 36rem; margin: .5rem auto 0; font-size: .75rem; line-height: 1rem; color: #64748B; }
            html.dark .cl-sechead p { color: #94A3B8; }
            @media (min-width: 640px) { .cl-sechead p { font-size: .875rem; line-height: 1.25rem; } }
            .cl-app { display: inline-flex; align-items: center; justify-content: center; padding: .125rem .5rem; border-radius: .375rem; border: 1px solid rgba(255, 255, 255, .2); background: #1877F2; color: #fff; font-size: 11px; font-weight: 700; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1); }
            .cl-app[data-app="PS"] { background: #31A8FF; color: #001E36; }
            .cl-app[data-app="AI"] { background: #FF9A00; color: #330000; }
            .cl-app[data-app="ID"] { background: #FF3366; color: #49021F; }
            .cl-app[data-app="FG"] { background: #0ACF83; color: #fff; }
            .cl-app[data-app="XD"] { background: #FF61F6; color: #470137; }
            .cl-app[data-app="AE"] { background: #9999FF; color: #00005B; }
            .cl-app[data-app="PR"] { background: #EA77FF; color: #2E001F; }

            /* carousels: a scroll-snapping track with arrows and page dots */
            .cl-car { position: relative; }
            .cl-track { display: grid; grid-auto-flow: column; grid-auto-columns: 85%; gap: 1.25rem; overflow-x: auto; overscroll-behavior-x: contain; scroll-snap-type: x mandatory; scrollbar-width: none; padding: .25rem .125rem 1rem; margin: -.25rem -.125rem -1rem; }
            .cl-track::-webkit-scrollbar { display: none; }
            .cl-track > * { scroll-snap-align: start; }
            @media (min-width: 640px) { .cl-track--4 { grid-auto-columns: calc((100% - 1.5rem) / 2); gap: 1.5rem; } }
            @media (min-width: 1024px) { .cl-track--4 { grid-auto-columns: calc((100% - 4.5rem) / 4); } }
            .cl-track--1 { grid-auto-columns: 100%; gap: 1.5rem; }
            .cl-arrows { display: none; }
            @media (min-width: 640px) { .cl-arrows { position: absolute; z-index: 10; left: -1.25rem; right: -1.25rem; top: 50%; display: flex; justify-content: space-between; transform: translateY(-50%); pointer-events: none; } }
            .cl-arrow { display: flex; width: 2.25rem; height: 2.25rem; align-items: center; justify-content: center; border-radius: 9999px; background: #fff; border: 1px solid #E2E8F0; color: #334155; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1); cursor: pointer; pointer-events: auto; transition: background .15s ease; }
            .cl-arrow:hover { background: #F8FAFC; }
            html.dark .cl-arrow { background: #1A2234; border-color: #334155; color: #E2E8F0; }
            .cl-arrow:disabled { opacity: .3; cursor: not-allowed; }
            .cl-arrows--lg .cl-arrow { width: 2.5rem; height: 2.5rem; background: rgba(255, 255, 255, .95); backdrop-filter: blur(12px); }
            html.dark .cl-arrows--lg .cl-arrow { background: rgba(26, 34, 52, .95); }
            .cl-dots { display: flex; align-items: center; justify-content: center; gap: .5rem; padding-top: 1.5rem; }
            .cl-dots button { width: .5rem; height: .5rem; padding: 0; border: 0; border-radius: 9999px; background: #CBD5E1; cursor: pointer; transition: all .3s ease; }
            html.dark .cl-dots button { background: #334155; }
            .cl-dots button[aria-current="true"] { width: 1.5rem; background: #0F172A; }
            html.dark .cl-dots button[aria-current="true"] { background: #fff; }

            /* learning paths */
            .cl-path { display: flex; flex-direction: column; justify-content: space-between; overflow: hidden; border-radius: 1rem; background: #fff; border: 1px solid rgba(226, 232, 240, .9); box-shadow: 0 1px 2px rgba(0, 0, 0, .05); transition: all .3s ease; }
            html.dark .cl-path { background: #151B23; border-color: #1E293B; }
            .cl-path:hover { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, .1), 0 8px 10px -6px rgba(0, 0, 0, .1); transform: translateY(-.25rem); }
            .cl-path .pic { position: relative; aspect-ratio: 16 / 10; overflow: hidden; background: #F1F5F9; }
            html.dark .cl-path .pic { background: #0F172A; }
            .cl-path .pic img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s ease; }
            .cl-path:hover .pic img { transform: scale(1.05); }
            .cl-path .pic .cl-app { position: absolute; top: .625rem; left: .625rem; }
            .cl-path .txt { flex: 1; display: flex; flex-direction: column; justify-content: space-between; gap: 1rem; padding: 1rem; }
            @media (min-width: 640px) { .cl-path .txt { padding: 1.25rem; } }
            .cl-path h3 { margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 1rem; line-height: 1.375; font-weight: 700; color: #0F172A; transition: color .15s ease; }
            html.dark .cl-path h3 { color: #fff; }
            .cl-path:hover h3 { color: #1877F2; }
            .cl-path .info { display: flex; align-items: center; gap: .5rem; margin-top: .5rem; font-size: 11px; font-weight: 600; color: #64748B; }
            html.dark .cl-path .info { color: #94A3B8; }
            .cl-path .info span { display: inline-flex; align-items: center; gap: .25rem; }
            .cl-path .info svg { color: #94A3B8; }
            .cl-path p { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; margin: .5rem 0 0; padding-top: .25rem; font-size: .75rem; line-height: 1.625; color: #475569; }
            html.dark .cl-path p { color: #CBD5E1; }
            .cl-path .act { display: flex; justify-content: flex-end; padding-top: .5rem; }

            /* big feature cards (featured slider and Digital Academy) */
            .cl-feat { display: grid; grid-template-columns: minmax(0, 1fr); overflow: hidden; border-radius: 1.5rem; background: #fff; border: 1px solid rgba(226, 232, 240, .9); box-shadow: 0 1px 2px rgba(0, 0, 0, .05); transition: box-shadow .3s ease; }
            html.dark .cl-feat { background: #151B23; border-color: #1E293B; }
            .cl-feat:hover { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1), 0 2px 4px -2px rgba(0, 0, 0, .1); }
            @media (min-width: 768px) { .cl-feat { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            .cl-feat .art { position: relative; aspect-ratio: 16 / 10; min-height: 220px; overflow: hidden; background: #020617; }
            @media (min-width: 768px) { .cl-feat .art { aspect-ratio: auto; min-height: 280px; } }
            .cl-feat .art > img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
            .cl-new { position: absolute; top: 1rem; left: 1rem; padding: .125rem .625rem; border-radius: 9999px; background: #6366F1; color: #fff; font-size: 10px; font-weight: 700; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1); }
            .cl-feat .txt { display: flex; flex-direction: column; justify-content: space-between; gap: 1rem; padding: 1.5rem; }
            @media (min-width: 640px) { .cl-feat .txt { padding: 2rem; } }
            @media (min-width: 1024px) { .cl-feat .txt { padding: 2.5rem; } }
            .cl-feat .who { display: flex; align-items: center; gap: .5rem; font-size: .75rem; font-weight: 700; color: #334155; }
            html.dark .cl-feat .who { color: #CBD5E1; }
            .cl-feat .who .cl-app { padding: .125rem .375rem; font-size: 10px; box-shadow: none; }
            .cl-feat .who em { font-style: normal; font-size: 11px; font-weight: 500; color: #1877F2; }
            html.dark .cl-feat .who em { color: #4DA3FF; }
            .cl-feat .who i { font-style: normal; color: #CBD5E1; }
            html.dark .cl-feat .who i { color: #334155; }
            .cl-feat h3 { margin: .75rem 0 0; font-size: 1.25rem; line-height: 1.375; font-weight: 900; color: #0F172A; }
            html.dark .cl-feat h3 { color: #fff; }
            @media (min-width: 640px) { .cl-feat h3 { font-size: 1.5rem; } }
            .cl-feat p { margin: .75rem 0 0; font-size: .75rem; line-height: 1.625; color: #475569; }
            html.dark .cl-feat p { color: #94A3B8; }
            @media (min-width: 640px) { .cl-feat p { font-size: .875rem; } }
            .cl-feat .bar { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding-top: .5rem; }
            .cl-feat .bar.line { padding-top: 1rem; border-top: 1px solid #F1F5F9; }
            html.dark .cl-feat .bar.line { border-color: rgba(30, 41, 59, .8); }
            .cl-feat .bar small { display: inline-flex; align-items: center; gap: .375rem; font-size: .75rem; font-weight: 500; color: #94A3B8; }
            .cl-play { display: inline-flex; align-items: center; gap: .5rem; padding: .5rem 1.25rem; border-radius: 9999px; background: #0F172A; color: #fff; font-size: .75rem; font-weight: 700; text-decoration: none; box-shadow: 0 1px 2px rgba(0, 0, 0, .05); transition: background .15s ease; }
            .cl-play svg { width: .875rem; height: .875rem; fill: currentColor; }
            .cl-play:hover { background: #1E293B; }
            html.dark .cl-play { background: #fff; color: #0F172A; }
            html.dark .cl-play:hover { background: #F1F5F9; }
            .cl-play--lg { padding: .625rem 1.25rem; }

            .cl-acad .art { display: flex; align-items: center; justify-content: center; min-height: 240px; }
            @media (min-width: 768px) { .cl-acad .art { min-height: 300px; } }
            .cl-acad .art > img { mix-blend-mode: overlay; opacity: .8; transition: transform .5s ease; }
            .cl-acad .art:hover > img { transform: scale(1.05); }
            .cl-acad .art::after { content: ''; position: absolute; inset: 0; pointer-events: none; background: linear-gradient(to top, rgba(0, 0, 0, .5), transparent); }
            .cl-acad .art .cl-app { position: absolute; z-index: 2; top: 1rem; left: 1rem; padding: .25rem .625rem; }
            .cl-acad[data-app="PS"] .art { background: linear-gradient(to bottom right, #001E36, #0284C7, #10B981); }
            .cl-acad[data-app="AI"] .art { background: linear-gradient(to bottom right, #330000, #EA580C, #FACC15); }
            .cl-acad[data-app="ID"] .art { background: linear-gradient(to bottom right, #49021F, #E11D48, #F472B6); }
            .cl-acad[data-app="FG"] .art { background: linear-gradient(to bottom right, #0ACF83, #06B6D4, #6366F1); }
            .cl-acad[data-app="AE"] .art, .cl-acad[data-app="PR"] .art { background: linear-gradient(to bottom right, #00005B, #4F46E5, #06B6D4); }
            .cl-acad .art { background: linear-gradient(to bottom right, #0F172A, #1877F2, #A855F7); }
            .cl-glass { position: relative; z-index: 2; width: calc(100% - 3rem); max-width: 20rem; padding: 1rem; border-radius: 1rem; background: rgba(255, 255, 255, .2); border: 1px solid rgba(255, 255, 255, .3); color: #fff; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, .1); backdrop-filter: blur(12px); }
            @media (min-width: 640px) { .cl-glass { padding: 1.25rem; } }
            .cl-glass .row { display: flex; align-items: center; justify-content: space-between; font-size: .75rem; font-weight: 700; }
            .cl-glass .row b { color: #6EE7B7; }
            .cl-glass .track { height: .5rem; margin-top: .625rem; border-radius: 9999px; background: rgba(255, 255, 255, .3); overflow: hidden; }
            .cl-glass .track i { display: block; height: 100%; border-radius: 9999px; background: #fff; }
            .cl-glass small { display: flex; align-items: center; gap: .375rem; margin-top: .625rem; font-size: 10px; color: rgba(255, 255, 255, .9); }
            .cl-glass small svg { width: .75rem; height: .75rem; color: #6EE7B7; }

            /* latest lessons */
            .cl-chips { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: .5rem; padding-top: 1.5rem; }
            @media (min-width: 640px) { .cl-chips { gap: .75rem; } }
            .cl-chip { display: inline-flex; align-items: center; gap: .375rem; padding: .375rem .875rem; border-radius: 9999px; border: 1px solid #E2E8F0; background: #fff; color: #334155; font: inherit; font-size: .75rem; line-height: 1rem; font-weight: 600; cursor: pointer; transition: all .15s ease; }
            html.dark .cl-chip { background: #1A2234; border-color: #334155; color: #CBD5E1; }
            .cl-chip:hover { border-color: #94A3B8; }
            .cl-chip[aria-pressed="true"] { background: #0F172A; border-color: #0F172A; color: #fff; box-shadow: 0 1px 2px rgba(0, 0, 0, .05); }
            html.dark .cl-chip[aria-pressed="true"] { background: #fff; border-color: #fff; color: #0F172A; }
            .cl-lessons { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; padding-top: 2.5rem; }
            @media (min-width: 640px) { .cl-lessons { gap: 1.75rem; } }
            @media (min-width: 768px) { .cl-lessons { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            @media (min-width: 1024px) { .cl-lessons { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
            .cl-lesson { display: flex; flex-direction: column; gap: .625rem; color: inherit; text-decoration: none; }
            .cl-lesson[hidden] { display: none; }
            .cl-lesson .pic { position: relative; aspect-ratio: 16 / 10; overflow: hidden; border-radius: 1rem; background: #020617; border: 1px solid rgba(226, 232, 240, .8); box-shadow: 0 1px 2px rgba(0, 0, 0, .05); transition: box-shadow .3s ease; }
            html.dark .cl-lesson .pic { border-color: #1E293B; }
            .cl-lesson:hover .pic { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, .1), 0 4px 6px -4px rgba(0, 0, 0, .1); }
            .cl-lesson .pic img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s ease; }
            .cl-lesson:hover .pic img { transform: scale(1.04); }
            .cl-lesson .pic .cl-app { position: absolute; top: .625rem; left: .625rem; padding: .125rem .5rem; font-size: 10px; }
            .cl-lesson .hov { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0, 0, 0, .25); opacity: 0; transition: opacity .15s ease; }
            .cl-lesson:hover .hov, .cl-lesson:focus-visible .hov { opacity: 1; }
            .cl-lesson .hov span { display: flex; width: 2.75rem; height: 2.75rem; align-items: center; justify-content: center; border-radius: 9999px; background: rgba(255, 255, 255, .95); color: #020617; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, .1); transform: scale(.9); transition: transform .15s ease; }
            .cl-lesson:hover .hov span { transform: scale(1); }
            .cl-lesson .hov svg { width: 1rem; height: 1rem; margin-left: .125rem; fill: currentColor; }
            .cl-lesson h3 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin: 0; font-size: .875rem; line-height: 1.375; font-weight: 700; color: #0F172A; transition: color .15s ease; }
            html.dark .cl-lesson h3 { color: #fff; }
            @media (min-width: 640px) { .cl-lesson h3 { font-size: 1rem; } }
            .cl-lesson:hover h3 { color: #1877F2; }
            html.dark .cl-lesson:hover h3 { color: #4DA3FF; }
            .cl-lesson small { display: flex; align-items: center; gap: .375rem; margin-top: .25rem; font-size: .75rem; font-weight: 500; color: #64748B; }
            html.dark .cl-lesson small { color: #94A3B8; }
            .cl-lesson small svg { width: .75rem; height: .75rem; fill: #94A3B8; color: #94A3B8; }

            /* the promotion banner keeps its own look; give it room here */
            .cl-promo .pbn { margin: 0; }

            html:lang(km) .hm-body :is(.cl-hero h1, .cl-sechead h2, .cl-feat h3) { line-height: 1.6; }
            html:lang(km) .hm-body :is(.cl-body h3, .cl-path h3) { line-height: 1.7; }
            html:lang(km) .hm-body :is(.cl-mainhead h2) { letter-spacing: 0; text-transform: none; }
            html:lang(km) .hm-body :is(.cl-pill, .cl-chip, .cl-go, .cl-play, .cl-btn-dark, .cl-btn-line) { line-height: 1.4; }
            @media (prefers-reduced-motion: reduce) {
                .cl-dot { animation: none; }
                .cl-card:hover, .cl-path:hover { transform: none; }
                .cl-track { scroll-behavior: auto; }
            }
        </style>
    </x-slot:styles>

    <div id="cl-page" data-cl-page>
        {{-- ================= Hero: search, filters, main courses ================= --}}
        <section class="cl-hero">
            <div class="cl-wrap">
                <div class="cl-toprow">
                    <button type="button" class="cl-pill" data-cl-all aria-pressed="false">
                        {!! $icon('list', 'i35') !!}<span class="{{ $km }}" data-cl-all-label>{{ $L('Browse all courses', 'ស្វែងយល់ពីវគ្គសិក្សាទាំងអស់') }}</span>
                    </button>
                    <button type="button" class="cl-link" data-cl-all>
                        {!! $icon('book', 'i35') !!}<span class="{{ $km }}">{{ $L('45h Masterclasses', 'វគ្គបណ្តុះបណ្តាល ៤៥ ម៉ោង') }}</span>
                    </button>
                </div>

                <h1 class="{{ $km }}">{{ $L('Start your learning journey here', 'ចាប់ផ្តើមដំណើរការសិក្សារបស់អ្នកនៅទីនេះ') }}</h1>

                <form method="GET" action="{{ route('courses') }}" class="cl-search"
                    x-data="{
                        sq: @js($search),
                        sOpen: false,
                        fOpen: false,
                        sIndex: @js($searchIndex),
                        get sMatches() {
                            const needle = this.sq.trim().toLowerCase();
                            if (needle === '') return [];
                            return this.sIndex.filter(c => c.title.toLowerCase().includes(needle) || (c.category || '').toLowerCase().includes(needle)).slice(0, 8);
                        }
                    }"
                    @click.outside="sOpen = false; fOpen = false" @keydown.escape="sOpen = false; fOpen = false">
                    <div class="cl-box">
                        <input type="search" name="q" x-model="sq" @input="sOpen = sq.trim() !== ''; fOpen = false" @focus="sOpen = sq.trim() !== ''"
                            autocomplete="off" aria-label="{{ $L('Search courses', 'ស្វែងរកវគ្គសិក្សា') }}"
                            placeholder="{{ $L('What skills would you like to build?', 'តើអ្នកចង់អភិវឌ្ឍជំនាញអ្វីខ្លះ?') }}" class="{{ $km }}">
                        @if ($search !== '')
                            <a href="{{ route('courses', array_filter(['level' => $level !== 'All' ? $level : null, 'free' => $free ? 1 : null, 'price_min' => $priceMin, 'price_max' => $priceMax], fn ($v) => $v !== null)) }}" class="cl-ib" aria-label="{{ $L('Clear search', 'សម្អាតការស្វែងរក') }}">{!! $icon('x', 'i35') !!}</a>
                        @endif
                        <button type="button" class="cl-ib" @click="fOpen = !fOpen; sOpen = false" :aria-expanded="fOpen ? 'true' : 'false'" aria-expanded="false" aria-controls="cl-filters" aria-label="{{ __('Filters') }}" title="{{ __('Filters') }}">
                            <svg class="i4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="21" x2="14" y1="4" y2="4"/><line x1="10" x2="3" y1="4" y2="4"/><line x1="21" x2="12" y1="12" y2="12"/><line x1="8" x2="3" y1="12" y2="12"/><line x1="21" x2="16" y1="20" y2="20"/><line x1="12" x2="3" y1="20" y2="20"/><line x1="14" x2="14" y1="2" y2="6"/><line x1="8" x2="8" y1="10" y2="14"/><line x1="16" x2="16" y1="18" y2="22"/></svg>
                            @if ($activeFilterCount > 0)
                                <b>{{ $activeFilterCount }}</b>
                            @endif
                        </button>
                        <button type="submit" class="cl-ib" aria-label="{{ $L('Search', 'ស្វែងរក') }}">{!! $icon('search') !!}</button>
                    </div>

                    <div class="cl-pop cl-sug" x-show="sOpen" x-cloak x-transition.origin.top>
                        <template x-for="course in sMatches" :key="course.slug">
                            <a :href="'/courses/' + course.slug"><span x-text="course.title"></span><span x-text="course.category"></span></a>
                        </template>
                        <p x-show="sMatches.length === 0">{{ $clText['none'] }}</p>
                    </div>

                    <div class="cl-pop" id="cl-filters" x-show="fOpen" x-cloak x-transition.origin.top>
                        <div class="cl-filters">
                            <div>
                                <h4 class="{{ $km }}">{{ __('Course Level') }}</h4>
                                <div class="cl-opts">
                                    <label class="cl-opt"><input type="radio" name="level" value="All" @checked($level === 'All')><span>{{ __('Any level') }}</span></label>
                                    @foreach ($levels as $lvl)
                                        @if ($lvl !== 'All')
                                            <label class="cl-opt"><input type="radio" name="level" value="{{ $lvl }}" @checked($level === $lvl)><span>{{ __($lvl) }}</span></label>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <h4 class="{{ $km }}">{{ __('Price') }}</h4>
                                <div class="cl-opts">
                                    <label class="cl-opt"><input type="checkbox" name="free" value="1" @checked($free)><span>{{ __('Free courses only') }}</span></label>
                                </div>
                            </div>
                            <div>
                                <h4 class="{{ $km }}">{{ __('Price range (USD)') }}</h4>
                                <div class="cl-range">
                                    <input type="number" name="price_min" min="0" step="1" inputmode="numeric" placeholder="{{ __('Min') }}" value="{{ $priceMin }}" aria-label="{{ __('Min') }}">
                                    <span aria-hidden="true">–</span>
                                    <input type="number" name="price_max" min="0" step="1" inputmode="numeric" placeholder="{{ __('Max') }}" value="{{ $priceMax }}" aria-label="{{ __('Max') }}">
                                </div>
                            </div>
                        </div>
                        <div class="cl-fbtns">
                            <a href="{{ route('courses') }}" class="cl-btn-line">{{ __('Clear') }}</a>
                            <button type="submit" class="cl-btn-dark">{{ __('Apply filters') }}</button>
                        </div>
                    </div>
                </form>

                @if ($hasFilters)
                    <div class="cl-active">
                        <span class="{{ $km }}">{{ $L('Showing', 'កំពុងបង្ហាញ') }} <b>{{ $num($courses->count()) }}</b> {{ $L($courses->count() === 1 ? 'course' : 'courses', 'វគ្គសិក្សា') }}</span>
                        @if ($search !== '')
                            <a href="{{ route('courses', array_filter(['level' => $level !== 'All' ? $level : null, 'free' => $free ? 1 : null, 'price_min' => $priceMin, 'price_max' => $priceMax], fn ($v) => $v !== null)) }}">“{{ $search }}” {!! $icon('x', 'i3') !!}</a>
                        @endif
                        @if ($level !== 'All')
                            <a href="{{ route('courses', array_filter(['q' => $search ?: null, 'free' => $free ? 1 : null, 'price_min' => $priceMin, 'price_max' => $priceMax], fn ($v) => $v !== null)) }}">{{ __($level) }} {!! $icon('x', 'i3') !!}</a>
                        @endif
                        @if ($free)
                            <a href="{{ route('courses', array_filter(['q' => $search ?: null, 'level' => $level !== 'All' ? $level : null, 'price_min' => $priceMin, 'price_max' => $priceMax], fn ($v) => $v !== null)) }}">{{ __('Free') }} {!! $icon('x', 'i3') !!}</a>
                        @endif
                        @if ($priceMin !== null || $priceMax !== null)
                            <a href="{{ route('courses', array_filter(['q' => $search ?: null, 'level' => $level !== 'All' ? $level : null, 'free' => $free ? 1 : null], fn ($v) => $v !== null)) }}">${{ $priceMin ?? 0 }} – {{ $priceMax !== null ? '$'.$priceMax : '∞' }} {!! $icon('x', 'i3') !!}</a>
                        @endif
                    </div>
                @endif

                {{-- The live promotion from Admin > Promotions. --}}
                @if ($promotion ?? null)
                    <div class="cl-promo">@include('partials._course-promo-banner')</div>
                @endif

                <div id="cl-courses">
                    <div class="cl-allhead">
                        <div>
                            <h2 class="{{ $km }}">{{ $L('Comprehensive Studio Masterclasses', 'វគ្គបណ្តុះបណ្តាលពេញលេញ (Full Studio Curriculums)') }}</h2>
                            <p class="{{ $km }}">{{ $L('45-hour deep-dive masterclasses with 1-on-1 critique and verified graduation certificates.', 'កម្មវិធីសិក្សាកម្រិតសាកលវិទ្យាល័យ ៤៥ ម៉ោង ជាមួយការកែលម្អផ្ទាល់ពីលោកគ្រូ ពិសិដ្ឋ។') }}</p>
                        </div>
                        <button type="button" class="cl-link" data-cl-all><span class="{{ $km }}">{{ $L('Back to tutorials', 'ត្រឡប់ទៅ Tutorials') }}</span></button>
                    </div>

                    <div class="cl-mainhead">
                        <div>
                            <span class="cl-dot" aria-hidden="true"></span>
                            <h2 class="{{ $km }}">{{ $L('Our Main Courses (45-Hour Curriculums)', 'វគ្គសិក្សាសំខាន់ៗរបស់យើង (Masterclass ៤៥ ម៉ោង)') }}</h2>
                        </div>
                        <span class="cl-count {{ $km }}">{{ $L($courses->count().' Official '.($courses->count() === 1 ? 'Program' : 'Programs'), $num($courses->count()).' វគ្គសិក្សាផ្លូវការ') }}</span>
                    </div>

                    @if ($categories->count() > 2 && $courses->isNotEmpty())
                        {{-- Category filter (a promotion's button can open the page on one: ?category=Illustrator). --}}
                        <div class="hm-tabs cl-tabs" data-jelly-tabs data-target="#cl-grid [data-category]">
                            <div class="hm-tabs-track" role="tablist" aria-label="{{ $L('Course categories', 'ប្រភេទវគ្គសិក្សា') }}">
                                <span class="hm-jelly-trail" aria-hidden="true"></span>
                                <span class="hm-jelly" aria-hidden="true"><span class="hm-jelly-blob"></span><span class="hm-sparks"></span></span>
                                @foreach ($categories as $category)
                                    <button type="button" class="hm-tab {{ $km }}" role="tab" data-value="{{ $category === 'All' ? '__all' : $category }}" aria-selected="{{ $activeCategory === $category ? 'true' : 'false' }}">{{ $category === 'All' ? $L('All', 'ទាំងអស់') : __($category) }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($cards->isEmpty())
                        <div class="cl-empty">
                            <p class="{{ $km }}">{{ $L('No main courses match your search', 'រកមិនឃើញវគ្គសិក្សាដែលត្រូវនឹងពាក្យគន្លឹះនេះទេ') }}</p>
                            <a href="{{ route('courses') }}" class="{{ $km }}">{{ $L('Reset search filter', 'បង្ហាញទាំងអស់ឡើងវិញ') }}</a>
                        </div>
                    @else
                        <div class="cl-grid" id="cl-grid">
                            @foreach ($cards as $card)
                                @php
                                    $course = $card['course'];
                                    $discounted = $course->activeDiscount()?->isDiscount() && $course->isOnSale();
                                    $group = $groupLabel[$card['group']] ?? [$card['group'], $card['group']];
                                @endphp
                                <article class="cl-card{{ $discounted ? ' is-promo' : '' }}" data-category="{{ $course->category }}" @if ($activeCategory !== 'All' && $activeCategory !== $course->category) hidden @endif>
                                    <div class="cl-cover">
                                        <img src="{{ $card['photo'] }}" alt="" loading="lazy" decoding="async">
                                        {{-- Blue ribbon, only on a course a live discount applies to. --}}
                                        <x-discount-ribbon :course="$course" />
                                        <div class="cl-tl">
                                            @foreach ($card['chips'] as $chip)
                                                <span class="cl-sw cl-mono" data-app="{{ strtoupper($chip) }}" title="{{ $appName[strtoupper($chip)] ?? $chip }}">{{ $chip }}</span>
                                            @endforeach
                                        </div>
                                        @if ($card['duration'] !== '')
                                            <div class="cl-tr"><span class="cl-dur">{{ $card['duration'] }}</span></div>
                                        @endif
                                        <div class="cl-bl"><span class="cl-tag {{ $km }}">{{ $L($group[0], $group[1]) }}</span></div>
                                        @if ($card['enrolled'])
                                            <div class="cl-br"><span class="cl-enr">{!! $icon('check-circle', 'i3') !!}<span class="{{ $km }}">{{ $L('Enrolled', 'បានចុះឈ្មោះ') }}</span></span></div>
                                        @endif
                                    </div>
                                    <div class="cl-body">
                                        <div>
                                            <h3 class="{{ $km }}"><a href="{{ $card['url'] }}">{{ $card['title'] }}</a></h3>
                                            <p class="cl-tools">{{ $card['tools'] }}</p>
                                            @if ($card['description'] !== '')
                                                <p class="cl-desc">{{ $card['description'] }}</p>
                                            @endif
                                            <div class="cl-meta">
                                                <span>{!! $icon('book', 'i35') !!}<span class="{{ $km }}">{{ $num($card['lessons']) }} {{ $L('Lessons', 'មេរៀន') }}</span></span>
                                                @if ($card['enrolled'])
                                                    <span class="prog">{!! $icon('trending-up', 'i35') !!}{{ $card['progress'] }}%</span>
                                                @elseif ($card['level'] !== '')
                                                    <span class="lvl">{!! $icon('chart', 'i35') !!}{{ $card['level'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="cl-foot">
                                            <div class="cl-price cl-mono">
                                                @if ($course->isFree())
                                                    <span class="free">{{ __('Free') }}</span>
                                                @elseif ($course->isOnSale())
                                                    <span class="sr-only">{{ __('Regular price') }}</span><s>{{ $course->priceLabel() }}</s><span class="sr-only">{{ __('Sale price') }}</span><span>{{ $course->salePriceLabel() }}</span>
                                                @else
                                                    <span>{{ $course->priceLabel() }}</span>
                                                @endif
                                            </div>
                                            @if ($card['enrolled'])
                                                <a href="{{ $card['continue'] }}" class="cl-go is-on"><span class="{{ $km }}">{{ $L('Continue', 'បន្តការរៀន') }}</span>{!! $icon('arrow-up-right', 'i35') !!}</a>
                                            @else
                                                <a href="{{ $card['url'] }}" class="cl-go"><span class="{{ $km }}">{{ $L('Explore', 'ស្វែងយល់') }}</span>{!! $icon('arrow-up-right', 'i35') !!}</a>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>

        @if ($cards->isNotEmpty())
            <div class="cl-tutorials">
                <div class="cl-wrap">
                    {{-- ================= Learning paths (course modules) ================= --}}
                    @if ($paths->isNotEmpty())
                        <section aria-labelledby="cl-paths-title">
                            <div class="cl-sechead">
                                <h2 id="cl-paths-title" class="{{ $km }}">{{ $L('Deepen your skills with a learning path', 'ពង្រឹងជំនាញរបស់អ្នកជាមួយគន្លងសិក្សា') }}</h2>
                                <p class="{{ $km }}">{{ $L('Master your craft through curated, sequential courses designed for all levels.', 'ពង្រឹងជំនាញរបស់អ្នកតាមរយៈវគ្គសិក្សាជាប្រព័ន្ធ រៀបចំឡើងសម្រាប់គ្រប់កម្រិត។') }}</p>
                            </div>
                            <div class="cl-car" data-cl-carousel style="margin-top: 2.5rem">
                                <div class="cl-arrows">
                                    <button type="button" class="cl-arrow" data-prev aria-label="{{ $L('Previous learning paths', 'គន្លងសិក្សាមុន') }}">{!! $icon('chevron-left') !!}</button>
                                    <button type="button" class="cl-arrow" data-next aria-label="{{ $L('Next learning paths', 'គន្លងសិក្សាបន្ទាប់') }}">{!! $icon('chevron-right') !!}</button>
                                </div>
                                <div class="cl-track cl-track--4" data-track>
                                    @foreach ($paths as ['card' => $card, 'module' => $module])
                                        @php
                                            $first = $module->lessons->first();
                                            $pathUrl = ($card['enrolled'] && $first && filled($first->getRouteKey())) ? route('courses.lessons.show', [$card['course'], $first]) : $card['url'];
                                            $chip = $card['chips'][0] ?? 'Pd';
                                        @endphp
                                        <article class="cl-path">
                                            <div class="pic">
                                                <img src="{{ $card['photo'] }}" alt="" loading="lazy" decoding="async">
                                                <span class="cl-app cl-mono" data-app="{{ strtoupper($chip) }}">{{ $chip }}</span>
                                            </div>
                                            <div class="txt">
                                                <div>
                                                    <h3 class="{{ $km }}">{{ $module->localized('title') }}</h3>
                                                    <div class="info">
                                                        <span>{!! $icon('grad', 'i35') !!}<span class="{{ $km }}">{{ $L('Learning path', 'គន្លងសិក្សា') }}</span></span>
                                                        <span aria-hidden="true">·</span>
                                                        <span>{!! $icon('clock', 'i3') !!}<span class="{{ $km }}">{{ $minutesLabel((int) $module->lessons->sum('duration_minutes')) }}</span></span>
                                                    </div>
                                                    <p class="{{ $km }}">{{ $card['title'] }} — {{ $module->lessons->map(fn ($lesson) => $lesson->localized('title'))->implode(' · ') }}</p>
                                                </div>
                                                <div class="act">
                                                    <a href="{{ $pathUrl }}" class="cl-btn-line"><span class="{{ $km }}">{{ $L('Start now', 'ចាប់ផ្តើមរៀន') }}</span></a>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                                <div class="cl-dots" data-dots></div>
                            </div>
                        </section>
                    @endif

                    {{-- ================= Featured courses slider ================= --}}
                    <section aria-label="{{ $L('Featured courses', 'វគ្គសិក្សាពិសេស') }}">
                        <div class="cl-car" data-cl-carousel data-autoplay="7000">
                            <div class="cl-track cl-track--1" data-track>
                                @foreach ($featured as $card)
                                    @php
                                        $course = $card['course'];
                                        $chip = $card['chips'][0] ?? 'Pd';
                                        $isNew = $course->created_at && $course->created_at->greaterThan(now()->subDays(60));
                                    @endphp
                                    <article class="cl-feat">
                                        <div class="art">
                                            <img src="{{ str_replace('w=800', 'w=1000', $card['photo']) }}" alt="" loading="lazy" decoding="async">
                                            <span class="cl-new">{{ $isNew ? $L('New', 'ថ្មី') : $L('Featured', 'ពិសេស') }}</span>
                                        </div>
                                        <div class="txt">
                                            <div>
                                                <div class="who"><span class="cl-app cl-mono" data-app="{{ strtoupper($chip) }}">{{ $chip }}</span><span>{{ $card['tools'] }}</span></div>
                                                <h3 class="{{ $km }}">{{ $card['title'] }}</h3>
                                                @if ($card['description'] !== '')
                                                    <p class="{{ $km }}">{{ $card['description'] }}</p>
                                                @endif
                                            </div>
                                            <div class="bar">
                                                <a href="{{ $card['enrolled'] ? $card['continue'] : $card['url'] }}" class="cl-play">{!! $icon('play') !!}<span class="{{ $km }}">{{ $card['enrolled'] ? $L('Continue', 'បន្តការរៀន') : $L('Watch now', 'ទស្សនាឥឡូវនេះ') }}</span></a>
                                                <small class="{{ $hmKm ? 'hm-km' : 'cl-mono' }}">{{ $card['duration'] !== '' ? $card['duration'] : $num($card['lessons']).' '.$L('lessons', 'មេរៀន') }}</small>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                            <div class="cl-dots" data-dots style="padding-top: 1rem"></div>
                        </div>
                    </section>

                    {{-- ================= Latest lessons ================= --}}
                    @if ($lessonsByBucket->isNotEmpty())
                        <section aria-labelledby="cl-latest-title" data-cl-latest>
                            <div class="cl-sechead">
                                <h2 id="cl-latest-title" class="{{ $km }}">{{ $L('Explore the latest tutorials', 'ស្វែងយល់ពីមេរៀនថ្មីៗបំផុត') }}</h2>
                            </div>
                            <div class="cl-chips" role="group" aria-label="{{ $L('Lesson topics', 'ប្រធានបទមេរៀន') }}">
                                @foreach ($lessonsByBucket as $bucket => $rows)
                                    <button type="button" class="cl-chip" data-bucket="{{ $bucket }}" aria-pressed="{{ $bucket === $firstBucket ? 'true' : 'false' }}">
                                        {!! $icon($buckets[$bucket][0], 'i35') !!}<span class="{{ $km }}">{{ $L($buckets[$bucket][1], $buckets[$bucket][2]) }}</span>
                                    </button>
                                @endforeach
                            </div>
                            <div class="cl-lessons">
                                @foreach ($lessonsByBucket as $bucket => $rows)
                                    @foreach ($rows as ['lesson' => $lesson, 'card' => $card])
                                        @php
                                            $chip = $card['chips'][0] ?? 'Pd';
                                            $yt = $lesson->youtubeId();
                                            $lessonUrl = ($card['enrolled'] && filled($lesson->getRouteKey())) ? route('courses.lessons.show', [$card['course'], $lesson]) : $card['url'];
                                        @endphp
                                        <a href="{{ $lessonUrl }}" class="cl-lesson" data-bucket="{{ $bucket }}" @if ($bucket !== $firstBucket) hidden @endif>
                                            <span class="pic">
                                                <img src="{{ $yt ? 'https://i.ytimg.com/vi/'.$yt.'/hqdefault.jpg' : $card['photo'] }}" alt="" loading="lazy" decoding="async">
                                                <span class="cl-app cl-mono" data-app="{{ strtoupper($chip) }}">{{ $chip }}</span>
                                                <span class="hov" aria-hidden="true"><span><svg viewBox="0 0 24 24"><polygon points="6 3 20 12 6 21 6 3"/></svg></span></span>
                                            </span>
                                            <span>
                                                <h3 class="{{ $km }}">{{ $lesson->localized('title') }}</h3>
                                                <small><svg viewBox="0 0 24 24" aria-hidden="true"><polygon points="6 3 20 12 6 21 6 3"/></svg><span class="{{ $km }}">{{ $L('Video', 'វីដេអូ') }} · {{ $minutesLabel(max(1, (int) $lesson->duration_minutes)) }}</span></small>
                                            </span>
                                        </a>
                                    @endforeach
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- ================= Digital Academy: a certificate for every course ================= --}}
                    <section aria-labelledby="cl-acad-title">
                        <div class="cl-sechead">
                            <h2 id="cl-acad-title" class="{{ $km }}">{{ $L('Earn a certificate with Piseth.Design Digital Academy', 'ទទួលបានវិញ្ញាបនបត្របញ្ជាក់សមត្ថភាព (Digital Academy)') }}</h2>
                            <p class="{{ $km }}">{{ $L('Validate your industry skills with sequential, portfolio-ready courses and a verified certificate.', 'បញ្ជាក់សមត្ថភាពរបស់អ្នកតាមរយៈមេរៀនអនុវត្តជាក់ស្តែង និងទទួលបានវិញ្ញាបនបត្រផ្លូវការ។') }}</p>
                        </div>
                        <div class="cl-car" data-cl-carousel style="margin-top: 1.5rem">
                            <div class="cl-arrows cl-arrows--lg">
                                <button type="button" class="cl-arrow" data-prev aria-label="{{ $L('Previous course', 'វគ្គមុន') }}">{!! $icon('chevron-left') !!}</button>
                                <button type="button" class="cl-arrow" data-next aria-label="{{ $L('Next course', 'វគ្គបន្ទាប់') }}">{!! $icon('chevron-right') !!}</button>
                            </div>
                            <div class="cl-track cl-track--1" data-track>
                                @foreach ($cards as $card)
                                    @php $chip = $card['chips'][0] ?? 'Pd'; @endphp
                                    <article class="cl-feat cl-acad" data-app="{{ strtoupper($chip) }}">
                                        <div class="art">
                                            <img src="{{ str_replace('w=800', 'w=1000', $card['photo']) }}" alt="" loading="lazy" decoding="async">
                                            <span class="cl-app cl-mono" data-app="{{ strtoupper($chip) }}">{{ $chip }}</span>
                                            <div class="cl-glass">
                                                <div class="row"><span class="{{ $km }}">{{ $card['enrolled'] ? $L('Your progress', 'វឌ្ឍនភាពរបស់អ្នក') : $L('Course length', 'រយៈពេលវគ្គ') }}</span><b class="{{ $hmKm ? 'hm-km' : 'cl-mono' }}">{{ $card['enrolled'] ? $card['progress'].'%' : ($card['duration'] !== '' ? $card['duration'] : $card['lessons']) }}</b></div>
                                                <div class="track"><i style="width: {{ $card['enrolled'] ? max(4, $card['progress']) : 80 }}%"></i></div>
                                                <small>{!! $icon('check-circle', 'i3') !!}<span class="{{ $km }}">{{ $L('Verified Digital Credential', 'វិញ្ញាបនបត្រឌីជីថលផ្លូវការ') }}</span></small>
                                            </div>
                                        </div>
                                        <div class="txt">
                                            <div>
                                                <div class="who"><span class="cl-app cl-mono" data-app="{{ strtoupper($chip) }}">{{ $chip }}</span><span>{{ $card['tools'] }}</span><i>·</i><em>Digital Academy</em></div>
                                                <h3 class="{{ $km }}">{{ $card['title'] }}</h3>
                                                @if ($card['description'] !== '')
                                                    <p class="{{ $km }}">{{ $card['description'] }}</p>
                                                @endif
                                            </div>
                                            <div class="bar line">
                                                <a href="{{ $card['enrolled'] ? $card['continue'] : $card['url'] }}" class="cl-play cl-play--lg">{!! $icon('play') !!}<span class="{{ $km }}">{{ $card['enrolled'] ? $L('Continue', 'បន្តការរៀន') : $L('Learn now', 'រៀនឥឡូវនេះ') }}</span></a>
                                                <small>{!! $icon('grad', 'i35') !!}<span class="{{ $hmKm ? 'hm-km' : 'cl-mono' }}">{{ $num($card['lessons']) }} {{ $L('lessons', 'មេរៀន') }}</span></small>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                            <div class="cl-dots" data-dots style="padding-top: 1rem"></div>
                        </div>
                    </section>
                </div>
            </div>
        @endif
    </div>

    <x-slot:scripts>
        <script>
            (function () {
                'use strict';
                var text = @json($clText);
                var page = document.querySelector('[data-cl-page]');
                if (!page) return;

                /* ---- "Browse all courses": the whole catalogue in two wide columns ---- */
                var allLabel = page.querySelector('[data-cl-all-label]');
                var allText = allLabel ? [allLabel.textContent, text.back] : [];
                page.querySelectorAll('[data-cl-all]').forEach(function (b) {
                    b.addEventListener('click', function () {
                        var on = page.classList.toggle('is-all');
                        page.querySelectorAll('[data-cl-all][aria-pressed]').forEach(function (x) { x.setAttribute('aria-pressed', on ? 'true' : 'false'); });
                        if (allLabel) allLabel.textContent = allText[on ? 1 : 0];
                        if (!on) return;
                        var top = document.getElementById('cl-courses').getBoundingClientRect().top + scrollY - 90;
                        scrollTo({ top: top, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
                    });
                });

                /* ---- carousels: arrows, page dots, optional autoplay ---- */
                page.querySelectorAll('[data-cl-carousel]').forEach(function (car) {
                    var track = car.querySelector('[data-track]');
                    var dots = car.querySelector('[data-dots]');
                    var prev = car.querySelector('[data-prev]');
                    var next = car.querySelector('[data-next]');
                    var pages = 1, timer = null;

                    function current() { return Math.round(track.scrollLeft / Math.max(1, track.clientWidth)); }
                    function go(i) {
                        i = Math.max(0, Math.min(pages - 1, i));
                        track.scrollTo({ left: i * track.clientWidth, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
                    }
                    function paint() {
                        var i = current();
                        if (prev) prev.disabled = i <= 0;
                        if (next) next.disabled = i >= pages - 1;
                        [].forEach.call(dots.children, function (d, n) { d.setAttribute('aria-current', n === i ? 'true' : 'false'); });
                    }
                    function build() {
                        pages = Math.max(1, Math.ceil((track.scrollWidth - 4) / Math.max(1, track.clientWidth)));
                        dots.innerHTML = '';
                        dots.hidden = pages < 2;
                        for (var n = 0; n < pages; n++) {
                            var d = document.createElement('button');
                            d.type = 'button';
                            d.setAttribute('aria-label', text.slide + ' ' + (n + 1));
                            d.addEventListener('click', go.bind(null, n));
                            dots.appendChild(d);
                        }
                        paint();
                    }
                    if (prev) prev.addEventListener('click', function () { go(current() - 1); });
                    if (next) next.addEventListener('click', function () { go(current() + 1); });
                    var raf = 0;
                    track.addEventListener('scroll', function () { cancelAnimationFrame(raf); raf = requestAnimationFrame(paint); }, { passive: true });
                    window.addEventListener('resize', build);
                    build();

                    var every = parseInt(car.getAttribute('data-autoplay'), 10);
                    if (every && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        var tick = function () { if (pages > 1) go(current() >= pages - 1 ? 0 : current() + 1); };
                        var start = function () { stop(); timer = setInterval(tick, every); };
                        var stop = function () { if (timer) clearInterval(timer); timer = null; };
                        car.addEventListener('mouseenter', stop);
                        car.addEventListener('mouseleave', start);
                        car.addEventListener('focusin', stop);
                        car.addEventListener('touchstart', stop, { passive: true });
                        start();
                    }
                });

                /* ---- latest lessons: topic chips ---- */
                var latest = page.querySelector('[data-cl-latest]');
                if (latest) {
                    latest.addEventListener('click', function (e) {
                        var chip = e.target.closest('.cl-chip');
                        if (!chip) return;
                        var b = chip.getAttribute('data-bucket');
                        latest.querySelectorAll('.cl-chip').forEach(function (c) { c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); });
                        latest.querySelectorAll('.cl-lesson').forEach(function (l) { l.hidden = l.getAttribute('data-bucket') !== b; });
                    });
                }
            })();
        </script>
    </x-slot:scripts>
</x-live-layout>
