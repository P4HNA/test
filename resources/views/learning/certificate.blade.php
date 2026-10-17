<x-classroom-layout :title="__('Certificate') . ' — ' . $certificate->course->localized('title')">
    <div class="max-w-2xl mx-auto px-6 py-20">
        <div class="relative pd-sheet rounded-sm p-10 text-center">
            <span class="crop-mark tl"></span>
            <span class="crop-mark tr"></span>
            <span class="crop-mark bl"></span>
            <span class="crop-mark br"></span>
            <p class="text-xs uppercase tracking-[0.25em] mb-6" style="color:var(--redline)">Piseth.Design Classroom</p>
            <h1 class="font-display font-extrabold text-3xl mb-3">{{ __('Certificate of completion') }}</h1>
            <p class="mb-8" style="color:var(--graphite)">{{ __('This sheet certifies that') }}</p>
            <p class="font-display text-2xl mb-2">{{ $certificate->holderName() }}</p>
            <p class="mb-8" style="color:var(--graphite)">{{ __('completed') }}</p>
            <p class="font-medium text-xl mb-8">{{ $certificate->course->localized('title') }}</p>
            <p class="text-xs" style="color:var(--graphite)">{{ $certificate->code }} · {{ $certificate->issued_at?->toFormattedDateString() }}</p>
        </div>
        <p class="text-center mt-8">
            <a href="{{ route('learning.index') }}" class="text-sm hover:opacity-70" style="color:var(--redline)">{{ __('Back to My Learning') }}</a>
        </p>
    </div>
</x-classroom-layout>
