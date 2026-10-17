@php use App\Support\Bytes; @endphp
<x-admin-layout title="Videos — {{ $course->localized('title') }}" active="videos" :live="20000">

    @php
        $hasVideo = fn ($lesson) => ($lesson->video_host === 'upload' && filled($lesson->video_path))
            || (in_array($lesson->video_host, ['youtube', 'drive']) && filled($lesson->video_url));
        $allLessons = $course->modules->flatMap->lessons;
        $withVideo = $allLessons->filter($hasVideo)->count();
        $number = 0;
    @endphp

    <div style="display:grid; gap:24px">

        <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:16px">
            <div>
                <a href="{{ route('admin.videos') }}" class="ad-sm" style="display:inline-flex; align-items:center; gap:4px; color:var(--muted); text-decoration:none">
                    <x-admin.icon name="arrow-left" class="ad-i16" /> All courses
                </a>
                <h1 class="ad-h1" style="margin-top:6px">{{ $course->localized('title') }}</h1>
                <p class="ad-muted ad-sm" style="margin:8px 0 0">
                    {{ $withVideo }} of {{ $allLessons->count() }} {{ Str::plural('lesson', $allLessons->count()) }} have a video. Add or change a video in the course editor.
                </p>
            </div>
            <a href="{{ route('admin.courses.edit', $course) }}" class="ad-btn ad-btn-primary">
                <x-admin.icon name="pencil" class="ad-i16" /> Edit course
            </a>
        </div>

        @if ($course->modules->isEmpty())
        <div class="ad-card" style="padding:56px 24px; text-align:center">
            <span class="ad-kpi-icon" style="margin:0 auto 16px"><x-admin.icon name="video" /></span>
            <p class="ad-h2" style="font-size:17px">No modules yet</p>
            <p class="ad-muted ad-sm" style="margin:6px auto 0; max-width:40ch">Add a module and its lessons from the course editor first.</p>
            <a href="{{ route('admin.courses.edit', $course) }}" class="ad-btn ad-btn-primary" style="margin-top:16px">Open course editor</a>
        </div>
        @else
        @foreach ($course->modules as $module)
        <div class="ad-card">
            <div class="ad-card-h" style="padding-bottom:14px">
                <p class="ad-h2">{{ $module->localized('title') }}</p>
                <span class="ad-muted ad-xs">{{ $module->lessons->filter($hasVideo)->count() }}/{{ $module->lessons->count() }} with video</span>
            </div>
            <div class="ad-scroll-x">
                <table class="ad-table">
                    <thead>
                        <tr>
                            <th style="width:1%">#</th>
                            <th>Lesson</th>
                            <th>Source</th>
                            <th class="ad-num">Size</th>
                            <th style="text-align:right"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($module->lessons as $lesson)
                        @php $number++; @endphp
                        <tr>
                            {{-- Numbered across the whole course, like the student's outline. --}}
                            <td class="ad-muted ad-sm" style="font-variant-numeric:tabular-nums">{{ $number }}</td>
                            <td class="ad-col-main" style="font-weight:600">{{ $lesson->localized('title') }}</td>
                            <td>
                                @if ($hasVideo($lesson))
                                <span class="ad-badge {{ $lesson->video_host === 'upload' ? 'ok' : 'blue' }}">
                                    <x-admin.icon :name="$lesson->video_host === 'upload' ? 'upload' : 'link'" class="ad-i16" />
                                    {{ $lesson->hostLabel() }}
                                </span>
                                @else
                                <span class="ad-badge warn dot">No video</span>
                                @endif
                            </td>
                            <td class="ad-muted ad-sm ad-num">
                                {{ $lesson->video_size ? Bytes::format((int) $lesson->video_size) : '—' }}
                            </td>
                            <td>
                                <div style="display:flex; justify-content:flex-end; gap:6px">
                                    @if ($lesson->video_url)
                                    <a href="{{ $lesson->video_url }}" target="_blank" rel="noopener" class="ad-btn ad-btn-sm ad-icon-btn" title="Open source link" aria-label="Open the video link for {{ $lesson->localized('title') }} (new tab)">
                                        <x-admin.icon name="external" class="ad-i16" />
                                    </a>
                                    @endif
                                    @if ($hasVideo($lesson))
                                    <form method="POST" action="{{ route('admin.videos.destroy', $lesson) }}"
                                        data-confirm="The lesson “{{ $lesson->localized('title') }}” is kept; only its video is removed." data-confirm-title="Remove this video?" data-confirm-ok="Remove video" data-confirm-tone="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ad-btn ad-btn-sm ad-icon-btn ad-btn-danger" title="Remove video" aria-label="Remove the video from {{ $lesson->localized('title') }}">
                                            <x-admin.icon name="trash" class="ad-i16" />
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="ad-muted ad-sm" style="text-align:center; padding:24px">No lessons in this module yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
        @endif
    </div>
</x-admin-layout>
