@php
    // Month markers for the activity grid: label a column only when its week
    // opens a new month, so the strip reads like a calendar rather than a wall.
    $monthLabels = [];
    $lastMonth = null;

    foreach ($grid as $index => $week) {
        $month = \Illuminate\Support\Carbon::parse($week[0]['date'])->isoFormat('MMM');
        $monthLabels[$index] = $month === $lastMonth ? null : $month;
        $lastMonth = $month;
    }

    $lessonsInGrid = collect($grid)->flatten(1)->sum('count');
    $checklist = $user->profileChecklist();
    $completeness = $user->profileCompleteness();
    $todo = collect($checklist)->reject(fn ($done) => $done)->keys();

    $tabs = [
        'courses' => __('Courses'),
        'certificates' => __('Certificates'),
        'activity' => __('Activity'),
        'payments' => __('Payments'),
    ];

    $tabCounts = [
        'courses' => $user->enrollments->count(),
        'certificates' => $user->certificates->count(),
        'activity' => $recent->count(),
        'payments' => $payments->count(),
    ];
@endphp

<x-classroom-layout :title="__('Profile') . ' — Piseth.Design'">
    <x-slot name="head">
        <script>
            /* Tab panels are only collapsed when the script that drives them can
               actually run. With no JS the page simply renders all four sections
               stacked, which is the honest fallback for real content. Alpine
               clears this class on init and takes over with .pf-hidden. */
            document.documentElement.classList.add('js-tabs');
        </script>
        <style>
            /* ============================================================
               STUDENT PROFILE
               Page-scoped; everything is prefixed .pf- so nothing here can
               reach the shared classroom styles.
               ============================================================ */

            .js-tabs [data-tabpanel]:not([data-tabpanel="courses"]) { display: none; }
            .pf-hidden { display: none; }

            /* ---------- Header ---------- */
            .pf-head {
                display: flex;
                flex-wrap: wrap;
                align-items: flex-start;
                gap: 20px;
            }

            .pf-chip {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 3px 9px;
                border-radius: 999px;
                border: 1px solid var(--mist);
                font-size: 11px;
                font-weight: 600;
                letter-spacing: .06em;
                text-transform: uppercase;
                color: var(--graphite);
                white-space: nowrap;
            }

            .pf-chip.is-role {
                border-color: color-mix(in srgb, var(--redline) 45%, transparent);
                color: var(--redline);
            }

            .pf-chip.is-verified {
                border-color: color-mix(in srgb, var(--blue) 45%, transparent);
                color: var(--blue);
            }

            .pf-meta {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 6px 16px;
                font-size: 13px;
                color: var(--graphite);
            }

            .pf-meta span,
            .pf-meta a {
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }

            .pf-meta a:hover { color: var(--ink); }

            /* ---------- Completeness meter ---------- */
            .pf-meter {
                height: 6px;
                border-radius: 999px;
                background: var(--mist);
                overflow: hidden;
            }

            .pf-meter i {
                display: block;
                height: 100%;
                border-radius: inherit;
                background: var(--redline);
                transition: width .6s cubic-bezier(.16, 1, .3, 1);
            }

            /* ---------- Stat tiles ---------- */
            .pf-stat {
                padding: 16px;
                border: 1px solid var(--mist);
                background: var(--paper);
            }

            .pf-stat dt {
                font-size: 11px;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: var(--graphite);
                margin-bottom: 4px;
            }

            .pf-stat dd {
                margin: 0;
                font-size: 28px;
                line-height: 1.1;
            }

            .pf-stat dd small {
                font-size: 14px;
                opacity: .5;
            }

            .pf-stat-sub {
                display: block;
                margin-top: 3px;
                font-size: 11px;
                letter-spacing: .04em;
                opacity: 1;
                color: var(--graphite);
            }

            /* ---------- Activity grid ---------- */
            .pf-scroll {
                overflow-x: auto;
                padding-bottom: 4px;
            }

            .pf-weeks {
                display: flex;
                gap: 3px;
                min-width: max-content;
            }

            .pf-week {
                display: grid;
                grid-template-rows: repeat(7, 1fr);
                gap: 3px;
            }

            .pf-day {
                width: 11px;
                height: 11px;
                border-radius: 2px;
                background: var(--mist);
            }

            .pf-day[data-level="1"] { background: color-mix(in srgb, var(--redline) 28%, var(--mist)); }
            .pf-day[data-level="2"] { background: color-mix(in srgb, var(--redline) 52%, var(--mist)); }
            .pf-day[data-level="3"] { background: color-mix(in srgb, var(--redline) 76%, var(--mist)); }
            .pf-day[data-level="4"] { background: var(--redline); }
            .pf-day[data-future="1"] { background: transparent; }

            .pf-months {
                display: flex;
                gap: 3px;
                min-width: max-content;
                font-size: 10px;
                letter-spacing: .06em;
                text-transform: uppercase;
                color: var(--graphite);
                margin-bottom: 6px;
            }

            .pf-months span {
                width: 11px;
                white-space: nowrap;
            }

            /* ---------- Badges ---------- */
            .pf-badges {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(168px, 1fr));
                gap: 12px;
            }

            .pf-badge {
                display: flex;
                align-items: center;
                gap: 11px;
                padding: 12px;
                border: 1px solid var(--mist);
            }

            .pf-badge i {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 34px;
                height: 34px;
                border-radius: 50%;
                flex: 0 0 auto;
                background: color-mix(in srgb, var(--redline) 12%, transparent);
                color: var(--redline);
            }

            .pf-badge.is-locked {
                border-style: dashed;
                opacity: .55;
            }

            .pf-badge.is-locked i {
                background: var(--mist);
                color: var(--graphite);
            }

            /* ---------- Tabs ---------- */
            .pf-tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 2px;
                border-bottom: 1px solid var(--mist);
                margin-bottom: 24px;
            }

            .pf-tab {
                appearance: none;
                background: none;
                border: 0;
                border-bottom: 2px solid transparent;
                margin-bottom: -1px;
                padding: 10px 14px;
                font: inherit;
                font-size: 14px;
                color: var(--graphite);
                cursor: pointer;
                transition: color .2s ease, border-color .2s ease;
            }

            .pf-tab:hover { color: var(--ink); }

            .pf-tab[aria-selected="true"] {
                color: var(--ink);
                font-weight: 600;
                border-bottom-color: var(--redline);
            }

            .pf-tab b {
                font-weight: inherit;
                opacity: .5;
                margin-left: 6px;
                font-variant-numeric: tabular-nums;
            }

            /* ---------- Progress bar on a course row ---------- */
            .pf-bar {
                height: 4px;
                border-radius: 999px;
                background: var(--mist);
                overflow: hidden;
            }

            .pf-bar i {
                display: block;
                height: 100%;
                border-radius: inherit;
                background: var(--ink);
            }

            .pf-bar.is-done i { background: var(--redline); }

            /* ---------- Activity feed ---------- */
            .pf-feed {
                list-style: none;
                margin: 0;
                padding: 0 0 0 22px;
                border-left: 1px solid var(--mist);
            }

            .pf-feed li {
                position: relative;
                padding-bottom: 20px;
            }

            .pf-feed li::before {
                content: "";
                position: absolute;
                left: -26px;
                top: 6px;
                width: 9px;
                height: 9px;
                border-radius: 50%;
                background: var(--paper);
                border: 1.5px solid var(--redline);
            }

            @media (max-width: 640px) {
                .pf-stat dd { font-size: 23px; }
            }
        </style>
    </x-slot>

    <div class="max-w-5xl mx-auto px-6 py-14">

        {{-- ================= Header ================= --}}
        <section class="pd-sheet rounded-sm p-6 sm:p-8 mb-5">
            <div class="pf-head">
                <x-user-avatar :user="$user" :size="88" />

                {{-- 200px, not more: at 400px wide the avatar, gutters and this
                     column have to fit on one line before the button wraps. --}}
                <div class="flex-1 min-w-[200px]">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="pf-chip is-role">{{ $user->isAdmin() ? __('Instructor') : __('Student') }}</span>
                        @if ($user->hasVerifiedEmail())
                            <span class="pf-chip is-verified">
                                <x-icon name="check" class="w-3 h-3" /> {{ __('Verified') }}
                            </span>
                        @endif
                    </div>

                    <h1 class="font-display font-bold text-3xl leading-tight">{{ $user->name }}</h1>

                    @if ($user->headline)
                        <p class="mt-1 text-[15px]" style="color:var(--ink)">{{ $user->headline }}</p>
                    @endif

                    <div class="pf-meta mt-3">
                        @if ($user->location)
                            <span><x-icon name="globe" class="w-3.5 h-3.5" /> {{ $user->location }}</span>
                        @endif
                        <span><x-icon name="calendar" class="w-3.5 h-3.5" /> {{ __('Joined :date', ['date' => $user->created_at?->isoFormat('MMMM YYYY')]) }}</span>
                        <span><x-icon name="mail" class="w-3.5 h-3.5" /> {{ $user->email }}</span>
                        @if ($user->phone)
                            <span><x-icon name="phone" class="w-3.5 h-3.5" /> {{ $user->phone }}</span>
                        @endif
                        @if ($user->website)
                            <a href="{{ $user->website }}" target="_blank" rel="noopener noreferrer nofollow">
                                <x-icon name="link" class="w-3.5 h-3.5" />
                                {{ preg_replace('#^https?://(www\.)?#i', '', rtrim($user->website, '/')) }}
                            </a>
                        @endif
                    </div>

                    @if ($user->bio)
                        <p class="mt-4 text-sm leading-relaxed max-w-prose" style="color:var(--graphite)">{{ $user->bio }}</p>
                    @endif
                </div>

                <a href="{{ route('profile.edit') }}"
                    class="text-sm px-4 py-2 rounded-sm shrink-0 inline-flex items-center gap-2"
                    style="border:1px solid var(--mist)">
                    <x-icon name="pencil-line" class="w-4 h-4" /> {{ __('Edit profile') }}
                </a>
            </div>
        </section>

        {{-- Only nag while there is something left to fill in. --}}
        @if ($completeness < 100)
            <section class="pd-sheet rounded-sm p-5 mb-10">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
                    <p class="text-sm font-medium">{{ __('Profile :percent% complete', ['percent' => $completeness]) }}</p>
                    <p class="text-xs" style="color:var(--graphite)">
                        {{ __('Next: :task', ['task' => __($todo->first())]) }}
                    </p>
                </div>
                <div class="pf-meter" role="progressbar" aria-valuenow="{{ $completeness }}" aria-valuemin="0" aria-valuemax="100"
                    aria-label="{{ __('Profile completeness') }}">
                    <i style="width:{{ $completeness }}%"></i>
                </div>
                <div class="flex flex-wrap gap-2 mt-3">
                    @foreach ($todo as $task)
                        <a href="{{ route('profile.edit') }}" class="pf-chip" style="text-transform:none; letter-spacing:0; font-weight:500">
                            <x-icon name="plus" class="w-3 h-3" /> {{ __($task) }}
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ================= Numbers ================= --}}
        <dl class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-10">
            <div class="pf-stat rounded-sm">
                <dt>{{ __('Courses') }}</dt>
                <dd class="font-display">{{ $user->enrollments->count() }}</dd>
            </div>
            <div class="pf-stat rounded-sm">
                <dt>{{ __('Lessons') }}</dt>
                <dd class="font-display">{{ $user->lessonCompletions()->count() }}</dd>
            </div>
            <div class="pf-stat rounded-sm">
                <dt>{{ __('Hours learned') }}</dt>
                <dd class="font-display">{{ $stats->hoursLearned() }}<small>h</small></dd>
            </div>
            <div class="pf-stat rounded-sm">
                <dt>{{ __('Certificates') }}</dt>
                <dd class="font-display">{{ $user->certificates->count() }}</dd>
            </div>
            <div class="pf-stat rounded-sm">
                <dt>{{ __('Day streak') }}</dt>
                {{-- Current run reads big; the personal best sits under it so a
                     broken streak still has something to show. --}}
                <dd class="font-display">
                    {{ $stats->currentStreak() }}
                    <small class="pf-stat-sub">{{ __('best :count', ['count' => $stats->longestStreak()]) }}</small>
                </dd>
            </div>
        </dl>

        {{-- ================= Activity grid ================= --}}
        <section class="pd-sheet rounded-sm p-6 mb-5">
            <div class="flex flex-wrap items-baseline justify-between gap-2 mb-5">
                <h2 class="font-display font-bold text-xl">{{ __('Activity') }}</h2>
                <p class="text-sm" style="color:var(--graphite)">
                    {{ __(':count lessons finished in the last :weeks weeks', ['count' => $lessonsInGrid, 'weeks' => \App\Support\LearningStats::WEEKS]) }}
                </p>
            </div>

            <div class="pf-scroll">
                <div class="pf-months" aria-hidden="true">
                    @foreach ($grid as $index => $week)
                        <span>{{ $monthLabels[$index] ?? '' }}</span>
                    @endforeach
                </div>

                {{-- A table would imply the columns mean something on their own;
                     the honest reading is one list of days, so the summary above
                     carries the meaning for screen readers and this is decorative. --}}
                <div class="pf-weeks" role="img"
                    aria-label="{{ __(':count lessons finished in the last :weeks weeks', ['count' => $lessonsInGrid, 'weeks' => \App\Support\LearningStats::WEEKS]) }}">
                    @foreach ($grid as $week)
                        <div class="pf-week">
                            @foreach ($week as $day)
                                <span class="pf-day"
                                    data-level="{{ $day['level'] }}"
                                    @if ($day['future']) data-future="1" @endif
                                    title="{{ $day['future'] ? '' : trans_choice('{0}No lessons on :date|{1}1 lesson on :date|[2,*]:count lessons on :date', $day['count'], ['count' => $day['count'], 'date' => $day['label']]) }}"></span>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 mt-5 text-xs" style="color:var(--graphite)">
                <p>
                    @if ($stats->currentStreak() > 0)
                        {{ trans_choice('{1}On a :count day streak.|[2,*]On a :count day streak.', $stats->currentStreak(), ['count' => $stats->currentStreak()]) }}
                    @else
                        {{ __('No streak running — finish a lesson today to start one.') }}
                    @endif
                    {{ __('Best run: :count days.', ['count' => $stats->longestStreak()]) }}
                </p>
                <div class="flex items-center gap-1.5">
                    <span>{{ __('Less') }}</span>
                    @foreach ([0, 1, 2, 3, 4] as $level)
                        <span class="pf-day" data-level="{{ $level }}"></span>
                    @endforeach
                    <span>{{ __('More') }}</span>
                </div>
            </div>
        </section>

        {{-- ================= Badges ================= --}}
        <section class="pd-sheet rounded-sm p-6 mb-12">
            <h2 class="font-display font-bold text-xl mb-5">{{ __('Milestones') }}</h2>
            <div class="pf-badges">
                @foreach ($badges as $badge)
                    <div class="pf-badge rounded-sm {{ $badge['earned'] ? '' : 'is-locked' }}">
                        <i><x-icon name="{{ $badge['icon'] }}" class="w-[18px] h-[18px]" /></i>
                        <div class="min-w-0">
                            <p class="text-sm font-medium">{{ __($badge['label']) }}</p>
                            <p class="text-xs" style="color:var(--graphite)">
                                {{ $badge['earned'] ? __($badge['note']) : __('Not yet: :note', ['note' => __($badge['note'])]) }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- ================= Tabbed detail ================= --}}
        <div
            x-data="{
                tab: 'courses',
                order: {{ Js::from(array_keys($tabs)) }},
                move(step) {
                    const next = (this.order.indexOf(this.tab) + step + this.order.length) % this.order.length;
                    this.tab = this.order[next];
                    this.$refs[this.tab].focus();
                },
            }"
            x-init="document.documentElement.classList.remove('js-tabs')"
        >
            <div class="pf-tabs" role="tablist" aria-label="{{ __('Profile sections') }}">
                @foreach ($tabs as $key => $label)
                    <button type="button"
                        class="pf-tab"
                        role="tab"
                        id="pf-tab-{{ $key }}"
                        x-ref="{{ $key }}"
                        aria-controls="pf-panel-{{ $key }}"
                        :aria-selected="tab === '{{ $key }}' ? 'true' : 'false'"
                        :tabindex="tab === '{{ $key }}' ? 0 : -1"
                        aria-selected="{{ $key === 'courses' ? 'true' : 'false' }}"
                        @click="tab = '{{ $key }}'"
                        @keydown.right.prevent="move(1)"
                        @keydown.left.prevent="move(-1)"
                        @keydown.home.prevent="tab = order[0]; $refs[tab].focus()"
                        @keydown.end.prevent="tab = order[order.length - 1]; $refs[tab].focus()">
                        {{ $label }}<b>{{ $tabCounts[$key] }}</b>
                    </button>
                @endforeach
            </div>

            {{-- ---------- Courses ---------- --}}
            <div data-tabpanel="courses" id="pf-panel-courses" role="tabpanel" aria-labelledby="pf-tab-courses"
                tabindex="0" :class="{ 'pf-hidden': tab !== 'courses' }">
                <div class="space-y-3">
                    @forelse ($user->enrollments as $enrollment)
                        @php
                            $course = $enrollment->course;
                            $percent = $course->progressPercent($user);
                            $done = $course->completedLessonCount($user);
                            $total = $course->lessons->count();
                        @endphp
                        <a href="{{ route('courses.show', $course) }}" class="pd-sheet rounded-sm p-5 block">
                            <div class="flex items-start justify-between gap-4 mb-3">
                                <div class="min-w-0">
                                    <p class="font-medium">{{ $course->localized('title') }}</p>
                                    <p class="text-xs mt-0.5" style="color:var(--graphite)">
                                        {{ __($course->category) }} ·
                                        {{ __('enrolled :date', ['date' => $enrollment->enrolled_at?->toDateString()]) }}
                                    </p>
                                </div>
                                <span class="font-display text-lg shrink-0">{{ $percent }}%</span>
                            </div>
                            <div class="pf-bar {{ $percent >= 100 ? 'is-done' : '' }}">
                                <i style="width:{{ $percent }}%"></i>
                            </div>
                            <p class="text-xs mt-2" style="color:var(--graphite)">
                                {{ __(':done of :total lessons', ['done' => $done, 'total' => $total]) }}
                            </p>
                        </a>
                    @empty
                        <p style="color:var(--graphite)">
                            {{ __('No courses yet.') }}
                            <a href="{{ route('courses') }}" style="color:var(--redline)">{{ __('Browse courses') }}</a>
                        </p>
                    @endforelse
                </div>
            </div>

            {{-- ---------- Certificates ---------- --}}
            <div data-tabpanel="certificates" id="pf-panel-certificates" role="tabpanel" aria-labelledby="pf-tab-certificates"
                tabindex="0" :class="{ 'pf-hidden': tab !== 'certificates' }">
                <div class="grid sm:grid-cols-2 gap-3">
                    @forelse ($user->certificates as $certificate)
                        <div class="pd-sheet rounded-sm p-5">
                            <div class="flex items-start gap-3 mb-3">
                                <x-icon name="award" class="w-5 h-5 shrink-0 mt-0.5" style="color:var(--redline)" />
                                <div class="min-w-0">
                                    <p class="font-medium">{{ $certificate->course->localized('title') }}</p>
                                    <p class="text-xs mt-0.5" style="color:var(--graphite)">
                                        {{ __('Issued :date', ['date' => $certificate->issued_at?->isoFormat('D MMM YYYY')]) }}
                                    </p>
                                </div>
                            </div>

                            {{-- The code is what someone else types to check the certificate,
                                 so it needs to leave the page cleanly. --}}
                            <div class="flex items-center gap-2" x-data="{ copied: false }">
                                <code class="text-xs px-2 py-1 rounded-sm flex-1 truncate" style="background:var(--surface)">{{ $certificate->code }}</code>
                                <button type="button"
                                    class="text-xs px-2.5 py-1.5 rounded-sm inline-flex items-center gap-1.5"
                                    style="border:1px solid var(--mist)"
                                    @click="navigator.clipboard.writeText('{{ $certificate->code }}').then(() => { copied = true; setTimeout(() => copied = false, 2000) })">
                                    <x-icon name="copy" class="w-3.5 h-3.5" />
                                    <span x-text="copied ? '{{ __('Copied') }}' : '{{ __('Copy') }}'">{{ __('Copy') }}</span>
                                </button>
                                <a href="{{ route('certificates.show', $certificate) }}"
                                    class="text-xs px-2.5 py-1.5 rounded-sm pd-btn-ink">{{ __('View') }}</a>
                            </div>
                        </div>
                    @empty
                        <p style="color:var(--graphite)">{{ __('Finish a course to stamp a certificate.') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- ---------- Activity feed ---------- --}}
            <div data-tabpanel="activity" id="pf-panel-activity" role="tabpanel" aria-labelledby="pf-tab-activity"
                tabindex="0" :class="{ 'pf-hidden': tab !== 'activity' }">
                @if ($recent->isEmpty())
                    <p style="color:var(--graphite)">{{ __('Nothing finished yet. Your lessons will show up here.') }}</p>
                @else
                    <ul class="pf-feed">
                        @foreach ($recent as $completion)
                            @php $course = $completion->lesson->module->course; @endphp
                            <li>
                                <p class="text-sm">
                                    {{ __('Finished') }}
                                    <a href="{{ route('courses.lessons.show', [$course, $completion->lesson]) }}" class="font-medium hover:underline">
                                        {{ $completion->lesson->localized('title') }}
                                    </a>
                                </p>
                                <p class="text-xs mt-0.5" style="color:var(--graphite)">
                                    {{ $course->localized('title') }} ·
                                    {{ ($completion->completed_at ?? $completion->created_at)?->isoFormat('D MMM YYYY, HH:mm') }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- ---------- Payments ---------- --}}
            {{--
                Read-only on purpose: this is the student's own receipt book, and
                it is also the record that explains why they have access to a
                course, so nothing here can be edited or removed.
            --}}
            <div data-tabpanel="payments" id="pf-panel-payments" role="tabpanel" aria-labelledby="pf-tab-payments"
                tabindex="0" :class="{ 'pf-hidden': tab !== 'payments' }">
                <div class="space-y-3">
                    @forelse ($payments as $payment)
                        @php
                            // Colours come from the classroom palette, not the admin one.
                            $tone = match ($payment->statusTone()) {
                                'ok' => 'var(--redline)',
                                'warn' => 'var(--graphite)',
                                default => 'var(--graphite)',
                            };
                        @endphp
                        <div class="pd-sheet rounded-sm p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-medium">
                                        @if ($payment->course)
                                            <a href="{{ route('courses.show', $payment->course) }}" class="hover:underline">
                                                {{ $payment->course->localized('title') }}
                                            </a>
                                        @else
                                            {{-- The course is no longer on sale; the receipt still stands. --}}
                                            {{ $payment->courseTitle() }}
                                        @endif
                                    </p>
                                    <p class="text-xs mt-1" style="color:var(--graphite)">
                                        {{ ($payment->paid_at ?? $payment->created_at)?->format('j M Y, H:i') }}
                                        · <span class="font-mono">{{ $payment->invoice_no }}</span>
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="font-display text-lg">{{ $payment->formattedAmount() }}</p>
                                    <p class="text-xs font-medium" style="color:{{ $tone }}">
                                        {{ $payment->statusLabel() }}
                                    </p>
                                </div>
                            </div>

                            {{-- Only say something when there is something for them to do. --}}
                            @if ($payment->isPaid())
                                @if ($payment->course)
                                    <p class="text-xs mt-3" style="color:var(--graphite)">
                                        {{ __('Unlocked — this course is yours to keep.') }}
                                    </p>
                                @endif
                            @elseif ($payment->status === 'review')
                                <p class="text-xs mt-3" style="color:var(--graphite)">
                                    {{ __('We are checking this payment by hand. Contact us with the invoice number above if it stays this way.') }}
                                </p>
                            @elseif ($payment->isRefunded())
                                <p class="text-xs mt-3" style="color:var(--graphite)">
                                    {{ __('This payment was refunded on :date, so the course was closed again. Your lesson progress is saved if you decide to buy it back.', ['date' => $payment->refunded_at?->format('j M Y')]) }}
                                </p>
                            @elseif ($payment->course && ! $user->isEnrolledIn($payment->course))
                                <a href="{{ route('payments.checkout', $payment->course) }}"
                                    class="inline-block text-xs mt-3 px-3 py-1.5 rounded-sm"
                                    style="border:1px solid var(--mist)">
                                    {{ __('Try paying again') }}
                                </a>
                            @endif
                        </div>
                    @empty
                        <p style="color:var(--graphite)">{{ __('No payments yet. Free courses do not appear here.') }}</p>
                    @endforelse
                </div>

                @if ($payments->isNotEmpty())
                    <p class="text-xs mt-4" style="color:var(--graphite)">
                        {{ __('Payment records are kept for accounting and tax purposes.') }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</x-classroom-layout>
