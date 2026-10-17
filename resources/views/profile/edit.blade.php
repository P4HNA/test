{{--
    Account settings.

    One page, four jobs: who you are, what the public profile shows, the
    password, and closing the account. The sidebar exists so none of that is a
    scroll-and-hope: it names every section, marks the one you are looking at,
    and keeps "view my profile" and "log out" within reach the whole way down.
    Below 960px it folds into a row of chips above the cards.
--}}
@php
    $checklist = $user->profileChecklist();
    $complete = $user->profileCompleteness();
    $missing = collect($checklist)->reject(fn ($done) => $done)->keys();

    $sections = [
        ['id' => 'profile-information', 'icon' => 'user', 'label' => __('Profile information')],
        ['id' => 'about-you', 'icon' => 'image', 'label' => __('About you')],
        ['id' => 'password', 'icon' => 'lock', 'label' => __('Password')],
        ['id' => 'danger-zone', 'icon' => 'alert', 'label' => __('Delete account')],
    ];
@endphp

<x-classroom-layout :title="__('Account Settings') . ' — Piseth.Design'">
    <x-slot:head>
        <style>
            .acct {
                max-width: 68rem;
                margin: 0 auto;
                padding: 40px 24px 72px;
            }

            /* ---------- Header ---------- */
            .acct-back {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                font-size: 13px;
                color: var(--graphite);
                text-decoration: none;
                transition: color .2s ease, gap .2s ease;
            }

            .acct-back:hover {
                color: var(--ink);
                gap: 10px;
            }

            .acct-head {
                display: flex;
                flex-wrap: wrap;
                align-items: flex-end;
                justify-content: space-between;
                gap: 16px;
                margin: 14px 0 30px;
            }

            .acct-eyebrow {
                font-size: 11px;
                font-weight: 700;
                letter-spacing: .14em;
                text-transform: uppercase;
                color: var(--redline);
            }

            .acct-head h1 {
                margin-top: 6px;
                font-size: clamp(26px, 4vw, 34px);
                font-weight: 800;
                line-height: 1.1;
            }

            .acct-head p.acct-sub {
                margin-top: 6px;
                font-size: 14px;
                color: var(--graphite);
            }

            .acct-head-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            /* ---------- Layout ---------- */
            /* minmax(0, 1fr), not 1fr: a grid column is min-content wide by
               default, and the section list below 960px is a scrolling row --
               its min-content width would otherwise stretch the whole page. */
            .acct-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 24px;
            }

            @media (min-width: 960px) {
                .acct-grid {
                    grid-template-columns: 252px minmax(0, 1fr);
                    gap: 32px;
                    align-items: start;
                }

                /* Clears the 68px top bar with air to spare. */
                .acct-side {
                    position: sticky;
                    top: 88px;
                }
            }

            .acct-side {
                display: flex;
                flex-direction: column;
                gap: 16px;
                min-width: 0;
            }

            .acct-main {
                display: flex;
                flex-direction: column;
                gap: 20px;
                min-width: 0;
            }

            /* ---------- Cards ---------- */
            .acct-card {
                background: var(--paper);
                border: 1px solid var(--mist);
                border-radius: .125rem;
                padding: 26px 26px 28px;
                /* Anchor links land below the sticky bar, not behind it. */
                scroll-margin-top: 92px;
            }

            @media (max-width: 560px) {
                .acct-card {
                    padding: 20px 18px 22px;
                }

                .acct {
                    padding: 28px 16px 56px;
                }
            }

            .acct-card-head {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                padding-bottom: 18px;
                margin-bottom: 22px;
                border-bottom: 1px solid var(--mist);
            }

            .acct-card-icon {
                display: grid;
                place-items: center;
                width: 38px;
                height: 38px;
                flex: 0 0 auto;
                border-radius: .125rem;
                background: var(--surface);
                color: var(--ink);
            }

            .acct-card-head h2 {
                font-size: 17px;
                font-weight: 700;
                line-height: 1.25;
            }

            .acct-card-head p {
                margin-top: 3px;
                font-size: 13.5px;
                color: var(--graphite);
            }

            .acct-card.is-danger {
                border-color: color-mix(in srgb, var(--danger) 40%, var(--mist));
            }

            .acct-card.is-danger .acct-card-icon {
                background: color-mix(in srgb, var(--danger) 12%, transparent);
                color: var(--danger);
            }

            /* ---------- Identity card ---------- */
            .acct-id {
                padding: 22px;
                text-align: center;
            }

            .acct-id .pd-avatar {
                margin: 0 auto 12px;
            }

            .acct-id-name {
                font-size: 15px;
                font-weight: 700;
                word-break: break-word;
            }

            .acct-id-mail {
                margin-top: 2px;
                font-size: 12.5px;
                color: var(--graphite);
                word-break: break-all;
            }

            .acct-chip {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                margin-top: 10px;
                padding: 3px 10px;
                border-radius: 999px;
                border: 1px solid var(--mist);
                background: var(--surface);
                font-size: 11px;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: var(--graphite);
            }

            .acct-chip.is-ok {
                border-color: color-mix(in srgb, var(--redline) 45%, transparent);
                color: var(--redline);
                background: transparent;
            }

            /* ---------- Completeness ---------- */
            .acct-meter {
                margin-top: 18px;
                text-align: left;
            }

            .acct-meter-top {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                font-size: 12px;
                color: var(--graphite);
            }

            .acct-meter-top b {
                color: var(--ink);
                font-variant-numeric: tabular-nums;
            }

            .acct-meter-track {
                height: 6px;
                margin-top: 7px;
                border-radius: 999px;
                background: var(--mist);
                overflow: hidden;
            }

            .acct-meter-fill {
                height: 100%;
                border-radius: inherit;
                background: var(--redline);
                transition: width .6s cubic-bezier(.19, 1, .22, 1);
            }

            .acct-meter-next {
                margin-top: 8px;
                font-size: 12px;
                line-height: 1.45;
                color: var(--graphite);
            }

            /* ---------- Section nav ---------- */
            .acct-nav {
                display: flex;
                flex-direction: column;
                gap: 2px;
                padding: 8px;
                border: 1px solid var(--mist);
                border-radius: .125rem;
                background: var(--paper);
            }

            .acct-nav a {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 9px 11px;
                border-radius: .125rem;
                font-size: 13.5px;
                color: var(--graphite);
                text-decoration: none;
                transition: background-color .2s ease, color .2s ease;
            }

            .acct-nav a.is-current {
                background: var(--surface);
                color: var(--ink);
                font-weight: 600;
            }

            .acct-nav a svg {
                flex: 0 0 auto;
                opacity: .75;
            }

            .acct-nav a.is-danger.is-current {
                color: var(--danger);
            }

            @media (max-width: 959px) {
                .acct-nav {
                    flex-direction: row;
                    overflow-x: auto;
                    scrollbar-width: none;
                    min-width: 0;
                }

                .acct-nav::-webkit-scrollbar {
                    display: none;
                }

                .acct-nav a {
                    white-space: nowrap;
                }
            }

            /* ---------- Buttons ---------- */
            .acct-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 7px;
                padding: 9px 15px;
                border: 1px solid transparent;
                border-radius: .125rem;
                background: none;
                font: inherit;
                font-size: 13.5px;
                font-weight: 600;
                line-height: 1.2;
                color: var(--ink);
                text-decoration: none;
                cursor: pointer;
                -webkit-tap-highlight-color: transparent;
                transition: background-color .2s ease, border-color .2s ease, opacity .2s ease, transform .12s ease;
            }

            .acct-btn:active {
                transform: translateY(1px);
            }

            .acct-btn-primary {
                background: var(--ink);
                color: var(--paper);
            }

            .acct-btn-quiet {
                border-color: var(--mist);
                color: var(--ink);
            }

            .acct-btn-ghost {
                color: var(--graphite);
                padding-inline: 8px;
            }

            .acct-btn-danger {
                border-color: var(--danger);
                color: var(--danger);
            }

            .acct-btn:disabled,
            .acct-btn[aria-disabled="true"] {
                opacity: .5;
                cursor: not-allowed;
                transform: none;
            }

            .acct-side .acct-btn {
                width: 100%;
            }

            /* ---------- Fields ----------
               .pd-input is a single underline everywhere else on the site. In a
               form this dense that reads as a stack of loose rules with no clear
               targets, so inside the settings page each field gets a real box. */
            .acct .pd-input {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid var(--mist);
                border-radius: .125rem;
                background: var(--paper);
                color: var(--ink);
                font: inherit;
                font-size: 14px;
                transition: border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
            }

            .acct .pd-input::placeholder {
                color: color-mix(in srgb, var(--graphite) 70%, transparent);
            }

            .acct .pd-input:focus {
                outline: none;
                border-color: var(--redline);
                box-shadow: 0 0 0 3px color-mix(in srgb, var(--redline) 18%, transparent);
            }

            .acct .pd-input[aria-invalid="true"] {
                border: 1px solid var(--error);
                box-shadow: 0 0 0 3px color-mix(in srgb, var(--error) 14%, transparent);
            }

            .acct-field {
                margin-bottom: 18px;
            }

            .acct-label {
                display: block;
                margin-bottom: 6px;
                font-size: 13px;
                font-weight: 600;
            }

            .acct-hint {
                margin-top: 6px;
                font-size: 12px;
                line-height: 1.5;
                color: var(--graphite);
            }

            /* Password fields carry a reveal button inside the box. */
            .acct-peek {
                position: relative;
            }

            .acct-peek .pd-input {
                padding-right: 44px;
            }

            .acct-peek button {
                position: absolute;
                top: 50%;
                right: 6px;
                transform: translateY(-50%);
                display: grid;
                place-items: center;
                width: 32px;
                height: 32px;
                border: 0;
                border-radius: .125rem;
                background: none;
                color: var(--graphite);
                cursor: pointer;
                transition: color .2s ease, background-color .2s ease;
            }

            @media (hover: hover) and (pointer: fine) {
                .acct-nav a:hover { background: var(--surface); color: var(--ink); }
                .acct-btn-primary:hover { opacity: .86; }
                .acct-btn-quiet:hover { background: var(--surface); }
                .acct-btn-ghost:hover { color: var(--ink); }
                .acct .pd-input:hover:not(:focus) { border-color: color-mix(in srgb, var(--ink) 22%, var(--mist)); }
                .acct-peek button:hover { color: var(--ink); background: var(--surface); }
                .acct-nav a.is-danger:hover { color: var(--danger); }
                .acct-btn-danger:hover { background: color-mix(in srgb, var(--danger) 10%, transparent); }
            }

            /* ---------- Form footer ---------- */
            .acct-actions {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 10px;
                padding-top: 18px;
                margin-top: 22px;
                border-top: 1px solid var(--mist);
            }

            .acct-saved {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                font-size: 13px;
                font-weight: 600;
                color: #16A34A;
            }

            .acct-note {
                font-size: 13px;
                color: var(--redline);
            }

            .acct-spacer {
                margin-left: auto;
            }

            @media (max-width: 480px) {
                .acct-spacer {
                    margin-left: 0;
                }
            }
        </style>
    </x-slot:head>

    <div class="acct">
        <a href="{{ route('learning.index') }}" class="acct-back">
            <x-icon name="arrow-left" class="w-4 h-4" /> {{ __('Back to My Learning') }}
        </a>

        <header class="acct-head">
            <div>
                <p class="acct-eyebrow">{{ __('Account') }}</p>
                <h1 class="font-display">{{ __('Account settings') }}</h1>
                <p class="acct-sub">{{ __('Manage your profile, password, and account.') }}</p>
            </div>

            <div class="acct-head-actions">
                <a href="{{ route('learning.profile') }}" class="acct-btn acct-btn-quiet">
                    <x-icon name="user" class="w-4 h-4" /> {{ __('View my profile') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="acct-btn acct-btn-quiet">
                        <x-icon name="logout" class="w-4 h-4" /> {{ __('Log out') }}
                    </button>
                </form>
            </div>
        </header>

        <div class="acct-grid">
            <aside class="acct-side">
                <div class="acct-card acct-id">
                    <x-user-avatar :user="$user" :size="72" />

                    <p class="acct-id-name">{{ $user->name }}</p>
                    <p class="acct-id-mail">{{ $user->email }}</p>

                    @if ($user->hasVerifiedEmail())
                        <span class="acct-chip is-ok">
                            <x-icon name="check" class="w-3 h-3" :stroke="2.6" /> {{ __('Verified') }}
                        </span>
                    @else
                        <span class="acct-chip">{{ __('Unverified') }}</span>
                    @endif

                    <div class="acct-meter">
                        <div class="acct-meter-top">
                            <span>{{ __('Profile complete') }}</span>
                            <b>{{ $complete }}%</b>
                        </div>
                        <div class="acct-meter-track" role="progressbar" aria-valuenow="{{ $complete }}"
                            aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('Profile complete') }}">
                            <div class="acct-meter-fill" style="width:{{ $complete }}%"></div>
                        </div>
                        @if ($missing->isNotEmpty())
                            <p class="acct-meter-next">{{ __('Next:') }} {{ __($missing->first()) }}.</p>
                        @else
                            <p class="acct-meter-next">{{ __('Everything is filled in. Nice.') }}</p>
                        @endif
                    </div>
                </div>

                <nav class="acct-nav" aria-label="{{ __('Settings sections') }}">
                    @foreach ($sections as $section)
                        <a href="#{{ $section['id'] }}"
                            class="{{ $section['id'] === 'danger-zone' ? 'is-danger' : '' }}"
                            data-acct-link="{{ $section['id'] }}">
                            <x-icon :name="$section['icon']" class="w-4 h-4" />
                            {{ $section['label'] }}
                        </a>
                    @endforeach
                </nav>
            </aside>

            <div class="acct-main">
                <section id="profile-information" class="acct-card">
                    @include('profile.partials.update-profile-information-form')
                </section>

                @include('profile.partials.verify-email-form')

                <section id="about-you" class="acct-card">
                    @include('profile.partials.update-public-profile-form')
                </section>

                <section id="password" class="acct-card">
                    @include('profile.partials.update-password-form')
                </section>

                <section id="danger-zone" class="acct-card is-danger">
                    @include('profile.partials.delete-user-form')
                </section>
            </div>
        </div>
    </div>

    <script>
        /* Marks the section you are reading in the sidebar: the last one whose
           top has passed under the bar. Plain anchors do the actual navigating,
           so this only ever adds a highlight -- with JavaScript off the list
           still works. */
        (function () {
            var links = Array.prototype.slice.call(document.querySelectorAll('[data-acct-link]'));
            if (!links.length) return;

            var ticking = false;

            function paint() {
                ticking = false;

                var current = links[0];

                links.forEach(function (a) {
                    var target = document.getElementById(a.dataset.acctLink);
                    if (target && target.getBoundingClientRect().top <= 150) current = a;
                });

                links.forEach(function (a) {
                    a.classList.toggle('is-current', a === current);
                });
            }

            window.addEventListener('scroll', function () {
                if (!ticking) {
                    ticking = true;
                    requestAnimationFrame(paint);
                }
            }, { passive: true });

            window.addEventListener('resize', paint);
            paint();
        })();
    </script>
</x-classroom-layout>
