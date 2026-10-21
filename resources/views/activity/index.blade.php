{{-- Activity page, built on the live piseth.design design (components/live-layout). Posts come from App\Support\ActivityFeed. --}}
@php
    $hmKm = app()->getLocale() === 'km';
    $L = fn (string $en, string $km) => $hmKm ? $km : $en;
    $icon = fn (string $name, string $class = 'i4') => \App\Support\LiveIcon::svg($name, $class);
    $typeIcon = [
        'lesson' => ['play', '#10B981'],
        'course' => ['sparkles', '#1877F2'],
        'news' => ['bell', '#F59E0B'],
        'cert' => ['award', '#1877F2'],
        'done' => ['check-circle', '#3B82F6'],
        'join' => ['user', '#4DA3FF'],
    ];
@endphp

<x-live-layout :title="$L('Community Learning Activity', 'សកម្មភាពសិក្សាក្នុងសហគមន៍').' · Piseth.Design'" active="activity" main-class="ac-main">
    <x-slot:styles>
        <style>
            .ac-main { font-family: Inter, 'Noto Sans Khmer', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.5; -webkit-font-smoothing: antialiased; }
            .ac-page { max-width: 56rem; margin: 0 auto; padding: 2rem 1rem 6rem; display: flex; flex-direction: column; gap: 1.5rem; }
            @media (min-width: 640px) { .ac-page { padding-left: 1.5rem; padding-right: 1.5rem; } }
            @media (min-width: 1024px) { .ac-page { padding-left: 2rem; padding-right: 2rem; } }
            .ac-head { padding-bottom: 1rem; border-bottom: 1px solid var(--line); }
            .ac-head h1 { margin: 0; font-size: 1.5rem; line-height: 2rem; font-weight: 900; color: var(--ink); }
            @media (min-width: 640px) { .ac-head h1 { font-size: 1.875rem; line-height: 2.25rem; } }
            .ac-head p { margin: .25rem 0 0; font-size: .75rem; line-height: 1rem; color: var(--mut); }
            @media (min-width: 640px) { .ac-head p { font-size: .875rem; line-height: 1.25rem; } }

            .ac-guest { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; padding: .875rem 1rem; border-radius: 1rem; background: #EAF5FF; border: 1px solid rgba(77, 163, 255, .3); font-size: .75rem; line-height: 1rem; color: #1E3A5F; }
            html.dark .ac-guest { background: rgba(77, 163, 255, .1); color: #CFE4FF; }
            .ac-guest a { display: inline-flex; align-items: center; gap: .375rem; padding: .375rem .75rem; border-radius: .75rem; background: #4DA3FF; color: #fff; font-weight: 600; text-decoration: none; }
            .ac-guest a:hover { background: #3892F3; }

            .ac-list { display: flex; flex-direction: column; gap: .75rem; }
            .ac-post { padding: 1rem; border-radius: 1rem; background: var(--card); border: 1px solid var(--line); box-shadow: 0 1px 2px rgba(0, 0, 0, .05); transition: all .2s ease; }
            @media (min-width: 640px) { .ac-post { padding: 1.25rem; } }
            .ac-post:hover { border-color: rgba(24, 119, 242, .4); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1), 0 2px 4px -2px rgba(0, 0, 0, .1); }
            html.dark .ac-post:hover { border-color: rgba(69, 149, 255, .4); }
            .ac-top { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; }
            .ac-who { display: flex; align-items: center; gap: .75rem; min-width: 0; }
            .ac-av { position: relative; flex-shrink: 0; width: 2.5rem; height: 2.5rem; }
            .ac-av .pd-avatar, .ac-av img, .ac-av .hm-avatar { width: 2.5rem !important; height: 2.5rem !important; border-radius: 9999px; box-shadow: 0 0 0 1px var(--line); object-fit: cover; }
            .ac-av img.logo { background: #fff; padding: .25rem; }
            .ac-type { position: absolute; right: -.25rem; bottom: -.25rem; width: 1.25rem; height: 1.25rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; background: var(--card); border: 1px solid var(--line); box-shadow: 0 1px 2px rgba(0, 0, 0, .05); }
            .ac-line { display: flex; flex-wrap: wrap; align-items: center; gap: .375rem; }
            .ac-line .n { font-size: .875rem; line-height: 1.25rem; font-weight: 600; color: var(--ink); }
            .ac-line .role { padding: 0 .375rem; border-radius: .375rem; background: #E7F3FF; color: #1877F2; font-size: 10px; font-weight: 500; }
            html.dark .ac-line .role { background: rgba(69, 149, 255, .15); color: #4595FF; }
            .ac-line .act { font-size: .75rem; line-height: 1rem; color: var(--mut); }
            .ac-time { margin-top: .125rem; display: flex; align-items: center; gap: .25rem; font-size: 11px; color: var(--mut); }
            .ac-badge { display: none; flex-shrink: 0; padding: .25rem .625rem; border-radius: 9999px; background: #F1F5F9; border: 1px solid var(--line); font-size: 11px; font-weight: 500; color: var(--ink); white-space: nowrap; }
            html.dark .ac-badge { background: #242629; }
            @media (min-width: 640px) { .ac-badge { display: inline-flex; } }
            .ac-target { margin-top: .875rem; display: flex; align-items: center; gap: .875rem; padding: .75rem; border-radius: .75rem; background: #F5F7FA; border: 1px solid rgba(228, 230, 235, .7); }
            html.dark .ac-target { background: #242629; border-color: #2E3034; }
            .ac-target img { width: 3.5rem; height: 3.5rem; flex-shrink: 0; border-radius: .5rem; object-fit: cover; border: 1px solid rgba(0, 0, 0, .05); }
            html.dark .ac-target img { border-color: rgba(255, 255, 255, .05); }
            @media (min-width: 640px) { .ac-target img { width: 4rem; height: 4rem; } }
            .ac-target > div { flex: 1; min-width: 0; }
            .ac-target h4 { margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .75rem; line-height: 1rem; font-weight: 700; color: var(--ink); }
            @media (min-width: 640px) { .ac-target h4 { font-size: .875rem; line-height: 1.25rem; } }
            .ac-target p { margin: .125rem 0 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 11px; color: var(--mut); }
            @media (min-width: 640px) { .ac-target p { font-size: .75rem; } }
            .ac-view { flex-shrink: 0; display: inline-flex; align-items: center; gap: .25rem; padding: .375rem .75rem; border-radius: .5rem; background: #1877F2; color: #fff; font-size: .75rem; line-height: 1rem; font-weight: 600; text-decoration: none; box-shadow: 0 1px 2px rgba(0, 0, 0, .05); transition: background .15s ease; }
            .ac-view:hover { background: #166FE5; }
            .ac-foot { margin-top: .75rem; padding-top: .625rem; border-top: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; font-size: .75rem; line-height: 1rem; color: var(--mut); }
            .ac-like { display: inline-flex; align-items: center; gap: .375rem; padding: .25rem .375rem; border: 0; border-radius: .375rem; background: transparent; font: inherit; color: inherit; text-decoration: none; cursor: pointer; transition: all .15s ease; }
            .ac-like:hover { background: #F1F5F9; }
            html.dark .ac-like:hover { background: #242629; }
            .ac-like[aria-pressed="true"] { color: #1877F2; font-weight: 600; }
            html.dark .ac-like[aria-pressed="true"] { color: #4595FF; }
            .ac-like[aria-pressed="true"] svg { fill: currentColor; }
            .ac-like.is-pop svg { animation: hm-label-pop 420ms cubic-bezier(.34, 1.56, .64, 1) both; }
            .ac-foot .k { font-size: 11px; }
            .ac-empty { padding: 4rem 1rem; text-align: center; border-radius: 1rem; border: 1px dashed var(--line); color: var(--mut); font-size: .875rem; }

            html:lang(km) .hm-body :is(.ac-head h1) { line-height: 1.5; }
        </style>
    </x-slot:styles>

    <div class="ac-page">
        <div class="ac-head">
            <h1 class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Community Learning Activity', 'សកម្មភាពសិក្សាក្នុងសហគមន៍') }}</h1>
            <p class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('New lessons from Teacher Piseth Yoeurn, academy news, and your classmates’ milestones.', 'មេរៀនថ្មីពីលោកគ្រូ យឿន ពិសិដ្ឋ ព័ត៌មានពីសាលា និងវឌ្ឍនភាពសិក្សារបស់មិត្តរួមថ្នាក់។') }}</p>
        </div>

        @guest
            <div class="ac-guest">
                <span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Sign in to see your classmates’ milestones and to like posts.', 'ចូលគណនីដើម្បីមើលវឌ្ឍនភាពរបស់មិត្តរួមថ្នាក់ និងចុចចូលចិត្ត។') }}</span>
                <a href="{{ route('login') }}">{!! $icon('user', 'i35') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Sign In', 'ចូលរៀន') }}</span></a>
            </div>
        @endguest

        @if ($posts->isEmpty())
            <p class="ac-empty {{ $hmKm ? 'hm-km' : '' }}">{{ $L('Nothing here yet. New lessons and milestones will show up as they happen.', 'មិនទាន់មានសកម្មភាពនៅឡើយទេ។ មេរៀនថ្មី និងវឌ្ឍនភាពនឹងបង្ហាញនៅទីនេះ។') }}</p>
        @else
            <div class="ac-list" id="ac-list">
                @foreach ($posts as $post)
                    @php [$glyph, $tone] = $typeIcon[$post['type']] ?? ['award', '#1877F2']; $actor = $post['actor']; @endphp
                    <article class="ac-post" data-key="{{ $post['key'] }}">
                        <div class="ac-top">
                            <div class="ac-who">
                                <div class="ac-av">
                                    @if ($actor['logo'] ?? false)
                                        <img class="logo" src="{{ asset('images/logo-mark-96.png') }}" alt="" width="40" height="40">
                                    @elseif ($actor['user'])
                                        <x-user-avatar :user="$actor['user']" :size="40" />
                                    @else
                                        <span class="hm-avatar">{!! \App\Support\LiveIcon::blankProfile() !!}</span>
                                    @endif
                                    <span class="ac-type" style="color: {{ $tone }}">{!! $icon($glyph, 'i35') !!}</span>
                                </div>
                                <div style="min-width:0">
                                    <div class="ac-line">
                                        <span class="n">{{ $actor['name'] }}</span>
                                        @if (filled($actor['role']))
                                            <span class="role">{{ ($actor['you'] ?? false) ? $L('You', 'អ្នក') : $actor['role'] }}</span>
                                        @endif
                                        <span class="act {{ $hmKm ? 'hm-km' : '' }}">{{ $post['action'] }}</span>
                                    </div>
                                    <div class="ac-time">{!! $icon('clock', 'i3') !!}<span>{{ $post['ago'] }}</span></div>
                                </div>
                            </div>
                            <span class="ac-badge {{ $hmKm ? 'hm-km' : '' }}">{{ $post['badge'] }}</span>
                        </div>

                        <div class="ac-target">
                            @if ($post['thumb'])
                                <img src="{{ $post['thumb'] }}" alt="" loading="lazy" decoding="async">
                            @endif
                            <div>
                                <h4>{{ $post['title'] }}</h4>
                                @if (filled($post['subtitle']))
                                    <p>{{ $post['subtitle'] }}</p>
                                @endif
                            </div>
                            @if ($post['url'])
                                <a href="{{ $post['url'] }}" class="ac-view"><span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('View', 'ចូលមើល') }}</span>{!! $icon('arrow-up-right', 'i3') !!}</a>
                            @endif
                        </div>

                        <div class="ac-foot">
                            @auth
                                <button type="button" class="ac-like" data-like aria-pressed="{{ $post['liked'] ? 'true' : 'false' }}" aria-label="{{ $L('Like', 'ចូលចិត្ត') }}">
                                    {!! $icon('thumbs-up', 'i35') !!}<span data-count>{{ $post['likes'] }}</span>
                                </button>
                            @else
                                <a href="{{ route('login') }}" class="ac-like" aria-label="{{ $L('Sign in to like', 'ចូលគណនីដើម្បីចូលចិត្ត') }}">
                                    {!! $icon('thumbs-up', 'i35') !!}<span>{{ $post['likes'] }}</span>
                                </a>
                            @endauth
                            <span class="k {{ $hmKm ? 'hm-km' : '' }}">{{ $L('Learning activity', 'សកម្មភាពសិក្សា') }}</span>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>

    <x-slot:scripts>
        @auth
            <script>
                (function () {
                    'use strict';
                    var list = document.getElementById('ac-list');
                    if (!list) return;
                    var url = @json(route('activity.like'));
                    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    function paint(btn, liked, count) {
                        btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
                        btn.querySelector('[data-count]').textContent = count;
                    }

                    list.addEventListener('click', function (e) {
                        var btn = e.target.closest('[data-like]');
                        if (!btn || btn.disabled) return;
                        var key = btn.closest('.ac-post').getAttribute('data-key');
                        var was = btn.getAttribute('aria-pressed') === 'true';
                        var before = parseInt(btn.querySelector('[data-count]').textContent, 10) || 0;
                        paint(btn, !was, Math.max(0, before + (was ? -1 : 1)));   // show it straight away
                        btn.classList.remove('is-pop'); void btn.offsetWidth; btn.classList.add('is-pop');
                        btn.disabled = true;
                        fetch(url, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                            body: JSON.stringify({ key: key })
                        }).then(function (r) { if (!r.ok) throw r; return r.json(); })
                          .then(function (data) { paint(btn, data.liked, data.count); })
                          .catch(function () { paint(btn, was, before); })
                          .finally(function () { btn.disabled = false; });
                    });
                })();
            </script>
        @endauth
    </x-slot:scripts>
</x-live-layout>
