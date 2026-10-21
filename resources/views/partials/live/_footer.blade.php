{{-- Footer of the live design. Holds the one Telegram QR dialog on the page. --}}
@php
    $hmKm = app()->getLocale() === 'km';
    $L = fn (string $en, string $km) => $hmKm ? $km : $en;
    $icon = fn (string $name, string $class = 'i4') => \App\Support\LiveIcon::svg($name, $class);
    // Student Platform: the assignment waiting for this student, and the first quiz.
    $liveFootTask = auth()->user() ? \App\Support\StudentWork::focus(auth()->user()) : null;
    $liveQuiz = \App\Models\Quiz::query()->published()->orderBy('sort_order')->orderBy('id')->first();

    // A page can hand in its own list (the Course Library passes the courses it is showing).
    $liveCourses = isset($footerCourses)
        ? collect($footerCourses)->take(4)
        : \App\Models\Course::query()->published()->orderBy('sort_order')->take(4)->get();
@endphp
<footer class="hm-footer">
    <div class="hm-wrap hm-footer-in">
        <div class="hm-fgrid">
            <div class="hm-fcol hm-fcol--brand">
                <span class="hm-wordmark">piseth<span>.design</span></span>
                <p class="hm-ftext">Modern creative design academy founded by university lecturer Piseth Yoeurn. Educating Cambodian creators to craft international-standard design.</p>
                <p class="hm-fmotto hm-km">រៀន Design ឲ្យច្បាស់ បង្កើតស្នាដៃឲ្យមានតម្លៃ។</p>
            </div>

            <div class="hm-fcol">
                <h4 class="hm-fhead hm-km">Courses · វគ្គសិក្សា</h4>
                <ul class="hm-flinks">
                    @foreach ($liveCourses as $liveCourse)
                        <li><a href="{{ route('courses.show', $liveCourse) }}">{{ $liveCourse->title_en ?: $liveCourse->localized('title') }}</a></li>
                    @endforeach
                    <li><a href="{{ route('courses', ['free' => 1]) }}">{{ __('Free Courses') }}</a></li>
                </ul>
            </div>

            <div class="hm-fcol">
                <h4 class="hm-fhead">Student Platform</h4>
                <ul class="hm-flinks">
                    <li><a href="{{ route('learning.index') }}">Student Dashboard</a></li>
                    <li><a href="{{ route('courses') }}">Video Classroom</a></li>
                    <li><a href="{{ route('assignments.index') }}">Assignments &amp; Feedback{{ $liveFootTask ? ' ('.$liveFootTask->code().')' : '' }}</a></li>
                    @if ($liveQuiz)
                        <li><a href="{{ route('quizzes.show', $liveQuiz) }}">{{ $liveQuiz->title_en }}</a></li>
                    @endif
                    <li><a href="{{ route('activity.index') }}">Community Activity</a></li>
                    <li><a href="{{ route('resources.index') }}">Free Khmer Fonts &amp; Templates</a></li>
                </ul>
            </div>

            <div class="hm-fcol">
                <h4 class="hm-fhead">Teacher &amp; Community</h4>
                <p class="hm-ftext">Lecturer at Build Bright University, Siem Reap.</p>
                <div class="hm-fcontact">
                    <span class="k hm-mono">Telegram Community:</span>
                    <button type="button"
                        aria-haspopup="dialog"
                        aria-label="{{ __('Telegram (show QR code)') }}"
                        x-data
                        x-on:click="$dispatch('open-telegram-qr')"
                        class="hm-tg pd-qr-trigger">@@PISETH_YOEURN {!! $icon('arrow-up-right', 'i3') !!}</button>
                    @include('partials._telegram-qr-dialog')
                    <span class="k hm-mono">Direct Mentorship:</span>
                    <a class="v" href="mailto:{{ 'pisethyoeurn69@gmail.com' }}">{{ 'pisethyoeurn69@gmail.com' }}</a>
                    <span class="k hm-mono">Follow:</span>
                    <span class="hm-fsocial">
                        <a href="{{ config('social.facebook') }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Facebook (opens in a new tab)') }}">Facebook</a>
                        <span aria-hidden="true">·</span>
                        <a href="{{ config('social.instagram') }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Instagram (opens in a new tab)') }}">Instagram</a>
                    </span>
                </div>
            </div>
        </div>

        <div class="hm-fbar">
            <p>&copy; {{ date('Y') }} piseth.design. All rights reserved. Phnom Penh, Cambodia.</p>
            <div>
                <span>Apple-inspired Minimalist UX</span>
                <span aria-hidden="true">·</span>
                <span>Khmer Creative Education</span>
                <span aria-hidden="true">·</span>
                <a href="{{ route('about') }}">About Teacher Piseth</a>
            </div>
        </div>
    </div>
</footer>
