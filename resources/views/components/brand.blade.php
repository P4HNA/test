{{--
    The Piseth.Design lockup: the flower mark and the two-line wordmark
    ("PISETH." / "DESIGN"), linking home. One component for every page, so the
    logo never drifts again. Styles live in resources/css/app.css (.pd-brand),
    the pointer-following shine in resources/js/app.js.

    <x-brand />                                   homepage / classroom menu (40px mark)
    <x-brand :size="30" :href="route('admin.dashboard')" />
    <x-brand :word="false" />                     mark only
--}}
@props(['href' => null, 'size' => 40, 'word' => true, 'label' => null])

<a href="{{ $href ?? url('/') }}" {{ $attributes->merge(['class' => 'pd-brand', 'style' => '--pd-mark: '.(int) $size.'px']) }}
    aria-label="{{ $label ?? 'Piseth.Design' }}" data-pd-brand>
    <x-brand-mark :size="$size" class="pd-brand-mark" />
    @if ($word)
        <span class="pd-brand-word" aria-hidden="true">
            <span class="pd-brand-lines"><span>PISETH<i>.</i></span><span>DESIGN</span></span>
            <span class="pd-brand-lines pd-brand-shine"><span>PISETH<i>.</i></span><span>DESIGN</span></span>
        </span>
    @endif
</a>
