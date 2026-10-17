<x-classroom-layout :title="$course->localized('title') . ' — Piseth.Design'">
    <div class="max-w-6xl mx-auto px-6 py-14">
        <a href="{{ route('courses') }}" class="inline-flex items-center gap-1.5 text-sm mb-10 hover:opacity-70" style="color:var(--graphite)">
            ← {{ __('Back to all courses') }}
        </a>

        <div class="grid lg:grid-cols-[1fr_280px] gap-12 items-start">
            <div>
                <!-- Breadcrumb Badges -->
                <div class="flex flex-wrap items-center gap-2 mb-6">
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-sm bg-white/10 text-white backdrop-blur-md">
                        {{ __($course->category) }}
                    </span>
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-sm" style="background:var(--surface); border:1px solid var(--mist)">
                        {{ __($course->level) }}
                    </span>
                    @if ($course->isFree())
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-sm bg-green-500/20 text-green-500">{{ __('Free access') }}</span>
                    @endif
                </div>

                <h1 class="font-display font-extrabold text-4xl sm:text-5xl leading-[1.05] mb-2">{{ $course->localized('title') }}</h1>
                <p class="text-lg mb-8 opacity-80 max-w-2xl" style="color:var(--graphite)">{{ $course->localized('subtitle') }}</p>

                <!-- Instructor Row -->
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 rounded-full bg-gray-300 overflow-hidden flex items-center justify-center">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="gray" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-semibold">{{ $course->instructor_name }}</div>
                        <div class="text-xs" style="color:var(--graphite)">{{ $course->instructor_title ?? __('Instructor') }}</div>
                    </div>
                </div>

                <!-- Stats Row -->
                <div class="flex flex-wrap items-center gap-6 text-sm mb-12 py-4 border-y" style="color:var(--graphite); border-color:var(--mist)">
                    <div class="flex items-center gap-2">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="m9 8 6 4-6 4Z"/></svg>
                        <span>{{ __(':count lessons', ['count' => $course->lessons->count()]) }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>{{ $course->formattedDuration() }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15v5s-2-2-5-2-5 2-5 2v-5"/><path d="M12 15v5s2-2 5-2 5 2 5 2v-5"/><circle cx="12" cy="9" r="6"/></svg>
                        <span>{{ __('Certificate included') }}</span>
                    </div>
                </div>

                <h2 class="font-display font-bold text-xl mb-4 uppercase tracking-widest text-xs" style="color:var(--graphite)">{{ __('About this course') }}</h2>
                <p class="max-w-xl mb-12 text-sm leading-relaxed" style="color:var(--graphite)">{{ $course->localized('description') }}</p>

                @if ($course->outcomes)
                    <h2 class="font-display font-bold text-xl mb-4">{{ __('What you will learn') }}</h2>
                    <ul class="grid sm:grid-cols-2 gap-3 mb-14">
                        @foreach ($course->outcomes as $outcome)
                            <li class="pd-sheet rounded-sm p-4 text-sm flex items-start gap-3">
                                <svg class="shrink-0 text-green-500 mt-0.5" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                <span>{{ $course->localizedOutcome($outcome) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <h2 class="font-display font-bold text-xl mb-4">{{ __('Syllabus') }}</h2>
                @php
                    // Finished lessons, fetched once by the controller.
                    $completedLessonIds = $completedLessonIds ?? collect();
                    $doneCount = $completedLessonIds->count();
                    $lessonCount = $course->lessons->count();
                @endphp
                <p class="text-xs mb-6" style="color:var(--graphite)">
                    {{ __(':count modules', ['count' => $course->modules->count()]) }} · {{ __(':count lessons', ['count' => $lessonCount]) }}
                    @if ($enrollment && $lessonCount > 0)
                        · <span class="font-semibold" style="color:var(--redline)">{{ __(':done of :total lessons complete', ['done' => $doneCount, 'total' => $lessonCount]) }}</span>
                    @endif
                </p>
                <div class="space-y-4">
                    @foreach ($course->modules as $module)
                        @php $modDone = $module->lessons->whereIn('id', $completedLessonIds)->count(); @endphp
                        <details class="pd-sheet rounded-sm p-5" {{ $loop->first ? 'open' : '' }}>
                            <summary class="cursor-pointer font-medium">{{ $module->localized('title') }}</summary>
                            <p class="text-xs mt-1 mb-3" style="color:var(--graphite)">
                                {{ __(':count lessons', ['count' => $module->lessons->count()]) }}
                                @if ($enrollment && $modDone > 0)
                                    · <span style="color:var(--redline)">{{ __(':count done', ['count' => $modDone]) }}</span>
                                @endif
                            </p>
                            <ul class="space-y-1 text-sm">
                                @foreach ($module->lessons as $lesson)
                                    @php
                                        $done = $completedLessonIds->contains($lesson->id);
                                        // A free preview opens for anyone, bought or not. For a
                                        // visitor who is not signed in the link lands on login
                                        // first, which is the point: it costs an account, not money.
                                        $preview = $lesson->isFreePreviewIn($course);
                                        $openable = $enrollment || $preview;
                                    @endphp
                                    <li>
                                        {{-- Enrolled learners, and anyone on a free preview, can open a lesson straight from here. --}}
                                        @if ($openable)
                                            <a href="{{ route('courses.lessons.show', [$course, $lesson]) }}"
                                                class="syllabus-row flex items-center gap-3 -mx-2 px-2 py-1.5 rounded-sm"
                                                style="color:{{ $done ? 'var(--ink)' : 'var(--graphite)' }}">
                                        @else
                                            <span class="flex items-center gap-3 py-1.5" style="color:var(--graphite)">
                                        @endif
                                                @if ($done)
                                                    <svg class="shrink-0 text-green-500" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                                                    <span class="sr-only">{{ __('Completed') }}:</span>
                                                @else
                                                    <span class="shrink-0 w-4 h-4 rounded-full" style="border:1.5px solid var(--mist)" aria-hidden="true"></span>
                                                @endif
                                                <span class="flex-1 min-w-0">{{ $lesson->localized('title') }}</span>
                                                @if ($preview && ! $enrollment)
                                                    <span class="text-[10px] uppercase tracking-wider font-bold px-1.5 py-0.5 rounded-sm shrink-0"
                                                        style="background:var(--redline); color:var(--paper)">{{ __('Free preview') }}</span>
                                                @endif
                                                <span class="text-xs shrink-0">{{ __(':count min', ['count' => $lesson->duration_minutes]) }}</span>
                                        @if ($openable)
                                            </a>
                                        @else
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endforeach
                </div>
                <style>
                    .syllabus-row { transition: background-color .15s ease; }
                    @media (hover: hover) and (pointer: fine) {
                        .syllabus-row:hover { background: var(--mist); color: var(--ink) !important; }
                    }
                </style>
            </div>

            <aside class="pd-sheet rounded-sm relative overflow-hidden flex flex-col">
                <span class="pin" style="z-index: 10;"></span>
                
                <!-- Thumbnail / Video Preview Placeholder -->
                <div class="relative w-full aspect-[4/3] flex items-center justify-center p-6" style="background:{{ $course->swatch()['bg'] }}">
                    @if ($thumb = $course->thumbnailSrc())
                        <img src="{{ $thumb }}" alt="" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover">
                    @else
                        <div class="font-display font-bold text-7xl opacity-80" style="color:{{ $course->swatch()['fg'] }}">{{ $course->mark }}</div>
                    @endif
                    <div class="absolute inset-0 flex items-center justify-center bg-black/20 backdrop-blur-[2px]">
                        <div class="w-14 h-14 rounded-full flex items-center justify-center bg-white/90 shadow-lg cursor-pointer hover:scale-105 transition-transform">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="var(--ink)" stroke="none"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        </div>
                    </div>
                </div>

                <div class="p-6 pt-5">
                    <p class="text-[10px] font-bold uppercase tracking-widest mb-1" style="color:var(--graphite)">{{ __('Access Level') }}</p>
                    @php $sale = $course->activeDiscount(); @endphp
                    @if ($course->isFree())
                        <p class="font-bold text-3xl mb-6">{{ __('100% Free') }}</p>
                    @elseif ($sale && $course->isOnSale())
                        {{-- The discount is real: checkout charges this price (Course::salePrice()). --}}
                        <p class="font-bold text-3xl mb-2"><x-sale-price :course="$course" /></p>
                        <p class="text-sm mb-6" style="color:var(--graphite)">
                            <span class="font-semibold" style="color:var(--action-ink)">{{ __(':percent% off', ['percent' => $sale->discount_percent]) }}</span>
                            · {{ $sale->localized('title') }}
                            @if ($sale->ends_at)
                                · <time datetime="{{ $sale->ends_at->toIso8601String() }}">{{ __('ends :date', ['date' => $sale->ends_at->translatedFormat('j M')]) }}</time>
                            @endif
                        </p>
                    @else
                        <p class="font-bold text-3xl mb-6">{{ $course->priceLabel() }}</p>
                    @endif

                    @auth
                        @if ($enrollment)
                            <a href="{{ route('courses.lessons.show', [$course, $enrollment->continueLesson() ?? $course->firstLesson()]) }}"
                                class="block text-center text-sm font-bold px-4 py-3.5 rounded-sm mb-6 bg-[var(--ink)] text-[var(--paper)] hover:opacity-90">
                                {{ __('Continue Learning') }} · {{ $progress }}%
                            </a>
                        @else
                            @if ($course->isFree())
                                <form method="POST" action="{{ route('courses.enroll', $course) }}">
                                    @csrf
                                    <button type="submit" class="w-full text-sm font-bold px-4 py-3.5 rounded-sm mb-6 bg-[var(--ink)] text-[var(--paper)] hover:opacity-90 flex items-center justify-center gap-2">
                                        {{ __('Enrol For Free') }}
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('payments.checkout', $course) }}" class="block w-full text-center text-sm font-bold px-4 py-3.5 rounded-sm mb-6 bg-[var(--ink)] text-[var(--paper)] hover:opacity-90">
                                    {{ __('Pay :amount with KHQR', ['amount' => '$'.number_format($course->salePrice(), 2)]) }}
                                </a>
                            @endif
                        @endif
                    @else
                        <a href="{{ route('register') }}" class="block text-center text-sm font-bold px-4 py-3.5 rounded-sm mb-6 bg-[var(--ink)] text-[var(--paper)] hover:opacity-90 flex items-center justify-center gap-2">
                            {{ __('Create Account To Enrol') }}
                        </a>
                    @endauth

                    <p class="text-[10px] font-bold uppercase tracking-widest mb-4 pb-4 border-b" style="color:var(--graphite); border-color:var(--mist)">{{ __('Course Includes') }}</p>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-center gap-3"><svg class="shrink-0 text-green-500" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> {{ __('Lifetime access') }}</li>
                        <li class="flex items-center gap-3"><svg class="shrink-0 text-green-500" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> {{ __('YouTube & Drive lessons') }}</li>
                        <li class="flex items-center gap-3"><svg class="shrink-0 text-green-500" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> {{ __('English and Khmer titles') }}</li>
                        <li class="flex items-center gap-3"><svg class="shrink-0 text-green-500" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> {{ __('Mark complete as you go') }}</li>
                        <li class="flex items-center gap-3"><svg class="shrink-0 text-green-500" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> {{ __('Certificate on finish') }}</li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</x-classroom-layout>
