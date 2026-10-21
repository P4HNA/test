<x-admin-layout :title="$resource->exists ? 'Edit resource' : 'Add resource'" active="resources">

    @php
        $isNew = ! $resource->exists;
        $hasFile = filled($resource->file_path);
    @endphp

    <form method="POST" enctype="multipart/form-data"
        action="{{ $isNew ? route('admin.resources.store') : route('admin.resources.update', $resource) }}"
        style="display:grid; gap:24px">
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        {{-- ---------- Header ---------- --}}
        <div style="display:grid; gap:10px">
            <a href="{{ route('admin.resources') }}" class="ad-back"><x-admin.icon name="arrow-left" class="ad-i16" /> Resources</a>
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px">
                <h1 class="ad-h1">{{ $isNew ? 'Add resource' : $resource->title_en }}</h1>
                @unless ($isNew)
                    <span class="ad-badge {{ $resource->is_published ? 'ok dot' : '' }}">{{ $resource->is_published ? 'Visible' : 'Hidden' }}</span>
                    <span class="ad-muted ad-sm">{{ number_format($resource->downloads_count) }} {{ Str::plural('download', $resource->downloads_count) }}</span>
                @endunless
            </div>
        </div>

        @if ($errors->any())
            <div class="ad-callout warn" role="alert">
                <x-admin.icon name="alert" class="ad-i18" />
                <div>
                    <strong>Check the highlighted {{ Str::plural('field', $errors->count()) }}.</strong>
                    @foreach ($errors->all() as $message)
                        <div>{{ $message }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="ad-grid ad-cols-xl-3">

            {{-- ---------- What it is ---------- --}}
            <div class="ad-card ad-span-xl-2">
                <div class="ad-card-h"><h2 class="ad-h2">What it is</h2></div>
                <div class="ad-card-b" style="display:grid; gap:22px">
                    <div class="ad-grid ad-grid-16 ad-cols-sm-2">
                        <div>
                            <label for="r-title-en" class="ad-label">Title in English</label>
                            <input id="r-title-en" name="title_en" value="{{ old('title_en', $resource->title_en) }}" maxlength="160" required class="ad-input"
                                placeholder="Editorial Poster Paper Texture & Grain Mockups" @error('title_en') aria-invalid="true" aria-describedby="r-title-en-error" @enderror>
                            @error('title_en') <p id="r-title-en-error" class="ad-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="r-title-km" class="ad-label">Title in Khmer <span class="ad-muted" style="font-weight:400">(optional)</span></label>
                            <input id="r-title-km" name="title_km" value="{{ old('title_km', $resource->title_km) }}" maxlength="160" class="ad-input">
                            <p class="ad-hint">Shown under the English title.</p>
                        </div>
                        <div>
                            <label for="r-desc-en" class="ad-label">Description in English <span class="ad-muted" style="font-weight:400">(optional)</span></label>
                            <textarea id="r-desc-en" name="description_en" rows="4" maxlength="2000" class="ad-input" style="resize:vertical">{{ old('description_en', $resource->description_en) }}</textarea>
                        </div>
                        <div>
                            <label for="r-desc-km" class="ad-label">Description in Khmer <span class="ad-muted" style="font-weight:400">(optional)</span></label>
                            <textarea id="r-desc-km" name="description_km" rows="4" maxlength="2000" class="ad-input" style="resize:vertical">{{ old('description_km', $resource->description_km) }}</textarea>
                            <p class="ad-hint">Leave empty to show the English description to everyone.</p>
                        </div>
                    </div>

                    <div class="ad-grid ad-grid-16 ad-cols-sm-3">
                        <div>
                            <label for="r-category" class="ad-label">Category</label>
                            <select id="r-category" name="category" class="ad-select" required @error('category') aria-invalid="true" @enderror>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}" @selected(old('category', $resource->category) === $category)>{{ $category }}</option>
                                @endforeach
                            </select>
                            <p class="ad-hint">The tab it is listed under.</p>
                            @error('category') <p class="ad-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="r-software" class="ad-label">Software</label>
                            <input id="r-software" name="software" value="{{ old('software', $resource->software) }}" maxlength="40" required class="ad-input" placeholder="Photoshop" list="r-software-list" @error('software') aria-invalid="true" @enderror>
                            <datalist id="r-software-list">
                                @foreach (['Photoshop', 'Illustrator', 'InDesign', 'After Effects', 'Figma', 'Typography', 'Branding'] as $sw)
                                    <option value="{{ $sw }}">
                                @endforeach
                            </datalist>
                            @error('software') <p class="ad-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="r-type" class="ad-label">Type</label>
                            <input id="r-type" name="type" value="{{ old('type', $resource->type) }}" maxlength="80" required class="ad-input" placeholder="Mockup & PSD Kit" @error('type') aria-invalid="true" @enderror>
                            @error('type') <p class="ad-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="r-tags" class="ad-label">Tags <span class="ad-muted" style="font-weight:400">(optional)</span></label>
                        <input id="r-tags" name="tags" value="{{ old('tags', implode(', ', $resource->tagList())) }}" maxlength="200" class="ad-input" placeholder="mockup, poster, grain">
                        <p class="ad-hint">Separate with commas. Students can search by them.</p>
                    </div>
                </div>
            </div>

            {{-- ---------- File, picture, visibility ---------- --}}
            <div style="display:grid; gap:20px; align-content:start">

                <section class="ad-card" aria-labelledby="file-title">
                    <div class="ad-card-h"><h2 id="file-title" class="ad-h2">The download</h2></div>
                    <div class="ad-card-b" style="display:grid; gap:16px">
                        @if ($hasFile)
                            <div class="ad-callout" style="margin:0">
                                <x-admin.icon name="file" class="ad-i18" style="flex:none; color:var(--text-3)" />
                                <div style="min-width:0">
                                    <strong style="display:block; overflow:hidden; text-overflow:ellipsis">{{ $resource->file_name }}</strong>
                                    <span class="ad-muted ad-xs">{{ $resource->sizeLabel() }}</span>
                                </div>
                            </div>
                            <label class="ad-choice">
                                <input type="checkbox" name="remove_file" value="1" @checked(old('remove_file'))>
                                <span><span style="display:block; font-weight:600">Remove this file</span><span class="ad-muted ad-xs">Then the link below is used instead.</span></span>
                            </label>
                        @endif
                        <div>
                            <label for="r-file" class="ad-label">{{ $hasFile ? 'Replace with a new file' : 'Upload a file' }}</label>
                            <input id="r-file" name="file" type="file" class="ad-input" @error('file') aria-invalid="true" aria-describedby="r-file-error" @enderror>
                            <p class="ad-hint">Up to 200 MB (ZIP, PSD, AI, FIG, PDF, fonts...). Bigger files: share a link instead.</p>
                            @error('file') <p id="r-file-error" class="ad-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="r-url" class="ad-label">Or a link to it</label>
                            <input id="r-url" name="external_url" type="url" value="{{ old('external_url', $resource->external_url) }}" maxlength="2048" class="ad-input" placeholder="https://drive.google.com/..." @error('external_url') aria-invalid="true" aria-describedby="r-url-error" @enderror>
                            <p class="ad-hint">Google Drive, Dropbox, Figma Community... Used when there is no uploaded file.</p>
                            @error('external_url') <p id="r-url-error" class="ad-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </section>

                <section class="ad-card" aria-labelledby="pic-title">
                    <div class="ad-card-h"><h2 id="pic-title" class="ad-h2">Picture</h2></div>
                    <div class="ad-card-b" style="display:grid; gap:16px">
                        <img src="{{ $resource->pictureSrc() }}" alt="" style="width:100%; aspect-ratio:16/10; object-fit:cover; border-radius:10px; background:var(--soft)">
                        <div>
                            <label for="r-pic" class="ad-label">Upload a picture</label>
                            <input id="r-pic" name="thumbnail" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="ad-input" @error('thumbnail') aria-invalid="true" @enderror>
                            @error('thumbnail') <p class="ad-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="r-pic-url" class="ad-label">Or a picture link</label>
                            <input id="r-pic-url" name="thumbnail_url" type="url" value="{{ old('thumbnail_url', preg_match('#^https?://#i', (string) $resource->thumbnail_url) ? $resource->thumbnail_url : '') }}" maxlength="2048" class="ad-input" placeholder="https://..." @error('thumbnail_url') aria-invalid="true" @enderror>
                            <p class="ad-hint">No picture: a cover matching the category is used.</p>
                            @error('thumbnail_url') <p class="ad-error">{{ $message }}</p> @enderror
                        </div>
                        @if (filled($resource->thumbnail_url))
                            <label class="ad-choice">
                                <input type="checkbox" name="remove_thumbnail" value="1">
                                <span style="font-weight:600">Remove the picture</span>
                            </label>
                        @endif
                    </div>
                </section>

                <section class="ad-card" aria-labelledby="vis-title">
                    <div class="ad-card-h"><h2 id="vis-title" class="ad-h2">Who sees it</h2></div>
                    <div class="ad-card-b" style="display:grid; gap:16px">
                        <label class="ad-choice">
                            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $isNew ? true : $resource->is_published))>
                            <span><span style="display:block; font-weight:600">Visible to students</span><span class="ad-muted ad-xs">Off hides it from the Resources page.</span></span>
                        </label>
                        <label class="ad-choice">
                            <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $resource->is_featured))>
                            <span><span style="display:block; font-weight:600">Featured</span><span class="ad-muted ad-xs">Adds the yellow "Featured" label.</span></span>
                        </label>
                        <div>
                            <label for="r-order" class="ad-label">Order</label>
                            <input id="r-order" name="sort_order" type="number" min="0" max="10000" step="1" inputmode="numeric" value="{{ old('sort_order', $resource->sort_order ?? 0) }}" class="ad-input" style="max-width:120px">
                            <p class="ad-hint">Lower numbers come first.</p>
                        </div>
                        <button type="submit" class="ad-btn ad-btn-primary" style="width:100%">{{ $isNew ? 'Add resource' : 'Save changes' }}</button>
                    </div>
                </section>
            </div>
        </div>
    </form>

    @unless ($isNew)
        <div class="ad-card" style="margin-top:24px; padding:18px 20px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px">
            <div>
                <p class="ad-h2">Delete this resource</p>
                <p class="ad-muted ad-sm" style="margin:4px 0 0">The file and its bookmarks are removed. This cannot be undone.</p>
            </div>
            <form method="POST" action="{{ route('admin.resources.destroy', $resource) }}"
                data-confirm="Delete “{{ $resource->title_en }}”? The file and its bookmarks are removed and this cannot be undone." data-confirm-title="Delete this resource?" data-confirm-ok="Delete resource" data-confirm-tone="danger">
                @csrf @method('DELETE')
                <button type="submit" class="ad-btn ad-btn-danger"><x-admin.icon name="trash" class="ad-i16" /> Delete resource</button>
            </form>
        </div>
    @endunless
</x-admin-layout>
