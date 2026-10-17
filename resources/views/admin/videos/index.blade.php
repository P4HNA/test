<x-admin-layout title="Videos" active="videos" :live="20000">

    @php
        $lessonTotal = (int) $courses->sum('lessons_count');
        $videoTotal = (int) $withVideo->sum();
        $missingTotal = max(0, $lessonTotal - $videoTotal);
    @endphp

    <div style="display:grid; gap:24px">

        <div>
            <h1 class="ad-h1">Videos</h1>
            <p class="ad-muted ad-sm" style="margin:8px 0 0">
                @if ($lessonTotal > 0)
                {{ $videoTotal }} of {{ $lessonTotal }} {{ Str::plural('lesson', $lessonTotal) }} have a video{{ $missingTotal > 0 ? ' — '.$missingTotal.' still missing' : '' }}. Open a course to see which.
                @else
                Open a course to see and manage its lessons' videos.
                @endif
            </p>
        </div>

        @if ($courses->isEmpty())
        <div class="ad-card" style="padding:56px 24px; text-align:center">
            <span class="ad-kpi-icon" style="margin:0 auto 16px"><x-admin.icon name="video" /></span>
            <p class="ad-h2" style="font-size:17px">No courses yet</p>
            <p class="ad-muted ad-sm" style="margin:6px auto 0; max-width:40ch">Create a course and add lessons before managing videos here.</p>
        </div>
        @else
        <div class="ad-card">
            <div class="ad-scroll-x">
                <table class="ad-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Video coverage</th>
                            <th>Status</th>
                            <th style="text-align:right"><span class="sr-only">Open</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($courses as $course)
                        @php
                            $sw = $course->swatch();
                            $has = (int) ($withVideo[$course->id] ?? 0);
                            $total = (int) $course->lessons_count;
                            $pct = $total > 0 ? (int) round($has / $total * 100) : 0;
                        @endphp
                        <tr>
                            <td class="ad-col-main">
                                <a href="{{ route('admin.videos.show', $course) }}" style="display:flex; align-items:center; gap:12px; color:inherit; text-decoration:none">
                                    <span class="ad-mark" style="background:{{ $sw['bg'] }}; color:{{ $sw['fg'] }}">{{ $course->mark }}</span>
                                    <span style="min-width:0">
                                        <span style="display:block; font-weight:600">{{ $course->localized('title') }}</span>
                                        <span class="ad-muted ad-xs">{{ $course->category }} · {{ $total }} {{ Str::plural('lesson', $total) }}</span>
                                    </span>
                                </a>
                            </td>
                            <td style="min-width:180px">
                                <div style="display:flex; align-items:center; gap:10px">
                                    <span class="ad-bar" style="flex:1; min-width:96px" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}" aria-label="Lessons with a video">
                                        <span style="width:{{ $pct }}%; {{ $total > 0 && $has === $total ? 'background:var(--success)' : '' }}"></span>
                                    </span>
                                    <span class="ad-sm" style="font-variant-numeric:tabular-nums">{{ $has }}/{{ $total }}</span>
                                </div>
                            </td>
                            <td>
                                @if ($total === 0)
                                <span class="ad-badge blue">No lessons</span>
                                @elseif ($has === $total)
                                <span class="ad-badge ok dot">Complete</span>
                                @else
                                <span class="ad-badge warn dot">{{ $total - $has }} missing</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex; justify-content:flex-end">
                                    <a href="{{ route('admin.videos.show', $course) }}" class="ad-btn ad-btn-sm">Open <x-admin.icon name="chevron-right" class="ad-i16" /></a>
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
