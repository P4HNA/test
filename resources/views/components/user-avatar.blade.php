{{--
    The student's picture, or their initials on the site's blue when there
    isn't one. Looks come from .pd-avatar* in resources/css/app.css; only the
    size varies per use.

        <x-user-avatar :user="$user" :size="88" />

    The initials are always rendered, with the photo laid over them inside the
    circle. If the photo fails to load -- a file that has since been deleted,
    or a stored address that doesn't match the host the site was opened on --
    it takes itself out of the page and the initials circle is what shows,
    instead of the browser's square broken-image box.
--}}
@props(['user', 'size' => 44])

@php
    $url = $user->avatarUrl();
    // Initials sit at roughly 38% of the circle; Khmer glyphs need the extra room.
    $font = max(11, (int) round($size * 0.38));
@endphp

<span
    aria-hidden="true"
    {{ $attributes->merge([
        'class' => 'pd-avatar'.($url ? ' pd-avatar-has-photo' : ''),
        'style' => "width:{$size}px;height:{$size}px;font-size:{$font}px;--hue:217;",
    ]) }}
>
    <span class="pd-avatar-initials">{{ $user->initials() }}</span>

    @if ($url)
        <img
            class="pd-avatar-photo"
            src="{{ $url }}"
            alt=""
            decoding="async"
            width="{{ $size }}"
            height="{{ $size }}"
            onerror="this.closest('.pd-avatar').classList.remove('pd-avatar-has-photo'); this.remove();"
        >
    @endif
</span>
