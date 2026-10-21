{{-- One assignment as a card. Needs $assignment (course loaded) and $submission (the latest version, or null). --}}
@php
    $tkKm = app()->getLocale() === 'km';
    $tkIcon = fn (string $name, string $class = 'i35') => \App\Support\LiveIcon::svg($name, $class);
    $late = ! $submission && $assignment->due_at && $assignment->due_at->isPast();
    [$tkLabel, $tkTone, $tkGroup] = match (true) {
        $submission !== null => [$submission->statusLabel(), $submission->statusTone(), in_array($submission->status, ['submitted', 'review'], true) ? 'review' : $submission->status],
        $late => [$tkKm ? 'ហួសកំណត់' : 'Overdue', 'red', 'todo'],
        default => [$tkKm ? 'មិនទាន់បញ្ជូន' : 'Not Submitted', 'gray', 'todo'],
    };
    $tkTitle = $tkKm && filled($assignment->title_km) ? $assignment->title_km : $assignment->title_en;
    $tkDue = $assignment->due_at ? ($tkKm ? 'កំណត់ '.$assignment->due_at->locale('km')->isoFormat('D MMM') : 'Due '.$assignment->due_at->locale('en')->isoFormat('MMM D, YYYY')) : ($tkKm ? 'គ្មានកាលកំណត់' : 'No due date');
@endphp
<a href="{{ route('assignments.show', $assignment) }}" class="tk-card fx-spot" data-category="{{ $tkGroup }}" data-fx>
    <span class="tk-pic">
        <img src="{{ $assignment->pictureSrc() }}" alt="" loading="lazy" decoding="async">
        <span class="tk-top">
            <span class="tk-num">Assignment {{ $assignment->code() }}</span>
            <span class="tk-pill {{ $tkKm ? 'hm-km' : '' }}" data-tone="{{ $tkTone }}">{{ $tkLabel }}</span>
        </span>
        <span class="tk-cap">
            <span>
                <h3 class="{{ $tkKm ? 'hm-km' : '' }}">{{ $tkTitle }}</h3>
                <small class="{{ $tkKm ? 'hm-km' : '' }}">{{ $assignment->course->localized('title') }} · {{ $tkDue }}</small>
            </span>
        </span>
    </span>
    <span class="tk-body">
        @if (filled($assignment->localized('brief')))
            <p>{{ \Illuminate\Support\Str::limit(strip_tags($assignment->localized('brief')), 200) }}</p>
        @endif
        @if ($submission && $submission->isReviewed())
            <span class="tk-fb">
                <span style="display:flex; align-items:center; justify-content:space-between; gap:.5rem">
                    <strong class="{{ $tkKm ? 'hm-km' : '' }}">{{ $tkKm ? 'មតិពីលោកគ្រូ' : 'Feedback from '.($submission->reviewer?->name ?? 'your teacher') }}:</strong>
                    @if ($submission->grade !== null)<b>{{ rtrim(rtrim((string) $submission->grade, '0'), '.') }}/10</b>@endif
                </span>
                @if (filled($submission->feedback()))<em>“{{ $submission->feedback() }}”</em>@endif
            </span>
        @endif
        <span class="tk-foot">
            <span class="{{ $late ? 'late' : '' }} {{ $tkKm ? 'hm-km' : '' }}">
                @if ($submission)
                    v{{ $submission->version }} · {{ $submission->created_at->locale($tkKm ? 'km' : 'en')->diffForHumans() }}
                @elseif ($assignment->due_at)
                    {{ $assignment->due_at->locale($tkKm ? 'km' : 'en')->diffForHumans() }}
                @else
                    {{ $assignment->format ?: ($tkKm ? 'បញ្ជូនពេលរួចរាល់' : 'Hand in when ready') }}
                @endif
            </span>
            <span class="go {{ $tkKm ? 'hm-km' : '' }}">{{ $submission?->status === 'revision' ? ($tkKm ? 'កែសម្រួល' : 'Resubmit') : ($submission ? ($tkKm ? 'មើល' : 'View') : ($tkKm ? 'បញ្ជូនកិច្ចការ' : 'Hand in')) }}{!! $tkIcon('chevron-right') !!}</span>
        </span>
    </span>
</a>
