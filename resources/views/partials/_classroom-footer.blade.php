<footer style="border-top:1px solid var(--mist); background:var(--surface)">
    <div class="max-w-6xl mx-auto px-6 py-16 grid sm:grid-cols-2 lg:grid-cols-4 gap-12">
        <div>
            <div class="mb-6">
                <x-brand :href="route('home')" :size="36" />
            </div>
            <p class="text-sm max-w-xs mb-8 leading-relaxed" style="color:var(--graphite)">
                {{ __('Empowering the next generation of Cambodian creatives with professional design education, taught in both Khmer and English.') }}
            </p>
            <div class="flex gap-4">
                <a href="{{ config('social.facebook') }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Facebook (opens in a new tab)') }}" class="opacity-60 hover:opacity-100 transition-opacity"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></a>
                <a href="{{ config('social.instagram') }}" target="_blank" rel="noopener noreferrer" aria-label="{{ __('Instagram (opens in a new tab)') }}" class="opacity-60 hover:opacity-100 transition-opacity"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg></a>
                <button type="button"
                    aria-haspopup="dialog"
                    aria-label="{{ __('Telegram (show QR code)') }}"
                    x-data
                    x-on:click="$dispatch('open-telegram-qr')"
                    class="pd-qr-trigger opacity-60 hover:opacity-100 transition-opacity"><svg width="20" height="20" viewBox="-1.5 -1.5 27 27" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21.198 2.433a2.242 2.242 0 0 0-1.022.215l-16.8 6.551a2.26 2.26 0 0 0-1.282 2.748 2.247 2.247 0 0 0 1.764 1.547l4.316 1.055 1.547 5.093c.174.55.674.912 1.25.922h.023a1.272 1.272 0 0 0 .963-.443l2.42-2.6 4.636 3.426a2.262 2.262 0 0 0 3.322-.96l3.413-14.86a2.25 2.25 0 0 0-2.544-2.705z"/></svg></button>
            </div>
            @include('partials._telegram-qr-dialog')
        </div>
        
        <div>
            <h4 class="text-xs font-bold uppercase tracking-widest mb-6" style="color:var(--graphite)">{{ __('Core Design Tracks') }}</h4>
            <ul class="space-y-3 text-sm font-medium">
                <li><a href="{{ route('courses') }}?q=Graphic+Design" class="hover:text-[var(--redline)] transition-colors">{{ __('Graphic Design Fundamentals') }}</a></li>
                <li><a href="{{ route('courses') }}?q=Photoshop" class="hover:text-[var(--redline)] transition-colors">{{ __('Adobe Photoshop Mastery') }}</a></li>
                <li><a href="{{ route('courses') }}?q=Illustrator" class="hover:text-[var(--redline)] transition-colors">{{ __('Adobe Illustrator Vector Art') }}</a></li>
                <li><a href="{{ route('courses') }}?q=InDesign" class="hover:text-[var(--redline)] transition-colors">{{ __('Adobe InDesign Editorial') }}</a></li>
                <li><a href="{{ route('courses') }}?q=UI" class="hover:text-[var(--redline)] transition-colors">{{ __('UI/UX Design with Figma') }}</a></li>
            </ul>
        </div>
        
        <div>
            <h4 class="text-xs font-bold uppercase tracking-widest mb-6" style="color:var(--graphite)">{{ __('Student Platform') }}</h4>
            <ul class="space-y-3 text-sm font-medium">
                <li><a href="{{ route('courses') }}" class="hover:text-[var(--redline)] transition-colors">{{ __('Course Library') }}</a></li>
                @auth
                    <li><a href="{{ route('learning.index') }}" class="hover:text-[var(--redline)] transition-colors">{{ __('My Learning Dashboard') }}</a></li>
                    <li><a href="{{ route('learning.index') }}" class="hover:text-[var(--redline)] transition-colors">{{ __('Verified Certificates') }}</a></li>
                    @if (auth()->user()->isAdmin())
                        <li><a href="{{ route('admin.dashboard') }}" class="hover:text-[var(--redline)] transition-colors">{{ __('Instructor Management') }}</a></li>
                    @endif
                @else
                    <li><a href="{{ route('login') }}" class="hover:text-[var(--redline)] transition-colors">{{ __('Student Login') }}</a></li>
                    <li><a href="{{ route('register') }}" class="hover:text-[var(--redline)] transition-colors">{{ __('Create Free Account') }}</a></li>
                @endauth
            </ul>
        </div>
        
        <div>
            <h4 class="text-xs font-bold uppercase tracking-widest mb-6" style="color:var(--graphite)">{{ __('Design Community') }}</h4>
            <p class="text-sm mb-4" style="color:var(--graphite)">{{ __('Join 5,000+ designers getting weekly resources and tips.') }}</p>
            <form class="flex flex-col gap-3">
                <input type="email" placeholder="{{ __('Email address') }}" class="w-full text-sm bg-transparent px-4 py-2.5 rounded-sm focus:outline-none" style="border:1px solid var(--mist); color:var(--ink)">
                <button type="button" class="w-full text-sm font-bold px-4 py-2.5 rounded-sm bg-[var(--ink)] text-[var(--paper)] hover:opacity-90">
                    {{ __('Subscribe') }}
                </button>
            </form>
        </div>
    </div>
    
    <div style="border-top:1px solid var(--mist)">
        <div class="max-w-6xl mx-auto px-6 py-6 flex flex-col md:flex-row items-center justify-between gap-4 text-xs font-medium" style="color:var(--graphite)">
            <div class="flex flex-wrap gap-6">
                <span>&copy; {{ date('Y') }} Piseth.Design Classroom. {{ __('All rights reserved.') }}</span>
            </div>
            <div class="flex gap-6">
                <a href="#" class="hover:text-[var(--ink)]">{{ __('Privacy Policy') }}</a>
                <a href="#" class="hover:text-[var(--ink)]">{{ __('Terms of Service') }}</a>
                <a href="#" class="hover:text-[var(--ink)]">{{ __('Student Support') }}</a>
            </div>
        </div>
    </div>
</footer>