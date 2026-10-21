<x-admin-layout :title="$assignment->exists ? 'Edit assignment' : 'New assignment'" active="assignments">

    @php $isNew = ! $assignment->exists; @endphp

    <form method="POST" enctype="multipart/form-data"
        action="{{ $isNew ? route('admin.assignments.store') : route('admin.assignments.update', $assignment) }}"
        style="display:grid; gap:24px">
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        <div style="display:grid; gap:10px">
            <a href="{{ route('admin.assignments') }}" class="ad-back"><x-admin.icon name="arrow-left" class="ad-i16" /> Assignments</a>
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px">
                <h1 class="ad-h1">{{ $isNew ? 'New assignment' : $assignment->code().' · '.$assignment->title_en }}</h1>
                @unless ($isNew)
                    <span class="ad-badge {{ $assignment->is_published ? 'ok dot' : '' }}">{{ $assignment->is_published ? 'Visible' : 'Hidden' }}</span>
                    <a href="{{ route('assignments.show', $assignment) }}" class="ad-link ad-sm" target="_blank" rel="noopener">See it as a student</a>
                @endunless
            </div>
        </div>

        @if ($errors->any())
            <div class="ad-callout warn" role="alert">
                <x-admin.icon name="alert" class="ad-i18" />
                <div><strong>Check the highlighted {{ Str::plural('field', $errors->count()) }}.</strong>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
            </div>
        @endif

        <div class="ad-grid ad-cols-xl-3">
            <div class="ad-card ad-span-xl-2">
                <div class="ad-card-h"><h2 class="ad-h2">The project</h2></div>
                <div class="ad-card-b" style="display:grid; gap:22px">
                    <div class="ad-grid ad-grid-16 ad-cols-sm-2">
                        <div>
                            <label for="a-course" class="ad-label">Course</label>
                            <select id="a-course" name="course_id" class="ad-input" required>
                                @foreach ($courses as $c)
                                    <option value="{{ $c->id }}" @selected((int) old('course_id', $assignment->course_id) === $c->id)>{{ $c->title_en }}</option>
                                @endforeach
                            </select>
                            @error('course_id') <p class="ad-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="a-num" class="ad-label">Number</label>
                            <input id="a-num" name="number" type="number" min="1" max="999" value="{{ old('number', $assignment->number) }}" class="ad-input" style="max-width:120px" required>
                            <p class="ad-hint">Shown as "Assignment #03".</p>
                        </div>
                        <div>
                            <label for="a-title-en" class="ad-label">Title in English</label>
                            <input id="a-title-en" name="title_en" value="{{ old('title_en', $assignment->title_en) }}" maxlength="160" required class="ad-input" placeholder="Typography Poster">
                            @error('title_en') <p class="ad-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="a-title-km" class="ad-label">Title in Khmer <span class="ad-muted" style="font-weight:400">(optional)</span></label>
                            <input id="a-title-km" name="title_km" value="{{ old('title_km', $assignment->title_km) }}" maxlength="160" class="ad-input" placeholder="ផ្ទាំងរូបភាព Typography">
                        </div>
                        <div>
                            <label for="a-brief-en" class="ad-label">Brief in English</label>
                            <textarea id="a-brief-en" name="brief_en" rows="5" maxlength="5000" class="ad-input" style="resize:vertical" placeholder="Create a poster using typography as the main visual element...">{{ old('brief_en', $assignment->brief_en) }}</textarea>
                        </div>
                        <div>
                            <label for="a-brief-km" class="ad-label">Brief in Khmer <span class="ad-muted" style="font-weight:400">(optional)</span></label>
                            <textarea id="a-brief-km" name="brief_km" rows="5" maxlength="5000" class="ad-input" style="resize:vertical">{{ old('brief_km', $assignment->brief_km) }}</textarea>
                        </div>
                    </div>
                    <div>
                        <label for="a-req" class="ad-label">Technical requirements <span class="ad-muted" style="font-weight:400">(one per line)</span></label>
                        <textarea id="a-req" name="requirements" rows="5" maxlength="3000" class="ad-input" style="resize:vertical" placeholder="Dimensions: 1080 × 1350 px (4:5 vertical)&#10;File format: JPG or PNG, 300 DPI">{{ old('requirements', implode("\n", $assignment->requirementList())) }}</textarea>
                    </div>
                    <div class="ad-grid ad-grid-16 ad-cols-sm-2">
                        <div>
                            <label for="a-format" class="ad-label">Format</label>
                            <input id="a-format" name="format" value="{{ old('format', $assignment->format) }}" maxlength="120" class="ad-input" placeholder="High-Res PNG / JPG / PDF">
                        </div>
                        <div>
                            <label for="a-due" class="ad-label">Due</label>
                            <input id="a-due" name="due_at" type="datetime-local" value="{{ old('due_at', $assignment->due_at?->format('Y-m-d\TH:i')) }}" class="ad-input">
                            @error('due_at') <p class="ad-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div style="display:grid; gap:24px; align-content:start">
                <section class="ad-card">
                    <div class="ad-card-h"><h2 class="ad-h2">Cover and brief file</h2></div>
                    <div class="ad-card-b" style="display:grid; gap:16px">
                        @if ($assignment->exists)
                            <img src="{{ $assignment->pictureSrc() }}" alt="" style="width:100%; aspect-ratio:16/9; object-fit:cover; border-radius:10px; background:var(--soft)">
                        @endif
                        <div>
                            <label for="a-cover" class="ad-label">Cover picture</label>
                            <input id="a-cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp" class="ad-input">
                            <p class="ad-hint">No picture: the course picture is used.</p>
                            @error('cover') <p class="ad-error">{{ $message }}</p> @enderror
                        </div>
                        @if (filled($assignment->cover_url))
                            <label class="ad-choice"><input type="checkbox" name="remove_cover" value="1"><span style="font-weight:600">Remove the cover</span></label>
                        @endif
                        <div>
                            <label for="a-brief-file" class="ad-label">Brief file <span class="ad-muted" style="font-weight:400">(optional)</span></label>
                            <input id="a-brief-file" name="brief_file" type="file" class="ad-input">
                            <p class="ad-hint">{{ $assignment->brief_name ? 'Now: '.$assignment->brief_name.'. ' : '' }}PDF, ZIP, JPG, PNG or DOCX, up to 20 MB. Students get a "Download Brief" button.</p>
                            @error('brief_file') <p class="ad-error">{{ $message }}</p> @enderror
                        </div>
                        @if (filled($assignment->brief_path))
                            <label class="ad-choice"><input type="checkbox" name="remove_brief" value="1"><span style="font-weight:600">Remove the brief file</span></label>
                        @endif
                    </div>
                </section>
                <section class="ad-card">
                    <div class="ad-card-h"><h2 class="ad-h2">Who sees it</h2></div>
                    <div class="ad-card-b" style="display:grid; gap:16px">
                        <label class="ad-choice">
                            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $isNew ? true : $assignment->is_published))>
                            <span><span style="display:block; font-weight:600">Visible to enrolled students</span><span class="ad-muted ad-xs">Only students in this course see it.</span></span>
                        </label>
                        <button type="submit" class="ad-btn ad-btn-primary" style="width:100%">{{ $isNew ? 'Set assignment' : 'Save changes' }}</button>
                    </div>
                </section>
            </div>
        </div>
    </form>

    @unless ($isNew)
        <div class="ad-card" style="margin-top:24px; padding:18px 20px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px">
            <div>
                <p class="ad-h2">Delete this assignment</p>
                <p class="ad-muted ad-sm" style="margin:4px 0 0">Every version students handed in, and your reviews, are removed. This cannot be undone.</p>
            </div>
            <form method="POST" action="{{ route('admin.assignments.destroy', $assignment) }}"
                data-confirm="Delete “{{ $assignment->title_en }}”? The work handed in for it is removed too." data-confirm-title="Delete this assignment?" data-confirm-ok="Delete assignment" data-confirm-tone="danger">
                @csrf @method('DELETE')
                <button type="submit" class="ad-btn ad-btn-danger"><x-admin.icon name="trash" class="ad-i16" /> Delete assignment</button>
            </form>
        </div>
    @endunless
</x-admin-layout>
