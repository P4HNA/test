{{--
    The blue corner ribbon on a course card that a live discount applies to.

    It comes from Course::activeDiscount(), the same rule the price and the
    checkout use, so it appears on exactly the courses the promotion targets
    (one course, a category, or all) and on no others. Announcements and free
    courses get none. Styles are .pd-ribbon in resources/css/app.css.

        <x-discount-ribbon :course="$course" />
--}}
@props(['course'])

@php($discount = $course->activeDiscount())

@if ($discount && $discount->isDiscount() && $course->isOnSale())
    <span class="pd-ribbon" aria-hidden="true"><span>{{ __(':percent% off', ['percent' => $discount->discount_percent]) }}</span></span>
@endif
