<x-classroom-layout :title="__('Certificate') . ' — ' . $course->localized('title')">
    <div class="max-w-xl mx-auto px-6 py-16">

        <p class="text-xs uppercase tracking-[0.2em] mb-2" style="color:var(--redline)">{{ __('Certificate') }}</p>
        <h1 class="font-display font-extrabold text-3xl mb-2">{{ $course->localized('title') }}</h1>

        @if (! $enrolled)
            {{-- Not enrolled: nothing to ask for yet. --}}
            <p class="mb-8" style="color:var(--graphite)">{{ __('You need to join this course before you can get a certificate.') }}</p>
            <a href="{{ route('courses.show', $course) }}" class="inline-flex text-sm font-medium px-4 py-2 rounded-sm pd-btn-ink">{{ __('Open the course') }}</a>

        @elseif ($certificate)
            {{-- Approved: the certificate exists. --}}
            <div class="pd-sheet rounded-sm p-6 mt-6">
                <p class="font-medium mb-1">{{ __('Your certificate is ready.') }}</p>
                <p class="text-sm mb-5" style="color:var(--graphite)">{{ $certificate->holderName() }} · {{ $certificate->code }}</p>
                <a href="{{ route('certificates.show', $certificate) }}" class="inline-flex text-sm font-medium px-4 py-2 rounded-sm pd-btn-ink">{{ __('View certificate') }}</a>
            </div>

        @elseif (! $finished)
            {{-- Still learning. --}}
            <p class="mb-6" style="color:var(--graphite)">{{ __('Finish every lesson, then you can ask for your certificate here.') }}</p>
            <div class="pd-sheet rounded-sm p-5 mb-8">
                <div class="flex items-center justify-between text-sm mb-2">
                    <span>{{ __('Your progress') }}</span>
                    <span class="font-medium">{{ $progress }}%</span>
                </div>
                <div style="height:6px; border-radius:99px; background:var(--mist); overflow:hidden">
                    <div style="height:100%; width:{{ $progress }}%; background:var(--redline)"></div>
                </div>
            </div>
            <a href="{{ route('learning.index') }}" class="inline-flex text-sm font-medium px-4 py-2 rounded-sm pd-btn-ink">{{ __('Back to My Learning') }}</a>

        @elseif ($certRequest && $certRequest->status === \App\Models\CertificateRequest::PENDING)
            {{-- Sent, waiting for an admin. --}}
            <div class="pd-sheet rounded-sm p-6 mt-6">
                <p class="font-medium mb-1">{{ __('Your request is waiting for a check.') }}</p>
                <p class="text-sm mb-4" style="color:var(--graphite)">
                    {{ __('An admin will message you on Telegram to check that it is really you. Keep an eye on your messages.') }}
                </p>
                <dl class="text-sm grid gap-2">
                    <div class="flex justify-between gap-4"><dt style="color:var(--graphite)">{{ __('Name on the certificate') }}</dt><dd class="font-medium">{{ $certRequest->real_name }}</dd></div>
                    <div class="flex justify-between gap-4"><dt style="color:var(--graphite)">Telegram</dt><dd class="font-medium">&#64;{{ $certRequest->telegram_username }}</dd></div>
                </dl>
            </div>

        @else
            {{-- Finished, and either never asked or was turned down: the form. --}}
            @if ($certRequest && $certRequest->status === \App\Models\CertificateRequest::REJECTED)
                <div class="rounded-sm p-4 mt-6 text-sm" style="border:1px solid var(--mist); background:var(--paper)">
                    <p class="font-medium mb-1">{{ __('Your last request was not approved.') }}</p>
                    @if ($certRequest->admin_note)
                        <p style="color:var(--graphite)">{{ $certRequest->admin_note }}</p>
                    @endif
                    <p class="mt-2" style="color:var(--graphite)">{{ __('Fix it below and send it again.') }}</p>
                </div>
            @else
                <p class="mb-2" style="color:var(--graphite)">{{ __('You finished this course. Tell us the name to print and your Telegram, so an admin can check it is really you.') }}</p>
            @endif

            <form method="POST" action="{{ route('certificates.request.store', $course) }}" class="mt-8 grid gap-5" novalidate>
                @csrf

                <div>
                    <label for="real_name" class="block text-sm font-medium mb-1.5">{{ __('Your real name') }}</label>
                    <input id="real_name" name="real_name" type="text" required maxlength="120" autocomplete="name"
                        class="pd-input w-full" value="{{ old('real_name', $certRequest?->real_name) }}"
                        aria-describedby="real_name-hint real_name-error">
                    <p id="real_name-hint" class="text-xs mt-1.5" style="color:var(--graphite)">{{ __('Exactly as it is written on your ID. This is the name printed on the certificate.') }}</p>
                    @error('real_name')
                        <p id="real_name-error" class="text-sm mt-1.5 font-medium" style="color:var(--error)">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="telegram_username" class="block text-sm font-medium mb-1.5">{{ __('Telegram username') }}</label>
                    <input id="telegram_username" name="telegram_username" type="text" required maxlength="80" autocomplete="off"
                        placeholder="&#64;username" class="pd-input w-full" value="{{ old('telegram_username', $certRequest?->telegram_username) }}"
                        aria-describedby="telegram-hint telegram-error">
                    <p id="telegram-hint" class="text-xs mt-1.5" style="color:var(--graphite)">{{ __('An admin will open it to check it is you. You can type @name or paste your t.me link.') }}</p>
                    @error('telegram_username')
                        <p id="telegram-error" class="text-sm mt-1.5 font-medium" style="color:var(--error)">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="inline-flex text-sm font-medium px-5 py-2.5 rounded-sm pd-btn-ink">
                        {{ $certRequest ? __('Send again') : __('Ask for my certificate') }}
                    </button>
                    <a href="{{ route('learning.index') }}" class="text-sm hover:opacity-70" style="color:var(--graphite)">{{ __('Back to My Learning') }}</a>
                </div>
            </form>
        @endif
    </div>
</x-classroom-layout>
