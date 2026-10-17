<div class="acct-card-head">
    <span class="acct-card-icon" aria-hidden="true"><x-icon name="lock" class="w-[18px] h-[18px]" /></span>
    <div>
        <h2>{{ __('Update password') }}</h2>
        <p>{{ __('Use a long, random password to keep your account secure.') }}</p>
    </div>
</div>

@php
    // Same field three times over; the only differences are the name, the
    // label and the autocomplete hint, so build them from one list.
    $fields = [
        ['id' => 'update_password_current_password', 'name' => 'current_password', 'label' => __('Current password'), 'autocomplete' => 'current-password'],
        ['id' => 'update_password_password', 'name' => 'password', 'label' => __('New password'), 'autocomplete' => 'new-password'],
        ['id' => 'update_password_password_confirmation', 'name' => 'password_confirmation', 'label' => __('Confirm password'), 'autocomplete' => 'new-password'],
    ];
@endphp

<form method="post" action="{{ route('password.update') }}">
    @csrf
    @method('put')

    @foreach ($fields as $field)
        <div class="acct-field" x-data="{ show: false }">
            <label for="{{ $field['id'] }}" class="acct-label">{{ $field['label'] }}</label>

            {{-- The reveal button sits inside the box: retyping a long password
                 blind is the single most annoying part of this form. --}}
            <div class="acct-peek">
                <input
                    id="{{ $field['id'] }}"
                    name="{{ $field['name'] }}"
                    x-bind:type="show ? 'text' : 'password'"
                    type="password"
                    class="pd-input"
                    autocomplete="{{ $field['autocomplete'] }}"
                    @if ($errors->updatePassword->has($field['name'])) aria-invalid="true" @endif
                />
                <button type="button" x-on:click="show = !show"
                    x-bind:aria-label="show ? '{{ __('Hide password') }}' : '{{ __('Show password') }}'"
                    x-bind:aria-pressed="show.toString()"
                    aria-label="{{ __('Show password') }}">
                    <x-icon name="eye" class="w-[18px] h-[18px]" x-show="!show" />
                    <x-icon name="eye-off" class="w-[18px] h-[18px]" x-show="show" x-cloak />
                </button>
            </div>

            <x-input-error :messages="$errors->updatePassword->get($field['name'])" class="mt-2 font-medium" style="color:var(--error)" />
        </div>
    @endforeach

    <div class="acct-actions">
        <button type="submit" class="acct-btn acct-btn-primary">{{ __('Update password') }}</button>
        <button type="reset" class="acct-btn acct-btn-ghost">{{ __('Cancel') }}</button>

        @if (session('status') === 'password-updated')
            <p
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2600)"
                class="acct-saved acct-spacer"
                role="status"
            ><x-icon name="check-circle" class="w-4 h-4" /> {{ __('Password updated.') }}</p>
        @endif
    </div>
</form>
