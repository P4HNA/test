<div class="acct-card-head">
    <span class="acct-card-icon" aria-hidden="true"><x-icon name="alert" class="w-[18px] h-[18px]" /></span>
    <div>
        <h2>{{ __('Delete account') }}</h2>
        <p>{{ __('This cannot be undone. Take a moment before you use it.') }}</p>
    </div>
</div>

<p class="acct-hint" style="margin-top:0">
    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
</p>

{{-- Payments survive the account on purpose, so say so plainly here. --}}
<p class="acct-hint">
    {{ __('Records of payments you have made are kept for accounting and tax purposes, including the name and email address used at the time of payment.') }}
</p>

<div class="acct-actions">
    <button
        type="button"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="acct-btn acct-btn-danger"
    >
        <x-icon name="trash" class="w-4 h-4" /> {{ __('Delete account') }}
    </button>

    <a href="{{ route('learning.index') }}" class="acct-btn acct-btn-ghost">{{ __('Never mind, take me back') }}</a>
</div>

<x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
    {{-- Styled from the theme tokens, not Tailwind's greys: this dialog has to
         read the same in dark mode as the page behind it. --}}
    <form method="post" action="{{ route('profile.destroy') }}" class="p-6"
        style="background:var(--paper); color:var(--ink)">
        @csrf
        @method('delete')

        <h2 class="font-display text-lg font-bold">
            {{ __('Are you sure you want to delete your account?') }}
        </h2>

        <p class="acct-hint" style="margin-top:8px">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
        </p>

        <div class="acct-field" style="margin-top:20px; margin-bottom:0" x-data="{ show: false }">
            <label for="password" class="acct-label">{{ __('Password') }}</label>
            <div class="acct-peek">
                <input
                    id="password"
                    name="password"
                    x-bind:type="show ? 'text' : 'password'"
                    type="password"
                    class="pd-input"
                    autocomplete="current-password"
                    placeholder="{{ __('Password') }}"
                    @if ($errors->userDeletion->isNotEmpty()) aria-invalid="true" @endif
                />
                <button type="button" x-on:click="show = !show"
                    x-bind:aria-label="show ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
                    aria-label="{{ __('Show password') }}">
                    <x-icon name="eye" class="w-[18px] h-[18px]" x-show="!show" />
                    <x-icon name="eye-off" class="w-[18px] h-[18px]" x-show="show" x-cloak />
                </button>
            </div>
            <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2 font-medium" style="color:var(--error)" />
        </div>

        <div class="acct-actions" style="justify-content:flex-end">
            <button type="button" class="acct-btn acct-btn-quiet" x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </button>

            <button type="submit" class="acct-btn acct-btn-danger">
                <x-icon name="trash" class="w-4 h-4" /> {{ __('Delete account') }}
            </button>
        </div>
    </form>
</x-modal>
