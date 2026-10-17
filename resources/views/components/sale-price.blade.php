{{--
    A course price as a student would pay it today.

    On sale: the regular price struck through, then the price charged at
    checkout. Screen readers hear "Regular price $35, sale price $28" rather
    than two bare numbers. Otherwise just the price ("$35", "Free").

    <x-sale-price :course="$course" />
    <x-sale-price :course="$course" class="..." />   (classes go on the wrapper)
--}}
@props(['course'])

@if ($course->isOnSale())
    <span {{ $attributes }}>
        <span class="sr-only">{{ __('Regular price') }}</span>
        <s style="font-weight:500; text-decoration-thickness:1.5px">{{ $course->priceLabel() }}</s>
        <span class="sr-only">{{ __('Sale price') }}</span>
        <span>{{ $course->salePriceLabel() }}</span>
    </span>
@else
    <span {{ $attributes }}>{{ $course->priceLabel() }}</span>
@endif
