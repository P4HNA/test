<div class="acct-card-head">
    <span class="acct-card-icon" aria-hidden="true"><x-icon name="image" class="w-[18px] h-[18px]" /></span>
    <div>
        <h2>{{ __('About you') }}</h2>
        <p>{{ __('The photo and details that appear on your profile. All optional.') }}</p>
    </div>
</div>

@php
    // After a failed save, land on the first field that needs fixing.
    $focusField = collect(['avatar', 'headline', 'bio', 'location', 'website'])
        ->first(fn ($field) => $errors->has($field));
@endphp

<form
    method="post"
    action="{{ route('profile.public.update') }}"
    enctype="multipart/form-data"
    x-data="{
        busy: false,
        /* Live preview of the chosen file, so nobody has to save to find out
           the photo was the wrong one. Falls back to the stored picture. */
        preview: @js($user->avatarUrl()),
        removed: false,
        pick(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.removed = false;
            const reader = new FileReader();
            reader.onload = e => this.preview = e.target.result;
            reader.readAsDataURL(file);
        },
        drop() {
            this.removed = true;
            this.preview = null;
            this.$refs.avatar.value = '';
        },
    }"
    x-on:submit="if (busy) { $event.preventDefault() } else { busy = true }"
    x-on:pageshow.window="busy = false"
>
    @csrf
    @method('patch')

    {{-- ---------- Photo ---------- --}}
    <div class="acct-field">
        <span class="acct-label">{{ __('Profile photo') }}</span>

        <div class="flex flex-wrap items-center gap-4">
            {{--
                One circle either way: the initials are always underneath, and
                the chosen or stored picture is laid over them inside the clip.
                A file that fails to load leaves the initials showing rather
                than the browser's square broken-image box.
            --}}
            <span class="pd-avatar" :class="preview ? 'pd-avatar-has-photo' : ''" aria-hidden="true"
                style="width:84px;height:84px;font-size:32px;--hue:217">
                <span class="pd-avatar-initials">{{ $user->initials() }}</span>
                <template x-if="preview">
                    <img class="pd-avatar-photo" :src="preview" alt="">
                </template>
            </span>

            <div class="flex flex-wrap items-center gap-2">
                <label class="acct-btn acct-btn-quiet" style="cursor:pointer">
                    <x-icon name="upload" class="w-4 h-4" />
                    <span x-text="preview ? '{{ __('Change photo') }}' : '{{ __('Choose a photo') }}'">{{ __('Choose a photo') }}</span>
                    <input
                        id="avatar"
                        name="avatar"
                        type="file"
                        class="sr-only"
                        accept="image/jpeg,image/png,image/webp"
                        x-ref="avatar"
                        x-on:change="pick($event)"
                        @if ($focusField === 'avatar') autofocus @endif
                        @error('avatar') aria-invalid="true" aria-describedby="avatar-error avatar-hint" @else aria-describedby="avatar-hint" @enderror
                    />
                </label>

                <button type="button" x-show="preview" x-cloak x-on:click="drop()" class="acct-btn acct-btn-ghost">
                    <x-icon name="trash" class="w-4 h-4" /> {{ __('Remove') }}
                </button>
            </div>
        </div>

        {{-- Set only when the Remove button was used; a plain save leaves the photo alone. --}}
        <input type="hidden" name="remove_avatar" :value="removed ? 1 : 0" value="0">

        <p id="avatar-hint" class="acct-hint">
            {{ __('JPG, PNG or WebP · at least 100 × 100 px · up to 3 MB.') }}
        </p>
        <x-input-error id="avatar-error" class="mt-2 font-medium" style="color:var(--error)" :messages="$errors->get('avatar')" />
    </div>

    {{-- ---------- Headline ---------- --}}
    <div class="acct-field">
        <label for="headline" class="acct-label">{{ __('Headline') }}</label>
        <input
            id="headline"
            name="headline"
            type="text"
            maxlength="120"
            class="pd-input"
            value="{{ old('headline', $user->headline) }}"
            placeholder="{{ __('e.g. Motion designer learning After Effects') }}"
            @if ($focusField === 'headline') autofocus @endif
            @error('headline') aria-invalid="true" aria-describedby="headline-error" @enderror
        />
        <x-input-error id="headline-error" class="mt-2 font-medium" style="color:var(--error)" :messages="$errors->get('headline')" />
    </div>

    {{-- ---------- Bio ---------- --}}
    <div class="acct-field" x-data="{ text: @js(old('bio', $user->bio) ?? '') }">
        <div class="flex items-baseline justify-between">
            <label for="bio" class="acct-label">{{ __('Short bio') }}</label>
            <span class="text-xs tabular-nums" style="color:var(--graphite)" aria-hidden="true"
                x-text="`${text.length} / 600`">{{ mb_strlen(old('bio', $user->bio) ?? '') }} / 600</span>
        </div>
        <textarea
            id="bio"
            name="bio"
            rows="4"
            maxlength="600"
            class="pd-input"
            style="resize:vertical"
            x-model="text"
            placeholder="{{ __('What you are working on, and what you want to get better at.') }}"
            @if ($focusField === 'bio') autofocus @endif
            @error('bio') aria-invalid="true" aria-describedby="bio-error" @enderror
        >{{ old('bio', $user->bio) }}</textarea>
        <x-input-error id="bio-error" class="mt-2 font-medium" style="color:var(--error)" :messages="$errors->get('bio')" />
    </div>

    {{-- ---------- Location ---------- --}}
    <div class="acct-field">
        <label for="location" class="acct-label">{{ __('Location') }}</label>
        <input
            id="location"
            name="location"
            type="text"
            maxlength="120"
            class="pd-input"
            value="{{ old('location', $user->location) }}"
            placeholder="{{ __('e.g. Phnom Penh, Cambodia') }}"
            autocomplete="address-level2"
            @if ($focusField === 'location') autofocus @endif
            @error('location') aria-invalid="true" aria-describedby="location-error" @enderror
        />
        <x-input-error id="location-error" class="mt-2 font-medium" style="color:var(--error)" :messages="$errors->get('location')" />
    </div>

    {{-- ---------- Website ---------- --}}
    <div class="acct-field">
        <label for="website" class="acct-label">{{ __('Website or portfolio') }}</label>
        <input
            id="website"
            name="website"
            type="text"
            inputmode="url"
            class="pd-input"
            value="{{ old('website', $user->website) }}"
            placeholder="behance.net/yourname"
            autocomplete="url"
            @if ($focusField === 'website') autofocus @endif
            @error('website') aria-invalid="true" aria-describedby="website-error website-hint" @else aria-describedby="website-hint" @enderror
        />
        <p id="website-hint" class="acct-hint">{{ __('https:// is added for you if you leave it out.') }}</p>
        <x-input-error id="website-error" class="mt-2 font-medium" style="color:var(--error)" :messages="$errors->get('website')" />
    </div>

    <div class="acct-actions">
        <button type="submit" class="acct-btn acct-btn-primary pd-btn-wait" x-bind:disabled="busy" x-bind:aria-busy="busy.toString()">
            <span x-text="busy ? '{{ __('Saving…') }}' : '{{ __('Save changes') }}'">{{ __('Save changes') }}</span>
        </button>

        <button type="reset" class="acct-btn acct-btn-ghost"
            x-on:click="removed = false; preview = @js($user->avatarUrl())">{{ __('Cancel') }}</button>

        <a href="{{ route('learning.profile') }}" class="acct-btn acct-btn-quiet acct-spacer">
            {{ __('View my profile') }} <x-icon name="arrow-right" class="w-4 h-4" />
        </a>

        @if (session('status') === 'public-profile-updated')
            <p
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2600)"
                class="acct-saved"
                role="status"
            ><x-icon name="check-circle" class="w-4 h-4" /> {{ __('Saved.') }}</p>
        @endif
    </div>
</form>
