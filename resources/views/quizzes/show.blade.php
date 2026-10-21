{{-- Taking a quiz (/quizzes/{uuid}), on the live piseth.design design. Works as one plain form without JavaScript. --}}
@php
    $hmKm = app()->getLocale() === 'km';
    $L = fn (string $en, string $km) => $hmKm ? $km : $en;
    $km = $hmKm ? 'hm-km' : '';
    $icon = fn (string $name, string $class = 'i4') => \App\Support\LiveIcon::svg($name, $class);
    $questions = $quiz->questions;
    $total = $questions->count();
    $letters = ['A', 'B', 'C', 'D', 'E', 'F'];
    $qzText = [
        'check' => $L('Submit Answer', 'ផ្ញើចម្លើយ'),
        'next' => $L('Next Question', 'សំណួរបន្ទាប់'),
        'finish' => $L('See my score', 'មើលពិន្ទុរបស់ខ្ញុំ'),
        'pick' => $L('Select an answer to continue', 'សូមជ្រើសរើសចម្លើយមួយ'),
        'right' => $L('Correct!', 'ត្រឹមត្រូវ!'),
        'wrong' => $L('Not quite. The right answer is highlighted.', 'មិនទាន់ត្រឹមត្រូវ។ ចម្លើយត្រឹមត្រូវត្រូវបានបន្លិច។'),
        'pass' => $L('Passed', 'ជាប់'),
        'fail' => $L('Keep practising', 'បន្តហាត់ទៀត'),
        'passLine' => $L('Great work. This quiz is passed and shows on your profile.', 'ល្អណាស់! អ្នកបានជាប់តេស្តនេះ ហើយវាបង្ហាញលើប្រវត្តិរូបរបស់អ្នក។'),
        'failLine' => $L('You need :pass% to pass. Review the answers and try again.', 'ត្រូវការ :pass% ដើម្បីជាប់។ សូមពិនិត្យចម្លើយ ហើយសាកម្តងទៀត។'),
        'correct' => $L('correct', 'ត្រឹមត្រូវ'),
        'error' => $L('That did not save. Check your connection and try again.', 'មិនអាចរក្សាទុកបានទេ។ សូមពិនិត្យអ៊ីនធឺណិត ហើយសាកម្តងទៀត។'),
    ];
@endphp

<x-live-layout :title="$quiz->title_en.' · Piseth.Design'" main-class="qz-main">
    <x-slot:styles>
        <script>document.documentElement.classList.add('qz-js');</script>
        @include('partials.live._quiz-styles')
    </x-slot:styles>

    <div class="qz-page">
        <a href="{{ route('assignments.index') }}" class="qz-back">{!! $icon('chevron-left') !!}<span class="{{ $km }}">{{ $L('Back to assignments & quizzes', 'ត្រឡប់ទៅកិច្ចការ និងតេស្ត') }}</span></a>

        <article class="qz-card" data-fx>
            <div class="qz-hero">
                <img src="{{ $quiz->pictureSrc() }}" alt="" decoding="async">
                <div class="qz-chips">
                    <div>
                        <span class="qz-chip qz-mono">{!! $icon('sparkles', 'i35') !!}{{ $quiz->course->category ?: 'Design' }}</span>
                        @if (filled($quiz->topic))<span class="qz-chip">{{ $quiz->topic }}</span>@endif
                    </div>
                    @if ($total)<span class="qz-chip qz-mono" data-qcount>Q 1 of {{ $total }}</span>@endif
                </div>
                <div class="qz-title">
                    <h1>{{ $quiz->title_en }}</h1>
                    @if (filled($quiz->title_km))<p class="hm-km">{{ $quiz->title_km }}</p>@endif
                </div>
            </div>

            @if ($total === 0)
                <div class="qz-done"><p class="{{ $km }}">{{ $L('This quiz has no questions yet.', 'តេស្តនេះមិនទាន់មានសំណួរនៅឡើយទេ។') }}</p></div>
            @else
                <form class="qz-body" method="POST" action="{{ route('quizzes.attempts', $quiz) }}" data-quiz>
                    @csrf
                    <div class="qz-prog"><span class="{{ $km }}">{{ $L('Progress', 'វឌ្ឍនភាព') }}</span><b data-pct>0%</b></div>
                    <div class="qz-bar"><i data-bar></i></div>

                    @foreach ($questions as $i => $question)
                        @php $promptKm = trim((string) $question->prompt_km); @endphp
                        <fieldset class="qz-q {{ $i === 0 ? 'is-on' : '' }}" data-q="{{ $question->id }}" style="border:0; padding:0; margin-inline:0">
                            <legend class="qz-label">{{ $L('Question', 'សំណួរ') }} {{ $i + 1 }}:</legend>
                            <h2>{{ $question->prompt_en }}</h2>
                            @if ($promptKm !== '')<p class="hm-km">{{ $promptKm }}</p>@endif
                            <div class="qz-opts">
                                @foreach ($question->optionTexts() as $o => $text)
                                    <label class="qz-opt" data-o="{{ $o }}">
                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $o }}">
                                        <span class="k">{{ $letters[$o] ?? $o + 1 }}</span>
                                        <span>{{ $text }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="qz-why" data-why role="status"></div>
                        </fieldset>
                    @endforeach

                    <div class="qz-foot">
                        <small class="{{ $km }}" data-hint>{{ $qzText['pick'] }}</small>
                        <button type="submit" class="qz-btn fx-btn" data-go><span class="{{ $km }}" data-go-label>{{ $L('Submit Answers', 'ផ្ញើចម្លើយ') }}</span>{!! $icon('arrow-right', 'i35') !!}</button>
                    </div>
                </form>

                <div class="qz-done" data-done hidden aria-live="polite">
                    <div class="qz-ring">
                        <svg viewBox="0 0 100 100" aria-hidden="true">
                            <defs><linearGradient id="qzGrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4DA3FF" /><stop offset="1" stop-color="#A855F7" /></linearGradient></defs>
                            <circle class="bg" cx="50" cy="50" r="46" />
                            <circle class="fg" cx="50" cy="50" r="46" data-ring />
                        </svg>
                        <b><span data-score>0%</span><small data-ratio></small></b>
                    </div>
                    <span class="qz-pass {{ $km }}" data-pass></span>
                    <h2 class="{{ $km }}">{{ $L('Quiz Completed', 'បញ្ចប់តេស្ត') }}</h2>
                    <p class="{{ $km }}" data-line></p>
                    <div class="row">
                        <a href="#" class="qz-btn fx-btn" data-review>{!! $icon('eye', 'i35') !!}<span class="{{ $km }}">{{ $L('Review answers', 'ពិនិត្យចម្លើយ') }}</span></a>
                        <a href="{{ route('quizzes.show', $quiz) }}" class="qz-btn qz-btn--ghost fx-btn">{!! $icon('trending-up', 'i35') !!}<span class="{{ $km }}">{{ $L('Retake quiz', 'ធ្វើតេស្តម្តងទៀត') }}</span></a>
                    </div>
                </div>
            @endif
        </article>

        @if ($attempts->isNotEmpty())
            <section class="qz-card" style="padding: 1.25rem 1.5rem" data-fx>
                <p class="qz-label" style="margin-bottom:.75rem">{{ $L('Your recent attempts', 'ការសាកល្បងថ្មីៗរបស់អ្នក') }}</p>
                <div style="display:flex; flex-wrap:wrap; gap:.5rem">
                    @foreach ($attempts as $a)
                        <a href="{{ route('quizzes.result', [$quiz, $a]) }}" class="qz-pass {{ $a->passed ? 'ok' : 'no' }}" style="text-decoration:none">{{ $a->score }}% · {{ $a->created_at->locale('en')->isoFormat('MMM D') }}</a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <x-slot:scripts>
        @if ($total)
            <script>
                (function () {
                    'use strict';
                    var form = document.querySelector('[data-quiz]');
                    if (!form) return;
                    var text = @json($qzText);
                    var pass = {{ (int) $quiz->pass_percent }};
                    var checkUrl = @json(route('quizzes.check', $quiz));
                    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    var qs = [].slice.call(form.querySelectorAll('[data-q]'));
                    var go = form.querySelector('[data-go]');
                    var goLabel = form.querySelector('[data-go-label]');
                    var hint = form.querySelector('[data-hint]');
                    var bar = form.querySelector('[data-bar]');
                    var pct = form.querySelector('[data-pct]');
                    var count = document.querySelector('[data-qcount]');
                    var at = 0, checked = false;

                    function post(url, body) {
                        return fetch(url, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                            body: JSON.stringify(body)
                        }).then(function (r) { if (!r.ok) throw r; return r.json(); });
                    }
                    function choice(q) { var c = q.querySelector('input:checked'); return c ? parseInt(c.value, 10) : null; }
                    function paint() {
                        var q = qs[at];
                        var done = Math.round((at + (checked ? 1 : 0)) / qs.length * 100);
                        bar.style.width = done + '%';
                        pct.textContent = done + '%';
                        if (count) count.textContent = 'Q ' + (at + 1) + ' of ' + qs.length;
                        go.disabled = choice(q) === null;
                        goLabel.textContent = !checked ? text.check : (at === qs.length - 1 ? text.finish : text.next);
                        hint.hidden = !go.disabled;
                    }

                    form.addEventListener('change', function () { if (!checked) paint(); });
                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        var q = qs[at];
                        var picked = choice(q);
                        if (picked === null) return;
                        if (!checked) {
                            go.disabled = true;
                            post(checkUrl, { question: parseInt(q.getAttribute('data-q'), 10), choice: picked }).then(function (res) {
                                checked = true;
                                q.classList.add('qz-locked');
                                q.querySelectorAll('input').forEach(function (i) { i.disabled = true; i.removeAttribute('name'); i.dataset.keep = '1'; });
                                var right = q.querySelector('[data-o="' + res.answer + '"]');
                                if (right) right.classList.add('is-right');
                                if (!res.correct) q.querySelector('[data-o="' + picked + '"]').classList.add('is-wrong');
                                else if (window.fxBurst) fxBurst(right, '#10B981');
                                var why = q.querySelector('[data-why]');
                                why.className = 'qz-why is-on ' + (res.correct ? 'ok' : 'no');
                                why.textContent = (res.correct ? text.right : text.wrong) + (res.explanation ? ' ' + res.explanation : '');
                                q.dataset.picked = picked;
                                paint();
                            }).catch(function () { hint.textContent = text.error; hint.hidden = false; go.disabled = false; });
                            return;
                        }
                        if (at < qs.length - 1) {
                            q.classList.remove('is-on');
                            at++; checked = false;
                            qs[at].classList.add('is-on');
                            paint();
                            form.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
                            return;
                        }
                        var answers = {};
                        qs.forEach(function (x) { answers[x.getAttribute('data-q')] = parseInt(x.dataset.picked, 10); });
                        go.disabled = true;
                        post(form.getAttribute('action'), { answers: answers }).then(finish)
                            .catch(function () { hint.textContent = text.error; hint.hidden = false; go.disabled = false; });
                    });

                    function finish(res) {
                        form.hidden = true;
                        var done = document.querySelector('[data-done]');
                        done.hidden = false;
                        done.querySelector('[data-review]').href = res.url;
                        done.querySelector('[data-ratio]').textContent = res.correct + ' / ' + res.total + ' ' + text.correct;
                        var flag = done.querySelector('[data-pass]');
                        flag.textContent = res.passed ? text.pass + ' ✓' : text.fail;
                        flag.classList.add(res.passed ? 'ok' : 'no');
                        done.querySelector('[data-line]').textContent = res.passed ? text.passLine : text.failLine.replace(':pass', pass);
                        var ring = done.querySelector('[data-ring]');
                        var score = done.querySelector('[data-score]');
                        requestAnimationFrame(function () { ring.style.strokeDashoffset = (289 * (1 - res.score / 100)).toFixed(1); });
                        var t0 = null;
                        (function tick(t) {
                            if (!t0) t0 = t;
                            var k = Math.min(1, (t - t0) / 1400);
                            score.textContent = Math.round(res.score * (1 - Math.pow(1 - k, 3))) + '%';
                            if (k < 1) requestAnimationFrame(tick);
                            else if (res.passed && window.fxBurst) { fxBurst(score, '#A855F7'); setTimeout(function () { fxBurst(flag, '#10B981'); }, 180); }
                        })(performance.now());
                        if (count) count.textContent = '✓ ' + res.score + '%';
                        done.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }

                    paint();
                })();
            </script>
        @endif
    </x-slot:scripts>
</x-live-layout>
