{{-- Product mark: the yellow flower. Same art the homepage nav uses, so every
     menu on the site carries one logo. Decorative — the anchors around it
     already say "Piseth.Design". --}}
@props(['size' => 28])
<img src="{{ asset('images/logo-mark-96.png') }}"
    width="{{ $size }}" height="{{ $size }}"
    alt="" aria-hidden="true" decoding="async"
    {{ $attributes->merge([
        'class' => 'pd-brand-mark',
        'style' => "width:{$size}px;height:{$size}px;object-fit:contain;flex:0 0 auto;",
    ]) }}>
