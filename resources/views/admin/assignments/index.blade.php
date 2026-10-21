<x-admin-layout title="Assignments" active="assignments">

    <div style="display:grid; gap:24px">

        <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:16px">
            <div>
                <h1 class="ad-h1">Assignments</h1>
                <p class="ad-muted ad-sm" style="margin:8px 0 0; max-width:66ch">
                    Projects students hand in for a course. Review each version, give a grade out of 10 and your critique,
                    then approve it or ask for changes. Students see it on their
                    <a href="{{ route('assignments.index') }}" class="ad-link" target="_blank" rel="noopener">Assignments page</a>.
                </p>
            </div>
            <a href="{{ route('admin.assignments.create') }}" class="ad-btn ad-btn-primary"><x-admin.icon name="plus" class="ad-i18" /> New assignment</a>
        </div>

        {{-- ---------- Work waiting for a review ---------- --}}
        <section class="ad-card" aria-labelledby="queue-title">
            <div class="ad-card-h" style="display:flex; align-items:center; justify-content:space-between; gap:12px">
                <h2 id="queue-title" class="ad-h2">Waiting for your review</h2>
                <span class="ad-badge {{ $queue->isNotEmpty() ? 'warn' : 'ok' }}">{{ $queue->count() }}</span>
            </div>
            @if ($queue->isEmpty())
                <div class="ad-card-b"><p class="ad-muted ad-sm" style="margin:0">All caught up. New hand-ins appear here.</p></div>
            @else
                <div class="ad-scroll-x">
                    <table class="ad-table">
                        <thead><tr><th>Student</th><th>Assignment</th><th>Version</th><th>Handed in</th><th style="text-align:right">Action</th></tr></thead>
                        <tbody>
                            @foreach ($queue as $s)
                                <tr>
                                    <td class="ad-col-main">
                                        <span class="ad-row-title" style="display:block">{{ $s->user->name }}</span>
                                        <span class="ad-muted ad-xs">{{ $s->user->email }}</span>
                                    </td>
                                    <td class="ad-sm">{{ $s->assignment->code() }} · {{ $s->assignment->title_en }}<span class="ad-muted ad-xs" style="display:block">{{ $s->assignment->course->title_en }}</span></td>
                                    <td class="ad-sm">v{{ $s->version }}</td>
                                    <td class="ad-sm">{{ $s->created_at->diffForHumans() }}</td>
                                    <td style="text-align:right"><a href="{{ route('admin.submissions.review', $s) }}" class="ad-btn ad-btn-sm ad-btn-primary">Review</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @if ($assignments->isEmpty())
            <div class="ad-card" style="padding:56px 24px; text-align:center">
                <span class="ad-kpi-icon" style="margin:0 auto 16px"><x-admin.icon name="pencil" /></span>
                <p class="ad-h2" style="font-size:17px">No assignments yet</p>
                <p class="ad-muted ad-sm" style="margin:6px auto 20px; max-width:46ch">Set the first project, for example "Typography Poster", with a brief, requirements and a due date.</p>
                <a href="{{ route('admin.assignments.create') }}" class="ad-btn ad-btn-primary">Set the first assignment</a>
            </div>
        @else
            <div class="ad-card">
                <div class="ad-scroll-x">
                    <table class="ad-table">
                        <thead>
                            <tr><th>Assignment</th><th>Course</th><th>Due</th><th style="text-align:right">Hand-ins</th><th style="text-align:right">Waiting</th><th>Status</th><th style="text-align:right">Actions</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($assignments as $a)
                                <tr>
                                    <td class="ad-col-main">
                                        <a href="{{ route('admin.assignments.edit', $a) }}" class="ad-row-link" style="display:flex; align-items:center; gap:12px">
                                            <img src="{{ $a->pictureSrc() }}" alt="" width="64" height="40" loading="lazy" style="width:64px; height:40px; object-fit:cover; border-radius:6px; flex:none; background:var(--soft)">
                                            <span style="min-width:0">
                                                <span class="ad-row-title" style="display:block">{{ $a->code() }} · {{ $a->title_en }}</span>
                                                <span class="ad-muted ad-xs" style="display:block">{{ $a->format ?: 'Any format' }}</span>
                                            </span>
                                        </a>
                                    </td>
                                    <td class="ad-sm">{{ $a->course->title_en }}</td>
                                    <td class="ad-sm">{{ $a->due_at ? $a->due_at->format('j M Y') : '—' }}</td>
                                    <td class="ad-sm" style="text-align:right">{{ $a->submissions_count }}</td>
                                    <td class="ad-sm" style="text-align:right">@if ($a->waiting_count)<span class="ad-badge warn">{{ $a->waiting_count }}</span>@else 0 @endif</td>
                                    <td><span class="ad-badge {{ $a->is_published ? 'ok dot' : '' }}">{{ $a->is_published ? 'Visible' : 'Hidden' }}</span></td>
                                    <td style="text-align:right">
                                        <div style="display:inline-flex; gap:8px">
                                            <a href="{{ route('admin.assignments.edit', $a) }}" class="ad-btn ad-btn-sm">Edit</a>
                                            <form method="POST" action="{{ route('admin.assignments.toggle', $a) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="ad-btn ad-btn-sm">{{ $a->is_published ? 'Hide' : 'Show' }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
