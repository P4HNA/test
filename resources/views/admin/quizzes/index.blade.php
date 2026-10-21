<x-admin-layout title="Quizzes" active="quizzes">

    <div style="display:grid; gap:24px">
        <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:16px">
            <div>
                <h1 class="ad-h1">Quizzes</h1>
                <p class="ad-muted ad-sm" style="margin:8px 0 0; max-width:66ch">
                    Multiple-choice checks for a course. Students answer one question at a time, see the right answer and your explanation, and get a score.
                </p>
            </div>
            <a href="{{ route('admin.quizzes.create') }}" class="ad-btn ad-btn-primary"><x-admin.icon name="plus" class="ad-i18" /> New quiz</a>
        </div>

        @if ($quizzes->isEmpty())
            <div class="ad-card" style="padding:56px 24px; text-align:center">
                <span class="ad-kpi-icon" style="margin:0 auto 16px"><x-admin.icon name="check-circle" /></span>
                <p class="ad-h2" style="font-size:17px">No quizzes yet</p>
                <p class="ad-muted ad-sm" style="margin:6px auto 20px; max-width:46ch">Write a short quiz, for example "Typography Knowledge Quiz" with five questions.</p>
                <a href="{{ route('admin.quizzes.create') }}" class="ad-btn ad-btn-primary">Write the first quiz</a>
            </div>
        @else
            <div class="ad-card">
                <div class="ad-scroll-x">
                    <table class="ad-table">
                        <thead><tr><th>Quiz</th><th>Course</th><th style="text-align:right">Questions</th><th style="text-align:right">Attempts</th><th style="text-align:right">Average</th><th>Status</th><th style="text-align:right">Actions</th></tr></thead>
                        <tbody>
                            @foreach ($quizzes as $q)
                                <tr>
                                    <td class="ad-col-main">
                                        <a href="{{ route('admin.quizzes.edit', $q) }}" class="ad-row-link" style="display:flex; align-items:center; gap:12px">
                                            <img src="{{ $q->pictureSrc() }}" alt="" width="64" height="40" loading="lazy" style="width:64px; height:40px; object-fit:cover; border-radius:6px; flex:none; background:var(--soft)">
                                            <span style="min-width:0">
                                                <span class="ad-row-title" style="display:block">{{ $q->title_en }}</span>
                                                <span class="ad-muted ad-xs" style="display:block">Pass mark {{ $q->pass_percent }}%{{ $q->topic ? ' · '.$q->topic : '' }}</span>
                                            </span>
                                        </a>
                                    </td>
                                    <td class="ad-sm">{{ $q->course->title_en }}</td>
                                    <td class="ad-sm" style="text-align:right">{{ $q->questions_count }}</td>
                                    <td class="ad-sm" style="text-align:right">{{ $q->attempts_count }}</td>
                                    <td class="ad-sm" style="text-align:right">{{ $q->attempts_avg_score !== null ? round($q->attempts_avg_score).'%' : '—' }}</td>
                                    <td><span class="ad-badge {{ $q->is_published ? 'ok dot' : '' }}">{{ $q->is_published ? 'Visible' : 'Hidden' }}</span></td>
                                    <td style="text-align:right">
                                        <div style="display:inline-flex; gap:8px">
                                            <a href="{{ route('admin.quizzes.edit', $q) }}" class="ad-btn ad-btn-sm">Edit</a>
                                            <form method="POST" action="{{ route('admin.quizzes.toggle', $q) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="ad-btn ad-btn-sm">{{ $q->is_published ? 'Hide' : 'Show' }}</button>
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
