<x-admin-layout :title="$course->exists ? 'Edit course' : 'New course'" active="courses" :live="20000">

    @php
        // Course form and lesson form both have title_en / title_km, so each one marks itself.
        $failed = old('_form');
        $courseFailed = $failed === 'course';
        $lessonFailed = $failed === 'lesson';
        $val = fn (string $field, $default = '') => $courseFailed ? old($field, $default) : $default;
        $isNew = ! $course->exists;
        $courseTitle = $isNew ? 'New course' : $course->localized('title');
        $lessonTotal = $modules->sum(fn ($m) => $m->lessons->count());

        $lessonCfg = [
            'storeUrl'    => $isNew ? '' : route('admin.lessons.store', $course),
            'updateUrl'   => $isNew ? '' : route('admin.lessons.update', [$course, '__ID__']),
            'chunkUrl'    => route('admin.uploads.chunk'),
            'maxMb'       => $maxMb,
            'firstModule' => $modules->first()?->id,
            'old'         => $lessonFailed ? [
                'lesson_id'        => old('lesson_id'),
                'module_id'        => old('module_id'),
                'title_en'         => old('title_en'),
                'title_km'         => old('title_km'),
                'video_host'       => old('video_host'),
                'video_url'        => old('video_url'),
                'video_path'       => old('video_path'),
                'duration_minutes' => old('duration_minutes'),
                'is_preview'       => old('is_preview'),
                'notes_en'         => old('notes_en'),
                'notes_km'         => old('notes_km'),
            ] : null,
        ];
    @endphp

    <div style="display:grid; gap:24px">

        {{-- ---------- Header ---------- --}}
        <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:16px">
            <div>
                <a href="{{ route('admin.courses') }}" class="ad-back"><x-admin.icon name="arrow-left" class="ad-i16" /> Courses</a>
                <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-top:8px">
                    <h1 class="ad-h1">{{ $courseTitle }}</h1>
                    @unless ($isNew)
                    @if ($course->isPublished())
                    <span class="ad-badge ok dot">Published</span>
                    @else
                    <span class="ad-badge blue">Draft · hidden from students</span>
                    @endif
                    @endunless
                </div>
            </div>
            @unless ($isNew)
            <a href="{{ route('courses.show', $course) }}" target="_blank" rel="noopener" class="ad-btn">
                <x-admin.icon name="external" class="ad-i18" /> View on website
            </a>
            @endunless
        </div>

        {{-- ---------- Course details ---------- --}}
        <form method="POST" action="{{ $isNew ? route('admin.courses.store') : route('admin.courses.update', $course) }}"
            enctype="multipart/form-data" class="ad-grid ad-cols-xl-3">
            @csrf
            @unless ($isNew)
            @method('PUT')
            @endunless
            <input type="hidden" name="_form" value="course">

            <div class="ad-card ad-span-xl-2">
                <div class="ad-card-h">
                    <p class="ad-h2">Course details</p>
                </div>
                <div class="ad-card-b" style="display:grid; gap:18px">

                    @if ($courseFailed && $errors->any())
                    <div style="padding:12px 14px; border-radius:10px; background:var(--danger-soft); border:1px solid var(--danger-border); color:var(--danger-text)" class="ad-sm" role="alert">
                        @foreach ($errors->all() as $message)
                        <div>{{ $message }}</div>
                        @endforeach
                    </div>
                    @endif

                    <div class="ad-grid ad-grid-16 ad-cols-sm-2">
                        <div>
                            <label for="f-title-en" class="ad-label">Title in English</label>
                            <input id="f-title-en" name="title_en" value="{{ $val('title_en', $course->title_en) }}" class="ad-input" required maxlength="255" placeholder="Adobe Photoshop for beginners">
                        </div>
                        <div>
                            <label for="f-title-km" class="ad-label">Title in Khmer</label>
                            <input id="f-title-km" name="title_km" value="{{ $val('title_km', $isNew ? '' : $course->title_km) }}" class="ad-input" maxlength="255">
                            <p class="ad-hint">Leave empty to show the English title to everyone.</p>
                        </div>
                        <div>
                            <label for="f-sub-en" class="ad-label">Subtitle in English</label>
                            <input id="f-sub-en" name="subtitle_en" value="{{ $val('subtitle_en', $course->subtitle_en) }}" class="ad-input" maxlength="255">
                        </div>
                        <div>
                            <label for="f-sub-km" class="ad-label">Subtitle in Khmer</label>
                            <input id="f-sub-km" name="subtitle_km" value="{{ $val('subtitle_km', $course->subtitle_km) }}" class="ad-input" maxlength="255">
                        </div>
                    </div>

                    <div class="ad-grid ad-grid-16 ad-cols-sm-3">
                        <div>
                            <label for="f-mark" class="ad-label">Mark</label>
                            <input id="f-mark" name="mark" value="{{ $val('mark', $course->mark) }}" class="ad-input" required maxlength="3" placeholder="Ps" list="mark-options">
                            <datalist id="mark-options">
                                @foreach (['Ps', 'Ai', 'Id', 'Gd', 'Fg', 'Ae'] as $mark)
                                <option value="{{ $mark }}"></option>
                                @endforeach
                            </datalist>
                            <p class="ad-hint">Two letters, like Ps. Ps, Ai, Id, Gd, Fg and Ae have their own colours.</p>
                        </div>
                        <div>
                            <label for="f-category" class="ad-label">Category</label>
                            <input id="f-category" name="category" value="{{ $val('category', $course->category) }}" class="ad-input" required maxlength="64" list="category-options" autocomplete="off">
                            <datalist id="category-options">
                                @foreach ($categories as $option)
                                <option value="{{ $option }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <label for="f-level" class="ad-label">Level</label>
                            <input id="f-level" name="level" value="{{ $val('level', $course->level) }}" class="ad-input" required maxlength="64" list="level-options" autocomplete="off">
                            <datalist id="level-options">
                                @foreach ($levels as $option)
                                <option value="{{ $option }}"></option>
                                @endforeach
                            </datalist>
                        </div>
                    </div>

                    <div class="ad-grid ad-grid-16 ad-cols-sm-3">
                        <div>
                            <label for="f-price" class="ad-label">Price in USD</label>
                            <input id="f-price" name="price" type="number" min="0" max="100000" step="0.01" inputmode="decimal"
                                value="{{ $val('price', $course->price) }}" class="ad-input" required>
                            <p class="ad-hint" style="display:flex; flex-wrap:wrap; align-items:center; gap:6px">
                                <span>Cents are allowed.</span>
                                <button type="button" class="ad-btn ad-btn-sm" onclick="document.getElementById('f-price').value = '0'">Free</button>
                                <button type="button" class="ad-btn ad-btn-sm" onclick="document.getElementById('f-price').value = '0.01'">$0.01</button>
                            </p>
                            <p class="ad-hint">0 makes the course free. The smallest paid price is $0.01.</p>
                        </div>
                        @unless ($isNew)
                        <div>
                            <label for="f-slug" class="ad-label">Web address</label>
                            <input id="f-slug" name="slug" value="{{ $val('slug', $course->slug) }}" class="ad-input" maxlength="255">
                            <p class="ad-hint">/courses/<strong>{{ $course->slug }}</strong>. Changing it breaks old links.</p>
                        </div>
                        @endunless
                    </div>

                    <div>
                        <label for="f-desc-en" class="ad-label">Description in English</label>
                        <textarea id="f-desc-en" name="description_en" class="ad-textarea" required maxlength="10000">{{ $val('description_en', $course->description_en) }}</textarea>
                    </div>
                    <div>
                        <label for="f-desc-km" class="ad-label">Description in Khmer</label>
                        <textarea id="f-desc-km" name="description_km" class="ad-textarea" maxlength="10000">{{ $val('description_km', $course->description_km) }}</textarea>
                    </div>

                    <div class="ad-grid ad-grid-16 ad-cols-sm-2">
                        <div>
                            <label for="f-instructor" class="ad-label">Instructor</label>
                            <input id="f-instructor" name="instructor_name" value="{{ $val('instructor_name', $course->instructor_name) }}" class="ad-input" maxlength="255">
                        </div>
                        <div>
                            <label for="f-instructor-title" class="ad-label">Instructor title</label>
                            <input id="f-instructor-title" name="instructor_title" value="{{ $val('instructor_title', $course->instructor_title) }}" class="ad-input" maxlength="255">
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:grid; gap:24px; align-self:start">

            {{-- ---------- Course picture (thumbnail) ---------- --}}
            @php
                $thumbSrc  = $isNew ? null : $course->thumbnailSrc();
                $thumbLink = preg_match('#^https?://#i', (string) $course->thumbnail_url) ? $course->thumbnail_url : '';
                $thumbLink = $courseFailed ? old('thumbnail_url', $thumbLink) : $thumbLink;
            @endphp
            <div class="ad-card"
                x-data="{
                    src: @js($thumbSrc),
                    link: @js($thumbLink),
                    removed: false,
                    dragging: false,
                    broken: false,
                    error: '',
                    fileName: '',
                    preview() { return this.src || this.link; },
                    pick(file) {
                        this.error = '';
                        if (!file) { return; }

                        // Some browsers/OSes leave file.type blank for a valid picture,
                        // so fall back to checking the extension before rejecting it.
                        var okType = /^image\/(jpeg|png|webp|gif)$/.test(file.type);
                        var okExt  = /\.(jpe?g|png|webp|gif)$/i.test(file.name || '');
                        if (!okType && !okExt) {
                            this.error = 'Please choose a JPG, PNG, WebP or GIF picture (this file looked like &quot;' + (file.type || 'unknown type') + '&quot;).';
                            this.$refs.file.value = '';
                            return;
                        }
                        if (file.size > 2 * 1024 * 1024) {
                            this.error = 'This picture is ' + (file.size / 1048576).toFixed(1) + ' MB. Please use one under 2 MB.';
                            this.$refs.file.value = '';
                            return;
                        }

                        try {
                            var url = URL.createObjectURL(file);
                        } catch (e) {
                            this.error = 'This browser could not preview that picture. Please try a different file.';
                            this.$refs.file.value = '';
                            return;
                        }

                        if (this.src && this.src.startsWith('blob:')) {
                            URL.revokeObjectURL(this.src);   // free the previous preview, avoid a memory leak
                        }
                        this.src = url;
                        this.fileName = file.name;
                        this.link = '';
                        this.broken = false;
                        this.removed = false;
                    },
                    clear() {
                        this.$refs.file.value = '';
                        this.src = '';
                        this.link = '';
                        this.fileName = '';
                        this.error = '';
                        this.removed = true;
                    }
                }">
                <div class="ad-card-h">
                    <p class="ad-h2">Course picture</p>
                </div>
                <div class="ad-card-b" style="display:grid; gap:12px">

                    @unless ($thumbsReady)
                    <div style="padding:10px 12px; border-radius:10px; border-left:4px solid var(--amber); background:var(--soft)" class="ad-sm" role="alert">
                        One more step before you can upload a picture: run <code>php artisan storage:link</code> in your project folder.
                    </div>
                    @endunless

                    <input type="hidden" name="remove_thumbnail" :value="removed ? 1 : 0">
                    <input id="f-thumb-file" x-ref="file" type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp,image/gif"
                        style="position:absolute; width:1px; height:1px; opacity:0; pointer-events:none"
                        @change="pick($event.target.files[0])">

                    <label for="f-thumb-file"
                        @dragover.prevent="dragging = true"
                        @dragleave.prevent="dragging = false"
                        @drop.prevent="dragging = false; $refs.file.files = $event.dataTransfer.files; pick($refs.file.files[0])"
                        {{-- A class, not a :style string: Alpine replaces the whole style
                             attribute when a :style binding returns a string, which wiped the
                             drop zone's dashed frame, 16:9 box and centring. --}}
                        class="ad-drop" :class="{ 'is-dragging': dragging }">
                        <img class="ad-drop-img" x-show="preview() && !broken" :src="preview() || false" alt="Course picture preview"
                            x-on:error="if (preview()) { broken = true; console.warn('Course picture preview failed to load:', preview()) }"
                            x-on:load="broken = false">
                        <span x-show="!preview() || broken" class="ad-drop-empty">
                            <span class="ad-kpi-icon"><x-admin.icon name="upload" /></span>
                            <template x-if="broken && preview()">
                                <span style="font-weight:600; color:var(--danger)">This picture could not be shown. Try choosing it again.</span>
                            </template>
                            <template x-if="!(broken && preview())">
                                <span style="font-weight:600">Drop a picture here</span>
                            </template>
                            <span class="ad-muted ad-xs" x-show="!(broken && preview())">or click to choose one from your computer</span>
                        </span>
                    </label>

                    <div class="ad-drop-meta" x-show="preview()" x-cloak>
                        <span class="ad-muted ad-xs" style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:60%"
                            x-text="fileName ? fileName + ' (not saved yet)' : 'Current picture'"></span>
                        <button type="button" class="ad-btn ad-btn-sm ad-btn-danger" @click="clear()">
                            <x-admin.icon name="trash" class="ad-i16" /> Remove
                        </button>
                    </div>

                    <p class="ad-sm" style="margin:0; padding:10px 12px; border-radius:10px; background:var(--danger-soft); border:1px solid var(--danger-border); color:var(--danger-text); font-weight:600"
                        x-show="error" x-text="error" x-cloak role="alert"></p>
                    <p class="ad-hint" style="margin:0" x-show="broken && link" x-cloak>This link does not show a picture.</p>

                    <div>
                        <label for="f-thumb" class="ad-label">Or paste a picture link</label>
                        <input id="f-thumb" name="thumbnail_url" x-model="link" class="ad-input" maxlength="2048" placeholder="https://..."
                            @input="if (link) { $refs.file.value = ''; src = ''; fileName = ''; removed = false; broken = false; }">
                    </div>
                    <p class="ad-hint" style="margin:0">JPG, PNG, WebP or GIF, up to 2 MB. A wide picture (16:9, for example 1280 x 720) looks best. Press <strong>Save</strong> to keep it.</p>
                </div>
            </div>

            <div class="ad-card" style="align-self:start">
                <div class="ad-card-h">
                    <p class="ad-h2">{{ $isNew ? 'Create course' : 'Save changes' }}</p>
                </div>
                <div class="ad-card-b" style="display:grid; gap:16px">
                    @php
                        $published = $courseFailed ? (bool) old('is_published') : ($isNew ? false : $course->isPublished());
                    @endphp
                    <fieldset style="margin:0; padding:0; border:0; display:grid; gap:8px">
                        <legend class="ad-label" style="padding:0">Who can see it</legend>
                        <label class="ad-choice">
                            <input type="radio" name="is_published" value="0" @checked(! $published)>
                            <span>
                                <span style="display:block; font-weight:600">Draft</span>
                                <span class="ad-muted ad-xs">Hidden while you build it. Students who already have it keep access.</span>
                            </span>
                        </label>
                        <label class="ad-choice">
                            <input type="radio" name="is_published" value="1" @checked($published)>
                            <span>
                                <span style="display:block; font-weight:600">Published</span>
                                <span class="ad-muted ad-xs">In the catalogue. Anyone can join or buy it.</span>
                            </span>
                        </label>
                    </fieldset>

                    <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer">
                        <input type="checkbox" name="is_featured" value="1" style="margin-top:3px; accent-color:var(--accent)"
                            {{ ($courseFailed ? old('is_featured') : $course->is_featured) ? 'checked' : '' }}>
                        <span>
                            <span style="display:block; font-weight:600">Featured</span>
                            <span class="ad-muted ad-xs">Highlights the course on the website.</span>
                        </span>
                    </label>

                    <button type="submit" class="ad-btn ad-btn-primary" style="width:100%">
                        {{ $isNew ? 'Create course' : 'Save course' }}
                    </button>

                    @if ($isNew)
                    <p class="ad-muted ad-sm" style="margin:0">After you create the course, you can add its modules and video lessons on the next screen. Publish it when it is ready.</p>
                    @else
                    <p class="ad-muted ad-sm" style="margin:0">{{ $modules->count() }} {{ Str::plural('module', $modules->count()) }}, {{ $lessonTotal }} {{ Str::plural('lesson', $lessonTotal) }}.</p>
                    @endif
                </div>
            </div>

            </div>
        </form>

        {{-- ---------- Curriculum: modules, lessons, video upload ---------- --}}
        @unless ($isNew)

        @unless ($videoReady)
        <div class="ad-card" style="padding:16px 20px; border-left:4px solid var(--amber)">
            <p class="ad-h2">One more step before you can add videos</p>
            <p class="ad-muted ad-sm" style="margin:4px 0 0">Run <code>php artisan migrate</code> in your project folder. It adds two video columns to the lessons table and keeps all your data.</p>
        </div>
        @endunless

        <div x-data="lessonForm(@js($lessonCfg))" style="display:grid; gap:24px">

            <div class="ad-card" id="curriculum" style="scroll-margin-top:88px">
                <div class="ad-card-h">
                    <div>
                        <p class="ad-h2">Curriculum</p>
                        <p class="ad-muted ad-xs" style="margin:2px 0 0">A course has modules, and each module has lessons. Students see them in this order.</p>
                    </div>
                </div>

                <div class="ad-card-b" style="display:grid; gap:16px">
                    @forelse ($modules as $module)
                    <div x-data="{ renaming: false }" style="border:1px solid var(--line); border-radius:12px; overflow:hidden">

                        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:12px; padding:12px 14px; background:var(--soft)">
                            <span class="ad-kpi-icon" style="width:34px; height:34px; border-radius:10px"><x-admin.icon name="layers" class="ad-i18" /></span>
                            <div style="min-width:0; flex:1">
                                <p style="margin:0; font-weight:600">{{ $module->title_en }}</p>
                                @if ($module->title_km && $module->title_km !== $module->title_en)
                                <p class="ad-muted ad-xs" style="margin:2px 0 0">{{ $module->title_km }}</p>
                                @endif
                            </div>

                            <div style="display:flex; flex-wrap:wrap; gap:6px">
                                @if ($videoReady)
                                <button type="button" class="ad-btn ad-btn-sm ad-btn-primary" @click="startLesson({{ $module->id }})">
                                    <x-admin.icon name="plus" class="ad-i16" /> Add lesson
                                </button>
                                @endif
                                <form method="POST" action="{{ route('admin.modules.move', [$course, $module]) }}">
                                    @csrf
                                    <input type="hidden" name="direction" value="up">
                                    <button type="submit" class="ad-btn ad-btn-sm ad-icon-btn" aria-label="Move module up" title="Move up" {{ $loop->first ? 'disabled' : '' }}>
                                        <x-admin.icon name="up" class="ad-i16" />
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.modules.move', [$course, $module]) }}">
                                    @csrf
                                    <input type="hidden" name="direction" value="down">
                                    <button type="submit" class="ad-btn ad-btn-sm ad-icon-btn" aria-label="Move module down" title="Move down" {{ $loop->last ? 'disabled' : '' }}>
                                        <x-admin.icon name="down" class="ad-i16" />
                                    </button>
                                </form>
                                <button type="button" class="ad-btn ad-btn-sm" @click="renaming = !renaming">
                                    <x-admin.icon name="pencil" class="ad-i16" /> Rename
                                </button>
                                <form method="POST" action="{{ route('admin.modules.destroy', [$course, $module]) }}"
                                    data-confirm="Delete this module and its {{ $module->lessons->count() }} lesson(s), including any uploaded videos?" data-confirm-title="Delete this module?" data-confirm-ok="Delete module" data-confirm-tone="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ad-btn ad-btn-sm ad-icon-btn ad-btn-danger" aria-label="Delete module" title="Delete module">
                                        <x-admin.icon name="trash" class="ad-i16" />
                                    </button>
                                </form>
                            </div>
                        </div>

                        {{-- Rename --}}
                        <form method="POST" action="{{ route('admin.modules.update', [$course, $module]) }}"
                            x-show="renaming" x-cloak class="ad-grid ad-grid-16 ad-cols-sm-3" style="align-items:end; padding:14px; border-top:1px solid var(--line)">
                            @csrf
                            @method('PUT')
                            <div>
                                <label class="ad-label">Title in English</label>
                                <input name="title_en" value="{{ $module->title_en }}" class="ad-input" required maxlength="255">
                            </div>
                            <div>
                                <label class="ad-label">Title in Khmer</label>
                                <input name="title_km" value="{{ $module->title_km }}" class="ad-input" maxlength="255">
                            </div>
                            <div style="display:flex; gap:8px">
                                <button type="submit" class="ad-btn ad-btn-primary">Save</button>
                                <button type="button" class="ad-btn" @click="renaming = false">Cancel</button>
                            </div>
                        </form>

                        {{-- Lessons --}}
                        <div style="padding:8px 8px 10px">
                            @forelse ($module->lessons as $lesson)
                            @php
                                $isUpload = $lesson->isUpload();
                                $meta = collect([
                                    $isUpload ? 'Uploaded video' : ($lesson->video_url ? $lesson->hostLabel() . ' link' : 'No video yet'),
                                    $isUpload && $lesson->video_size ? \App\Support\Bytes::format($lesson->video_size) : null,
                                    $lesson->duration_minutes ? $lesson->duration_minutes . ' min' : null,
                                ])->filter()->implode(', ');
                                $row = [
                                    'id'        => $lesson->uuid,
                                    'module_id' => $lesson->module_id,
                                    'title_en'  => $lesson->title_en,
                                    'title_km'  => $lesson->title_km,
                                    'host'      => $lesson->video_host,
                                    'url'       => (string) $lesson->video_url,
                                    'file'      => $isUpload ? basename($lesson->video_path) : '',
                                    'duration'  => $lesson->duration_minutes,
                                    'preview'   => (bool) $lesson->is_preview,
                                    'notes_en'  => $lesson->notes_en,
                                    'notes_km'  => $lesson->notes_km,
                                ];
                            @endphp
                            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:12px; padding:8px">
                                <span class="ad-avatar" style="font-size:12px; width:30px; height:30px">{{ $loop->iteration }}</span>
                                <div style="min-width:0; flex:1">
                                    <p style="margin:0; font-weight:600; overflow:hidden; text-overflow:ellipsis">{{ $lesson->title_en }}</p>
                                    <p class="ad-muted ad-xs" style="margin:2px 0 0">{{ $meta }}</p>
                                </div>

                                @if ($lesson->is_preview)
                                <span class="ad-badge blue">Free preview</span>
                                @endif

                                @if ($isUpload)
                                <span class="ad-badge ok">Uploaded</span>
                                @elseif ($lesson->video_url)
                                <span class="ad-badge blue">{{ $lesson->hostLabel() }}</span>
                                @else
                                <span class="ad-badge">No video</span>
                                @endif

                                <div style="display:flex; gap:6px">
                                    @if ($isUpload)
                                    <a href="{{ route('courses.lessons.video', [$course, $lesson]) }}" target="_blank" rel="noopener" class="ad-btn ad-btn-sm ad-icon-btn" aria-label="Preview video" title="Preview video">
                                        <x-admin.icon name="eye" class="ad-i16" />
                                    </a>
                                    @endif
                                    <form method="POST" action="{{ route('admin.lessons.move', [$course, $lesson]) }}">
                                        @csrf
                                        <input type="hidden" name="direction" value="up">
                                        <button type="submit" class="ad-btn ad-btn-sm ad-icon-btn" aria-label="Move lesson up" title="Move up" {{ $loop->first ? 'disabled' : '' }}>
                                            <x-admin.icon name="up" class="ad-i16" />
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.lessons.move', [$course, $lesson]) }}">
                                        @csrf
                                        <input type="hidden" name="direction" value="down">
                                        <button type="submit" class="ad-btn ad-btn-sm ad-icon-btn" aria-label="Move lesson down" title="Move down" {{ $loop->last ? 'disabled' : '' }}>
                                            <x-admin.icon name="down" class="ad-i16" />
                                        </button>
                                    </form>
                                    @if ($videoReady)
                                    <button type="button" class="ad-btn ad-btn-sm" @click="edit(@js($row))">
                                        <x-admin.icon name="pencil" class="ad-i16" /> Edit
                                    </button>
                                    @endif
                                    <form method="POST" action="{{ route('admin.lessons.destroy', [$course, $lesson]) }}"
                                        data-confirm="Delete this lesson and its video file?" data-confirm-title="Delete this lesson?" data-confirm-ok="Delete lesson" data-confirm-tone="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ad-btn ad-btn-sm ad-icon-btn ad-btn-danger" aria-label="Delete lesson" title="Delete lesson">
                                            <x-admin.icon name="trash" class="ad-i16" />
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @empty
                            <p class="ad-muted ad-sm" style="margin:0; padding:18px 10px; text-align:center">No lessons in this module yet.</p>
                            @endforelse
                        </div>
                    </div>
                    @empty
                    <p class="ad-muted ad-sm" style="margin:0; padding:24px 12px; text-align:center">No modules yet. Add the first module below, then put lessons inside it.</p>
                    @endforelse

                    {{-- Add a module --}}
                    <form method="POST" action="{{ route('admin.modules.store', $course) }}"
                        class="ad-grid ad-grid-16 ad-cols-sm-3" style="align-items:end; padding-top:16px; border-top:1px solid var(--line)">
                        @csrf
                        <div>
                            <label for="m-title-en" class="ad-label">New module, title in English</label>
                            <input id="m-title-en" name="title_en" class="ad-input" required maxlength="255" placeholder="Module 01: Getting started">
                        </div>
                        <div>
                            <label for="m-title-km" class="ad-label">Title in Khmer</label>
                            <input id="m-title-km" name="title_km" class="ad-input" maxlength="255">
                        </div>
                        <div>
                            <button type="submit" class="ad-btn"><x-admin.icon name="plus" class="ad-i16" /> Add module</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Add / edit a lesson --}}
            @if ($videoReady && $modules->isNotEmpty())
            <div class="ad-card" id="lesson-form-card">
                <div class="ad-card-h">
                    <p class="ad-h2" x-text="mode === 'edit' ? 'Edit lesson' : 'Add a lesson'">Add a lesson</p>
                    <button type="button" class="ad-btn ad-btn-sm" x-show="mode === 'edit'" x-cloak @click="cancelEdit()">Cancel editing</button>
                </div>

                <form x-ref="form" method="POST" :action="action" @submit="submit($event)" class="ad-card-b" style="display:grid; gap:18px">
                    @csrf
                    <template x-if="mode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <input type="hidden" name="_form" value="lesson">
                    <input type="hidden" name="lesson_id" :value="id">
                    <input type="hidden" name="video_host" :value="source">
                    <input type="hidden" name="video_path" :value="path">

                    @if ($lessonFailed && $errors->any())
                    <div style="padding:12px 14px; border-radius:10px; background:var(--danger-soft); border:1px solid var(--danger-border); color:var(--danger-text)" class="ad-sm" role="alert">
                        @foreach ($errors->all() as $message)
                        <div>{{ $message }}</div>
                        @endforeach
                    </div>
                    @endif

                    <div>
                        <label for="l-module" class="ad-label">Module</label>
                        <select id="l-module" name="module_id" x-model="moduleId" class="ad-select" required>
                            @foreach ($modules as $module)
                            <option value="{{ $module->id }}">{{ $module->title_en }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ad-grid ad-grid-16 ad-cols-sm-2">
                        <div>
                            <label for="l-title-en" class="ad-label">Lesson title in English</label>
                            <input id="l-title-en" name="title_en" x-model="titleEn" class="ad-input" required maxlength="255" placeholder="Setting up your workspace">
                        </div>
                        <div>
                            <label for="l-title-km" class="ad-label">Lesson title in Khmer</label>
                            <input id="l-title-km" name="title_km" x-model="titleKm" class="ad-input" maxlength="255">
                        </div>
                    </div>

                    <div>
                        <p class="ad-label">Video</p>
                        <div style="display:inline-flex; flex-wrap:wrap; gap:4px; padding:3px; border-radius:11px; background:var(--soft)" role="group" aria-label="Video source">
                            <button type="button" class="ad-btn ad-btn-sm" @click="setSource('upload')"
                                :style="source === 'upload' ? 'background:var(--card)' : 'background:transparent; border-color:transparent'">Upload a file</button>
                            <button type="button" class="ad-btn ad-btn-sm" @click="setSource('youtube')"
                                :style="source === 'youtube' ? 'background:var(--card)' : 'background:transparent; border-color:transparent'">YouTube link</button>
                            <button type="button" class="ad-btn ad-btn-sm" @click="setSource('drive')"
                                :style="source === 'drive' ? 'background:var(--card)' : 'background:transparent; border-color:transparent'">Google Drive link</button>
                            <button type="button" class="ad-btn ad-btn-sm" x-show="mode === 'edit' && hostWas === 'upload'" x-cloak @click="setSource('keep')"
                                :style="source === 'keep' ? 'background:var(--card)' : 'background:transparent; border-color:transparent'">Keep current</button>
                        </div>

                        {{-- Upload --}}
                        <div style="margin-top:14px" x-show="source === 'upload'">
                            <div class="ad-drop" :class="{ 'is-drag': dragging }" tabindex="0" role="button"
                                @click="$refs.file.click()" @keydown.enter.prevent="$refs.file.click()"
                                @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="drop($event)">
                                <x-admin.icon name="upload" class="ad-i24" />
                                <p style="margin:0; font-weight:600; color:var(--ink)" x-text="fileName || 'Drop a video here or choose a file'"></p>
                                <p class="ad-xs" style="margin:0" x-text="file ? size(file.size) : 'MP4, WebM, MOV or M4V, up to {{ number_format($maxMb) }} MB'"></p>
                                <input type="file" x-ref="file" style="display:none" accept="video/mp4,video/webm,video/quicktime,.mp4,.m4v,.mov,.webm" @change="pick($event)">
                            </div>

                            <div style="margin-top:12px" x-show="uploading || path" x-cloak>
                                <div class="ad-bar"><span :style="'width:' + progress + '%'"></span></div>
                                <p class="ad-xs ad-muted" style="margin:6px 0 0"
                                    x-text="uploading ? 'Uploading ' + progress + '%. Keep this tab open until it finishes.' : 'Upload finished. Press the save button to attach it to the lesson.'"></p>
                            </div>
                        </div>

                        {{-- YouTube / Drive link --}}
                        <div style="margin-top:14px" x-show="source === 'youtube' || source === 'drive'" x-cloak>
                            <input name="video_url" x-model="url" :disabled="!(source === 'youtube' || source === 'drive')" class="ad-input" maxlength="2048" inputmode="url"
                                :placeholder="source === 'drive' ? 'https://drive.google.com/file/d/.../view' : 'https://youtu.be/abc123'">
                            <p class="ad-hint">You can leave it empty for now and add the link later. The lesson shows a waiting message until then.</p>
                        </div>

                        {{-- Keep --}}
                        <p class="ad-muted ad-sm" style="margin:14px 0 0" x-show="source === 'keep'" x-cloak>
                            The lesson keeps its current video: <strong x-text="current || 'the uploaded file'"></strong>.
                        </p>

                        <p class="ad-error" x-show="error" x-text="error" x-cloak></p>
                    </div>

                    <div class="ad-grid ad-grid-16 ad-cols-sm-3">
                        <div>
                            <label for="l-duration" class="ad-label">Length in minutes</label>
                            <input id="l-duration" name="duration_minutes" type="number" min="0" max="600" x-model="duration" class="ad-input" placeholder="0">
                            <p class="ad-hint">Filled in automatically when the browser can read your video.</p>
                        </div>

                        <div style="grid-column:span 2">
                            <label class="ad-label">Free preview</label>
                            <label style="display:flex; align-items:flex-start; gap:10px; cursor:pointer">
                                <input type="checkbox" name="is_preview" value="1" x-model="isPreview"
                                    style="margin-top:3px; accent-color:var(--accent)">
                                <span>
                                    <span style="display:block; font-weight:600">Anyone signed in can watch this lesson free</span>
                                    <span class="ad-muted ad-xs">Your shop window: a student can try this one lesson before paying. The rest of the course stays locked, and a preview does not count towards a certificate. Only works once the course is published.</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="ad-grid ad-grid-16 ad-cols-sm-2">
                        <div>
                            <label for="l-notes-en" class="ad-label">Notes in English</label>
                            <textarea id="l-notes-en" name="notes_en" x-model="notesEn" class="ad-textarea" maxlength="20000"></textarea>
                        </div>
                        <div>
                            <label for="l-notes-km" class="ad-label">Notes in Khmer</label>
                            <textarea id="l-notes-km" name="notes_km" x-model="notesKm" class="ad-textarea" maxlength="20000"></textarea>
                        </div>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:10px">
                        <button type="submit" class="ad-btn ad-btn-primary" :disabled="uploading || busy"
                            x-text="uploading ? 'Uploading video...' : (mode === 'edit' ? 'Save lesson' : 'Add lesson')">Add lesson</button>
                    </div>
                </form>
            </div>
            @endif
        </div>

        {{-- ---------- Delete ---------- --}}
        <div class="ad-card">
            <div class="ad-card-b" style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:16px">
                <div>
                    <p class="ad-h2">Delete this course</p>
                    <p class="ad-muted ad-sm" style="margin:4px 0 0">Removes the course, its modules, lessons and uploaded videos, plus every student's enrollment, progress and certificate for it. This cannot be undone.</p>
                </div>
                <form method="POST" action="{{ route('admin.courses.destroy', $course) }}"
                    data-confirm="Delete this course, all its lessons and videos, and every enrollment and certificate? This cannot be undone." data-confirm-title="Delete this course?" data-confirm-ok="Delete course" data-confirm-tone="danger">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="ad-btn ad-btn-danger">Delete course</button>
                </form>
            </div>
        </div>
        @endunless
    </div>

    @unless ($isNew)
    @push('scripts')
    <script>
        function lessonForm(cfg) {
            var CHUNK = 1.5 * 1024 * 1024;   // must match CHUNK_BYTES in VideoUploadController
            var VIDEO = /\.(mp4|m4v|mov|webm)$/i;

            function csrf() {
                return document.querySelector('meta[name="csrf-token"]').content;
            }
            function sleep(ms) {
                return new Promise(function(resolve) { setTimeout(resolve, ms); });
            }
            function firstError(j) {
                if (j && j.errors) {
                    var k = Object.keys(j.errors)[0];
                    if (k) return j.errors[k][0];
                }
                return (j && j.message) || 'The server rejected the upload.';
            }

            return {
                mode: 'create',
                id: null,
                moduleId: String(cfg.firstModule || ''),
                titleEn: '',
                titleKm: '',
                source: 'upload',
                hostWas: '',
                url: '',
                duration: '',
                isPreview: false,
                notesEn: '',
                notesKm: '',
                current: '',
                path: '',
                file: null,
                fileName: '',
                progress: 0,
                uploading: false,
                busy: false,
                dragging: false,
                error: '',
                token: 0,

                get action() {
                    return this.mode === 'edit' ? cfg.updateUrl.replace('__ID__', this.id) : cfg.storeUrl;
                },

                init() {
                    var self = this, o = cfg.old;

                    /* The save failed on the server: put back what was typed and the file that is already uploaded. */
                    if (o) {
                        this.moduleId = String(o.module_id || this.moduleId);
                        this.titleEn = o.title_en || '';
                        this.titleKm = o.title_km || '';
                        this.url = o.video_url || '';
                        this.source = o.video_host || 'upload';
                        this.duration = o.duration_minutes || '';
                        this.isPreview = !! o.is_preview;
                        this.notesEn = o.notes_en || '';
                        this.notesKm = o.notes_km || '';
                        if (o.lesson_id) {
                            this.mode = 'edit';
                            this.id = o.lesson_id;
                            if (this.source === 'keep') this.hostWas = 'upload';
                        }
                        if (o.video_path) {
                            this.path = o.video_path;
                            this.fileName = o.video_path.split('/').pop();
                            this.progress = 100;
                        }
                    }

                    window.addEventListener('beforeunload', function(e) {
                        if (self.uploading) { e.preventDefault(); e.returnValue = ''; }
                    });
                },

                size(n) {
                    if (n >= 1073741824) return (n / 1073741824).toFixed(1) + ' GB';
                    if (n >= 1048576) return (n / 1048576).toFixed(1) + ' MB';
                    return Math.max(1, Math.round(n / 1024)) + ' KB';
                },

                setSource(s) {
                    this.source = s;
                    this.error = '';
                },

                scrollToForm() {
                    var card = document.getElementById('lesson-form-card');
                    if (card) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                },

                /* "Add lesson" on a module: a fresh form, with that module already chosen. */
                startLesson(moduleId) {
                    this.cancelEdit();
                    this.moduleId = String(moduleId);
                    this.scrollToForm();
                },

                edit(row) {
                    this.resetFile();
                    this.mode = 'edit';
                    this.id = row.id;
                    this.moduleId = String(row.module_id);
                    this.titleEn = row.title_en || '';
                    this.titleKm = row.title_km || '';
                    this.hostWas = row.host;
                    this.source = row.host === 'upload' ? 'keep' : (row.host === 'drive' ? 'drive' : 'youtube');
                    this.url = row.url || '';
                    this.current = row.file || '';
                    this.duration = row.duration || '';
                    this.isPreview = !! row.preview;
                    this.notesEn = row.notes_en || '';
                    this.notesKm = row.notes_km || '';
                    this.error = '';
                    this.scrollToForm();
                },

                cancelEdit() {
                    var keepModule = this.moduleId;
                    this.mode = 'create';
                    this.id = null;
                    this.moduleId = keepModule;
                    this.titleEn = '';
                    this.titleKm = '';
                    this.hostWas = '';
                    this.source = 'upload';
                    this.url = '';
                    this.current = '';
                    this.duration = '';
                    this.isPreview = false;
                    this.notesEn = '';
                    this.notesKm = '';
                    this.resetFile();
                    this.error = '';
                },

                resetFile() {
                    this.token++;   // stops an upload that is still running
                    this.file = null;
                    this.fileName = '';
                    this.path = '';
                    this.progress = 0;
                    this.uploading = false;
                },

                pick(e) {
                    var f = e.target.files[0];
                    e.target.value = '';
                    if (f) this.setFile(f);
                },

                drop(e) {
                    this.dragging = false;
                    var f = e.dataTransfer.files[0];
                    if (f) this.setFile(f);
                },

                setFile(f) {
                    if (!VIDEO.test(f.name)) { this.error = 'Choose an MP4, WebM, MOV or M4V video.'; return; }
                    if (f.size > cfg.maxMb * 1048576) { this.error = 'That video is larger than ' + cfg.maxMb + ' MB.'; return; }

                    this.error = '';
                    this.resetFile();
                    this.file = f;
                    this.fileName = f.name;
                    if (!this.titleEn.trim()) this.titleEn = f.name.replace(/\.[^.]+$/, '').replace(/[_-]+/g, ' ').trim();
                    this.detectDuration(f);
                    this.upload();   // starts at once, so you can type the title while it uploads
                },

                /* Ask the browser how long the video is; only fills the box if it is still empty. */
                detectDuration(f) {
                    var self = this;
                    try {
                        var v = document.createElement('video');
                        v.preload = 'metadata';
                        v.onloadedmetadata = function() {
                            URL.revokeObjectURL(v.src);
                            if (isFinite(v.duration) && !Number(self.duration)) {
                                self.duration = Math.max(1, Math.round(v.duration / 60));
                            }
                        };
                        v.src = URL.createObjectURL(f);
                    } catch (e) {}
                },

                async upload() {
                    var f = this.file, mine = this.token;
                    var total = Math.max(1, Math.ceil(f.size / CHUNK));
                    var id = (window.crypto && crypto.randomUUID) ? crypto.randomUUID()
                        : (Date.now().toString(16) + Math.random().toString(16).slice(2) + '0000000000000000').slice(0, 32);
                    var last = null;

                    this.uploading = true;
                    this.progress = 0;

                    for (var i = 0; i < total; i++) {
                        var body = new FormData();
                        body.append('upload_id', id);
                        body.append('index', i);
                        body.append('total', total);
                        body.append('filename', f.name);
                        body.append('chunk', f.slice(i * CHUNK, Math.min(f.size, (i + 1) * CHUNK)), 'part');

                        var ok = false, why = '';
                        for (var attempt = 0; attempt < 4 && !ok; attempt++) {
                            if (mine !== this.token) return;
                            try {
                                var res = await fetch(cfg.chunkUrl, {
                                    method: 'POST',
                                    body: body,
                                    credentials: 'same-origin',
                                    headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' }
                                });
                                if (res.status === 422 || res.status === 413) {
                                    var j = await res.json().catch(function() { return {}; });
                                    this.error = firstError(j);
                                    this.uploading = false;
                                    return;
                                }
                                if (!res.ok) throw new Error('HTTP ' + res.status);
                                last = await res.json();
                                ok = true;
                            } catch (err) {
                                why = err.message;
                                await sleep(800 * (attempt + 1));   // a dropped connection only re-sends this one piece
                            }
                        }

                        if (mine !== this.token) return;
                        if (!ok) {
                            this.error = 'The upload stopped (' + why + '). Check your connection, then choose the file again.';
                            this.uploading = false;
                            return;
                        }
                        this.progress = Math.floor(((i + 1) / total) * 100);
                    }

                    if (mine !== this.token) return;

                    if (last && last.done && last.path) {
                        this.path = last.path;
                        this.progress = 100;
                    } else {
                        this.error = 'The upload did not finish. Please choose the file again.';
                    }
                    this.uploading = false;
                },

                submit(e) {
                    this.error = '';

                    if (this.uploading) {
                        e.preventDefault();
                        this.error = 'Wait for the upload to reach 100%, then save.';
                    } else if (this.source === 'upload' && !this.path) {
                        e.preventDefault();
                        this.error = 'Choose a video file to upload.';
                    } else {
                        this.busy = true;
                    }
                }
            };
        }
    </script>
    @endpush
    @endunless
</x-admin-layout>
