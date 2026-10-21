{{-- One quiz as a card. Needs $quiz (course loaded, questions_count) and $attempt (the best one, or null). --}}
@php
    $qzKm = app()->getLocale() === 'km';
    $qzIcon = fn (string $name, string $class = 'i35') => \App\Support\LiveIcon::svg($name, $class);
    $qzTitle = $qzKm && filled($quiz->title_km) ? $quiz->title_km : $quiz->title_en;
    $qzUrl = $attempt ? route('quizzes.result', [$quiz, $attempt]) : route('quizzes.show', $quiz);
@endphp
<a href="{{ $qzUrl }}" class="tk-card fx-spot" data-fx>
    <span class="tk-pic">
        <img src="{{ $quiz->pictureSrc() }}" alt="" loading="lazy" decoding="async">
        <span class="tk-top">
            <span class="tk-num tk-num--sm">{{ $attempt ? $attempt->created_at->locale('en')->isoFormat('MMM D, YYYY') : ($quiz->questions_count ?? $quiz->questions()->count()).' '.($qzKm ? 'សំណួរ' : 'questions') }}</span>
            @if ($attempt)
                <span class="tk-pill {{ $attempt->passed ? 'is-pass' : '' }}" data-tone="{{ $attempt->passed ? 'green' : 'amber' }}">{{ $attempt->passed ? 'Passed ✓' : ($qzKm ? 'សាកម្តងទៀត' : 'Try again') }}</span>
            @else
                <span class="tk-pill {{ $qzKm ? 'hm-km' : '' }}" data-tone="blue">{{ $qzKm ? 'ថ្មី' : 'New' }}</span>
            @endif
        </span>
        <span class="tk-cap">
            <span style="min-width:0">
                <h3 class="{{ $qzKm ? 'hm-km' : '' }}">{{ $qzTitle }}</h3>
                <small>{{ $quiz->title_en }}</small>
            </span>
            @if ($attempt)<span class="tk-score">{{ $attempt->score }}%</span>@endif
        </span>
    </span>
    <span class="tk-body">
        <span class="tk-foot">
            <span class="tk-mono">{{ $attempt ? 'Correct: '.$attempt->correct.' / '.$attempt->total : ($quiz->topic ?: $quiz->course->localized('title')) }}</span>
            <span class="go {{ $qzKm ? 'hm-km' : '' }}">{{ $attempt ? ($qzKm ? 'មើលលទ្ធផល' : 'Review Quiz') : ($qzKm ? 'ចាប់ផ្តើម' : 'Start Quiz') }}{!! $qzIcon('chevron-right') !!}</span>
        </span>
    </span>
</a>
