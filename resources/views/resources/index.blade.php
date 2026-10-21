{{-- Resources library, built on the live piseth.design design (components/live-layout). --}}
@php
    $hmKm = app()->getLocale() === 'km';
    $L = fn (string $en, string $km) => $hmKm ? $km : $en;
    $icon = fn (string $name, string $class = 'i4') => \App\Support\LiveIcon::svg($name, $class);
    $me = auth()->user();
    $isAdmin = (bool) $me?->isAdmin();

    $items = $resources->map(fn ($r) => [
        'id' => $r->uuid,
        'title' => $r->title_en,
        'titleKh' => $r->title_km,
        'description' => $r->localized('description'),
        'type' => $r->type,
        'software' => $r->software,
        'category' => $r->category,
        'tags' => $r->tagList(),
        'size' => $r->sizeLabel(),
        'picture' => $r->pictureSrc(),
        'featured' => $r->is_featured,
        'downloads' => $r->downloads_count,
        'saved' => in_array($r->id, $saved, true),
        'link' => $r->isLink(),
        'downloadUrl' => $r->isDownloadable() ? route('resources.download', $r) : null,
        'bookmarkUrl' => route('resources.bookmark', $r),
    ])->values();

    $text = [
        'downloads' => $L('downloads', 'ដង'),
        'totalDownloads' => $L('total downloads', 'ដងនៃការទាញយក'),
        'downloading' => $L('Downloading', 'កំពុងទាញយក'),
        'opening' => $L('Opening', 'កំពុងបើក'),
        'bookmark' => $L('Bookmark', 'កត់ចំណាំ'),
        'bookmarked' => $L('Bookmarked', 'បានកត់ចំណាំ'),
    ];
@endphp

<x-live-layout :title="$L('Design Resources Library', 'បណ្ណាល័យធនធានរចនា').' · Piseth.Design'" active="resources" main-class="rs-main">
    <x-slot:styles>
        <style>
            .rs-main { font-family: Inter, 'Noto Sans Khmer', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.5; -webkit-font-smoothing: antialiased; }
            .rs-page { display: flex; flex-direction: column; gap: 2rem; padding-top: 2.5rem; padding-bottom: 6rem; }
            .rs-head { position: relative; isolation: isolate; display: flex; flex-direction: column; justify-content: space-between; gap: 1.5rem; padding-bottom: 2rem; border-bottom: 1px solid var(--line2); }
            .rs-head .fx-aurora { inset: -2rem 0 0; border-radius: 1.5rem; }
            .rs-intro, .rs-manage { position: relative; z-index: 2; }
            .rs-files { position: absolute; inset: 0; z-index: 1; pointer-events: none; display: none; }
            @media (min-width: 1024px) { .rs-files { display: block; } }
            @media (min-width: 768px) { .rs-head { flex-direction: row; align-items: flex-end; } }
            .rs-intro { max-width: 48rem; display: flex; flex-direction: column; gap: .75rem; }
            .rs-kicker { margin: 0; }
            .rs-kicker .k { color: var(--faint); text-transform: none; letter-spacing: 0; }
            .rs-h1 { margin: 0; font-size: 1.875rem; line-height: 2.25rem; font-weight: 900; color: var(--strong); }
            @media (min-width: 640px) { .rs-h1 { font-size: 2.25rem; line-height: 2.5rem; } }
            .rs-lead { margin: 0; font-size: .875rem; line-height: 1.625; color: #475569; }
            html.dark .rs-lead { color: #94A3B8; }
            @media (min-width: 640px) { .rs-lead { font-size: 1rem; } }
            .rs-manage { flex-shrink: 0; align-self: flex-start; display: inline-flex; align-items: center; gap: .5rem; padding: .625rem 1rem; border-radius: .75rem; background: #F1F5F9; border: 1px solid rgba(226, 232, 240, .8); color: #1E293B; font-size: .75rem; line-height: 1rem; font-weight: 600; text-decoration: none; box-shadow: 0 1px 3px rgba(0, 0, 0, .1); transition: background .15s ease; }
            .rs-manage:hover { background: #E2E8F0; }
            html.dark .rs-manage { background: #1E2530; border-color: #2F3847; color: #fff; }
            html.dark .rs-manage:hover { background: #283241; }
            @media (min-width: 768px) { .rs-manage { align-self: auto; } }
            .rs-manage svg { color: var(--sky); transition: transform .2s ease; }
            .rs-manage:hover svg { transform: rotate(45deg); }
            .rs-manage .tag { padding: 0 .375rem; border-radius: .25rem; background: rgba(77, 163, 255, .15); color: var(--sky); font-size: 10px; font-weight: 700; text-transform: uppercase; }

            .rs-toast { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .875rem; border-radius: 1rem; background: #ECFDF5; border: 1px solid rgba(16, 185, 129, .3); color: #065F46; font-size: .75rem; line-height: 1rem; animation: hm-fade-in .2s ease-out both; }
            html.dark .rs-toast { background: rgba(2, 44, 34, .4); color: #6EE7B7; }
            .rs-toast[hidden] { display: none; }
            .rs-toast > span:first-child { display: flex; align-items: center; gap: .5rem; font-weight: 500; }
            .rs-toast svg { color: #059669; }
            .rs-toast .r { flex-shrink: 0; font-size: 11px; color: #059669; }

            .rs-tools { display: flex; flex-direction: column; gap: 1rem; }
            .rs-bar { display: flex; flex-direction: column; justify-content: space-between; gap: 1rem; }
            @media (min-width: 640px) { .rs-bar { flex-direction: row; align-items: center; } }
            .rs-search { position: relative; flex: 1; max-width: 28rem; }
            .rs-search svg { position: absolute; left: .75rem; top: 50%; transform: translateY(-50%); color: #94A3B8; pointer-events: none; }
            .rs-search input { width: 100%; padding: .5rem 1rem .5rem 2.25rem; border-radius: .75rem; background: #fff; border: 1px solid #E2E8F0; color: var(--strong); font: inherit; font-size: .75rem; line-height: 1rem; }
            html.dark .rs-search input { background: #151B23; border-color: #222936; }
            .rs-search input::placeholder { color: #94A3B8; }
            .rs-search input:focus { outline: none; box-shadow: 0 0 0 1px var(--sky); }
            .rs-actions { display: flex; align-items: center; gap: .5rem; }
            .rs-saved { display: inline-flex; align-items: center; gap: .5rem; padding: .5rem .875rem; border-radius: .75rem; border: 1px solid #E2E8F0; background: #fff; color: #475569; font: inherit; font-size: .75rem; line-height: 1rem; font-weight: 600; text-decoration: none; cursor: pointer; transition: all .15s ease; }
            html.dark .rs-saved { background: #151B23; border-color: #222936; color: #94A3B8; }
            .rs-saved[aria-pressed="true"] { background: #EAF5FF; color: var(--sky); border-color: rgba(77, 163, 255, .4); }
            html.dark .rs-saved[aria-pressed="true"] { background: rgba(77, 163, 255, .2); }
            .rs-saved[aria-pressed="true"] svg { fill: currentColor; }
            .rs-upload { display: inline-flex; align-items: center; gap: .375rem; padding: .5rem .75rem; border-radius: .75rem; background: #4DA3FF; color: #fff; font-size: .75rem; line-height: 1rem; font-weight: 600; text-decoration: none; box-shadow: 0 1px 3px rgba(0, 0, 0, .1); transition: background .15s ease; }
            .rs-upload:hover { background: #3892F3; }

            /* tabs: same jelly motion as the homepage, live colours (dark text on the white pill) */
            .rs-tabs { align-self: stretch; }
            .rs-tabs .hm-tab { min-height: 0; padding: .375rem .875rem; font-weight: 500; }
            .rs-tabs .hm-tab[aria-selected="true"], html.dark .rs-tabs .hm-tab[aria-selected="true"] { color: var(--strong); font-weight: 600; }
            html.dark .rs-tabs .hm-jelly-blob { background: #202834; }

            .rs-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; }
            @media (min-width: 768px) { .rs-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            @media (min-width: 1024px) { .rs-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
            .rs-card { position: relative; display: flex; flex-direction: column; justify-content: space-between; gap: 1rem; overflow: hidden; padding: 1rem; border-radius: 1rem; background: var(--card2); border: 1px solid var(--line2); box-shadow: 0 1px 3px rgba(0, 0, 0, .1), 0 1px 2px -1px rgba(0, 0, 0, .1); transition: all .15s ease; }
            @media (min-width: 640px) { .rs-card { padding: 1.25rem; } }
            .rs-card:hover { border-color: rgba(77, 163, 255, .5); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, .1), 0 4px 6px -4px rgba(0, 0, 0, .1); }
            .rs-card[hidden] { display: none; }
            .rs-card.is-in { animation: hm-pop-in 420ms cubic-bezier(.22, 1, .36, 1) both; }
            .rs-thumb { position: relative; aspect-ratio: 16 / 10; width: 100%; overflow: hidden; border-radius: .75rem; background: #0F172A; border: 1px solid #F1F5F9; box-shadow: 0 1px 2px rgba(0, 0, 0, .05); }
            .rs-hit { position: absolute; inset: 0; display: block; width: 100%; height: 100%; padding: 0; border: 0; background: none; cursor: pointer; }
            .rs-hit:focus-visible { outline: 2px solid var(--sky); outline-offset: -2px; }
            html.dark .rs-thumb { border-color: rgba(30, 41, 59, .8); }
            .rs-thumb img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s ease-out; }
            .rs-card:hover .rs-thumb img { transform: scale(1.05); }
            .rs-thumb .shade { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0, 0, 0, .85), rgba(0, 0, 0, .2), rgba(0, 0, 0, .45)); }
            .rs-thumb .top { position: absolute; z-index: 10; top: .625rem; left: .625rem; right: .625rem; display: flex; align-items: center; justify-content: space-between; gap: .375rem; pointer-events: none; }
            .rs-thumb .top .rs-mark { pointer-events: auto; }
            .rs-thumb .chips { display: flex; flex-wrap: wrap; align-items: center; gap: .375rem; }
            .rs-chip { padding: .125rem .5rem; border-radius: .375rem; background: rgba(0, 0, 0, .65); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, .15); font-size: 10px; font-weight: 500; color: rgba(255, 255, 255, .9); }
            .rs-chip--sw { font-weight: 700; color: #4DA3FF; border-color: rgba(77, 163, 255, .3); }
            .rs-chip--feat { display: inline-flex; align-items: center; gap: .25rem; padding: .125rem .375rem; background: #FBBF24; border: 0; color: #451A03; font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; box-shadow: 0 1px 3px rgba(0, 0, 0, .1); backdrop-filter: none; }
            .rs-chip--feat svg { width: .625rem; height: .625rem; }
            .rs-mark { flex-shrink: 0; display: flex; padding: .375rem; border-radius: .5rem; border: 1px solid rgba(255, 255, 255, .2); background: rgba(0, 0, 0, .55); backdrop-filter: blur(12px); color: rgba(255, 255, 255, .8); text-decoration: none; cursor: pointer; transition: all .15s ease; }
            .rs-mark:hover { background: rgba(0, 0, 0, .8); color: #fff; }
            .rs-mark[aria-pressed="true"] { background: #4DA3FF; border-color: #4DA3FF; color: #fff; }
            .rs-mark[aria-pressed="true"] svg { fill: currentColor; }
            .rs-thumb .bottom { position: absolute; z-index: 10; bottom: .5rem; left: .625rem; right: .625rem; display: flex; align-items: center; justify-content: space-between; }
            .rs-thumb .bottom .rs-chip { font-weight: 600; }
            .rs-peek { display: inline-flex; align-items: center; gap: .25rem; padding: .125rem .5rem; border-radius: .375rem; background: rgba(0, 0, 0, .75); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, .2); font-size: 10px; font-weight: 600; color: #4DA3FF; opacity: 0; transition: opacity .15s ease; }
            .rs-card:hover .rs-peek, .rs-hit:focus-visible .rs-peek { opacity: 1; }
            .rs-body { flex: 1; display: flex; flex-direction: column; gap: .375rem; }
            .rs-title { margin: 0; padding: 0; border: 0; background: none; font: inherit; text-align: left; font-size: .875rem; line-height: 1.375; font-weight: 700; color: var(--strong); cursor: pointer; transition: color .15s ease; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
            @media (min-width: 640px) { .rs-title { font-size: 1rem; } }
            .rs-card:hover .rs-title { color: var(--sky); }
            .rs-kh { margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .75rem; line-height: 1rem; color: #94A3B8; }
            .rs-desc { margin: 0; padding-top: .25rem; font-size: .75rem; line-height: 1.625; color: #475569; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
            html.dark .rs-desc { color: #94A3B8; }
            .rs-tags { display: flex; flex-wrap: wrap; align-items: center; gap: .375rem; padding-top: .25rem; }
            .rs-tags span { padding: .125rem .375rem; border-radius: .25rem; background: #F1F5F9; font-size: 10px; color: #94A3B8; }
            html.dark .rs-tags span { background: #1A212B; color: #64748B; }
            .rs-foot { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding-top: .75rem; border-top: 1px solid var(--line3); }
            .rs-count { font-size: 11px; color: #94A3B8; }
            .rs-dl { display: inline-flex; align-items: center; gap: .375rem; padding: .375rem .875rem; border-radius: .75rem; background: #4DA3FF; color: #fff; font-size: .75rem; line-height: 1rem; font-weight: 600; text-decoration: none; box-shadow: 0 1px 3px rgba(77, 163, 255, .2); transition: all .15s ease; }
            .rs-dl:hover { background: #3892F3; }
            .rs-dl:active { transform: scale(.95); }
            .rs-dl[aria-disabled="true"] { background: #CBD5E1; box-shadow: none; pointer-events: none; }
            html.dark .rs-dl[aria-disabled="true"] { background: #334155; }

            .rs-empty { padding: 4rem 0; text-align: center; display: flex; flex-direction: column; align-items: center; gap: .75rem; }
            .rs-empty[hidden] { display: none; }
            .rs-empty p { margin: 0; }
            .rs-empty .a { font-size: .875rem; font-weight: 600; color: #334155; }
            html.dark .rs-empty .a { color: #CBD5E1; }
            .rs-empty .b { font-size: .75rem; color: #94A3B8; max-width: 32rem; }
            .rs-empty .rs-upload { padding: .5rem 1rem; }

            /* preview dialog */
            .rs-modal { position: fixed; inset: 0; z-index: 70; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(0, 0, 0, .75); backdrop-filter: blur(12px); animation: hm-fade-in .2s ease-out both; font-family: Inter, 'Noto Sans Khmer', sans-serif; }
            .rs-modal[hidden] { display: none; }
            .rs-dialog { width: 100%; max-width: 42rem; max-height: 92vh; overflow-y: auto; border-radius: 1.5rem; background: #fff; border: 1px solid #E2E8F0; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, .25); animation: hm-zoom-in .2s ease-out both; }
            html.dark .rs-dialog { background: #151B23; border-color: #262F3D; }
            .rs-dimg { position: relative; aspect-ratio: 16 / 9; width: 100%; overflow: hidden; background: #020617; }
            .rs-dimg img { width: 100%; height: 100%; object-fit: cover; }
            .rs-dimg .shade { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0, 0, 0, .85), transparent, rgba(0, 0, 0, .4)); }
            .rs-dclose { position: absolute; top: 1rem; right: 1rem; display: flex; padding: .5rem; border-radius: 9999px; border: 1px solid rgba(255, 255, 255, .2); background: rgba(0, 0, 0, .6); backdrop-filter: blur(8px); color: #fff; cursor: pointer; transition: background .15s ease; }
            .rs-dclose:hover { background: rgba(0, 0, 0, .9); }
            .rs-dimg .row { position: absolute; left: 1.25rem; right: 1.25rem; bottom: 1rem; display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
            .rs-dimg .row > div { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
            .rs-dimg .rs-chip { padding: .25rem .625rem; border-radius: .5rem; font-size: .75rem; line-height: 1rem; }
            .rs-dbody { display: flex; flex-direction: column; gap: 1rem; padding: 1.5rem; }
            .rs-dbody h3 { margin: 0; font-size: 1.25rem; line-height: 1.75rem; font-weight: 700; color: var(--strong); }
            .rs-dbody .kh { margin: .25rem 0 0; font-size: .875rem; color: #94A3B8; }
            .rs-dbody .desc { margin: 0; font-size: .75rem; line-height: 1.625; color: #475569; }
            html.dark .rs-dbody .desc { color: #CBD5E1; }
            @media (min-width: 640px) { .rs-dbody .desc { font-size: .875rem; } }
            .rs-dbody .rs-tags span { padding: .125rem .5rem; border-radius: .375rem; font-size: .75rem; color: #64748B; }
            .rs-dfoot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; padding-top: 1rem; border-top: 1px solid var(--line3); }
            .rs-dfoot .rs-count { font-size: .75rem; }
            .rs-dfoot > div { display: flex; align-items: center; gap: .5rem; }
            .rs-dmark { display: inline-flex; align-items: center; gap: .375rem; padding: .5rem .75rem; border-radius: .75rem; border: 1px solid #E2E8F0; background: transparent; color: #475569; font: inherit; font-size: .75rem; line-height: 1rem; font-weight: 600; text-decoration: none; cursor: pointer; transition: all .15s ease; }
            html.dark .rs-dmark { border-color: #222936; color: #94A3B8; }
            .rs-dmark[aria-pressed="true"] { background: #EAF5FF; color: var(--sky); border-color: rgba(77, 163, 255, .4); }
            html.dark .rs-dmark[aria-pressed="true"] { background: rgba(77, 163, 255, .2); }
            .rs-dmark[aria-pressed="true"] svg { fill: currentColor; }
            .rs-dfoot .rs-dl { padding: .5rem 1.25rem; gap: .5rem; box-shadow: 0 4px 6px -1px rgba(77, 163, 255, .2); }

            html:lang(km) .hm-body :is(.rs-h1, .rs-title) { line-height: 1.5; }
            html:lang(km) .hm-body :is(.rs-lead, .rs-desc) { line-height: 1.625; }
            html:lang(km) .hm-body .rs-kicker { letter-spacing: .05em; }
        </style>
    </x-slot:styles>

    <div class="hm-wrap rs-page">

        <div class="rs-head" data-fx-parallax>
            <div class="fx-aurora" aria-hidden="true"><i></i><i></i><i></i></div>
            <div class="rs-files" aria-hidden="true">
                <span class="fx-float a" style="top: 8%; right: 30%; --depth: 14px"><span class="fx-tile" data-app="PS" style="width:2.5rem; height:2.5rem; font-size:.6875rem"><span>.PSD</span></span></span>
                <span class="fx-float c" style="top: 2%; right: 4%; --depth: 20px"><span class="fx-tile" data-app="AI" style="width:2.5rem; height:2.5rem; font-size:.6875rem"><span>.AI</span></span></span>
                <span class="fx-float b" style="top: 40%; right: 18%; --depth: 10px"><span class="fx-tile" data-app="FG" style="width:2.5rem; height:2.5rem; font-size:.6875rem; color:#0ACF83"><span>.FIG</span></span></span>
                <span class="fx-star" style="top: 18%; right: 14%"></span>
                <span class="fx-star" style="top: 50%; right: 34%; animation-delay: 1.2s"></span>
            </div>
            <div class="rs-intro">
                <p class="hm-label rs-kicker"><span>Creative Studio Toolkit</span><span aria-hidden="true">·</span><span class="k hm-km">{{ $L('Curated Design Assets', 'បណ្ណាល័យធនធាន និងឯកសាររចនា') }}</span></p>
                <h1 class="rs-h1 {{ $hmKm ? 'hm-km' : '' }}">@if ($hmKm)បណ្ណាល័យ<span class="fx-grad">ធនធានរចនា</span>@else Design Resources <span class="fx-grad">Library</span>@endif</h1>
                <p class="rs-lead {{ $hmKm ? 'hm-km' : '' }}">{{ $L('Download industry-standard presets, Khmer typography specimens, vector grid kits, and Figma component libraries prepared by Teacher Piseth Yoeurn.', 'ទាញយក Presets ស្តង់ដារ, ពុម្ពអក្សរខ្មែរ, Vector Kit និង Figma Library រៀបចំដោយលោកគ្រូ យឿន ពិសិដ្ឋ។') }}</p>
            </div>
            @if ($isAdmin)
                <a href="{{ route('admin.resources') }}" class="rs-manage">
                    {!! $icon('grid') !!}
                    <span class="hm-km">{{ $L('Manage & Upload Resources', 'ផ្ទាំងគ្រប់គ្រង និងបញ្ចូលឯកសារ (Management Dashboard)') }}</span>
                    <span class="tag hm-mono">Admin</span>
                </a>
            @endif
        </div>

        <div class="rs-toast" id="rs-toast" role="status" aria-live="polite" hidden>
            <span>{!! $icon('check-circle') !!}<span data-toast-text></span></span>
            <span class="r hm-mono">Ready in downloads</span>
        </div>

        @if ($resources->isEmpty())
            <div class="rs-empty">
                <p class="a {{ $hmKm ? 'hm-km' : '' }}">{{ $L('The library is being stocked.', 'បណ្ណាល័យកំពុងរៀបចំឯកសារ។') }}</p>
                <p class="b {{ $hmKm ? 'hm-km' : '' }}">{{ $L('New presets, fonts and templates from Teacher Piseth Yoeurn will appear here soon.', 'Presets ពុម្ពអក្សរ និងគំរូថ្មីៗពីលោកគ្រូ យឿន ពិសិដ្ឋ នឹងបង្ហាញនៅទីនេះឆាប់ៗនេះ។') }}</p>
                @if ($isAdmin)
                    <a href="{{ route('admin.resources.create') }}" class="rs-upload hm-km">+ បញ្ចូលឯកសារថ្មី (Upload)</a>
                @endif
            </div>
        @else
            <div class="rs-tools">
                <div class="rs-bar">
                    <label class="rs-search">
                        <span class="hm-sr">{{ $L('Search resources', 'ស្វែងរកឯកសារ') }}</span>
                        {!! $icon('search') !!}
                        <input type="search" id="rs-q" autocomplete="off" placeholder="{{ $L('Search resources by title, type, or software...', 'ស្វែងរកឯកសារតាមចំណងជើង ប្រភេទ ឬកម្មវិធី...') }}">
                    </label>
                    <div class="rs-actions">
                        @auth
                            <button type="button" class="rs-saved" id="rs-saved" aria-pressed="false">
                                {!! $icon('bookmark', 'i35') !!}
                                <span>{{ $L('Bookmarked Only', 'បានកត់ចំណាំ') }} (<span data-saved-count>{{ count($saved) }}</span>)</span>
                            </button>
                        @else
                            <a href="{{ route('login') }}" class="rs-saved">
                                {!! $icon('bookmark', 'i35') !!}
                                <span>{{ $L('Bookmarked Only', 'បានកត់ចំណាំ') }} (0)</span>
                            </a>
                        @endauth
                        @if ($isAdmin)
                            <a href="{{ route('admin.resources.create') }}" class="rs-upload hm-km">{!! $icon('upload', 'i35') !!}<span>{{ $L('Upload', 'បញ្ចូលឯកសារ') }}</span></a>
                        @endif
                    </div>
                </div>

                <div class="hm-tabs rs-tabs" data-jelly-tabs>
                    <div class="hm-tabs-track" role="tablist" aria-label="{{ $L('Resource category', 'ប្រភេទឯកសារ') }}">
                        <span class="hm-jelly-trail" aria-hidden="true"></span>
                        <span class="hm-jelly" aria-hidden="true"><span class="hm-jelly-blob"></span><span class="hm-sparks"></span></span>
                        <button type="button" role="tab" class="hm-tab" aria-selected="true" data-value="__all"><span>{{ $L('All', 'ទាំងអស់') }}</span></button>
                        @foreach ($categories as $category)
                            <button type="button" role="tab" class="hm-tab" aria-selected="false" tabindex="-1" data-value="{{ $category }}"><span>{{ $category }}</span></button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="rs-grid" id="rs-grid">
                @foreach ($resources as $i => $r)
                    @php $item = $items[$i]; @endphp
                    <article class="rs-card fx-spot" data-fx data-id="{{ $item['id'] }}" data-category="{{ $r->category }}"
                        data-search="{{ mb_strtolower(implode(' ', array_filter([$r->title_en, $r->title_km, $r->type, $r->software, $r->localized('description'), implode(' ', $r->tagList())]))) }}">
                        <div class="rs-thumb">
                            <button type="button" class="rs-hit" data-preview aria-label="{{ $L('Preview', 'មើលគំរូ') }}: {{ $r->title_en }}">
                                <img src="{{ $r->pictureSrc() }}" alt="" loading="lazy" decoding="async">
                                <span class="shade"></span>
                                <span class="bottom">
                                    @if ($item['size'] !== '')
                                        <span class="rs-chip hm-mono">{{ $item['size'] }}</span>
                                    @else
                                        <span></span>
                                    @endif
                                    <span class="rs-peek">{!! $icon('eye', 'i3') !!}<span>{{ $L('Preview', 'មើលគំរូ') }}</span></span>
                                </span>
                            </button>
                            <div class="top">
                                <span class="chips">
                                    <span class="rs-chip rs-chip--sw hm-mono">{{ $r->software }}</span>
                                    <span class="rs-chip">{{ $r->type }}</span>
                                    @if ($r->is_featured)
                                        <span class="rs-chip rs-chip--feat">{!! $icon('sparkles') !!}Featured</span>
                                    @endif
                                </span>
                                @auth
                                    <button type="button" class="rs-mark" data-bookmark aria-pressed="{{ $item['saved'] ? 'true' : 'false' }}"
                                        aria-label="{{ $item['saved'] ? 'Remove bookmark' : 'Bookmark resource' }}" title="{{ $item['saved'] ? 'Bookmarked' : 'Add bookmark' }}">{!! $icon('bookmark', 'i35') !!}</button>
                                @else
                                    <a href="{{ route('login') }}" class="rs-mark" aria-label="Bookmark resource" title="Add bookmark">{!! $icon('bookmark', 'i35') !!}</a>
                                @endauth
                            </div>
                        </div>

                        <div class="rs-body">
                            <h3 style="margin:0"><button type="button" class="rs-title" data-preview>{{ $r->title_en }}</button></h3>
                            @if (filled($r->title_km))
                                <p class="rs-kh hm-km">{{ $r->title_km }}</p>
                            @endif
                            @if (filled($item['description']))
                                <p class="rs-desc">{{ $item['description'] }}</p>
                            @endif
                            @if ($item['tags'])
                                <div class="rs-tags">
                                    @foreach (array_slice($item['tags'], 0, 3) as $tag)
                                        <span>#{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="rs-foot">
                            <span class="rs-count hm-mono"><span data-downloads>{{ number_format($r->downloads_count) }}</span> {{ $text['downloads'] }}</span>
                            @if ($item['downloadUrl'])
                                <a href="{{ $item['downloadUrl'] }}" class="rs-dl fx-btn" data-download @if ($item['link']) target="_blank" rel="noopener noreferrer" @endif>
                                    {!! $icon('download', 'i35') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Download', 'ទាញយក') }}</span>
                                </a>
                            @else
                                <span class="rs-dl" aria-disabled="true">{!! $icon('download', 'i35') !!}<span>{{ $L('Soon', 'ឆាប់ៗ') }}</span></span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="rs-empty" id="rs-none" hidden>
                <p class="a {{ $hmKm ? 'hm-km' : '' }}">{{ $L('No resources found in this category or search.', 'រកមិនឃើញឯកសារក្នុងប្រភេទ ឬពាក្យស្វែងរកនេះទេ។') }}</p>
                <p class="b {{ $hmKm ? 'hm-km' : '' }}">{{ $L('Try adjusting your search or choose another category.', 'សូមសាកល្បងផ្លាស់ប្តូរពាក្យស្វែងរក ឬជ្រើសរើសប្រភេទផ្សេង។') }}</p>
                @if ($isAdmin)
                    <a href="{{ route('admin.resources.create') }}" class="rs-upload hm-km">+ បញ្ចូលឯកសារថ្មី (Upload)</a>
                @endif
            </div>
        @endif
    </div>

    <x-slot:after>
        <div class="rs-modal" id="rs-modal" hidden>
            <div class="rs-dialog" role="dialog" aria-modal="true" aria-labelledby="rs-dtitle">
                <div class="rs-dimg">
                    <img data-f="picture" alt="">
                    <span class="shade"></span>
                    <button type="button" class="rs-dclose" data-close aria-label="{{ $L('Close', 'បិទ') }}">{!! $icon('x') !!}</button>
                    <div class="row">
                        <div>
                            <span class="rs-chip rs-chip--sw hm-mono" data-f="software"></span>
                            <span class="rs-chip" data-f="type"></span>
                        </div>
                        <span class="rs-chip hm-mono" data-f="size"></span>
                    </div>
                </div>
                <div class="rs-dbody">
                    <div>
                        <h3 id="rs-dtitle" data-f="title"></h3>
                        <p class="kh hm-km" data-f="titleKh"></p>
                    </div>
                    <p class="desc" data-f="description"></p>
                    <div class="rs-tags" data-f="tags"></div>
                    <div class="rs-dfoot">
                        <span class="rs-count hm-mono"><span data-f="downloads"></span> {{ $text['totalDownloads'] }}</span>
                        <div>
                            @auth
                                <button type="button" class="rs-dmark" data-modal-bookmark aria-pressed="false">{!! $icon('bookmark', 'i35') !!}<span data-modal-bookmark-label></span></button>
                            @else
                                <a href="{{ route('login') }}" class="rs-dmark">{!! $icon('bookmark', 'i35') !!}<span>{{ $text['bookmark'] }}</span></a>
                            @endauth
                            <a href="#" class="rs-dl fx-btn" data-modal-download>{!! $icon('download') !!}<span class="hm-km">{{ $L('Download Resource', 'ទាញយកឯកសារនេះ') }}</span></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot:after>

    <x-slot:scripts>
        <script>
            window.rsItems = @json($items->keyBy('id'));
            window.rsText = @json($text);
        </script>
        <script>
            (function () {
                'use strict';
                var items = window.rsItems || {};
                var text = window.rsText || {};
                var grid = document.getElementById('rs-grid');
                if (!grid) return;
                var cards = Array.prototype.slice.call(grid.querySelectorAll('.rs-card'));
                var none = document.getElementById('rs-none');
                var search = document.getElementById('rs-q');
                var savedBtn = document.getElementById('rs-saved');
                var toast = document.getElementById('rs-toast');
                var modal = document.getElementById('rs-modal');
                var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                var state = { category: '__all', q: '', savedOnly: false };
                var openId = null, lastFocus = null, toastTimer = 0;

                function restart(el, cls) { el.classList.remove(cls); void el.offsetWidth; el.classList.add(cls); }

                /* ---------- filters: category tab + search + bookmarked only ---------- */
                function apply() {
                    var q = state.q.trim().toLowerCase(), shown = 0;
                    cards.forEach(function (card) {
                        var item = items[card.getAttribute('data-id')];
                        var show = (state.category === '__all' || card.getAttribute('data-category') === state.category)
                            && (!q || card.getAttribute('data-search').indexOf(q) !== -1)
                            && (!state.savedOnly || (item && item.saved));
                        if (show && card.hidden) { card.hidden = false; restart(card, 'is-in'); }
                        else if (!show) { card.hidden = true; }
                        if (show) shown++;
                    });
                    none.hidden = shown !== 0;
                }
                document.addEventListener('jelly:change', function (e) {
                    if (!e.target.closest('.rs-tabs')) return;
                    state.category = e.detail.value;
                    apply();
                });
                if (search) search.addEventListener('input', function () { state.q = search.value; apply(); });
                if (savedBtn) savedBtn.addEventListener('click', function () {
                    state.savedOnly = !state.savedOnly;
                    savedBtn.setAttribute('aria-pressed', state.savedOnly ? 'true' : 'false');
                    apply();
                });

                /* ---------- bookmarks (saved to the account) ---------- */
                function paintSaved(id) {
                    var item = items[id];
                    var card = grid.querySelector('.rs-card[data-id="' + id + '"]');
                    var btn = card && card.querySelector('[data-bookmark]');
                    if (btn) {
                        btn.setAttribute('aria-pressed', item.saved ? 'true' : 'false');
                        btn.setAttribute('aria-label', item.saved ? 'Remove bookmark' : 'Bookmark resource');
                        btn.title = item.saved ? 'Bookmarked' : 'Add bookmark';
                    }
                    if (openId === id) paintModalSaved();
                }
                function toggleBookmark(id) {
                    var item = items[id];
                    if (!item) return;
                    item.saved = !item.saved;          // show it straight away; the server confirms below
                    paintSaved(id);
                    var mark = grid.querySelector('.rs-card[data-id="' + id + '"] [data-bookmark]');
                    if (item.saved && mark && window.fxBurst) fxBurst(mark, '#4DA3FF');
                    fetch(item.bookmarkUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin'
                    }).then(function (r) { if (!r.ok) throw r; return r.json(); }).then(function (data) {
                        item.saved = !!data.saved;
                        paintSaved(id);
                        var count = document.querySelector('[data-saved-count]');
                        if (count) count.textContent = data.count;
                        if (state.savedOnly) apply();
                    }).catch(function () {
                        item.saved = !item.saved;      // undo when it did not save
                        paintSaved(id);
                    });
                }
                grid.addEventListener('click', function (e) {
                    var mark = e.target.closest('[data-bookmark]');
                    if (mark) { toggleBookmark(mark.closest('.rs-card').getAttribute('data-id')); return; }
                    var preview = e.target.closest('[data-preview]');
                    if (preview) { open(preview.closest('.rs-card').getAttribute('data-id')); return; }
                    var dl = e.target.closest('[data-download]');
                    if (dl) downloaded(dl.closest('.rs-card').getAttribute('data-id'));
                });

                /* ---------- downloads: a note, and the counter goes up ---------- */
                function downloaded(id) {
                    var item = items[id];
                    if (!item) return;
                    item.downloads += 1;
                    var card = grid.querySelector('.rs-card[data-id="' + id + '"] [data-downloads]');
                    if (card) card.textContent = item.downloads.toLocaleString();
                    if (openId === id) modal.querySelector('[data-f="downloads"]').textContent = item.downloads.toLocaleString();
                    var title = (document.documentElement.lang === 'km' && item.titleKh) ? item.titleKh : item.title;
                    toast.querySelector('[data-toast-text]').textContent = (item.link ? text.opening : text.downloading) + ' "' + title + '"' + (item.size && !item.link ? ' (' + item.size + ')' : '') + '...';
                    toast.hidden = false;
                    clearTimeout(toastTimer);
                    toastTimer = setTimeout(function () { toast.hidden = true; }, 4000);
                }

                /* ---------- preview dialog ---------- */
                var mDownload = modal.querySelector('[data-modal-download]');
                var mBookmark = modal.querySelector('[data-modal-bookmark]');
                function paintModalSaved() {
                    if (!mBookmark || !openId) return;
                    var saved = items[openId].saved;
                    mBookmark.setAttribute('aria-pressed', saved ? 'true' : 'false');
                    modal.querySelector('[data-modal-bookmark-label]').textContent = saved ? text.bookmarked : text.bookmark;
                }
                function open(id) {
                    var item = items[id];
                    if (!item) return;
                    openId = id;
                    lastFocus = document.activeElement;
                    modal.querySelectorAll('[data-f]').forEach(function (el) {
                        var key = el.getAttribute('data-f');
                        if (key === 'picture') { el.src = item.picture; el.alt = item.title; }
                        else if (key === 'tags') {
                            el.innerHTML = '';
                            item.tags.forEach(function (t) { var s = document.createElement('span'); s.textContent = '#' + t; el.appendChild(s); });
                            el.hidden = item.tags.length === 0;
                        } else if (key === 'downloads') { el.textContent = item.downloads.toLocaleString(); }
                        else { el.textContent = item[key] || ''; el.hidden = !item[key]; }
                    });
                    if (item.downloadUrl) {
                        mDownload.href = item.downloadUrl;
                        mDownload.hidden = false;
                        if (item.link) { mDownload.target = '_blank'; mDownload.rel = 'noopener noreferrer'; }
                        else { mDownload.removeAttribute('target'); mDownload.removeAttribute('rel'); }
                    } else {
                        mDownload.hidden = true;
                    }
                    paintModalSaved();
                    modal.hidden = false;
                    document.documentElement.style.overflow = 'hidden';
                    modal.querySelector('[data-close]').focus();
                }
                function close() {
                    if (!openId) return;
                    modal.hidden = true;
                    openId = null;
                    document.documentElement.style.overflow = '';
                    if (lastFocus && lastFocus.focus) lastFocus.focus();
                }
                modal.addEventListener('mousedown', function (e) { if (e.target === modal) close(); });
                modal.querySelector('[data-close]').addEventListener('click', close);
                if (mBookmark) mBookmark.addEventListener('click', function () { if (openId) toggleBookmark(openId); });
                mDownload.addEventListener('click', function () { var id = openId; if (id) { downloaded(id); setTimeout(close, 50); } });
                document.addEventListener('keydown', function (e) {
                    if (!openId) return;
                    if (e.key === 'Escape') { close(); return; }
                    if (e.key === 'Tab') {
                        var nodes = Array.prototype.filter.call(modal.querySelectorAll('button, [href]'), function (n) { return !n.hidden; });
                        var first = nodes[0], last = nodes[nodes.length - 1];
                        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                    }
                });
            })();
        </script>
    </x-slot:scripts>
</x-live-layout>
