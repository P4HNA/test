{{-- Phone bottom bar of the live design (hidden from 768px). --}}
@php
    $hmKm = app()->getLocale() === 'km';
    $L = fn (string $en, string $km) => $hmKm ? $km : $en;
    $icon = fn (string $name, string $class = 'i4') => \App\Support\LiveIcon::svg($name, $class);
    $liveActive = $liveActive ?? null;
@endphp
<nav class="hm-bnav" aria-label="{{ $L('Quick links', 'តំណរហ័ស') }}">
    <div>
        <a href="{{ route('home') }}" aria-current="page">{!! $icon('house', 'i5') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Home', 'ទំព័រដើម') }}</span></a>
        <a href="{{ route('courses') }}">{!! $icon('book', 'i5') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Courses', 'វគ្គសិក្សា') }}</span></a>
        <a href="{{ route('courses', ['free' => 1]) }}">{!! $icon('folder', 'i5') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Resources', 'ឯកសារ') }}</span></a>
        <a href="{{ route('about') }}">{!! $icon('sparkles', 'i5') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('About', 'អំពី') }}</span></a>
        <a href="{{ route('learning.profile') }}">{!! $icon('user', 'i5') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Profile', 'គណនី') }}</span></a>
    </div>
</nav>
