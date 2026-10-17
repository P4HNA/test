<x-admin-layout title="Courses" active="courses" :live="20000">

    <div style="display:grid; gap:24px">

        <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:16px">
            <div>
                <h1 class="ad-h1">Courses</h1>
                <p class="ad-muted ad-sm" style="margin:8px 0 0; max-width:70ch">
                    Open a course to edit its details, modules and lessons. Drafts are hidden from students until you publish them.
                    Check lesson videos in <a href="{{ route('admin.videos') }}" class="pd-link">Videos</a> and cover pictures in <a href="{{ route('admin.thumbnails') }}" class="pd-link">Course pictures</a>.
                </p>
            </div>
            <a href="{{ route('admin.courses.create') }}" class="ad-btn ad-btn-primary">
                <x-admin.icon name="plus" class="ad-i18" /> New course
            </a>
        </div>

        @if ($counts['all'] > 0)
        <nav class="ad-seg" aria-label="Filter courses" style="justify-self:start">
            @foreach (['all' => 'All', 'published' => 'Published', 'draft' => 'Drafts'] as $key => $label)
            <a href="{{ route('admin.courses', $key !== 'all' ? ['status' => $key] : []) }}" @if ($status === $key) aria-current="page" @endif>
                {{ $label }} <span>{{ number_format($counts[$key]) }}</span>
            </a>
            @endforeach
        </nav>
        @endif

        @if ($counts['all'] > 0 && $courses->isEmpty())
        <div class="ad-card" style="padding:40px 24px; text-align:center">
            <p class="ad-h2" style="font-size:16px">{{ $status === 'draft' ? 'No drafts' : 'Nothing published yet' }}</p>
            <p class="ad-muted ad-sm" style="margin:6px auto 16px; max-width:44ch">
                {{ $status === 'draft' ? 'Every course is live on the website.' : 'Publish a course to show it in the catalogue.' }}
            </p>
            <a href="{{ route('admin.courses') }}" class="ad-btn ad-btn-sm">Show all courses</a>
        </div>
        @elseif ($courses->isEmpty())
        <div class="ad-card" style="padding:56px 24px; text-align:center">
            <span class="ad-kpi-icon" style="margin:0 auto 16px"><x-admin.icon name="video" /></span>
            <p class="ad-h2" style="font-size:17px">No courses yet</p>
            <p class="ad-muted ad-sm" style="margin:6px auto 20px; max-width:40ch">Create a course, add its modules, then upload each lesson as a video file or paste a YouTube or Google Drive link.</p>
            <a href="{{ route('admin.courses.create') }}" class="ad-btn ad-btn-primary">Create your first course</a>
        </div>
        @else
        <div class="ad-card">
            <div class="ad-scroll-x">
                <table class="ad-table">
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Status</th>
                            <th>Price</th>
                            <th>Content</th>
                            <th>Students</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($courses as $c)
                        @php
                            $sw = $c->swatch();
                            $title = $c->localized('title');
                            $missing = (int) ($missingVideos[$c->id] ?? 0);
                        @endphp
                        <tr>
                            <td class="ad-col-main">
                                <a href="{{ route('admin.courses.edit', $c) }}" class="ad-row-link">
                                    @if ($thumb = $c->thumbnailSrc())
                                    <img src="{{ $thumb }}" alt="" loading="lazy" style="flex:none; width:72px; height:40px; object-fit:cover; border-radius:6px; border:1px solid var(--line)">
                                    @else
                                    <span class="ad-mark" style="background:{{ $sw['bg'] }}; color:{{ $sw['fg'] }}">{{ $c->mark }}</span>
                                    @endif
                                    <span style="min-width:0">
                                        <span class="ad-row-title">{{ $title }}</span>
                                        <span class="ad-muted ad-xs">{{ $c->category }} · {{ $c->level }}@if ($c->is_featured) · featured @endif</span>
                                    </span>
                                </a>
                            </td>
                            <td>
                                {{-- Publish / hide right here: the last step of building a course
                                     should not need the full editor. --}}
                                <form method="POST" action="{{ route('admin.courses.visibility', $c) }}" style="display:grid; justify-items:start; gap:2px"
                                    @unless ($c->isPublished())
                                    @if ($c->lessons_count === 0)
                                    data-confirm="{{ $title }} has no lessons yet. Students who join it will find nothing to watch. Publish anyway?" data-confirm-title="Publish without lessons?" data-confirm-ok="Publish anyway" data-confirm-tone="primary"
                                    @endif
                                    @endunless>
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="published" value="{{ $c->isPublished() ? 0 : 1 }}">
                                    @if ($c->isPublished())
                                    <span class="ad-badge ok dot">Published</span>
                                    <button type="submit" class="ad-link-btn" aria-label="Hide {{ $title }} from students">Hide</button>
                                    @else
                                    <span class="ad-badge blue">Draft</span>
                                    <button type="submit" class="ad-link-btn" aria-label="Publish {{ $title }}">Publish</button>
                                    @endif
                                </form>
                            </td>
                            <td>
                                @if ($c->isFree())
                                <span class="ad-badge ok">Free</span>
                                @else
                                <span class="ad-badge blue">{{ $c->priceLabel() }}</span>
                                @endif
                            </td>
                            <td>
                                <span style="display:block; font-weight:600" class="ad-sm">{{ $c->lessons_count }} {{ Str::plural('lesson', $c->lessons_count) }}</span>
                                <span class="ad-muted ad-xs" style="white-space:nowrap">{{ $c->modules_count }} {{ Str::plural('module', $c->modules_count) }} · {{ $uploads[$c->id] ?? 0 }} uploaded</span>
                                @if ($c->lessons_count === 0)
                                <a href="{{ route('admin.courses.edit', $c) }}#curriculum" class="ad-warn-line">
                                    <x-admin.icon name="alert" class="ad-i16" /> No lessons yet
                                </a>
                                @elseif ($missing > 0)
                                <a href="{{ route('admin.videos.show', $c) }}" class="ad-warn-line">
                                    <x-admin.icon name="alert" class="ad-i16" /> {{ $missing }} {{ Str::plural('lesson', $missing) }} without a video
                                </a>
                                @endif
                            </td>
                            <td class="ad-num" style="text-align:left">{{ $c->enrollments_count }}</td>
                            <td>
                                <div style="display:flex; justify-content:flex-end; gap:6px">
                                    <a href="{{ route('admin.courses.edit', $c) }}" class="ad-btn ad-btn-sm">
                                        <x-admin.icon name="pencil" class="ad-i16" /> Edit
                                    </a>
                                    <a href="{{ route('courses.show', $c) }}" target="_blank" rel="noopener" class="ad-btn ad-btn-sm ad-icon-btn" title="View on website" aria-label="View on website">
                                        <x-admin.icon name="external" class="ad-i16" />
                                    </a>
                                    <form method="POST" action="{{ route('admin.courses.destroy', $c) }}"
                                        data-confirm="Delete {{ $title }}? This also removes its modules, lessons, uploaded videos and the {{ $c->enrollments_count }} enrollment(s) with their progress and certificates. Payment records are kept for your books. This cannot be undone." data-confirm-title="Delete this course?" data-confirm-ok="Delete course" data-confirm-tone="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ad-btn ad-btn-sm ad-icon-btn ad-btn-danger" title="Delete course" aria-label="Delete course">
                                            <x-admin.icon name="trash" class="ad-i16" />
                                        </button>
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
