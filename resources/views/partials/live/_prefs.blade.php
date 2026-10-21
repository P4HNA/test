{{-- Language and theme pill in the bottom-right corner. --}}
@php
    $hmKm = app()->getLocale() === 'km';
    $L = fn (string $en, string $km) => $hmKm ? $km : $en;
    $icon = fn (string $name, string $class = 'i4') => \App\Support\LiveIcon::svg($name, $class);
@endphp
<div class="hm-prefs">
    <form method="POST" action="{{ route('locale') }}">
        @csrf
        <input type="hidden" name="locale" value="{{ $hmKm ? 'en' : 'km' }}">
        <button type="submit" class="hm-pref" aria-label="{{ $hmKm ? 'Switch to English' : 'ប្តូរទៅភាសាខ្មែរ' }}" title="{{ $hmKm ? 'Switch to English (EN)' : 'ប្តូរទៅភាសាខ្មែរ (KM)' }}">
            @if ($hmKm)
                <svg class="hm-flag" viewBox="0 0 30 20" aria-hidden="true"><rect width="30" height="20" fill="#032EA1" /><rect y="5" width="30" height="10" fill="#E00025" /><path fill="#fff" d="M9 13.4h12v1.2H9zM10.2 11.6h9.6v1.8h-9.6zM11.6 9.4h1.2v2.2h-1.2zM14.4 7.4h1.2v4.2h-1.2zM17.2 9.4h1.2v2.2h-1.2z" /></svg>
                <span class="l hm-km">ខ្មែរ</span>
            @else
                <svg class="hm-flag" viewBox="0 0 60 30" aria-hidden="true"><clipPath id="hm-uk-clip"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z" /></clipPath><path d="M0,0 v30 h60 v-30 z" fill="#012169" /><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6" /><path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#hm-uk-clip)" stroke="#C8102E" stroke-width="4" /><path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10" /><path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6" /></svg>
                <span class="l">EN</span>
            @endif
        </button>
    </form>
    <span class="hm-pref-divider" aria-hidden="true"></span>
    <button type="button" class="hm-pref" @click="toggleTheme($event)"
        :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'" :title="dark ? 'Switch to Light Mode' : 'Switch to Dark Mode'">
        <span class="hm-spin-in" style="display:flex; color:#4595FF" x-show="dark" x-cloak>{!! $icon('moon', 'i35') !!}</span>
        <span class="hm-spin-in" style="display:flex; color:#F59E0B" x-show="!dark">{!! $icon('sun', 'i35') !!}</span>
        <span class="l m" x-text="dark ? 'Dark' : 'Light'">Dark</span>
    </button>
</div>
