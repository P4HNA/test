<x-classroom-layout :title="__('About') . ' — Piseth.Design Classroom'">
    <div class="max-w-6xl mx-auto px-6 py-16">
        <span class="text-xs font-semibold uppercase tracking-[0.2em]" style="color:var(--redline)">{{ __('Studio') }}</span>
        <h1 class="font-display font-extrabold text-4xl sm:text-5xl leading-[1.05] mt-4 mb-6 max-w-2xl">
            {{ __('A clutter-free classroom for Cambodian designers.') }}
        </h1>
        <p class="text-lg max-w-xl mb-16" style="color:var(--graphite)">
            {{ __('Piseth.Design Classroom is a graphic design LMS: structured video lessons, real briefs, English and Khmer titles, and a verified certificate when you finish the sheet.') }}
        </p>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-20">
            @foreach ([
                ['title' => __('Practical project-based'), 'body' => __('Build posters, branding systems, and social kits from scratch — work that belongs in a portfolio.')],
                ['title' => __('Bilingual & local context'), 'body' => __('Khmer font pairing, local print guidelines, and global standards explained in both languages.')],
                ['title' => __('YouTube & Drive streaming'), 'body' => __('Lessons embed from YouTube or Google Drive. Instructors paste a URL; students hit play.')],
                ['title' => __('Official certificate'), 'body' => __('Finish every lesson in a course and a stamped certificate lands on your desk.')],
            ] as $card)
                <div class="pd-sheet rounded-sm p-5">
                    <h2 class="font-medium mb-2">{{ $card['title'] }}</h2>
                    <p class="text-sm" style="color:var(--graphite)">{{ $card['body'] }}</p>
                </div>
            @endforeach
        </div>

        <h2 class="font-display font-bold text-2xl mb-8">{{ __('How it works') }}</h2>
        <ol class="grid sm:grid-cols-4 gap-6 mb-16">
            @foreach ([
                [__('Create an account'), __('Sign up and open the free starter lessons.')],
                [__('Select your tool'), __('Photoshop, Illustrator, Figma, InDesign, or Motion.')],
                [__('Learn & practise'), __('Watch, mark complete, and keep the notes under the player.')],
                [__('Get certified'), __('Finish the syllabus and show the certificate on your profile.')],
            ] as $i => $step)
                <li class="pd-sheet rounded-sm p-5">
                    <span class="text-xs font-semibold" style="color:var(--redline)">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                    <h3 class="font-medium mt-2 mb-1">{{ $step[0] }}</h3>
                    <p class="text-sm" style="color:var(--graphite)">{{ $step[1] }}</p>
                </li>
            @endforeach
        </ol>

        <a href="{{ route('courses') }}" class="inline-flex text-sm font-medium px-5 py-3 rounded-sm pd-btn-ink">{{ __('Browse the catalogue') }}</a>
    </div>
</x-classroom-layout>
