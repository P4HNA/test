<x-admin-layout :title="$promotion->exists ? 'Edit promotion' : 'New promotion'" active="promotions">

    @php
        $isNew = ! $promotion->exists;
        $dt = fn ($value) => $value ? $value->format('Y-m-d\TH:i') : '';
        $state = [
            'kind' => old('kind', $promotion->kind),
            'pct' => (int) old('discount_percent', $promotion->discount_percent ?? 20),
            'title' => old('title_en', $promotion->title_en),
            'message' => old('message_en', $promotion->message_en),
            'appliesTo' => old('applies_to', $promotion->applies_to),
            'courseId' => (string) old('course_id', $promotion->course_id),
            'category' => old('category', $promotion->category),
            'ctaLabel' => old('cta_label', $promotion->cta_label),
            'showHome' => (bool) old('show_on_home', $isNew ? true : $promotion->show_on_home),
        ];
        // Just enough about each course for the preview and the price example.
        $courseData = $courses->map(fn ($c) => [
            'id' => (string) $c->id,
            'title' => $c->title_en,
            'price' => (float) $c->price,
            'free' => $c->isFree(),
            'category' => $c->category,
            'draft' => ! $c->isPublished(),
        ])->values();
    @endphp

    <form method="POST" action="{{ $isNew ? route('admin.promotions.store') : route('admin.promotions.update', $promotion) }}"
        x-data="promotionForm(@js($state), @js($courseData))" style="display:grid; gap:24px">
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        {{-- ---------- Header ---------- --}}
        <div style="display:grid; gap:10px">
            <a href="{{ route('admin.promotions') }}" class="ad-back"><x-admin.icon name="arrow-left" class="ad-i16" /> Promotions</a>
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px">
                <h1 class="ad-h1">{{ $isNew ? 'New promotion' : $promotion->title_en }}</h1>
                @unless ($isNew)
                    @php $s = $promotion->status(); @endphp
                    <span class="ad-badge {{ ['live' => 'ok dot', 'scheduled' => 'blue'][$s] ?? '' }}">{{ ['live' => 'Live', 'scheduled' => 'Scheduled', 'ended' => 'Ended', 'off' => 'Off'][$s] }}</span>
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

                    <fieldset class="ad-fieldset">
                        <legend class="ad-label">Type</legend>
                        <div class="ad-grid ad-grid-16 ad-cols-sm-2">
                            <label class="ad-choice">
                                <input type="radio" name="kind" value="discount" x-model="kind">
                                <span>
                                    <span style="display:block; font-weight:600">Discount</span>
                                    <span class="ad-muted ad-xs">Lowers the price students pay at checkout while it runs.</span>
                                </span>
                            </label>
                            <label class="ad-choice">
                                <input type="radio" name="kind" value="announcement" x-model="kind">
                                <span>
                                    <span style="display:block; font-weight:600">Announcement</span>
                                    <span class="ad-muted ad-xs">A message only. Prices stay the same.</span>
                                </span>
                            </label>
                        </div>
                    </fieldset>

                    <div x-show="kind === 'discount'" x-cloak>
                        <label for="p-pct" class="ad-label">Discount</label>
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap">
                            <div style="position:relative; width:120px">
                                <input id="p-pct" name="discount_percent" type="number" min="1" max="{{ \App\Models\Promotion::MAX_PERCENT }}" step="1" inputmode="numeric"
                                    x-model.number="pct" class="ad-input" style="padding-right:34px" :disabled="kind !== 'discount'"
                                    aria-describedby="p-pct-hint @error('discount_percent') p-pct-error @enderror" @error('discount_percent') aria-invalid="true" @enderror>
                                <span class="ad-muted" style="position:absolute; right:12px; top:10px; pointer-events:none">%</span>
                            </div>
                            <p id="p-pct-hint" class="ad-sm" style="margin:0" x-text="example()"></p>
                        </div>
                        <p class="ad-hint">From 1% to {{ \App\Models\Promotion::MAX_PERCENT }}%. A course never drops below $0.01, and discounts never stack: each course takes its best one.</p>
                        @error('discount_percent') <p id="p-pct-error" class="ad-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="ad-grid ad-grid-16 ad-cols-sm-2">
                        <div>
                            <label for="p-title-en" class="ad-label">Title in English</label>
                            <input id="p-title-en" name="title_en" x-model="title" maxlength="80" required class="ad-input"
                                placeholder="Illustrator week: 20% off" @error('title_en') aria-invalid="true" aria-describedby="p-title-en-error" @enderror>
                            @error('title_en') <p id="p-title-en-error" class="ad-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="p-title-km" class="ad-label">Title in Khmer</label>
                            <input id="p-title-km" name="title_km" value="{{ old('title_km', $promotion->title_km) }}" maxlength="80" class="ad-input">
                            <p class="ad-hint">Leave empty to show the English title to everyone.</p>
                        </div>
                        <div>
                            <label for="p-msg-en" class="ad-label">Short message in English <span class="ad-muted" style="font-weight:400">(optional)</span></label>
                            <input id="p-msg-en" name="message_en" x-model="message" maxlength="160" class="ad-input"
                                placeholder="Learn logo design for less, this week only.">
                            <p class="ad-hint">One sentence. Shown after the title on wider screens.</p>
                        </div>
                        <div>
                            <label for="p-msg-km" class="ad-label">Short message in Khmer <span class="ad-muted" style="font-weight:400">(optional)</span></label>
                            <input id="p-msg-km" name="message_km" value="{{ old('message_km', $promotion->message_km) }}" maxlength="160" class="ad-input">
                        </div>
                    </div>

                    <fieldset class="ad-fieldset">
                        <legend class="ad-label" x-text="kind === 'discount' ? 'Which courses get the discount' : 'Which courses it is about'">Which courses</legend>
                        <div class="ad-grid ad-grid-16 ad-cols-sm-3">
                            <label class="ad-choice"><input type="radio" name="applies_to" value="course" x-model="appliesTo"><span style="font-weight:600">One course</span></label>
                            <label class="ad-choice"><input type="radio" name="applies_to" value="category" x-model="appliesTo"><span style="font-weight:600">A category</span></label>
                            <label class="ad-choice"><input type="radio" name="applies_to" value="all" x-model="appliesTo"><span style="font-weight:600">All courses</span></label>
                        </div>

                        <div x-show="appliesTo === 'course'" x-cloak style="margin-top:12px">
                            <label for="p-course" class="ad-label">Course</label>
                            <select id="p-course" name="course_id" x-model="courseId" class="ad-select" :disabled="appliesTo !== 'course'" @error('course_id') aria-invalid="true" aria-describedby="p-course-error" @enderror>
                                <option value="">Choose a course…</option>
                                @foreach ($courses as $c)
                                    <option value="{{ $c->id }}">{{ $c->title_en }} ({{ $c->isFree() ? 'free' : $c->priceLabel() }}){{ $c->isPublished() ? '' : ', draft' }}</option>
                                @endforeach
                            </select>
                            <p class="ad-hint" x-show="selectedCourse() && selectedCourse().free">This course is free, so a discount changes nothing. It can still be announced.</p>
                            <p class="ad-hint" x-show="selectedCourse() && selectedCourse().draft">This course is a draft: the homepage will not show a promotion for it until it is published.</p>
                            @error('course_id') <p id="p-course-error" class="ad-error">{{ $message }}</p> @enderror
                        </div>

                        <div x-show="appliesTo === 'category'" x-cloak style="margin-top:12px">
                            <label for="p-category" class="ad-label">Category</label>
                            <select id="p-category" name="category" x-model="category" class="ad-select" :disabled="appliesTo !== 'category'" @error('category') aria-invalid="true" aria-describedby="p-category-error" @enderror>
                                <option value="">Choose a category…</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                            @error('category') <p id="p-category-error" class="ad-error">{{ $message }}</p> @enderror
                        </div>
                    </fieldset>

                    <div class="ad-grid ad-grid-16 ad-cols-sm-2">
                        <div>
                            <label for="p-cta-label" class="ad-label">Button label <span class="ad-muted" style="font-weight:400">(optional)</span></label>
                            <input id="p-cta-label" name="cta_label" x-model="ctaLabel" maxlength="40" class="ad-input" :placeholder="defaultCta()">
                        </div>
                        <div>
                            <label for="p-cta-url" class="ad-label">Button link <span class="ad-muted" style="font-weight:400">(optional)</span></label>
                            <input id="p-cta-url" name="cta_url" value="{{ old('cta_url', $promotion->cta_url) }}" maxlength="2048" class="ad-input"
                                placeholder="Goes to what is on offer" @error('cta_url') aria-invalid="true" aria-describedby="p-cta-url-error" @enderror>
                            <p class="ad-hint">A page here like <code>/courses</code>, or a full address starting with https://</p>
                            @error('cta_url') <p id="p-cta-url-error" class="ad-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- ---------- Preview, schedule, save ---------- --}}
            <div style="display:grid; gap:20px; align-content:start">

                <section class="ad-card" aria-labelledby="preview-title">
                    <div class="ad-card-h">
                        <h2 id="preview-title" class="ad-h2">Homepage preview</h2>
                    </div>
                    <div class="ad-card-b">
                        {{-- Mirrors the banner on the Course Library (partials/_course-promo-banner). --}}
                        <div class="promo-preview" aria-hidden="true">
                            <span class="promo-preview-pill" style="grid-row: span 2" x-text="kind === 'discount' ? '−' + (pct || 0) + '%' : 'News'"></span>
                            <span class="promo-preview-text">
                                <strong x-text="title || 'Your title'"></strong>
                                <span x-show="message" x-text="message" class="ad-muted"></span>
                            </span>
                            <span class="promo-preview-cta" x-text="ctaLabel || defaultCta()"></span>
                        </div>
                        <p class="ad-hint" x-show="!showHome" x-cloak>Not shown on the homepage or Course Library: turn on "Show on the homepage" below.</p>
                    </div>
                </section>

                <section class="ad-card" aria-labelledby="when-title">
                    <div class="ad-card-h"><h2 id="when-title" class="ad-h2">When and where</h2></div>
                    <div class="ad-card-b" style="display:grid; gap:16px">
                        <label class="ad-choice">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $isNew ? true : $promotion->is_active))>
                            <span>
                                <span style="display:block; font-weight:600">Turned on</span>
                                <span class="ad-muted ad-xs">Off stops it everywhere, right away.</span>
                            </span>
                        </label>
                        <label class="ad-choice">
                            <input type="checkbox" name="show_on_home" value="1" x-model="showHome" @checked($state['showHome'])>
                            <span>
                                <span style="display:block; font-weight:600">Show on the homepage</span>
                                <span class="ad-muted ad-xs">A bar at the top of the homepage and a featured banner on the Course Library. Discounts apply to prices either way.</span>
                            </span>
                        </label>

                        <div class="ad-grid ad-grid-16 ad-cols-sm-2" style="grid-template-columns:repeat(auto-fit, minmax(160px, 1fr))">
                            <div>
                                <label for="p-start" class="ad-label">Starts</label>
                                <input id="p-start" name="starts_at" type="datetime-local" value="{{ old('starts_at', $dt($promotion->starts_at)) }}" class="ad-input" @error('starts_at') aria-invalid="true" aria-describedby="p-start-error" @enderror>
                                <p class="ad-hint">Empty: right away.</p>
                                @error('starts_at') <p id="p-start-error" class="ad-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="p-end" class="ad-label">Ends</label>
                                <input id="p-end" name="ends_at" type="datetime-local" value="{{ old('ends_at', $dt($promotion->ends_at)) }}" class="ad-input" @error('ends_at') aria-invalid="true" aria-describedby="p-end-error" @enderror>
                                <p class="ad-hint">Empty: until you turn it off.</p>
                                @error('ends_at') <p id="p-end-error" class="ad-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <p class="ad-hint" style="margin-top:-8px">Times are Cambodia time (UTC+7).</p>

                        <div>
                            <label for="p-priority" class="ad-label">Priority</label>
                            <input id="p-priority" name="priority" type="number" min="0" max="100" step="1" inputmode="numeric" value="{{ old('priority', $promotion->priority ?? 0) }}" class="ad-input" style="max-width:120px" @error('priority') aria-invalid="true" aria-describedby="p-priority-error" @enderror>
                            <p class="ad-hint">When several are live, the homepage shows the highest number.</p>
                            @error('priority') <p id="p-priority-error" class="ad-error">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="ad-btn ad-btn-primary" style="width:100%">{{ $isNew ? 'Create promotion' : 'Save changes' }}</button>
                    </div>
                </section>
            </div>
        </div>
    </form>

    @unless ($isNew)
        <div class="ad-card" style="margin-top:24px; padding:18px 20px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px">
            <div>
                <p class="ad-h2">Delete this promotion</p>
                <p class="ad-muted ad-sm" style="margin:4px 0 0">It stops right away. Payments already made at its price keep their amount.</p>
            </div>
            <form method="POST" action="{{ route('admin.promotions.destroy', $promotion) }}"
                data-confirm="Delete “{{ $promotion->title_en }}”? It stops right away and cannot be undone." data-confirm-title="Delete this promotion?" data-confirm-ok="Delete promotion" data-confirm-tone="danger">
                @csrf @method('DELETE')
                <button type="submit" class="ad-btn ad-btn-danger"><x-admin.icon name="trash" class="ad-i16" /> Delete promotion</button>
            </form>
        </div>
    @endunless

    @push('scripts')
    <script>
        function promotionForm(state, courses) {
            return Object.assign({}, state, {
                courses: courses,
                selectedCourse() {
                    var id = this.courseId;
                    return this.courses.find(function (c) { return c.id === id; }) || null;
                },
                /* A real course from what the promotion covers, to show the new price. */
                exampleCourse() {
                    var self = this;
                    if (this.appliesTo === 'course') return this.selectedCourse();
                    return this.courses.find(function (c) {
                        return !c.free && (self.appliesTo === 'all' || c.category === self.category);
                    }) || null;
                },
                money(n) { return '$' + (Math.round(n) === n ? n : n.toFixed(2)); },
                example() {
                    var c = this.exampleCourse();
                    var pct = Math.max(0, Math.min({{ \App\Models\Promotion::MAX_PERCENT }}, parseInt(this.pct, 10) || 0));
                    if (!c || c.free || !pct) return '';
                    var sale = Math.max(0.01, Math.round(c.price * (100 - pct)) / 100);
                    return c.title + ': ' + this.money(c.price) + ' becomes ' + this.money(sale) + '.';
                },
                defaultCta() {
                    return this.appliesTo === 'course' ? 'See the course' : 'See the courses';
                }
            });
        }
    </script>
    @endpush
</x-admin-layout>
