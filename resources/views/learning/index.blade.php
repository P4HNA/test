<x-classroom-layout :title="__('My Learning') . ' — Piseth.Design'">
    <div class="max-w-6xl mx-auto px-6 py-14">
        <div class="flex flex-wrap items-end justify-between gap-4 mb-10">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] mb-2" style="color:var(--redline)">{{ __('Student desk') }}</p>
                <h1 class="font-display font-extrabold text-4xl mb-2">{{ __('Welcome back, :name.', ['name' => Str::of(auth()->user()->name)->before(' ')]) }}</h1>
                <p style="color:var(--graphite)">{{ __('Pick up the last sheet, or open another course.') }}</p>
            </div>
            <a href="{{ route('courses') }}" class="text-sm font-medium px-4 py-2 rounded-sm" style="border:1px solid var(--mist)">{{ __('Browse courses') }}</a>
        </div>

        @if ($continue && $continue->continueLesson())
            @php $resume = $continue->continueLesson(); @endphp
            <div class="pd-sheet rounded-sm p-6 mb-10 relative">
                <span class="pin"></span>
                <p class="text-xs uppercase tracking-wide mb-2" style="color:var(--graphite)">{{ __('Continue where you left off') }}</p>
                <h2 class="font-display font-bold text-2xl mb-1">{{ $continue->course->localized('title') }}</h2>
                <p class="text-sm mb-4" style="color:var(--graphite)">{{ $resume->localized('title') }} · {{ $continue->course->progressPercent(auth()->user()) }}%</p>
                <a href="{{ route('courses.lessons.show', [$continue->course, $resume]) }}" class="inline-flex text-sm font-medium px-4 py-2 rounded-sm pd-btn-ink">{{ __('Continue learning') }}</a>
            </div>
        @endif

        <div class="grid sm:grid-cols-4 gap-4 mb-12">
            <div class="pd-sheet rounded-sm p-4">
                <p class="text-xs uppercase tracking-wide mb-1" style="color:var(--graphite)">{{ __('Courses completed') }}</p>
                <p class="font-display text-3xl">{{ $coursesCompleted }}<span class="text-base opacity-50"> / {{ $enrollments->count() }}</span></p>
            </div>
            <div class="pd-sheet rounded-sm p-4">
                <p class="text-xs uppercase tracking-wide mb-1" style="color:var(--graphite)">{{ __('Lessons finished') }}</p>
                <p class="font-display text-3xl">{{ $completedLessons }}</p>
            </div>
            <div class="pd-sheet rounded-sm p-4">
                <p class="text-xs uppercase tracking-wide mb-1" style="color:var(--graphite)">{{ __('Catalogue hours') }}</p>
                <p class="font-display text-3xl">{{ $learningHours }}h</p>
            </div>
            <div class="pd-sheet rounded-sm p-4">
                <p class="text-xs uppercase tracking-wide mb-1" style="color:var(--graphite)">{{ __('Certificates') }}</p>
                <p class="font-display text-3xl">{{ $certificates->count() }}</p>
            </div>
        </div>

        <h2 class="font-display font-bold text-xl mb-5">{{ __('Enrolled courses') }}</h2>
        @if ($enrollments->isEmpty())
            <p style="color:var(--graphite)">{{ __('Nothing on the desk yet.') }} <a href="{{ route('courses') }}" style="color:var(--redline)">{{ __('Enrol in a course') }}</a>.</p>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 mb-14">
                @foreach ($enrollments as $enrollment)
                    @php
                        $course = $enrollment->course;
                        $percent = $course->progressPercent(auth()->user());
                        $next = $enrollment->continueLesson();
                    @endphp
                    <a href="{{ $next ? route('courses.lessons.show', [$course, $next]) : route('courses.show', $course) }}"
                        class="course-tile relative rounded-sm p-6 block"
                        style="background:var(--paper); border:1px solid var(--mist); color:var(--ink)">
                        <div class="font-display font-bold text-3xl mb-5">{{ $course->mark }}</div>
                        <span class="text-xs uppercase tracking-wide opacity-60">{{ __($course->level) }}</span>
                        <h3 class="font-medium mt-1 mb-3">{{ $course->localized('title') }}</h3>
                        <p class="text-xs opacity-60">{{ __(':position/:total lessons', ['position' => $course->completedLessonCount(auth()->user()), 'total' => $course->lessons->count()]) }} · {{ $percent }}%</p>
                    </a>
                @endforeach
            </div>
        @endif

        @php
            $awaiting = $enrollments->filter(
                fn ($e) => $e->course->progressPercent(auth()->user()) >= 100
                    && ! $certificates->contains('course_id', $e->course_id)
            );
        @endphp
        @if ($awaiting->isNotEmpty())
            <h2 class="font-display font-bold text-xl mb-5">{{ __('Ready for a certificate') }}</h2>
            <div class="space-y-3 mb-10">
                @foreach ($awaiting as $e)
                    <a href="{{ route('certificates.request', $e->course) }}" class="pd-sheet rounded-sm p-5 flex items-center justify-between">
                        <div>
                            <p class="font-medium">{{ $e->course->localized('title') }}</p>
                            <p class="text-xs" style="color:var(--graphite)">{{ __('You finished this course.') }}</p>
                        </div>
                        <span class="text-sm" style="color:var(--redline)">{{ __('Ask for it') }} →</span>
                    </a>
                @endforeach
            </div>
        @endif

        @if ($certificates->isNotEmpty())
            <h2 class="font-display font-bold text-xl mb-5">{{ __('Certificates') }}</h2>
            <div class="space-y-3">
                @foreach ($certificates as $certificate)
                    <a href="{{ route('certificates.show', $certificate) }}" class="pd-sheet rounded-sm p-5 flex items-center justify-between">
                        <div>
                            <p class="font-medium">{{ $certificate->course->localized('title') }}</p>
                            <p class="text-xs" style="color:var(--graphite)">{{ $certificate->code }}</p>
                        </div>
                        <span class="text-sm" style="color:var(--redline)">{{ __('View') }} →</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-classroom-layout>
