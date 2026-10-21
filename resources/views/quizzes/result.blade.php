{{-- A finished quiz attempt: the score and every answer next to the right one. --}}
@php
    $hmKm = app()->getLocale() === 'km';
    $L = fn (string $en, string $km) => $hmKm ? $km : $en;
    $km = $hmKm ? 'hm-km' : '';
    $icon = fn (string $name, string $class = 'i4') => \App\Support\LiveIcon::svg($name, $class);
    $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
    $answers = (array) $attempt->answers;
@endphp

<x-live-layout :title="$quiz->title_en.' — '.$attempt->score.'% · Piseth.Design'" main-class="qz-main">
    <x-slot:styles>
        @include('partials.live._quiz-styles')
    </x-slot:styles>

    <div class="qz-page">
        <a href="{{ route('assignments.index') }}" class="qz-back">{!! $icon('chevron-left') !!}<span class="{{ $km }}">{{ $L('Back to assignments & quizzes', 'ត្រឡប់ទៅកិច្ចការ និងតេស្ត') }}</span></a>

        <article class="qz-card" data-fx>
            <div class="qz-hero">
                <img src="{{ $quiz->pictureSrc() }}" alt="" decoding="async">
                <div class="qz-chips">
                    <div><span class="qz-chip qz-mono">{!! $icon('sparkles', 'i35') !!}{{ $quiz->course->category ?: 'Design' }}</span></div>
                    <span class="qz-chip qz-mono">{{ $attempt->created_at->locale('en')->isoFormat('MMM D, YYYY') }}</span>
                </div>
                <div class="qz-title">
                    <h1>{{ $quiz->title_en }}</h1>
                    @if (filled($quiz->title_km))<p class="hm-km">{{ $quiz->title_km }}</p>@endif
                </div>
            </div>
            <div class="qz-done" data-done>
                <div class="qz-ring">
                    <svg viewBox="0 0 100 100" aria-hidden="true">
                        <defs><linearGradient id="qzGrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4DA3FF" /><stop offset="1" stop-color="#A855F7" /></linearGradient></defs>
                        <circle class="bg" cx="50" cy="50" r="46" />
                        <circle class="fg" cx="50" cy="50" r="46" data-ring data-score="{{ $attempt->score }}" />
                    </svg>
                    <b><span data-fx-count>{{ $attempt->score }}%</span><small>{{ $attempt->correct }} / {{ $attempt->total }} {{ $L('correct', 'ត្រឹមត្រូវ') }}</small></b>
                </div>
                <span class="qz-pass {{ $attempt->passed ? 'ok' : 'no' }} {{ $km }}" data-flag>{{ $attempt->passed ? $L('Passed', 'ជាប់').' ✓' : $L('Keep practising', 'បន្តហាត់ទៀត') }}</span>
                <p class="{{ $km }}">{{ $attempt->passed ? $L('Great work. This quiz is passed and shows on your profile.', 'ល្អណាស់! អ្នកបានជាប់តេស្តនេះ ហើយវាបង្ហាញលើប្រវត្តិរូបរបស់អ្នក។') : $L('You need '.$quiz->pass_percent.'% to pass. Read the answers below and try again.', 'ត្រូវការ '.$quiz->pass_percent.'% ដើម្បីជាប់។ អានចម្លើយខាងក្រោម ហើយសាកម្តងទៀត។') }}</p>
                <div class="row">
                    <a href="{{ route('quizzes.show', $quiz) }}" class="qz-btn fx-btn">{!! $icon('trending-up', 'i35') !!}<span class="{{ $km }}">{{ $L('Retake quiz', 'ធ្វើតេស្តម្តងទៀត') }}</span></a>
                    <a href="{{ route('learning.profile') }}#quizzes" class="qz-btn qz-btn--ghost fx-btn">{!! $icon('user', 'i35') !!}<span class="{{ $km }}">{{ $L('My results', 'លទ្ធផលរបស់ខ្ញុំ') }}</span></a>
                </div>
            </div>
        </article>

        <section class="qz-review" aria-label="{{ $L('Your answers', 'ចម្លើយរបស់អ្នក') }}">
            @foreach ($quiz->questions as $i => $question)
                @php
                    $picked = $answers[$question->id] ?? null;
                    $right = $picked !== null && (int) $picked === $question->correct_index;
                    $why = $question->localized('explanation');
                @endphp
                <div class="qz-item {{ $right ? 'is-right' : 'is-wrong' }}" data-fx>
                    <p class="qz-label">{{ $L('Question', 'សំណួរ') }} {{ $i + 1 }} · {{ $right ? $L('Correct', 'ត្រឹមត្រូវ') : $L('Missed', 'ខុស') }}</p>
                    <h3>{{ $question->prompt_en }}@if (filled($question->prompt_km))<br><span class="hm-km" style="font-weight:500; color:var(--mut)">{{ $question->prompt_km }}</span>@endif</h3>
                    <ul>
                        @foreach ($question->optionTexts() as $o => $text)
                            <li class="{{ $o === $question->correct_index ? 'right' : '' }} {{ $picked !== null && (int) $picked === $o ? 'picked' : '' }}">
                                <span class="k">{{ $letters[$o] ?? $o + 1 }}</span><span>{{ $text }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if (filled($why))<p class="why">{{ $why }}</p>@endif
                </div>
            @endforeach
        </section>
    </div>

    <x-slot:scripts>
        <script>
            (function () {
                var ring = document.querySelector('[data-ring]');
                if (!ring) return;
                var score = parseInt(ring.getAttribute('data-score'), 10) || 0;
                requestAnimationFrame(function () { requestAnimationFrame(function () { ring.style.strokeDashoffset = (289 * (1 - score / 100)).toFixed(1); }); });
                @if ($attempt->passed)
                    setTimeout(function () { if (window.fxBurst) fxBurst(document.querySelector('[data-flag]'), '#10B981'); }, 1300);
                @endif
            })();
        </script>
    </x-slot:scripts>
</x-live-layout>
