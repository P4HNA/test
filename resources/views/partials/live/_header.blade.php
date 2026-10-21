{{-- Top bar of the live design: wordmark, main tabs, notification bell and the account menu. --}}
@php
    $hmKm = app()->getLocale() === 'km';
    $L = fn (string $en, string $km) => $hmKm ? $km : $en;
    $icon = fn (string $name, string $class = 'i4') => \App\Support\LiveIcon::svg($name, $class);
    $hmUser = auth()->user();
    $liveActive = $liveActive ?? null;
@endphp
<header id="site-nav" class="hm-header">
    <div class="hm-hbar">
        <a href="{{ route('home') }}" class="hm-hlogo" aria-label="piseth.design home">piseth<span>.design</span></a>

        <nav class="hm-hnav" aria-label="{{ $L('Main', 'ម៉ឺនុយ') }}">
            <a href="{{ route('home') }}" aria-current="page" class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Home', 'ទំព័រដើម') }}</a>
            <a href="{{ route('courses') }}" class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Courses', 'វគ្គសិក្សា') }}</a>
            <a href="{{ route('courses', ['free' => 1]) }}" class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Resources', 'ឯកសារ') }}</a>
            <a href="{{ route('about') }}" class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('About', 'អំពីលោកគ្រូ') }}</a>
        </nav>

        <div class="hm-hright" x-data="{ bell: false, acct: false }" @click.outside="bell = false; acct = false" @keydown.escape.window="bell = false; acct = false">
            <button type="button" class="hm-hbell" @click="bell = !bell; acct = false" :aria-expanded="bell.toString()" aria-expanded="false" aria-haspopup="true" aria-label="Notifications" title="Notification Center">
                {!! $icon('bell') !!}
                @if ($promotion ?? null)
                    <span class="dot" aria-hidden="true"></span>
                @endif
            </button>
            <div class="hm-hpanel hm-hpanel--bell" x-show="bell" x-cloak>
                <div class="hm-hpanel-head hm-hbrand">
                    <img src="{{ asset('images/logo-mark-96.png') }}" alt="" width="24" height="24">
                    <div style="min-width:0">
                        <p class="hm-hname {{ $hmKm ? 'hm-km' : '' }}">{{ $L('Notifications', 'ការជូនដំណឹង') }}</p>
                        <p class="hm-hmail">piseth.design</p>
                    </div>
                </div>
                @if ($promotion ?? null)
                    <a href="{{ $promotion->ctaUrl() }}" class="hm-hnote">
                        <span class="pill">{{ $promotion->isDiscount() ? __(':percent% off', ['percent' => $promotion->discount_percent]) : __('News') }}</span>
                        <span class="t">{{ $promotion->localized('title') }}</span>
                        @if (filled($promotion->localized('message')))
                            <span class="m">{{ $promotion->localized('message') }}</span>
                        @endif
                        <span class="go">{{ $promotion->ctaLabel() }} {!! $icon('arrow-right', 'i3') !!}</span>
                    </a>
                @else
                    <p class="hm-hempty {{ $hmKm ? 'hm-km' : '' }}">{{ $L("You're all caught up.", 'មិនមានការជូនដំណឹងថ្មីទេ។') }}</p>
                @endif
            </div>

            @auth
                <button type="button" class="hm-havatar" @click="acct = !acct; bell = false" :aria-expanded="acct.toString()" aria-expanded="false" aria-haspopup="true" aria-label="User profile menu">
                    <x-user-avatar :user="$hmUser" :size="32" />
                </button>
                <div class="hm-hpanel" x-show="acct" x-cloak>
                    <div class="hm-hpanel-head">
                        <div class="hm-hrow">
                            <p class="hm-hname">{{ $hmUser->name }}</p>
                            <span class="hm-hrole {{ $hmUser->isAdmin() ? 'hm-hrole--teacher' : '' }}">{{ $hmUser->isAdmin() ? 'Teacher' : 'Student' }}</span>
                        </div>
                        <p class="hm-hmail">{{ $hmUser->email }}</p>
                    </div>
                    <div class="hm-hmenu">
                        @if ($hmUser->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="hm-hitem hm-hitem--accent">{!! $icon('shield', 'i35') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Teacher Admin Portal', 'ផ្ទាំងគ្រប់គ្រងគ្រូបង្រៀន') }}</span></a>
                        @endif
                        <a href="{{ route('learning.index') }}" class="hm-hitem">{!! $icon('book', 'i35') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('My Dashboard', 'ផ្ទាំងសិក្សារបស់ខ្ញុំ') }}</span></a>
                        <a href="{{ route('learning.profile') }}" class="hm-hitem">{!! $icon('user', 'i35') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Student Profile', 'គណនីនិស្សិត') }}</span></a>
                        <a href="{{ route('profile.edit') }}" class="hm-hitem">{!! $icon('settings', 'i35') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ __('Account settings') }}</span></a>
                        <div class="hm-hsep">
                            <form method="POST" action="{{ route('locale') }}">
                                @csrf
                                <input type="hidden" name="locale" value="{{ $hmKm ? 'en' : 'km' }}">
                                <button type="submit" class="hm-hitem hm-hitem--split">
                                    <span><span aria-hidden="true">{{ $hmKm ? '🇰🇭' : '🇬🇧' }}</span><span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Language: English (EN)', 'ភាសា: ខ្មែរ (KM)') }}</span></span>
                                    <span class="hint {{ $hmKm ? 'hm-km' : '' }}">{{ $L('Switch KM', 'ប្តូរជា EN') }}</span>
                                </button>
                            </form>
                            <button type="button" class="hm-hitem hm-hitem--split" @click="toggleTheme($event)">
                                <span>
                                    <span style="display:flex; color:#4DA3FF" x-show="dark">{!! $icon('moon', 'i35') !!}</span>
                                    <span style="display:flex; color:#F59E0B" x-show="!dark" x-cloak>{!! $icon('sun', 'i35') !!}</span>
                                    <span class="{{ $hmKm ? 'hm-km' : '' }}" x-text="dark ? @js($L('Appearance: Dark Mode', 'ទម្រង់ពន្លឺ: ងងឹត (Dark)')) : @js($L('Appearance: Light Mode', 'ទម្រង់ពន្លឺ: ភ្លឺ (Light)'))"></span>
                                </span>
                                <span class="hint hint--mono hm-mono">Toggle</span>
                            </button>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="hm-hitem hm-hitem--danger">{!! $icon('logout', 'i35') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Log Out', 'ចាកចេញពីគណនី') }}</span></button>
                            </form>
                        </div>
                    </div>
                </div>

                @if ($hmUser->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="hm-hportal" title="Open Teacher / Admin Portal">{!! $icon('shield', 'i35') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Teacher Portal', 'ផ្ទាំងគ្រូបង្រៀន') }}</span></a>
                @else
                    <a href="{{ route('learning.index') }}" class="hm-hdash" title="Open Student Dashboard">{!! $icon('book', 'i35') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('My Dashboard', 'ផ្ទាំងសិក្សា') }}</span></a>
                @endif
            @else
                <a href="{{ route('login') }}" class="hm-hsignin">{!! $icon('user', 'i35') !!}<span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Sign In', 'ចូលរៀន') }}</span></a>
                <a href="{{ route('admin.dashboard') }}" class="hm-hportal" title="Open Teacher / Admin Portal"><span class="{{ $hmKm ? 'hm-km' : '' }}">{{ $L('Teacher Portal', 'ផ្ទាំងគ្រូបង្រៀន') }}</span></a>
            @endauth
        </div>
    </div>
</header>
