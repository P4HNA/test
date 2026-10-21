<x-admin-layout title="Review work" active="assignments">
    @php
        $a = $submission->assignment;
        $statuses = ['review' => ['Under review', 'Still looking at it. The student sees "Under Review".'], 'revision' => ['Revision required', 'Ask for changes. The student can upload a new version.'], 'approved' => ['Approved', 'Done. The assignment counts as complete.']];
        $now = old('status', in_array($submission->status, ['review', 'revision', 'approved'], true) ? $submission->status : 'revision');
    @endphp

    <div style="display:grid; gap:24px">
        <div style="display:grid; gap:10px">
            <a href="{{ route('admin.assignments') }}" class="ad-back"><x-admin.icon name="arrow-left" class="ad-i16" /> Assignments</a>
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px">
                <h1 class="ad-h1">{{ $submission->user->name }} · {{ $a->code() }} {{ $a->title_en }}</h1>
                <span class="ad-badge">v{{ $submission->version }}</span>
                <span class="ad-badge {{ $submission->status === 'approved' ? 'ok dot' : ($submission->status === 'revision' ? 'warn' : 'blue') }}">{{ $submission->statusLabel('en') }}</span>
            </div>
            <p class="ad-muted ad-sm" style="margin:0">{{ $submission->user->email }} · {{ $a->course->title_en }} · handed in {{ $submission->created_at->format('j M Y, H:i') }}</p>
        </div>

        <div class="ad-grid ad-cols-xl-3">
            <section class="ad-card ad-span-xl-2">
                <div class="ad-card-h"><h2 class="ad-h2">The work</h2></div>
                <div class="ad-card-b" style="display:grid; gap:16px">
                    @if ($submission->file_path && $submission->isImage())
                        <a href="{{ route('assignments.work', $submission) }}" target="_blank" rel="noopener">
                            <img src="{{ route('assignments.work', $submission) }}" alt="Work by {{ $submission->user->name }}" style="width:100%; max-height:70vh; object-fit:contain; border-radius:10px; background:var(--soft)">
                        </a>
                    @endif
                    <div style="display:flex; flex-wrap:wrap; gap:10px">
                        @if ($submission->file_path)
                            <a href="{{ route('assignments.work', [$submission, 'download' => 1]) }}" class="ad-btn"><x-admin.icon name="download" class="ad-i16" /> {{ $submission->file_name }} · {{ $submission->sizeLabel() }}</a>
                        @endif
                        @if ($submission->link_url)
                            <a href="{{ $submission->link_url }}" class="ad-btn" target="_blank" rel="noopener noreferrer"><x-admin.icon name="external" class="ad-i16" /> Open the link</a>
                        @endif
                    </div>
                    @if (filled($submission->note))
                        <div class="ad-callout" style="margin:0"><x-admin.icon name="message" class="ad-i18" style="flex:none; color:var(--text-3)" /><div><strong>Student's note</strong><div>{{ $submission->note }}</div></div></div>
                    @endif
                    @if ($versions->count() > 1)
                        <div>
                            <p class="ad-label">Earlier versions</p>
                            <div style="display:flex; flex-wrap:wrap; gap:8px">
                                @foreach ($versions as $v)
                                    @if ($v->id !== $submission->id)
                                        <a href="{{ route('admin.submissions.review', $v) }}" class="ad-btn ad-btn-sm">v{{ $v->version }} · {{ $v->statusLabel('en') }}@if ($v->grade !== null) · {{ $v->grade }}/10 @endif</a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <form method="POST" action="{{ route('admin.submissions.grade', $submission) }}" class="ad-card" style="align-self:start">
                @csrf @method('PATCH')
                <div class="ad-card-h"><h2 class="ad-h2">Your review</h2></div>
                <div class="ad-card-b" style="display:grid; gap:16px">
                    <div style="display:grid; gap:8px">
                        @foreach ($statuses as $key => [$label, $hint])
                            <label class="ad-choice">
                                <input type="radio" name="status" value="{{ $key }}" @checked($now === $key)>
                                <span><span style="display:block; font-weight:600">{{ $label }}</span><span class="ad-muted ad-xs">{{ $hint }}</span></span>
                            </label>
                        @endforeach
                        @error('status') <p class="ad-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="g-grade" class="ad-label">Grade out of 10</label>
                        <input id="g-grade" name="grade" type="number" min="0" max="10" step="0.1" inputmode="decimal" value="{{ old('grade', $submission->grade) }}" class="ad-input" style="max-width:120px" placeholder="8.8">
                        @error('grade') <p class="ad-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="g-en" class="ad-label">Feedback in English</label>
                        <textarea id="g-en" name="feedback_en" rows="5" maxlength="3000" class="ad-input" style="resize:vertical" placeholder="Good composition! Improve hierarchy and spacing between the header and the body text...">{{ old('feedback_en', $submission->feedback_en) }}</textarea>
                    </div>
                    <div>
                        <label for="g-km" class="ad-label">Feedback in Khmer <span class="ad-muted" style="font-weight:400">(optional)</span></label>
                        <textarea id="g-km" name="feedback_km" rows="4" maxlength="3000" class="ad-input" style="resize:vertical">{{ old('feedback_km', $submission->feedback_km) }}</textarea>
                    </div>
                    <button type="submit" class="ad-btn ad-btn-primary" style="width:100%">Save review</button>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
