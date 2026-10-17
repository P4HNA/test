{{--
    The one top bar, used by the homepage and by every classroom page, so the
    header never changes shape as you move around the site. Styling lives in
    resources/css/app.css (.sn-*), which every layout already loads.

    It expects `dark` and `toggleTheme()` from the surrounding Alpine scope --
    both layouts declare them on their page wrapper -- and owns its own open/
    closed state so nothing outside has to know about it.
--}}

@php
    $user = auth()->user();

    // Built here rather than inline so the desktop row and the mobile sheet can
    // never drift apart.
    $links = [
        ['label' => __('Courses'), 'href' => route('courses'), 'active' => request()->routeIs('courses*')],
    ];

    if ($user) {
        $links[] = ['label' => __('My Learning'), 'href' => route('learning.index'), 'active' => request()->routeIs('learning.*')];

        if ($user->isAdmin()) {
            $links[] = ['label' => __('Admin'), 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.*')];
        }
    }

    $links[] = ['label' => __('About'), 'href' => route('about'), 'active' => request()->routeIs('about')];
@endphp

<nav
    id="site-nav"
    class="sn"
    aria-label="{{ __('Main') }}"
    x-data="{ mobileOpen: false, acctOpen: false }"
    :data-open="mobileOpen || acctOpen"
    @keydown.escape.window="mobileOpen = false; acctOpen = false"
>
    <div class="sn-inner">
        {{-- The shared lockup: logo, wordmark, and the blue shine on mouse hover. --}}
        <x-brand :href="route('home')" class="sn-brand shrink-0" />

        <div class="sn-links">
            {{--
                data-label feeds .sn-link::before, an invisible copy of the
                label already set in the bold weight the current page uses. It
                is what stops the row shifting sideways as you move between
                pages; without it the CSS has nothing to measure.
            --}}
            @foreach ($links as $link)
                <a href="{{ $link['href'] }}" class="sn-link" data-label="{{ $link['label'] }}"
                    @if ($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
            @endforeach
        </div>

        <div class="sn-right">
            {{-- Language and theme, one pill. --}}
            <div class="sn-cluster">
                @include('partials._locale-toggle')
                <span class="sn-divider" aria-hidden="true"></span>
                <button type="button" @click="toggleTheme($event)"
                    :aria-label="dark ? '{{ __('Switch to light mode') }}' : '{{ __('Switch to dark mode') }}'"
                    :aria-pressed="dark">
                    <x-icon name="sun" class="w-4 h-4" x-show="!dark" />
                    <x-icon name="moon" class="w-4 h-4" x-show="dark" x-cloak />
                </button>
            </div>

            @auth
                <div class="relative" @click.outside="acctOpen = false">
                    <button type="button" class="sn-acct" @click="acctOpen = !acctOpen"
                        :aria-expanded="acctOpen ? 'true' : 'false'" aria-expanded="false"
                        aria-haspopup="menu" aria-controls="sn-acct-menu">
                        <x-user-avatar :user="$user" :size="26" />
                        <span>{{ \Illuminate\Support\Str::of($user->name)->before(' ') }}</span>
                        <x-icon name="chevron-down" class="w-3.5 h-3.5" :stroke="2.2" />
                    </button>

                    <div id="sn-acct-menu" class="sn-menu" role="menu" x-show="acctOpen" x-cloak
                        x-transition.origin.top.right>
                        <div class="sn-menu-head">
                            <x-user-avatar :user="$user" :size="34" />
                            <div class="min-w-0">
                                <p class="text-sm font-semibold truncate">{{ $user->name }}</p>
                                <p class="text-xs truncate" style="color:var(--graphite)">{{ $user->isAdmin() ? __('Instructor') : __('Student') }}</p>
                            </div>
                        </div>

                        <a href="{{ route('learning.index') }}" role="menuitem" @click="acctOpen = false">
                            <x-icon name="book-open" class="w-4 h-4" /> {{ __('My Learning') }}
                        </a>
                        <a href="{{ route('learning.profile') }}" role="menuitem" @click="acctOpen = false">
                            <x-icon name="user" class="w-4 h-4" /> {{ __('Profile') }}
                        </a>
                        <a href="{{ route('profile.edit') }}" role="menuitem" @click="acctOpen = false">
                            <x-icon name="settings" class="w-4 h-4" /> {{ __('Account settings') }}
                        </a>
                        @if ($user->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" role="menuitem" @click="acctOpen = false">
                                <x-icon name="sliders" class="w-4 h-4" /> {{ __('Admin portal') }}
                            </a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}" class="sn-menu-foot">
                            @csrf
                            <button type="submit" role="menuitem" style="color:var(--redline, #DC2626)">
                                <x-icon name="logout" class="w-4 h-4" style="color:inherit" /> {{ __('Log out') }}
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="sn-ghost">{{ __('Log in') }}</a>
                <a href="{{ route('register') }}" class="sn-cta">{{ __('Start free') }}</a>
            @endauth

            <button type="button" class="sn-burger" @click="mobileOpen = !mobileOpen"
                :aria-expanded="mobileOpen ? 'true' : 'false'" aria-expanded="false"
                aria-controls="sn-sheet" aria-label="{{ __('Toggle menu') }}">
                <x-icon name="menu" class="w-[18px] h-[18px]" x-show="!mobileOpen" />
                <x-icon name="x" class="w-[18px] h-[18px]" x-show="mobileOpen" x-cloak />
            </button>
        </div>

    </div>

    <div id="sn-sheet" class="sn-sheet" x-show="mobileOpen" x-cloak
        x-transition.origin.top>
        @auth
            <div class="sn-sheet-head">
                <x-user-avatar :user="$user" :size="40" />
                <div class="min-w-0">
                    <p class="text-sm font-semibold truncate">{{ $user->name }}</p>
                    <p class="text-xs truncate" style="color:var(--graphite)">{{ $user->email }}</p>
                </div>
            </div>
        @endauth

        @foreach ($links as $link)
            <a href="{{ $link['href'] }}" class="sn-sheet-link" @click="mobileOpen = false"
                @if ($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
        @endforeach

        @auth
            <a href="{{ route('learning.profile') }}" class="sn-sheet-link" @click="mobileOpen = false">{{ __('Profile') }}</a>
            <a href="{{ route('profile.edit') }}" class="sn-sheet-link" @click="mobileOpen = false">{{ __('Account settings') }}</a>
        @else
            <a href="{{ route('login') }}" class="sn-sheet-link" @click="mobileOpen = false">{{ __('Log in') }}</a>
        @endauth

        <div class="sn-sheet-foot">
            {{-- Below 640px the pill in the bar is hidden, so it lives down here. --}}
            <div class="sn-cluster" style="display:flex">
                @include('partials._locale-toggle')
                <span class="sn-divider" aria-hidden="true"></span>
                <button type="button" @click="toggleTheme($event)"
                    :aria-label="dark ? '{{ __('Switch to light mode') }}' : '{{ __('Switch to dark mode') }}'"
                    :aria-pressed="dark">
                    <x-icon name="sun" class="w-4 h-4" x-show="!dark" />
                    <x-icon name="moon" class="w-4 h-4" x-show="dark" x-cloak />
                </button>
            </div>

            @auth
                <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                    @csrf
                    <button type="submit" class="text-sm font-medium" style="color:var(--redline, #DC2626)">{{ __('Log out') }}</button>
                </form>
            @else
                <a href="{{ route('register') }}" class="sn-cta ml-auto" style="display:inline-flex">{{ __('Start free') }}</a>
            @endauth
        </div>
    </div>
</nav>

<script>
    /* Smart bar: gains its edge once the page moves, hides on the way down and
       comes back on any scroll up. It stays put at the very top, while a menu
       is open, and while anything inside it has keyboard focus. */
    (function () {
        var nav = document.getElementById('site-nav');
        if (!nav) return;

        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var lastY = window.scrollY;
        var ticking = false;
        var DELTA = 6;      // ignore tiny movements so the bar cannot flicker
        var HIDE_AFTER = 90; // never hide within the first screenful

        function menuOpen() {
            var v = nav.getAttribute('data-open');
            return v !== null && v !== 'false';
        }

        function update() {
            ticking = false;

            var y = Math.max(0, window.scrollY);

            nav.classList.toggle('is-stuck', y > 4);

            if (!reduce) {
                if (y <= HIDE_AFTER || menuOpen()) {
                    nav.classList.remove('is-hidden');
                } else if (Math.abs(y - lastY) >= DELTA) {
                    nav.classList.toggle('is-hidden', y > lastY);
                }
            }

            if (Math.abs(y - lastY) >= DELTA) lastY = y;
        }

        window.addEventListener('scroll', function () {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(update);
            }
        }, { passive: true });

        nav.addEventListener('focusin', function () {
            nav.classList.remove('is-hidden');
        });

        update();
    })();
</script>
