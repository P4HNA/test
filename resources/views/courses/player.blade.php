<x-classroom-layout title="{{ $lesson->localized('title') }} — {{ $course->localized('title') }}">
    <div class="max-w-6xl mx-auto px-6 py-10">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-8 text-sm border-b pb-4" style="border-color:var(--mist); color:var(--graphite)">
            <a href="{{ route('courses.show', $course) }}" class="hover:opacity-70 flex items-center gap-2">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                {{ $course->localized('title') }}
            </a>
            
            <div class="flex items-center gap-4">
                <span class="font-medium text-xs">{{ __(':position/:total lessons', ['position' => $position, 'total' => $total]) }} · {{ __(':percent% complete', ['percent' => (int) round(($position / max($total, 1)) * 100)]) }}</span>
                <div class="w-32 h-1.5 rounded-full overflow-hidden" style="background:var(--mist)">
                    <div class="h-full rounded-full transition-all" style="width:{{ (int) round(($position / max($total, 1)) * 100) }}%; background:var(--ink)"></div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-[300px_1fr] gap-8 items-start">
            {{-- Lesson content (video, title/actions, up next, notes tabs) comes
                 first in the DOM so mobile reaches it immediately, without
                 scrolling past the curriculum first. Desktop position (right
                 of the 300px sidebar) is restored purely via grid placement
                 below -- DOM order is otherwise unchanged from before. --}}
            <div class="lg:col-start-2 lg:row-start-1">
                <div class="relative pd-sheet rounded-sm overflow-hidden mb-6 aspect-video bg-[var(--surface)]">
                    @if ($lesson->isUpload())
                        <video class="absolute inset-0 w-full h-full bg-black" controls controlsList="nodownload" preload="metadata" playsinline src="{{ route('courses.lessons.video', [$course, $lesson]) }}"></video>
                    @elseif ($lesson->embedSrc())
                        <iframe class="absolute inset-0 w-full h-full" src="{{ $lesson->embedSrc() }}" title="{{ $lesson->localized('title') }}" allowfullscreen></iframe>
                    @else
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-6" style="background:{{ $course->swatch()['bg'] }}">
                            <div class="font-display font-bold text-5xl mb-4 opacity-80" style="color:{{ $course->swatch()['fg'] }}">{{ $course->mark }}</div>
                            <p class="text-sm max-w-sm" style="color:var(--graphite)">
                                {{ $lesson->video_host === 'drive' ? __('Google Drive link not pasted yet. Notes are below.') : __('Video embed is waiting on a YouTube URL.') }}
                            </p>
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
                    <div>
                        <div class="flex items-center gap-2 mb-2 text-xs uppercase tracking-widest font-bold">
                            <span class="bg-[var(--ink)] text-[var(--paper)] px-2 py-0.5 rounded-sm">{{ __('Lesson :number', ['number' => $position]) }}</span>
                            <span style="color:var(--graphite)">•</span>
                            <span style="color:var(--graphite)">{{ __(':count min', ['count' => $lesson->duration_minutes]) }}</span>
                        </div>
                        <h1 class="font-display font-bold text-3xl mb-1">{{ $lesson->localized('title') }}</h1>
                        <p class="text-sm" style="color:var(--graphite)">{{ $currentModule?->localized('title') }}</p>
                    </div>
                    @if ($previewOnly)
                        {{-- Watching the free sample. There is no progress to record
                             without an enrolment, so this sells the course instead.
                             It points at the course page rather than straight at
                             checkout so the free / paid / on-offer button there stays
                             the one place that decides what "get this" means. --}}
                        <a href="{{ route('courses.show', $course) }}"
                            class="text-sm font-bold px-5 py-2.5 rounded-sm transition-opacity hover:opacity-80 flex items-center gap-2"
                            style="background:var(--redline); color:var(--paper)">
                            {{ __('Get the full course') }}
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    @elseif (! $isComplete)
                        <form method="POST" action="{{ route('courses.lessons.complete', [$course, $lesson]) }}">
                            @csrf
                            <button class="text-sm font-bold px-5 py-2.5 rounded-sm bg-green-500 text-white hover:bg-green-600 transition-colors flex items-center gap-2">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                {{ __('Mark as complete') }}
                            </button>
                        </form>
                    @else
                        <span class="text-sm font-bold px-5 py-2.5 rounded-sm bg-green-500/20 text-green-500 flex items-center gap-2">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            {{ __('Completed') }}
                        </span>
                    @endif
                </div>

                @if ($next && (! $previewOnly || $next->isFreePreviewIn($course)))
                    <div class="mb-8">
                        <a href="{{ route('courses.lessons.show', [$course, $next]) }}" class="inline-flex items-center justify-between text-sm w-full max-w-md pd-sheet rounded-sm p-4 hover:opacity-80 transition-opacity" style="border:1px solid var(--mist)">
                            <div>
                                <span class="text-xs uppercase tracking-wide mb-1 block" style="color:var(--graphite)">{{ __('Up Next') }}</span>
                                <span class="font-semibold">{{ $next->localized('title') }}</span>
                            </div>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    </div>
                @endif

                <div class="pd-sheet rounded-sm p-6" x-data="{ tab: 'notes' }">
                    <div class="flex flex-wrap gap-6 mb-5 text-xs font-bold uppercase tracking-widest" style="border-bottom:1px solid var(--mist)">
                        @foreach (['notes' => __('Lesson Notes'), 'resources' => __('Resources & Files (2)'), 'assignment' => __('Assignment').' 🟢', 'discussion' => __('Discussion (2)')] as $key => $label)
                            <button type="button" class="pb-3 -mb-px transition-colors" @click="tab = '{{ $key }}'"
                                :style="tab === '{{ $key }}' ? 'border-bottom:2px solid var(--ink); color:var(--ink)' : 'border-bottom:2px solid transparent; color:var(--graphite)'">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    <div x-show="tab === 'notes'">
                        <div class="p-4 rounded-sm mb-6 bg-[var(--surface)] border-l-4" style="border-color:var(--ink)">
                            <h4 class="font-bold mb-2 flex items-center gap-2">
                                🔑 {{ __('Key Concept & Shortcuts') }}
                            </h4>
                            <p class="text-sm" style="color:var(--graphite)">{{ __('Pay special attention to the core concepts discussed in this lesson. Practice the shortcuts provided to improve your workflow speed.') }}</p>
                        </div>
                        
                        @if ($lesson->localized('notes') || $lesson->notes_en)
                            <div class="text-sm leading-relaxed prose prose-sm max-w-none" style="color:var(--graphite)">
                                {!! nl2br(e($lesson->localized('notes') ?: $lesson->notes_en)) !!}
                            </div>
                        @else
                            <p class="text-sm" style="color:var(--graphite)">{{ __('No additional notes for this lesson.') }}</p>
                        @endif
                    </div>
                    <div x-show="tab === 'resources'" x-cloak>
                        @if ($lesson->video_url)
                            <a href="{{ $lesson->video_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm font-medium px-4 py-2 rounded-sm bg-[var(--surface)] hover:opacity-80">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/></svg>
                                {{ __('Download Source Files') }}
                            </a>
                        @else
                            <p class="text-sm" style="color:var(--graphite)">{{ __('No extra files attached.') }}</p>
                        @endif
                    </div>
                    <div x-show="tab === 'assignment'" x-cloak>
                        <p class="text-sm leading-relaxed" style="color:var(--graphite)">
                            {{ __('Produce a proof of this lesson and bring it to critique. Mark the lesson complete when your sheet is ready to review.') }}
                        </p>
                    </div>
                    <div x-show="tab === 'discussion'" x-cloak>
                        <p class="text-sm leading-relaxed" style="color:var(--graphite)">
                            {{ __('Critiques happen in class. Email your reviewer or use the studio Telegram once the instructor posts it.') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Curriculum -- follows the lesson content on mobile; restored to
                 the left 300px sidebar column at lg: via grid placement only. --}}
            <aside class="pd-sheet rounded-sm py-4 lg:col-start-1 lg:row-start-1">
                <div class="px-5 mb-4">
                    <h3 class="font-display font-bold text-lg mb-1">{{ $course->localized('title') }}</h3>
                    <p class="text-xs" style="color:var(--graphite)">{{ __('Course Curriculum') }}</p>
                </div>
                
                @foreach ($course->modules as $module)
                    <div class="mb-2">
                        <div class="px-5 py-2.5 bg-[var(--surface)] text-xs font-bold uppercase tracking-wider border-y" style="border-color:var(--mist)">
                            {{ $module->localized('title') }}
                        </div>
                        <ul class="py-2">
                            @foreach ($module->lessons as $index => $item)
                                @php
                                    // On a free preview only the sample lessons open. The rest
                                    // show a padlock rather than a link that bounces you out.
                                    $locked = $previewOnly && ! $item->isFreePreviewIn($course);
                                @endphp
                                <li>
                                    @if ($locked)
                                    <span class="flex items-start gap-3 px-5 py-2.5 opacity-60" title="{{ __('Get the full course to open this lesson') }}">
                                    @else
                                    <a href="{{ route('courses.lessons.show', [$course, $item]) }}"
                                        class="flex items-start gap-3 px-5 py-2.5 transition-colors hover:bg-[var(--surface)] {{ $item->id === $lesson->id ? 'bg-[var(--surface)] border-l-2' : '' }}"
                                        style="{{ $item->id === $lesson->id ? 'border-color:var(--ink)' : 'border-color:transparent' }}">
                                    @endif

                                        <div class="mt-0.5 shrink-0 w-5 h-5 rounded-full border flex items-center justify-center text-[10px]"
                                            style="{{ $completedIds->contains($item->id) ? 'background:var(--ink); border-color:var(--ink); color:var(--paper)' : 'border-color:var(--mist); color:var(--graphite)' }}">
                                            @if ($locked)
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                                <span class="sr-only">{{ __('Locked') }}:</span>
                                            @elseif ($completedIds->contains($item->id))
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                            @else
                                                {{ $index + 1 }}
                                            @endif
                                        </div>
                                        
                                        <div>
                                            <span class="block text-sm font-medium {{ $item->id === $lesson->id ? '' : 'opacity-80' }}">
                                                {{ $item->localized('title') }}
                                            </span>
                                            <span class="block text-xs mt-0.5" style="color:var(--graphite)">
                                                {{ __(':count min', ['count' => $item->duration_minutes]) }}
                                                @if (! $locked && $previewOnly)
                                                    · <span style="color:var(--redline); font-weight:600">{{ __('Free preview') }}</span>
                                                @endif
                                            </span>
                                        </div>
                                    @if ($locked)
                                    </span>
                                    @else
                                    </a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </aside>
        </div>
    </div>
</x-classroom-layout>
