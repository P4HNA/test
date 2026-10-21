{{-- Activity page, built on the live piseth.design design (components/live-layout). Posts come from App\Support\ActivityFeed. --}}
@php
    $hmKm = app()->getLocale() === 'km';
    $L = fn (string $en, string $km) => $hmKm ? $km : $en;
    $icon = fn (string $name, string $class = 'i4') => \App\Support\LiveIcon::svg($name, $class);
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

            html:lang(km) .hm-body :is(.ac-head h1) { line-height: 1.5; }
        </style>
        @include('partials.live._activity-styles')
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
            <div class="ac-list" id="ac-list" data-ac-list>
                @foreach ($posts as $post)
                    @include('partials.live._activity-post', ['post' => $post])
                @endforeach
            </div>
        @endif
    </div>

    <x-slot:scripts>
        @include('partials.live._activity-likes')
    </x-slot:scripts>
</x-live-layout>
