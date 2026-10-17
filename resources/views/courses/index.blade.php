<x-classroom-layout :title="__('Courses') . ' — Piseth.Design Classroom'">
    <div x-data="{ category: @js($activeCategory ?? 'All'), open: false }">
        <header class="max-w-6xl mx-auto px-6 pt-16 pb-6">
            <span class="text-xs font-semibold uppercase tracking-[0.2em]" style="color:var(--graphite)">{{ __('Curriculum Directory') }}</span>
            <h1 class="font-display font-extrabold text-4xl sm:text-5xl leading-[1.05] tracking-tight mt-3 mb-4">
                {{ __('Course Library') }}
            </h1>
            <p class="text-base mb-8" style="color:var(--graphite)">
                {{ __('Browse our complete collection of design courses and masterclasses.') }}
            </p>

            {{-- The live promotion from Admin > Promotions, featured above the courses. --}}
            @include('partials._course-promo-banner')

            <form method="GET" action="{{ route('courses') }}" class="pd-sheet rounded-sm p-4 flex flex-col md:flex-row gap-4 mb-8">
                <div class="flex-1 relative"
                    x-data="{
                        sq: @js($search),
                        sOpen: false,
                        sIndex: @js($searchIndex),
                        get sMatches() {
                            const needle = this.sq.trim().toLowerCase();
                            if (needle === '') return [];
                            return this.sIndex.filter(c => c.title.toLowerCase().includes(needle)).slice(0, 8);
                        }
                    }"
                    @click.outside="sOpen = false">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 opacity-50" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="search" name="q" x-model="sq" @input="sOpen = sq.trim() !== ''" @focus="sOpen = sq.trim() !== ''"
                        autocomplete="off" placeholder="{{ __('Search courses, tools, lessons...') }}"
                        class="w-full text-sm bg-transparent py-2 pl-9 pr-10 focus:outline-none rounded-sm"
                        style="background:var(--surface); border:1px solid var(--mist); color:var(--ink)">

                    <div x-show="sOpen && sMatches.length > 0" x-cloak x-transition.origin.top
                        class="absolute left-0 right-0 mt-2 z-40 rounded-sm shadow-lg overflow-hidden"
                        style="background:var(--paper); border:1px solid var(--mist)">
                        <template x-for="course in sMatches" :key="course.slug">
                            <a :href="'/courses/' + course.slug" class="block px-4 py-2 text-sm hover:opacity-70"
                                style="color:var(--ink); border-bottom:1px solid var(--mist)">
                                <span x-text="course.title"></span>
                                <span class="text-xs ml-2" style="color:var(--graphite)" x-text="course.category"></span>
                            </a>
                        </template>
                    </div>
                    <div x-show="sOpen && sq.trim() !== '' && sMatches.length === 0" x-cloak
                        class="absolute left-0 right-0 mt-2 z-40 rounded-sm shadow-lg px-4 py-3 text-sm"
                        style="background:var(--paper); border:1px solid var(--mist); color:var(--graphite)">
                        {{ __('No matching courses.') }}
                    </div>
                </div>

                <div class="relative" @click.outside="open = false">
                    <button type="button" @click="open = !open" aria-haspopup="true" :aria-expanded="open"
                        class="w-full md:w-auto flex items-center justify-center gap-2 text-sm py-2 px-4 rounded-sm"
                        style="background:var(--surface); border:1px solid var(--mist); color:var(--ink)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="4" y1="21" x2="4" y2="14"></line>
                            <line x1="4" y1="10" x2="4" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12" y2="3"></line>
                            <line x1="20" y1="21" x2="20" y2="16"></line>
                            <line x1="20" y1="12" x2="20" y2="3"></line>
                            <line x1="1" y1="14" x2="7" y2="14"></line>
                            <line x1="9" y1="8" x2="15" y2="8"></line>
                            <line x1="17" y1="16" x2="23" y2="16"></line>
                        </svg>
                        {{ __('Filters') }}
                        @if ($activeFilterCount > 0)
                            <span class="text-[10px] font-bold leading-none w-5 h-5 rounded-full flex items-center justify-center" style="background:var(--ink); color:var(--paper)">{{ $activeFilterCount }}</span>
                        @endif
                    </button>

                    <div x-show="open" x-cloak x-transition.origin.top.right
                        class="absolute right-0 mt-2 w-full md:w-80 z-40 rounded-sm p-5 shadow-lg"
                        style="background:var(--paper); border:1px solid var(--mist)">

                        <div class="mb-5">
                            <p class="text-xs font-semibold uppercase tracking-wide mb-3" style="color:var(--graphite)">{{ __('Course Level') }}</p>
                            <div class="flex flex-col gap-2 text-sm">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="level" value="All" style="accent-color:var(--redline)" {{ $level === 'All' ? 'checked' : '' }}>
                                    {{ __('Any level') }}
                                </label>
                                @foreach ($levels as $lvl)
                                    @if ($lvl !== 'All')
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="level" value="{{ $lvl }}" style="accent-color:var(--redline)" {{ $level === $lvl ? 'checked' : '' }}>
                                        {{ __($lvl) }}
                                    </label>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-5 pt-4" style="border-top:1px solid var(--mist)">
                            <label class="flex items-center justify-between gap-3 cursor-pointer">
                                <span class="text-sm font-medium">{{ __('Free courses only') }}</span>
                                <input type="checkbox" name="free" value="1" style="accent-color:var(--redline); width:18px; height:18px" {{ $free ? 'checked' : '' }}>
                            </label>
                        </div>

                        <div class="mb-5 pt-4" style="border-top:1px solid var(--mist)">
                            <p class="text-xs font-semibold uppercase tracking-wide mb-3" style="color:var(--graphite)">{{ __('Price range (USD)') }}</p>
                            <div class="flex items-center gap-2">
                                <input type="number" name="price_min" min="0" step="1" inputmode="numeric" placeholder="{{ __('Min') }}" value="{{ $priceMin }}"
                                    class="w-full text-sm py-2 px-3 rounded-sm focus:outline-none" style="background:var(--surface); border:1px solid var(--mist); color:var(--ink)">
                                <span style="color:var(--graphite)">&ndash;</span>
                                <input type="number" name="price_max" min="0" step="1" inputmode="numeric" placeholder="{{ __('Max') }}" value="{{ $priceMax }}"
                                    class="w-full text-sm py-2 px-3 rounded-sm focus:outline-none" style="background:var(--surface); border:1px solid var(--mist); color:var(--ink)">
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-4" style="border-top:1px solid var(--mist)">
                            <button type="submit" class="flex-1 text-sm font-semibold py-2 rounded-sm" style="background:var(--ink); color:var(--paper)">{{ __('Apply filters') }}</button>
                            <a href="{{ route('courses') }}" class="text-sm font-medium py-2 px-3 rounded-sm text-center" style="border:1px solid var(--mist); color:var(--ink)">{{ __('Clear') }}</a>
                        </div>
                    </div>
                </div>
            </form>

            <div class="flex flex-wrap gap-2 mb-6">
                @foreach ($categories as $category)
                    <button type="button" @click="category = @js($category)"
                        class="text-xs font-medium px-4 py-2 rounded-[20px]"
                        :style="category === @js($category)
                            ? 'background:var(--ink); color:var(--paper); border:1px solid var(--ink)'
                            : 'background:transparent; border:1px solid var(--mist)'">
                        {{ __($category) }}
                    </button>
                @endforeach
            </div>

            <p class="text-xs" style="color:var(--graphite)">
                {!! __('Showing :count courses', ['count' => '<span class="font-bold text-[var(--ink)]">'.$courses->count().'</span>']) !!}
            </p>
        </header>

        <main class="max-w-6xl mx-auto px-6 pb-24">
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($courses as $course)
                    @php $discounted = $course->activeDiscount()?->isDiscount() && $course->isOnSale(); @endphp
                    <a href="{{ route('courses.show', $course) }}"
                        class="course-tile relative rounded-sm flex flex-col block overflow-hidden{{ $discounted ? ' is-promo' : '' }}"
                        x-show="category === 'All' || category === @js($course->category)"
                        style="background:var(--paper); border:1px solid var(--mist); color:var(--ink)">
                        @if ($loop->iteration === 1 || $loop->iteration === 4)
                            <span class="pin" aria-hidden="true" style="z-index: 10;"></span>
                        @endif
                        
                        <!-- Thumbnail Area -->
                        <div class="relative w-full aspect-[4/3] flex items-center justify-center p-6" style="background:{{ $course->swatch()['bg'] }}">
                            {{-- Blue ribbon, only on a course a live discount applies to. --}}
                            <x-discount-ribbon :course="$course" />

                            @if ($thumb = $course->thumbnailSrc())
                                <img src="{{ $thumb }}" alt="" loading="lazy" style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover">
                            @else
                                <div class="font-display font-bold text-6xl opacity-80" style="color:{{ $course->swatch()['fg'] }}">{{ $course->mark }}</div>
                            @endif
                            
                            <!-- Badges -->
                            <div class="absolute top-3 left-3 flex flex-col gap-2">
                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-sm bg-white/90 text-black backdrop-blur-md">
                                    {{ __($course->category) }}
                                </span>
                                @if($course->is_featured)
                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-1 rounded-sm text-white" style="background:var(--action)">
                                        {{ __('Featured') }}
                                    </span>
                                @endif
                            </div>
                            
                            <!-- Price Badge -->
                            {{-- On a discounted card the ribbon owns the top corner, so the price sits at the bottom. --}}
                            <div class="absolute right-3 {{ $discounted ? 'bottom-3' : 'top-3' }}">
                                @if ($course->isFree())
                                    <span class="text-[10px] font-bold uppercase px-2 py-1 rounded-sm bg-green-500 text-white">{{ __('Free') }}</span>
                                @elseif ($course->isOnSale())
                                    {{-- A live promotion: the regular price struck through, today's price beside it. --}}
                                    <x-sale-price :course="$course" class="text-[11px] font-bold px-2 py-1 rounded-sm text-white" style="background:#0866FF" />
                                @else
                                    <span class="text-[10px] font-bold uppercase px-2 py-1 rounded-sm bg-black/60 text-white backdrop-blur-md">{{ $course->priceLabel() }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-5 flex flex-col flex-1">
                            <div class="flex items-center justify-between text-xs mb-2" style="color:var(--graphite)">
                                <span class="font-medium">{{ __($course->level) }}</span>
                            </div>
                            
                            <h2 class="font-bold text-lg leading-tight mb-2 flex-1">{{ $course->localized('title') }}</h2>
                            <p class="text-sm mb-4 line-clamp-2" style="color:var(--graphite)">{{ $course->localized('description') }}</p>
                            
                            <!-- Stats Row -->
                            <div class="flex items-center gap-4 text-xs font-medium mb-4" style="color:var(--graphite)">
                                <div class="flex items-center gap-1.5">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="m9 8 6 4-6 4Z"/></svg>
                                    {{ __(':count lessons', ['count' => $course->lessons_count]) }}
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    {{ $course->formattedDuration() }}
                                </div>
                            </div>
                            
                            <!-- Instructor Row -->
                            <div class="flex items-center gap-2 mb-5">
                                <div class="w-6 h-6 rounded-full bg-gray-300 overflow-hidden flex items-center justify-center">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="gray" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </div>
                                <span class="text-xs font-medium">{{ $course->instructor_name }}</span>
                            </div>

                            <!-- Footer Action -->
                            <div class="mt-auto pt-4 border-t" style="border-color:var(--mist)">
                                @if($enrolledIds->contains($course->id))
                                    @php $progress = $course->progressPercent($user); @endphp
                                    <div class="flex justify-between text-xs mb-1.5 font-medium">
                                        <span>{{ __('Progress') }}</span>
                                        <span>{{ $progress }}%</span>
                                    </div>
                                    <div class="w-full h-1.5 rounded-full overflow-hidden mb-3" style="background:var(--mist)">
                                        <div class="h-full rounded-full transition-all" style="width:{{ $progress }}%; background:var(--ink)"></div>
                                    </div>
                                    <span class="block w-full text-center text-sm font-semibold py-2 rounded-sm" style="background:var(--surface); color:var(--ink)">
                                        {{ __('Continue Learning') }} →
                                    </span>
                                @else
                                    <span class="block w-full text-center text-sm font-semibold py-2 rounded-sm" style="color:inherit">
                                        {{ __('View Course') }} →
                                    </span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </main>
    </div>
</x-classroom-layout>
