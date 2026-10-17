<div class="acct-card-head">
    <span class="acct-card-icon" aria-hidden="true"><x-icon name="user" class="w-[18px] h-[18px]" /></span>
    <div>
        <h2>{{ __('Profile information') }}</h2>
        <p>{{ __('Update your name, phone, and email address.') }}</p>
    </div>
</div>

<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

@php
    // After a failed save, put the cursor (and screen readers) on the
    // first field that needs fixing instead of always on Name.
    $focusField = collect(['name', 'phone', 'email'])->first(fn ($field) => $errors->has($field)) ?? 'name';
@endphp

<form
    method="post"
    action="{{ route('profile.update') }}"
    x-data="{ busy: false }"
    x-on:submit="if (busy) { $event.preventDefault() } else { busy = true }"
    x-on:pageshow.window="busy = false"
>
    @csrf
    @method('patch')

    <div class="acct-field">
        <label for="name" class="acct-label">{{ __('Name') }}</label>
        <input
            id="name"
            name="name"
            type="text"
            class="pd-input"
            value="{{ old('name', $user->name) }}"
            required
            autocomplete="name"
            @if ($focusField === 'name') autofocus @endif
            @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
        />
        <x-input-error id="name-error" role="alert" class="mt-2 font-medium" style="color:var(--error)" :messages="$errors->get('name')" />
    </div>

    <div class="acct-field">
        <label for="phone" class="acct-label">{{ __('Phone') }}</label>
        {{--
            No `pattern` attribute: the browser would refuse to submit an
            untouched legacy value such as "+855 12 000 001", which the
            server deliberately still accepts. The server enforces the
            digits-only rule on new/changed values; this filter is only a
            typing convenience (Khmer digits ០-៩ become 0-9).
        --}}
        <input
            id="phone"
            name="phone"
            type="text"
            inputmode="numeric"
            placeholder="012345678"
            oninput="var v = this.value.replace(/[០-៩]/g, function (d) { return String(d.charCodeAt(0) - 0x17E0); }).replace(/[^0-9]/g, ''); if (v !== this.value) this.value = v;"
            class="pd-input"
            value="{{ old('phone', $user->phone) }}"
            autocomplete="tel"
            @if ($focusField === 'phone') autofocus @endif
            @error('phone') aria-invalid="true" aria-describedby="phone-error phone-hint" @else aria-describedby="phone-hint" @enderror
        />
        <p id="phone-hint" class="acct-hint">{{ __('Digits only — no spaces, +, dashes, or symbols.') }}</p>
        <x-input-error id="phone-error" class="mt-2 font-medium" style="color:var(--error)" :messages="$errors->get('phone')" />
    </div>

    <div class="acct-field">
        <label for="email" class="acct-label">{{ __('Email') }}</label>
        <input
            id="email"
            name="email"
            type="email"
            class="pd-input"
            value="{{ old('email', $user->email) }}"
            required
            autocomplete="username"
            @if ($focusField === 'email') autofocus @endif
            @error('email') aria-invalid="true" aria-describedby="email-error email-hint" @else aria-describedby="email-hint" @enderror
        />
        <p id="email-hint" class="acct-hint">{{ __('Changing this sends a confirmation code to the new address before it takes effect.') }}</p>
        <x-input-error id="email-error" class="mt-2 font-medium" style="color:var(--error)" :messages="$errors->get('email')" />

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="acct-hint" style="margin-top:10px">
                <p>
                    {{ __('Your email address is unverified.') }}
                </p>
                <button form="send-verification" type="submit" class="acct-btn acct-btn-quiet" style="margin-top:8px">
                    <x-icon name="mail" class="w-4 h-4" /> {{ __('Re-send the verification email') }}
                </button>

                @if (session('status') === 'verification-link-sent')
                    <p class="acct-note" style="margin-top:8px" role="status">
                        {{ __('A new verification link has been sent to your email address.') }}
                    </p>
                @endif

                {{-- Resend cooldown / mail failure (EmailVerificationNotificationController). --}}
                <x-input-error class="mt-2 font-medium" style="color:var(--error)" role="alert" :messages="$errors->get('verification')" />
            </div>
        @endif
    </div>

    <div class="acct-actions">
        <button type="submit" class="acct-btn acct-btn-primary pd-btn-wait" x-bind:disabled="busy" x-bind:aria-busy="busy.toString()">
            <span x-text="busy ? '{{ __('Saving…') }}' : '{{ __('Save changes') }}'">{{ __('Save changes') }}</span>
        </button>

        {{-- Puts every field back to what is stored, without a round trip. --}}
        <button type="reset" class="acct-btn acct-btn-ghost">{{ __('Cancel') }}</button>

        @if (session('status') === 'profile-updated')
            <p
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2600)"
                class="acct-saved acct-spacer"
                role="status"
            ><x-icon name="check-circle" class="w-4 h-4" /> {{ __('Saved.') }}</p>
        @endif

        @if (session('status') === 'email-verification-sent')
            <p class="acct-note acct-spacer">{{ __('We sent a code to confirm your new email — see below.') }}</p>
        @endif

        @if (session('status') === 'email-updated')
            <p
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 3000)"
                class="acct-saved acct-spacer"
                role="status"
            ><x-icon name="check-circle" class="w-4 h-4" /> {{ __('Email updated.') }}</p>
        @endif
    </div>
</form>
