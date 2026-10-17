<x-admin-layout title="Course pictures" active="thumbnails" :live="20000">

    <div style="display:grid; gap:24px">

        <div>
            <h1 class="ad-h1">Course pictures</h1>
            <p class="ad-muted ad-sm" style="margin:8px 0 0">
                Change a course's cover picture without opening the full editor. Each preview shows the cover exactly as students see it.
            </p>
        </div>

        @unless ($thumbsReady)
        <div class="ad-callout warn" role="note">
            <x-admin.icon name="alert" class="ad-i18" />
            <p style="margin:0"><strong>Uploads won't be visible yet.</strong> Run <code>php artisan storage:link</code> on the server so uploaded pictures can be served.</p>
        </div>
        @endunless

        @if ($courses->isEmpty())
        <div class="ad-card" style="padding:56px 24px; text-align:center">
            <span class="ad-kpi-icon" style="margin:0 auto 16px"><x-admin.icon name="image" /></span>
            <p class="ad-h2" style="font-size:17px">No courses yet</p>
            <p class="ad-muted ad-sm" style="margin:6px auto 0; max-width:40ch">Create a course first, then its cover picture can be managed here.</p>
        </div>
        @else
        <div class="ad-grid ad-cols-sm-2 ad-cols-xl-3">
            @foreach ($courses as $course)
            @php
                $tint = $course->swatch()['bg'];
                $title = $course->localized('title');
                $fid = 'cover-'.$course->id;
            @endphp
            <div class="ad-card" style="overflow:hidden">
                {{-- Same cover component as the course catalogue. --}}
                <div class="pd-course-cover" style="--tint: {{ $tint }}">
                    @if ($thumb = $course->thumbnailSrc())
                    <img src="{{ $thumb }}" alt="Current picture for {{ $title }}" loading="lazy">
                    @else
                    <span class="pd-tool-tile" style="--tint: {{ $tint }}" aria-hidden="true">{{ $course->mark }}</span>
                    @endif
                    <span class="pd-course-badges" style="justify-content:flex-end">
                        <span class="pd-badge pd-badge-overlay">{{ $course->thumbnail_url ? 'Custom picture' : 'Default cover' }}</span>
                    </span>
                </div>

                <div class="ad-card-b" style="display:grid; grid-template-columns:minmax(0, 1fr); gap:12px">
                    <div>
                        <p class="ad-h2" style="font-size:14px">{{ $title }}</p>
                        <p class="ad-muted ad-xs" style="margin:2px 0 0">{{ $course->category }}</p>
                    </div>

                    <form method="POST" action="{{ route('admin.thumbnails.update', $course) }}" enctype="multipart/form-data" style="display:grid; grid-template-columns:minmax(0, 1fr); gap:8px; min-width:0"
                        x-data="{ file: '', link: '', busy: false }" x-on:submit="busy = true">
                        @csrf
                        {{-- Tells the page which card a validation error belongs to. --}}
                        <input type="hidden" name="course_id" value="{{ $course->id }}">

                        <label class="ad-file" :class="{ 'has-file': file }">
                            <span class="sr-only">Upload a new picture for {{ $title }}</span>
                            <input type="file" name="thumbnail" id="{{ $fid }}-file" accept="image/png,image/jpeg,image/webp,image/gif"
                                x-on:change="file = $event.target.files[0]?.name || ''">
                            <span class="ad-btn ad-btn-sm" aria-hidden="true"><x-admin.icon name="upload" class="ad-i16" /> Choose</span>
                            <span class="ad-file-name" x-text="file || 'Image, up to 2 MB'">Image, up to 2 MB</span>
                        </label>

                        <label class="sr-only" for="{{ $fid }}-link">Or paste a picture link for {{ $title }}</label>
                        <input type="url" name="thumbnail_url" id="{{ $fid }}-link" class="ad-input" x-model="link" placeholder="or paste a picture link (https://…)">

                        @if ((int) old('course_id') === $course->id)
                            @error('thumbnail')
                            <p class="ad-error" role="alert">{{ $message }}</p>
                            @enderror
                            @error('thumbnail_url')
                            <p class="ad-error" role="alert">{{ $message }}</p>
                            @enderror
                        @endif

                        <div style="display:flex; gap:8px">
                            <button type="submit" class="ad-btn ad-btn-sm" style="flex:1"
                                :class="{ 'ad-btn-primary': file || link.trim() }"
                                x-bind:disabled="busy || !(file || link.trim())" disabled>
                                <span class="pd-spinner" x-show="busy" x-cloak aria-hidden="true" style="width:14px; height:14px"></span>
                                Save picture
                            </button>

                            @if ($course->thumbnail_url)
                            <button type="submit" name="remove_thumbnail" value="1" class="ad-btn ad-btn-sm ad-icon-btn ad-btn-danger" title="Remove picture" aria-label="Remove the picture from {{ $title }}"
                                onclick="const f=this.closest('form'); f.querySelector('[name=thumbnail]').value=''; f.querySelector('[name=thumbnail_url]').value='';">
                                <x-admin.icon name="trash" class="ad-i16" />
                            </button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</x-admin-layout>
