{{-- Phone bottom bar of the live design (hidden from 768px). --}}
@php
    $hmKm = app()->getLocale() === 'km';
    $L = fn (string $en, string $km) => $hmKm ? $km : $en;
    $icon = fn (string $name, string $class = 'i4') => \App\Support\LiveIcon::svg($name, $class);
    $liveActive = $liveActive ?? null;
    $liveItems = [
        ['home', route('home'), 'house', 'Home', 'ទំព័រដើម'],
        ['courses', route('courses'), 'book', 'Courses', 'វគ្គសិក្សា'],
        ['resources', route('resources.index'), 'folder', 'Resources', 'ឯកសារ'],
        ['activity', route('activity.index'), 'sparkles', 'Activity', 'សកម្មភាព'],
        ['profile', route('learning.profile'), 'user', 'Profile', 'គណនី'],
    ];
@endphp
<nav class="hm-bnav" aria-label="{{ $L('Quick links', 'តំណរហ័ស') }}">
    <div>
        @foreach ($liveItems as [$key, $href, $glyph, $en, $km])
            <a href="{{ $href }}" @if ($liveActive === $key) aria-current="page" @endif>{!! $icon($glyph, 'i5') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L($en, $km) }}</span></a>
        @endforeach
    </div>
</nav>
