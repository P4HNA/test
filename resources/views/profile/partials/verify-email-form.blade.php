@if ($pendingEmailChange)
<section id="confirm-email" class="acct-card" style="border-color:var(--redline)">
    <div class="acct-card-head">
        <span class="acct-card-icon" aria-hidden="true" style="background:color-mix(in srgb, var(--redline) 12%, transparent); color:var(--redline)">
            <x-icon name="mail" class="w-[18px] h-[18px]" />
        </span>
        <div>
            <h2>{{ __('Confirm your new email') }}</h2>
            <p>{{ __('One more step before the change takes effect.') }}</p>
        </div>
    </div>

    <p class="acct-hint" style="margin-top:0">
        {!! __('We sent a 6-digit code to :email. Enter it below to finish changing your email. The code expires in 10 minutes.', ['email' => '<strong class="break-all">'.e($pendingEmailChange->new_email).'</strong>']) !!}
    </p>

    @if ($mailIsLogOnly ?? false)
        {{-- Local development only: be honest that nothing reached an inbox. --}}
        <p class="mt-3 text-xs rounded-lg px-3 py-2" style="background:var(--surface);color:var(--graphite)">
            {!! __('Local mail mode (:mailer): no real email was sent. The message was written to :log. Configure SMTP in :env for real delivery.', ['mailer' => '<code>MAIL_MAILER='.e(config('mail.default')).'</code>', 'log' => '<code>storage/logs/laravel.log</code>', 'env' => '<code>.env</code>']) !!}
        </p>
    @endif

    <form
        method="post"
        action="{{ route('profile.email.confirm') }}"
        class="mt-5 flex flex-wrap items-end gap-3"
        x-data="{ busy: false }"
        x-on:submit="if (busy) { $event.preventDefault() } else { busy = true }"
        x-on:pageshow.window="busy = false"
    >
        @csrf
        <div>
            <label for="code" class="acct-label">{{ __('Verification code') }}</label>
            <input
                id="code"
                name="code"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                placeholder="000000"
                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                class="pd-input w-40 text-lg tracking-widest text-center"
                @if ($errors->emailChange->has('code')) aria-invalid="true" aria-describedby="code-error" autofocus @endif
            />
            <x-input-error id="code-error" class="mt-2 font-medium" style="color:var(--error)" :messages="$errors->emailChange->get('code')" />
        </div>
        <button type="submit" class="acct-btn acct-btn-primary pd-btn-wait" x-bind:disabled="busy">{{ __('Confirm') }}</button>
    </form>

    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
        {{--
            The 60-second cooldown is enforced on the server
            (EmailChangeVerifier::RESEND_COOLDOWN_SECONDS); this only mirrors
            it. The remaining time comes from the server on every page load,
            so it stays correct after a refresh, and it counts down against a
            fixed deadline so a backgrounded tab doesn't drift.
        --}}
        <form
            method="post"
            action="{{ route('profile.email.resend') }}"
            x-data="{
                left: {{ (int) $resendAvailableIn }},
                deadline: Date.now() + {{ (int) $resendAvailableIn }} * 1000,
                busy: false,
                timer: null,
                tick() {
                    this.left = Math.max(0, Math.ceil((this.deadline - Date.now()) / 1000));
                    if (this.left === 0) {
                        clearInterval(this.timer);
                        document.getElementById('resend-status').textContent = '{{ __('You can now request a new code.') }}';
                    }
                },
            }"
            x-init="if (left > 0) timer = setInterval(() => tick(), 250)"
            x-on:submit="if (busy || left > 0) { $event.preventDefault() } else { busy = true }"
            x-on:pageshow.window="busy = false"
        >
            @csrf
            <button
                type="submit"
                class="acct-btn acct-btn-ghost pd-btn-wait"
                aria-describedby="resend-status"
                @disabled($resendAvailableIn > 0)
                x-bind:disabled="busy || left > 0"
            >
                <span x-text="busy ? '{{ __('Sending…') }}' : (left > 0 ? '{{ __('Resend code in :lefts', ['left' => '__LEFT__']) }}'.replace('__LEFT__', left) : '{{ __('Resend code') }}')">
                    {{ $resendAvailableIn > 0 ? __('Resend code in :lefts', ['left' => $resendAvailableIn]) : __('Resend code') }}
                </span>
            </button>
        </form>
        <form method="post" action="{{ route('profile.email.cancel') }}">
            @csrf
            @method('delete')
            <button type="submit" class="acct-btn acct-btn-ghost">{{ __('Cancel change') }}</button>
        </form>
    </div>

    {{-- Announced once when it changes -- not on every countdown tick. --}}
    <p id="resend-status" class="sr-only" aria-live="polite">
        @if ($resendAvailableIn > 0)
            {{ __('You can request another code in :seconds seconds.', ['seconds' => $resendAvailableIn]) }}
        @endif
    </p>

    <x-input-error class="mt-3 font-medium" style="color:var(--error)" :messages="$errors->emailChange->get('resend')" />

    @if (session('status') === 'email-code-resent')
        <p class="mt-3 acct-note" role="status">
            {{ __('A new code was sent. Any earlier code no longer works.') }}
            @if ($resendAvailableIn > 0)
                {{ __('You can request another in :seconds seconds.', ['seconds' => $resendAvailableIn]) }}
            @endif
        </p>
    @endif
</section>
@endif
