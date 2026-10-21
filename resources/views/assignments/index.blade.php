{{-- Assignments & quizzes for the student's courses (/assignments), on the live piseth.design design. --}}
@php
    $hmKm = app()->getLocale() === 'km';
    $L = fn (string $en, string $km) => $hmKm ? $km : $en;
    $km = $hmKm ? 'hm-km' : '';
    $icon = fn (string $name, string $class = 'i4') => \App\Support\LiveIcon::svg($name, $class);

    $groupOf = function ($assignment) use ($latest) {
        $s = $latest->get($assignment->id);
        return ! $s ? 'todo' : (in_array($s->status, ['submitted', 'review'], true) ? 'review' : $s->status);
    };
    $counts = $assignments->countBy(fn ($a) => $groupOf($a));
    $tiles = [
        'todo' => ['pencil', $L('To hand in', 'ត្រូវបញ្ជូន'), '#64748B'],
        'review' => ['eye', $L('Waiting for review', 'កំពុងរង់ចាំពិនិត្យ'), '#3B82F6'],
        'revision' => ['pen-tool', $L('Needs changes', 'ត្រូវកែសម្រួល'), '#F59E0B'],
        'approved' => ['check-circle', $L('Approved', 'បានអនុម័ត'), '#10B981'],
    ];
@endphp

<x-live-layout :title="$L('Assignments & Feedback', 'កិច្ចការ & ការកែលម្អ').' · Piseth.Design'" main-class="as-main">
    <x-slot:styles>
        <style>
            .as-main { font-family: Inter, 'Noto Sans Khmer', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.5; -webkit-font-smoothing: antialiased; }
            .as-page { max-width: 72rem; margin: 0 auto; padding: 2rem 1rem 6rem; display: flex; flex-direction: column; gap: 2rem; }
            @media (min-width: 640px) { .as-page { padding-left: 1.5rem; padding-right: 1.5rem; } }
            @media (min-width: 1024px) { .as-page { padding-left: 2rem; padding-right: 2rem; } }
            .as-head { position: relative; isolation: isolate; overflow: hidden; padding: 2rem 1.25rem; border-radius: 1.5rem; background: rgba(255, 255, 255, .7); border: 1px solid rgba(226, 232, 240, .8); }
            html.dark .as-head { background: rgba(21, 27, 35, .7); border-color: #222936; }
            @media (min-width: 640px) { .as-head { padding: 2.5rem 2rem; } }
            .as-kick { display: inline-flex; align-items: center; gap: .5rem; font-size: .75rem; font-weight: 600; color: #1877F2; }
            html.dark .as-kick { color: #4DA3FF; }
            .as-head h1 { margin: .75rem 0 0; font-size: 1.875rem; line-height: 1.15; font-weight: 900; letter-spacing: -.025em; color: var(--ink); text-wrap: balance; }
            @media (min-width: 640px) { .as-head h1 { font-size: 2.5rem; } }
            .as-head p { margin: .75rem 0 0; max-width: 40rem; font-size: .875rem; line-height: 1.6; color: var(--mut); }
            .as-tiles { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; margin-top: 1.75rem; }
            @media (min-width: 768px) { .as-tiles { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
            .as-tile { display: flex; align-items: center; gap: .75rem; padding: .875rem 1rem; border-radius: 1rem; background: var(--card); border: 1px solid var(--line); transition: transform .3s cubic-bezier(.22, 1, .36, 1); }
            .as-tile:hover { transform: translateY(-2px); }
            .as-tile i { display: inline-flex; flex-shrink: 0; width: 2.25rem; height: 2.25rem; align-items: center; justify-content: center; border-radius: .75rem; background: color-mix(in srgb, var(--t) 14%, transparent); color: var(--t); }
            .as-tile b { display: block; font-size: 1.5rem; line-height: 1.1; font-weight: 900; color: var(--ink); font-variant-numeric: tabular-nums; }
            .as-tile span { display: block; font-size: 11px; color: var(--mut); }
            .as-sec { display: flex; flex-direction: column; gap: 1.25rem; }
            .as-sechead { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; }
            .as-sechead h2 { margin: 0; font-size: 1.375rem; font-weight: 900; color: var(--ink); }
            .as-sechead p { margin: .25rem 0 0; font-size: .8125rem; color: var(--mut); }
            .as-empty { padding: 3rem 1rem; border-radius: 1.25rem; border: 1px dashed var(--line); text-align: center; font-size: .875rem; color: var(--mut); }
            .as-empty a { display: inline-flex; margin-top: .75rem; font-weight: 600; color: #1877F2; }
            html:lang(km) .hm-body :is(.as-head h1, .as-sechead h2) { line-height: 1.6; }
        </style>
        @include('partials.live._task-styles')
    </x-slot:styles>

    <div class="as-page">
        <header class="as-head" data-fx>
            <div class="fx-aurora" aria-hidden="true"><i></i><i></i><i></i></div>
            <span class="as-kick {{ $km }}">{!! $icon('sparkles', 'i35') !!}{{ $L('Student workspace · Projects reviewed by Teacher Piseth', 'កន្លែងធ្វើការរបស់សិស្ស · ពិនិត្យដោយលោកគ្រូ ពិសិដ្ឋ') }}</span>
            <h1 class="{{ $km }}">{{ $L('Assignments &', 'កិច្ចការ &') }} <span class="fx-grad">{{ $L('Feedback', 'ការកែលម្អ') }}</span></h1>
            <p class="{{ $km }}">{{ $L('Hand in your projects, read the teacher’s critique, and resubmit until it shines. Quizzes check what you learned along the way.', 'បញ្ជូនគម្រោងរបស់អ្នក អានមតិកែលម្អពីលោកគ្រូ ហើយកែសម្រួលរហូតដល់ល្អឥតខ្ចោះ។ តេស្តចំណេះដឹងជួយពិនិត្យអ្វីដែលអ្នកបានរៀន។') }}</p>
            <div class="as-tiles">
                @foreach ($tiles as $key => [$glyph, $label, $tone])
                    <div class="as-tile" style="--t: {{ $tone }}">
                        <i>{!! $icon($glyph) !!}</i>
                        <div><b data-fx-count>{{ $counts->get($key, 0) }}</b><span class="{{ $km }}">{{ $label }}</span></div>
                    </div>
                @endforeach
            </div>
        </header>

        <section class="as-sec" aria-labelledby="as-a-title">
            <div class="as-sechead">
                <div>
                    <h2 id="as-a-title" class="{{ $km }}">{{ $L('Your assignments', 'កិច្ចការរបស់អ្នក') }}</h2>
                    <p class="{{ $km }}">{{ $L('Newest deadlines first.', 'កាលកំណត់ជិតបំផុតនៅខាងលើ។') }}</p>
                </div>
                @if ($assignments->isNotEmpty())
                    <div class="hm-tabs" data-jelly-tabs data-target="#as-grid [data-category]">
                        <div class="hm-tabs-track" role="tablist" aria-label="{{ $L('Filter assignments', 'ច្រោះកិច្ចការ') }}">
                            <span class="hm-jelly-trail" aria-hidden="true"></span>
                            <span class="hm-jelly" aria-hidden="true"><span class="hm-jelly-blob"></span><span class="hm-sparks"></span></span>
                            <button type="button" class="hm-tab {{ $km }}" role="tab" data-value="__all" aria-selected="true">{{ $L('All', 'ទាំងអស់') }}</button>
                            @foreach ($tiles as $key => [$glyph, $label, $tone])
                                <button type="button" class="hm-tab {{ $km }}" role="tab" data-value="{{ $key }}" aria-selected="false">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            @if ($assignments->isEmpty())
                <div class="as-empty">
                    <p class="{{ $km }}" style="margin:0">{{ $L('No assignments yet. They appear here when your teacher sets one for a course you are taking.', 'មិនទាន់មានកិច្ចការទេ។ កិច្ចការនឹងបង្ហាញនៅទីនេះ ពេលលោកគ្រូដាក់ឲ្យសម្រាប់វគ្គដែលអ្នកកំពុងរៀន។') }}</p>
                    <a href="{{ route('courses') }}" class="{{ $km }}">{{ $L('Browse courses', 'មើលវគ្គសិក្សា') }}</a>
                </div>
            @else
                <div class="tk-grid" id="as-grid">
                    @foreach ($assignments as $assignment)
                        @include('partials.live._assignment-card', ['assignment' => $assignment, 'submission' => $latest->get($assignment->id)])
                    @endforeach
                </div>
            @endif
        </section>

        <section class="as-sec" aria-labelledby="as-q-title">
            <div class="as-sechead">
                <div>
                    <h2 id="as-q-title" class="{{ $km }}">{{ $L('Knowledge quizzes', 'តេស្តចំណេះដឹង') }}</h2>
                    <p class="{{ $km }}">{{ $L('Short multiple-choice checks. Retake them as often as you like; your best score counts.', 'តេស្តពហុជម្រើសខ្លីៗ។ អាចធ្វើឡើងវិញបានច្រើនដង ពិន្ទុខ្ពស់បំផុតត្រូវបានរាប់។') }}</p>
                </div>
            </div>
            @if ($quizzes->isEmpty())
                <div class="as-empty"><p class="{{ $km }}" style="margin:0">{{ $L('No quizzes yet for your courses.', 'មិនទាន់មានតេស្តសម្រាប់វគ្គរបស់អ្នកទេ។') }}</p></div>
            @else
                <div class="tk-grid tk-grid--3">
                    @foreach ($quizzes as $quiz)
                        @include('partials.live._quiz-card', ['quiz' => $quiz, 'attempt' => $best->get($quiz->id)])
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-live-layout>
