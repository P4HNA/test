<x-admin-layout :title="$quiz->exists ? 'Edit quiz' : 'New quiz'" active="quizzes">
    @php
        $isNew = ! $quiz->exists;
        $blank = ['prompt_en' => '', 'prompt_km' => '', 'options' => [['en' => '', 'km' => ''], ['en' => '', 'km' => ''], ['en' => '', 'km' => ''], ['en' => '', 'km' => '']], 'correct' => 0, 'explanation_en' => '', 'explanation_km' => ''];
        $start = old('questions', $questions ?: [$blank]);
        // Old input arrives with string indexes; normalise for the builder.
        $start = array_values(array_map(function ($q) use ($blank) {
            $q = array_merge($blank, (array) $q);
            $q['options'] = array_values(array_map(fn ($o) => ['en' => (string) ($o['en'] ?? ''), 'km' => (string) ($o['km'] ?? '')], (array) $q['options']));
            while (count($q['options']) < 4) $q['options'][] = ['en' => '', 'km' => ''];
            $q['correct'] = (int) ($q['correct'] ?? 0);
            return $q;
        }, $start));
    @endphp

    <form method="POST" enctype="multipart/form-data"
        action="{{ $isNew ? route('admin.quizzes.store') : route('admin.quizzes.update', $quiz) }}"
        style="display:grid; gap:24px"
        x-data="{ questions: @js($start), blank: @js($blank), add() { this.questions.push(JSON.parse(JSON.stringify(this.blank))); this.$nextTick(() => this.$refs.list.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'start' })); }, remove(i) { if (this.questions.length > 1) this.questions.splice(i, 1); }, move(i, d) { const j = i + d; if (j < 0 || j >= this.questions.length) return; const [q] = this.questions.splice(i, 1); this.questions.splice(j, 0, q); } }">
        @csrf
        @unless ($isNew) @method('PUT') @endunless

        <div style="display:grid; gap:10px">
            <a href="{{ route('admin.quizzes') }}" class="ad-back"><x-admin.icon name="arrow-left" class="ad-i16" /> Quizzes</a>
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:10px">
                <h1 class="ad-h1">{{ $isNew ? 'New quiz' : $quiz->title_en }}</h1>
                @unless ($isNew)<a href="{{ route('quizzes.show', $quiz) }}" class="ad-link ad-sm" target="_blank" rel="noopener">Try it as a student</a>@endunless
            </div>
        </div>

        @if ($errors->any())
            <div class="ad-callout warn" role="alert">
                <x-admin.icon name="alert" class="ad-i18" />
                <div><strong>Check the quiz.</strong>@foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>
            </div>
        @endif

        <div class="ad-grid ad-cols-xl-3">
            <div class="ad-span-xl-2" style="display:grid; gap:16px; align-content:start">
                <div x-ref="list" style="display:grid; gap:16px">
                    <template x-for="(q, i) in questions" :key="i">
                        <section class="ad-card">
                            <div class="ad-card-h" style="display:flex; align-items:center; justify-content:space-between; gap:8px">
                                <h2 class="ad-h2" x-text="'Question ' + (i + 1)"></h2>
                                <div style="display:inline-flex; gap:6px">
                                    <button type="button" class="ad-btn ad-btn-sm" @click="move(i, -1)" :disabled="i === 0" aria-label="Move up"><x-admin.icon name="up" class="ad-i16" /></button>
                                    <button type="button" class="ad-btn ad-btn-sm" @click="move(i, 1)" :disabled="i === questions.length - 1" aria-label="Move down"><x-admin.icon name="down" class="ad-i16" /></button>
                                    <button type="button" class="ad-btn ad-btn-sm" @click="remove(i)" :disabled="questions.length === 1" aria-label="Remove question"><x-admin.icon name="trash" class="ad-i16" /></button>
                                </div>
                            </div>
                            <div class="ad-card-b" style="display:grid; gap:14px">
                                <div class="ad-grid ad-grid-16 ad-cols-sm-2">
                                    <div>
                                        <label class="ad-label" :for="'q' + i + 'en'">Question in English</label>
                                        <textarea class="ad-input" rows="2" maxlength="500" required :id="'q' + i + 'en'" :name="'questions[' + i + '][prompt_en]'" x-model="q.prompt_en" placeholder="Which principle most directly improves readability in long-form body text?"></textarea>
                                    </div>
                                    <div>
                                        <label class="ad-label" :for="'q' + i + 'km'">In Khmer <span class="ad-muted" style="font-weight:400">(optional)</span></label>
                                        <textarea class="ad-input" rows="2" maxlength="500" :id="'q' + i + 'km'" :name="'questions[' + i + '][prompt_km]'" x-model="q.prompt_km"></textarea>
                                    </div>
                                </div>
                                <div style="display:grid; gap:8px">
                                    <p class="ad-label" style="margin:0">Answers <span class="ad-muted" style="font-weight:400">— pick the right one</span></p>
                                    <template x-for="(o, j) in q.options" :key="j">
                                        <div style="display:grid; grid-template-columns:auto minmax(0, 1fr) minmax(0, 1fr); gap:8px; align-items:center">
                                            <label style="display:inline-flex; align-items:center; gap:6px; font-weight:700; font-size:13px">
                                                <input type="radio" :name="'questions[' + i + '][correct]'" :value="j" x-model.number="q.correct" :aria-label="'Answer ' + 'ABCDEF'[j] + ' is right'">
                                                <span x-text="'ABCDEF'[j]"></span>
                                            </label>
                                            <input class="ad-input" maxlength="300" :name="'questions[' + i + '][options][' + j + '][en]'" x-model="o.en" :placeholder="'Answer ' + 'ABCDEF'[j] + ' in English'">
                                            <input class="ad-input" maxlength="300" :name="'questions[' + i + '][options][' + j + '][km]'" x-model="o.km" placeholder="ជាភាសាខ្មែរ (optional)">
                                        </div>
                                    </template>
                                </div>
                                <div class="ad-grid ad-grid-16 ad-cols-sm-2">
                                    <div>
                                        <label class="ad-label" :for="'q' + i + 'xe'">Why it is right <span class="ad-muted" style="font-weight:400">(shown after answering)</span></label>
                                        <textarea class="ad-input" rows="2" maxlength="1000" :id="'q' + i + 'xe'" :name="'questions[' + i + '][explanation_en]'" x-model="q.explanation_en"></textarea>
                                    </div>
                                    <div>
                                        <label class="ad-label" :for="'q' + i + 'xk'">In Khmer <span class="ad-muted" style="font-weight:400">(optional)</span></label>
                                        <textarea class="ad-input" rows="2" maxlength="1000" :id="'q' + i + 'xk'" :name="'questions[' + i + '][explanation_km]'" x-model="q.explanation_km"></textarea>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </template>
                </div>
                <button type="button" class="ad-btn" @click="add()" style="justify-self:start"><x-admin.icon name="plus" class="ad-i16" /> Add a question</button>
            </div>

            <div style="display:grid; gap:24px; align-content:start">
                <section class="ad-card">
                    <div class="ad-card-h"><h2 class="ad-h2">The quiz</h2></div>
                    <div class="ad-card-b" style="display:grid; gap:16px">
                        <div>
                            <label for="z-course" class="ad-label">Course</label>
                            <select id="z-course" name="course_id" class="ad-input" required>
                                @foreach ($courses as $c)
                                    <option value="{{ $c->id }}" @selected((int) old('course_id', $quiz->course_id) === $c->id)>{{ $c->title_en }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="z-title-en" class="ad-label">Title in English</label>
                            <input id="z-title-en" name="title_en" value="{{ old('title_en', $quiz->title_en) }}" maxlength="160" required class="ad-input" placeholder="Typography Knowledge Quiz">
                            @error('title_en') <p class="ad-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="z-title-km" class="ad-label">Title in Khmer <span class="ad-muted" style="font-weight:400">(optional)</span></label>
                            <input id="z-title-km" name="title_km" value="{{ old('title_km', $quiz->title_km) }}" maxlength="160" class="ad-input" placeholder="តេស្តចំណេះដឹង Typography">
                        </div>
                        <div>
                            <label for="z-topic" class="ad-label">Topic <span class="ad-muted" style="font-weight:400">(optional)</span></label>
                            <input id="z-topic" name="topic" value="{{ old('topic', $quiz->topic) }}" maxlength="160" class="ad-input" placeholder="Typography & Font Psychology">
                        </div>
                        <div>
                            <label for="z-pass" class="ad-label">Pass mark (%)</label>
                            <input id="z-pass" name="pass_percent" type="number" min="1" max="100" value="{{ old('pass_percent', $quiz->pass_percent) }}" class="ad-input" style="max-width:120px" required>
                        </div>
                        <div>
                            <label for="z-cover" class="ad-label">Cover picture <span class="ad-muted" style="font-weight:400">(optional)</span></label>
                            <input id="z-cover" name="cover" type="file" accept="image/jpeg,image/png,image/webp" class="ad-input">
                            <p class="ad-hint">No picture: the course picture is used.</p>
                        </div>
                        <label class="ad-choice">
                            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $isNew ? true : $quiz->is_published))>
                            <span><span style="display:block; font-weight:600">Visible to enrolled students</span></span>
                        </label>
                        <button type="submit" class="ad-btn ad-btn-primary" style="width:100%">{{ $isNew ? 'Save quiz' : 'Save changes' }}</button>
                        <p class="ad-hint" style="margin:0">Saving replaces the questions. Scores students already have stay as they are.</p>
                    </div>
                </section>
            </div>
        </div>
    </form>

    @unless ($isNew)
        <div class="ad-card" style="margin-top:24px; padding:18px 20px; display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px">
            <div>
                <p class="ad-h2">Delete this quiz</p>
                <p class="ad-muted ad-sm" style="margin:4px 0 0">Its questions and every student's results are removed.</p>
            </div>
            <form method="POST" action="{{ route('admin.quizzes.destroy', $quiz) }}"
                data-confirm="Delete “{{ $quiz->title_en }}” and all results?" data-confirm-title="Delete this quiz?" data-confirm-ok="Delete quiz" data-confirm-tone="danger">
                @csrf @method('DELETE')
                <button type="submit" class="ad-btn ad-btn-danger"><x-admin.icon name="trash" class="ad-i16" /> Delete quiz</button>
            </form>
        </div>
    @endunless
</x-admin-layout>
