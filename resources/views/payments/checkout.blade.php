@php
    /*
    | The QR, the amount and the expiry all come from the payment row, which was
    | created from courses.price on the server. Nothing here is taken from the
    | request, so there is no field a customer could rewrite to change what they
    | owe.
    |
    | The code is drawn from the payload ourselves rather than using PayWay's own
    | qr_image: that one is a whole pre-branded KHQR card (tall, with its own
    | header and logo) and does not fit this page. The payload is identical, so
    | any bank app reads it the same. PayWay's version is still stored on the
    | payment row for the record.
    */
    $qrSvg = \App\Support\QrImage::svg((string) $payment->qr, 260);

    $expiresAt = $payment->expires_at ?? now()->addMinutes(10);

    // Total life of this QR, for the countdown ring's arc.
    $qrSeconds = $payment->created_at
        ? max(60, (int) abs($payment->created_at->diffInSeconds($expiresAt)))
        : 600;
@endphp

<x-classroom-layout :title="__('Pay :course', ['course' => $course->localized('title')]) . ' — Piseth.Design'">

    <x-slot:head>
        <style>
            /* =========================================================
               PAYMENT — built from the same paper/proof pieces as the
               rest of the site (.pd-sheet, .crop-mark, the "01/02" field
               numbering from the login form) rather than a one-off
               "checkout widget" look.
            ========================================================= */

            :root {
                /* The payment page's own tokens, derived from the shared paper
                   palette in partials/_classroom-theme.blade.php. Defined here
                   because nothing outside this page uses them. */
                --pay-accent: var(--action-ink);
                --pay-card: #FFFFFF;
                --pay-border: var(--mist);
                --pay-green: #16A34A;
                --pay-danger: #DC2626;
            }

            /* The site's dark mode is a class on <html> (see
               partials/_classroom-theme.blade.php), not a media query. */
            html.dark {
                --pay-card: #151516;
                --pay-green: #4ADE80;
                --pay-danger: #F87171;
            }

            .pay-card {
                position: relative;
                border-radius: 3px;
            }

            .pay-row {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 20px;
                padding: 15px 0;
                border-top: 1px solid var(--mist);
            }

            .pay-row:first-child {
                border-top: 0;
                padding-top: 0;
            }

            .pay-num {
                font-size: 11px;
                font-weight: 700;
                color: var(--redline);
            }

            .pay-label {
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .1em;
                color: var(--graphite);
            }

            .pay-value {
                font-size: 14px;
                font-weight: 700;
                text-align: right;
            }

            .pay-amount {
                font-size: 28px;
                letter-spacing: -.03em;
            }

            .copy-btn {
                border: 0;
                background: none;
                padding: 0;
                font-size: 10px;
                font-weight: 800;
                letter-spacing: .07em;
                text-transform: uppercase;
                color: var(--graphite);
                cursor: pointer;
                transition: color .18s ease;
            }

            .copy-btn:hover {
                color: var(--redline);
            }

            /* Section eyebrow — the small red-dotted label used site-wide. */
            .eyebrow {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                font-size: 10px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: .15em;
                color: var(--graphite);
            }

            .eyebrow-dot {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: var(--redline);
            }

            .step-row {
                display: flex;
                gap: 14px;
                padding: 14px 0;
                border-top: 1px solid var(--mist);
            }

            .step-num {
                flex: none;
                font-size: 11px;
                font-weight: 700;
                color: var(--redline);
                padding-top: 1px;
            }

            .step-text {
                font-size: 13px;
                line-height: 1.6;
                color: var(--graphite);
            }

            .step-text strong {
                color: var(--ink);
                font-weight: 700;
            }

            /* =========================================================
               QR CARD
            ========================================================= */

            .qr-card {
                position: relative;
                border: 1px solid var(--pay-border);
                border-radius: 16px;
                background: var(--pay-card);
            }

            .qr-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 16px;
            }

            .qr-frame {
                position: relative;
                width: 100%;
                /* 240px QR + 14px padding on each side. Below that available
                   width, "width: 100%" takes over and the frame (and the QR
                   inside it) shrinks to fit instead of forcing a fixed size
                   past the viewport. */
                max-width: 268px;
                box-sizing: border-box;
                margin: 22px auto 16px;
                padding: 14px;
                /* Always white, in both themes: a KHQR has to stay high-contrast
                   for the camera, and PayWay's PNG has a transparent ground. */
                background: #fff;
                border: 1px solid var(--mist);
                border-radius: 3px;
            }

            /* The QR is a square inline SVG, so it can simply fill the frame. */
            #qrcode,
            #qrcode svg {
                display: block;
                width: 100%;
                height: auto;
                aspect-ratio: 1 / 1;
                border-radius: 2px;
            }

            /* Dimmed once the QR can no longer be paid, so nobody scans a dead code. */
            .qr-frame.is-dead #qrcode {
                opacity: .18;
                filter: grayscale(1);
            }

            .qr-dead-label {
                position: absolute;
                inset: 0;
                display: grid;
                place-items: center;
                text-align: center;
                padding: 18px;
                font-size: 11px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: .12em;
                color: var(--pay-danger);
            }

            .scan-hint {
                text-align: center;
                font-size: 12px;
                line-height: 1.6;
                color: var(--graphite);
            }

            .scan-hint strong {
                color: var(--ink);
            }

            /* Countdown clock in the card header */
            .timer {
                display: flex;
                align-items: center;
                gap: 8px;
                flex: none;
            }

            .timer-icon {
                display: grid;
                place-items: center;
                width: 26px;
                height: 26px;
                border-radius: 8px;
                background: color-mix(in srgb, var(--pay-accent) 9%, transparent);
                color: var(--pay-accent);
            }

            .countdown-num {
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-variant-numeric: tabular-nums;
                font-weight: 800;
                font-size: 12px;
                transition: color .3s ease;
            }

            .timer-danger {
                color: var(--pay-danger);
            }

            /* Countdown ring */
            .ring {
                position: relative;
                width: 44px;
                height: 44px;
            }

            .ring svg {
                width: 100%;
                height: 100%;
                transform: rotate(-90deg);
            }

            .ring-track {
                stroke: var(--mist);
            }

            .ring-bar {
                stroke: var(--pay-accent);
                stroke-linecap: round;
                transition: stroke-dashoffset 1s linear, stroke .3s ease;
            }

            .ring-bar.is-danger {
                stroke: var(--pay-danger);
            }

            .ring-bar.is-done {
                stroke: var(--pay-green);
            }

            /* Amount block under the QR */
            .qr-amount {
                margin-top: 18px;
                padding-top: 16px;
                border-top: 1px solid var(--pay-border);
                text-align: center;
            }

            .qr-amount-label {
                font-size: 10px;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: .14em;
                color: var(--graphite);
            }

            .qr-amount-value {
                margin-top: 4px;
                font-size: 30px;
                font-weight: 800;
                letter-spacing: -.03em;
                color: var(--ink);
            }

            .invoice-small {
                margin-top: 6px;
                font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
                font-size: 10px;
                color: var(--graphite);
            }

            .expires-text {
                margin-top: 2px;
                font-size: 10px;
                color: var(--graphite);
                opacity: .7;
            }

            /* =========================================================
               ABA MOBILE DEEPLINK
               Only worth offering on a phone: on a desktop the link opens
               nothing, and telling someone to "open the app" on the machine
               showing the QR is just confusing.
            ========================================================= */

            .aba-open {
                display: none;
            }

            @media (hover: none) and (pointer: coarse) {
                .aba-open {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    width: 100%;
                    margin-top: 14px;
                    padding: 13px 16px;
                    border: 0;
                    border-radius: 11px;
                    background: var(--ink);
                    color: var(--paper);
                    font-size: 13px;
                    font-weight: 800;
                    letter-spacing: .02em;
                    text-decoration: none;
                }

                .aba-open:active {
                    transform: translateY(1px);
                }

                /* On a phone the camera cannot scan the screen it is showing. */
                .scan-hint-desktop {
                    display: none;
                }
            }

            /* =========================================================
               PAYMENT STATUS
            ========================================================= */

            .status-card {
                display: flex;
                align-items: center;
                gap: 13px;
                padding: 15px 17px;
                border-radius: 14px;
                border: 1px solid var(--pay-border);
                background: var(--pay-card);
            }

            .status-icon {
                position: relative;
                width: 34px;
                height: 34px;
                min-width: 34px;
                display: grid;
                place-items: center;
                border-radius: 10px;
                background: color-mix(in srgb, var(--pay-accent) 9%, transparent);
                color: var(--pay-accent);
            }

            .status-icon::after {
                content: "";
                position: absolute;
                inset: -4px;
                border-radius: 13px;
                border: 1px solid currentColor;
                opacity: .12;
                animation: statusPulse 1.8s ease-out infinite;
            }

            .status-icon.success {
                background: rgba(22, 163, 74, .1);
                color: var(--pay-green);
            }

            .status-icon.error {
                background: rgba(220, 38, 38, .1);
                color: var(--pay-danger);
            }

            .status-icon.success::after,
            .status-icon.error::after {
                animation: none;
                opacity: 0;
            }

            .pay-action {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 8px 12px;
                border: 1px solid var(--pay-border);
                border-radius: 9px;
                background: var(--pay-card);
                color: var(--ink);
                font-size: 12px;
                font-weight: 700;
                text-decoration: none;
                cursor: pointer;
                transition: transform .15s ease, border-color .15s ease;
            }

            .pay-action:hover {
                border-color: var(--pay-accent);
                transform: translateY(-1px);
            }

            .pay-action:disabled {
                opacity: .5;
                cursor: wait;
                transform: none;
            }

            .pay-action-primary {
                background: var(--action);
                border-color: var(--action);
                color: #fff;
            }

            @keyframes statusPulse {
                0% {
                    transform: scale(.85);
                    opacity: .5;
                }

                100% {
                    transform: scale(1.25);
                    opacity: 0;
                }
            }

            /* Success animation */
            .check-circle {
                stroke-dasharray: 76;
                stroke-dashoffset: 76;
                animation: drawCircle .5s ease forwards;
            }

            .check-mark {
                stroke-dasharray: 24;
                stroke-dashoffset: 24;
                animation: drawCheck .35s ease .45s forwards;
            }

            @keyframes drawCircle {
                to { stroke-dashoffset: 0; }
            }

            @keyframes drawCheck {
                to { stroke-dashoffset: 0; }
            }

            @media (max-width: 640px) {
                .pay-amount { font-size: 24px; }
            }

            @media (prefers-reduced-motion: reduce) {
                .status-icon::after { animation: none; opacity: 0; }
                .check-circle,
                .check-mark { animation: none; stroke-dashoffset: 0; }
                .ring-bar { transition: none; }
            }
        </style>
    </x-slot:head>

    {{-- =============================================================
         The whole page is one Alpine component: the countdown, the
         3-second poll and every visible state come out of paywayCheckout()
         at the bottom of this file.
    ============================================================= --}}
    <div
        class="payment-page max-w-6xl mx-auto px-6 py-10 sm:py-14"
        x-data="paywayCheckout({
            checkUrl: @js(route('payments.check', $payment)),
            retryUrl: @js(route('payments.checkout', $course)),
            expiresAt: @js($expiresAt->toIso8601String()),
            totalSeconds: {{ (int) $qrSeconds }},
            invoiceNo: @js($payment->invoice_no),
            alreadyPaid: {{ $payment->isPaid() ? 'true' : 'false' }},
        })"
        x-init="start()"
    >

        <a
            href="{{ route('courses.show', $course) }}"
            class="inline-flex items-center gap-2 text-sm font-medium mb-10 transition-opacity hover:opacity-60"
            style="color:var(--graphite)"
        >
            <span style="font-size:18px;">←</span>
            {{ __('Back to course') }}
        </a>

        <main class="grid lg:grid-cols-[1fr_420px] gap-x-12 xl:gap-x-20 gap-y-0 items-start">

            {{-- =====================================================
                 Course/order context. Mobile order: this, then the
                 order summary, then amount+QR (the primary action),
                 then secondary how-to-pay/status. Desktop: unchanged
                 two-column layout via grid placement below.
            ====================================================== --}}
            <div class="lg:col-start-1 lg:row-start-1">

                <div class="eyebrow mb-4">
                    <span class="eyebrow-dot"></span>
                    {{ __('ABA PayWay secure payment') }}
                </div>

                <h1
                    class="font-display font-extrabold text-5xl sm:text-6xl leading-[.98] tracking-[-.045em] mb-5"
                    style="color:var(--ink)"
                >
                    {!! __('Complete your :payment', ['payment' => '<br><span style="color:var(--redline)">'.e(__('payment.')).'</span>']) !!}
                </h1>

                <p
                    class="max-w-xl text-[15px] leading-7"
                    style="color:var(--graphite)"
                >
                    {{ __('Scan the KHQR with ABA Mobile, ACLEDA, Wing, Bakong, or any Cambodian banking app that supports KHQR. Once your payment is confirmed, we\'ll automatically take you to your course.') }}
                </p>
            </div>

            {{-- Order summary --}}
            <div class="lg:col-start-1 lg:row-start-2">
                <section class="pay-card pd-sheet p-6 sm:p-7 mt-9">
                    <span class="crop-mark tl"></span>
                    <span class="crop-mark tr"></span>
                    <span class="crop-mark bl"></span>
                    <span class="crop-mark br"></span>

                    <p class="text-[10px] font-extrabold uppercase tracking-[.14em] mb-1" style="color:var(--graphite)">
                        {{ __('Order summary') }}
                    </p>
                    <p class="text-xs opacity-50 mb-1">{{ __('Secure checkout') }}</p>

                    <div class="pay-row">
                        <div class="flex items-baseline gap-2">
                            <span class="pay-num">01</span>
                            <span class="pay-label">{{ __('Course') }}</span>
                        </div>
                        <strong class="pay-value max-w-[60%]">{{ $course->localized('title') }}</strong>
                    </div>

                    <div class="pay-row">
                        <div class="flex items-baseline gap-2">
                            <span class="pay-num">02</span>
                            <span class="pay-label">{{ __('Invoice') }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <strong class="font-mono text-[11px]">{{ $payment->invoice_no }}</strong>
                            <button
                                type="button"
                                class="copy-btn"
                                @click="copyInvoice()"
                                x-text="copied ? @js(__('Copied')) : @js(__('Copy'))"
                                :style="copied ? 'color:var(--pay-green)' : ''"
                            >{{ __('Copy') }}</button>
                        </div>
                    </div>

                    <div class="pay-row">
                        <div class="flex items-baseline gap-2">
                            <span class="pay-num">03</span>
                            <span class="pay-label">{{ __('Total') }}</span>
                        </div>
                        <strong class="pay-amount font-display">
                            {{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}
                        </strong>
                    </div>
                </section>
            </div>

            {{-- =====================================================
                 Amount to pay + QR — the primary action. Comes right
                 after the order summary in source order (so mobile
                 reaches it immediately, without touching tab/reading
                 order); placed as the sticky right column at lg: purely
                 via grid placement, matching the original desktop look.
            ====================================================== --}}
            <aside class="lg:col-start-2 lg:row-start-1 lg:row-span-3 mt-9 lg:mt-0 lg:sticky lg:top-8">

                <div class="qr-card p-6 sm:p-7">

                    <div class="qr-header">

                        <div>
                            <p
                                class="text-[10px] font-extrabold uppercase tracking-[.15em]"
                                style="color:var(--graphite)"
                            >
                                {{ __('Scan to pay') }}
                            </p>

                            <p class="text-xs mt-1 opacity-50">
                                {{ __('KHQR payment') }}
                            </p>
                        </div>

                        <div class="timer">

                            <div class="timer-icon">
                                <svg
                                    width="15"
                                    height="15"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M12 7v5l3 2"></path>
                                </svg>
                            </div>

                            <div>
                                <div
                                    class="countdown-num"
                                    :class="{ 'timer-danger': isUrgent }"
                                    x-text="clock"
                                >10:00</div>

                                <div class="text-[8px] uppercase tracking-widest opacity-40">
                                    {{ __('remaining') }}
                                </div>
                            </div>

                        </div>

                    </div>


                    {{-- QR. Drawn server-side as an inline SVG, so this page
                         loads no QR library and no third-party script at all. --}}
                    <div class="qr-frame" :class="{ 'is-dead': isDead }">
                        @if ($qrSvg)
                            <div
                                id="qrcode"
                                role="img"
                                aria-label="{{ __('KHQR code for :amount', ['amount' => $payment->currency.' '.number_format((float) $payment->amount, 2)]) }}"
                            >{!! $qrSvg !!}</div>
                            <div class="qr-dead-label" x-show="isDead" x-cloak>
                                {{ __('Expired') }}
                            </div>
                        @else
                            {{-- The payload could not be encoded. The deeplink
                                 below still works, and the payment itself is
                                 fine. --}}
                            <p class="text-center text-xs py-16" style="color:var(--graphite)">
                                {{ __('The QR code could not be drawn. Use the button below, or tap "Get a new QR".') }}
                            </p>
                        @endif
                    </div>

                    <p class="scan-hint scan-hint-desktop">
                        {!! __('Scan with :apps (ABA, ACLEDA, Wing, Bakong…)', ['apps' => '<strong>'.e(__('any Cambodian bank app')).'</strong>']) !!}
                    </p>

                    @if ($payment->deeplink)
                        {{-- Phones only (see .aba-open). Hidden once the QR is
                             dead, so it cannot open a payment that would fail. --}}
                        <a
                            href="{{ $payment->deeplink }}"
                            class="aba-open"
                            x-show="!isDead && !isPaid"
                            x-cloak
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                                <rect x="6" y="2" width="12" height="20" rx="2.5"></rect>
                                <path d="M11 18.5h2"></path>
                            </svg>
                            {{ __('Open ABA Mobile') }}
                        </a>
                    @endif


                    {{-- Timer ring --}}
                    <div class="flex justify-center mt-5">

                        <div class="ring">

                            <svg viewBox="0 0 44 44" aria-hidden="true">

                                <circle
                                    class="ring-track"
                                    cx="22"
                                    cy="22"
                                    r="18"
                                    fill="none"
                                    stroke-width="3"
                                ></circle>

                                <circle
                                    class="ring-bar"
                                    :class="{ 'is-danger': isUrgent && !isPaid, 'is-done': isPaid }"
                                    cx="22"
                                    cy="22"
                                    r="18"
                                    fill="none"
                                    stroke-width="3"
                                    stroke-dasharray="113.1"
                                    :stroke-dashoffset="ringOffset"
                                ></circle>

                            </svg>

                        </div>

                    </div>


                    {{-- Amount --}}
                    <div class="qr-amount">

                        <p class="qr-amount-label">
                            {{ __('Amount to pay') }}
                        </p>

                        <p class="qr-amount-value font-display">
                            {{ $payment->currency }}
                            {{ number_format((float) $payment->amount, 2) }}
                        </p>

                        <p class="invoice-small">
                            {{ $payment->invoice_no }}
                        </p>

                        <p class="expires-text">
                            {{ __('Expires at :time', ['time' => $expiresAt->format('H:i:s')]) }}
                        </p>

                    </div>

                </div>

            </aside>


            {{-- =====================================================
                 Secondary — how to pay + live status
            ====================================================== --}}
            <div class="lg:col-start-1 lg:row-start-3">

                <section class="mt-10">
                    <div class="flex items-end justify-between mb-1">
                        <p class="text-[10px] font-extrabold uppercase tracking-[.14em]" style="color:var(--redline)">
                            {{ __('Simple checkout') }}
                        </p>
                        <span class="text-[10px] font-bold uppercase tracking-widest opacity-40">{{ __('3 steps') }}</span>
                    </div>
                    <h2 class="font-display font-bold text-xl mb-1">{{ __('How to pay') }}</h2>

                    <div class="step-row">
                        <span class="step-num">01</span>
                        <p class="step-text">{!! __('Open :aba or any supported Cambodian banking app.', ['aba' => '<strong>ABA Mobile</strong>']) !!}</p>
                    </div>
                    <div class="step-row">
                        <span class="step-num">02</span>
                        <p class="step-text">{!! __('Tap :scan and point your camera at the KHQR code.', ['scan' => '<strong>'.e(__('Scan')).'</strong>']) !!}</p>
                    </div>
                    <div class="step-row">
                        <span class="step-num">03</span>
                        <p class="step-text">{!! __('Confirm the :amount and complete the payment. We\'ll detect it automatically.', ['amount' => '<strong>'.e(__('amount')).'</strong>']) !!}</p>
                    </div>
                </section>

                {{-- Live status --}}
                <section class="status-card mt-6" aria-live="polite">

                    {{-- Paid: the drawn tick, replacing the spinner entirely. --}}
                    <template x-if="isPaid">
                        <div class="status-icon success" style="width:38px;height:38px;min-width:38px;">
                            <svg width="25" height="25" viewBox="0 0 34 34" fill="none" aria-hidden="true">
                                <circle class="check-circle" cx="17" cy="17" r="12" stroke="currentColor" stroke-width="2.5"/>
                                <path class="check-mark" d="M11 17.5l4 4 8-8.5" stroke="currentColor" stroke-width="2.5"
                                      stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                            </svg>
                        </div>
                    </template>

                    <template x-if="!isPaid">
                        <span class="status-icon" :class="{ 'error': isProblem }">
                            <span style="font-size:14px;" x-text="isProblem ? '!' : '↻'">↻</span>
                        </span>
                    </template>

                    <div class="min-w-0 flex-1">
                        <strong
                            class="block text-sm"
                            :style="isPaid ? 'color:var(--pay-green)' : ''"
                            x-text="title"
                        >{{ __('Waiting for payment…') }}</strong>

                        <p class="mt-1 text-xs leading-5 opacity-60" x-text="detail">
                            {{ __('After you pay, we confirm it automatically — usually within a few seconds.') }}
                        </p>

                        <div class="flex flex-wrap gap-2 mt-3" x-show="!isPaid" x-cloak>
                            <button
                                type="button"
                                class="pay-action"
                                x-show="!isDead"
                                @click="poll(true)"
                                :disabled="checking"
                            >
                                {{ __('I\'ve paid — check now') }}
                            </button>

                            <a
                                :href="retryUrl"
                                class="pay-action pay-action-primary"
                                x-show="isDead"
                                x-cloak
                            >
                                {{ __('Get a new QR') }}
                            </a>
                        </div>
                    </div>
                </section>

            </div>

        </main>

    </div>


    {{-- =============================================================
         The Alpine component.

         Everything the page shows about the payment comes from OUR OWN
         status endpoint, which reads our database. PayWay tells the
         server about a payment by calling /payway/callback, so this poll
         is cheap: no third party is contacted on a normal tick.
    ============================================================= --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('paywayCheckout', (config) => ({

                /* ---------- config ---------- */
                checkUrl: config.checkUrl,
                retryUrl: config.retryUrl,
                invoiceNo: config.invoiceNo,
                expiresAt: new Date(config.expiresAt).getTime(),
                totalSeconds: Math.max(60, config.totalSeconds),

                /* ---------- state ---------- */
                state: config.alreadyPaid ? 'paid' : 'pending',
                title: @js(__('Waiting for payment…')),
                detail: @js(__('After you pay, we confirm it automatically — usually within a few seconds.')),
                clock: '--:--',
                ringOffset: 0,
                remaining: 0,
                checking: false,
                copied: false,
                stopped: false,
                timers: [],

                /* Poll our own database every 3 seconds. */
                POLL_MS: 3000,
                /* Circumference of the r=18 ring. */
                CIRC: 2 * Math.PI * 18,

                get isPaid() {
                    return this.state === 'paid';
                },

                /* Nothing more will happen to this QR: it needs replacing. */
                get isDead() {
                    return this.state === 'expired';
                },

                /* Show the "!" icon rather than the spinner. */
                get isProblem() {
                    return ['expired', 'review', 'error', 'blocked'].includes(this.state);
                },

                get isUrgent() {
                    return !this.isPaid && this.remaining <= 60;
                },

                start() {
                    this.tick();
                    this.timers.push(setInterval(() => this.tick(), 1000));

                    if (this.isPaid) {
                        // Landed on an already-paid payment: confirm with the
                        // server (it also sets the flash message) and go.
                        this.poll();

                        return;
                    }

                    this.poll();
                    this.timers.push(setInterval(() => this.poll(), this.POLL_MS));

                    // Coming back from ABA Mobile on a phone: check at once
                    // rather than waiting out the interval.
                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'visible') {
                            this.poll();
                        }
                    });
                },

                stop() {
                    this.stopped = true;
                    this.timers.forEach(clearInterval);
                    this.timers = [];
                },

                /* ---------- countdown ---------- */

                tick() {
                    this.remaining = Math.max(0, (this.expiresAt - Date.now()) / 1000);

                    const fraction = this.isPaid
                        ? 1
                        : Math.max(0, Math.min(1, this.remaining / this.totalSeconds));

                    this.ringOffset = this.CIRC * (1 - fraction);
                    this.clock = this.isPaid ? '00:00' : this.formatClock(this.remaining);
                },

                formatClock(seconds) {
                    const s = Math.max(0, Math.floor(seconds));

                    return String(Math.floor(s / 60)).padStart(2, '0')
                        + ':' + String(s % 60).padStart(2, '0');
                },

                /* ---------- status poll ---------- */

                async poll(manual = false) {
                    if (this.checking || (this.stopped && !manual)) {
                        return;
                    }

                    this.checking = true;

                    try {
                        const response = await fetch(this.checkUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                // The check is a POST, so every poll carries the
                                // session's CSRF token. A session that outlives
                                // the page comes back 419, handled below.
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                            },
                            credentials: 'same-origin',
                            cache: 'no-store',
                        });

                        if (response.status === 401 || response.status === 419) {
                            this.fail('blocked',
                                @js(__('Please log in again')),
                                @js(__('Your session ended. Log in, then open the course to continue.')));

                            return;
                        }

                        if (response.status === 429) {
                            // Throttled. The next tick will carry on.
                            this.set('pending',
                                @js(__('Waiting for payment…')),
                                @js(__('Checking a little slower…')));

                            return;
                        }

                        // 403 means the server refused this check outright (not
                        // "not paid yet"). Polling on would show "Waiting for
                        // payment..." forever, so stop and say something the
                        // customer can act on.
                        if (response.status === 403) {
                            this.fail('blocked',
                                @js(__('We cannot check this payment')),
                                @js(__('Please contact us with your invoice number and we will confirm it for you.')));

                            return;
                        }

                        const data = await response.json().catch(() => null);

                        if (!data) {
                            this.set('error',
                                @js(__('Still checking…')),
                                @js(__('Connection problem. We will keep trying automatically.')));

                            return;
                        }

                        if (data.paid) {
                            this.succeed(data.redirect);

                            return;
                        }

                        if (data.state === 'expired') {
                            if (data.retry_url) {
                                this.retryUrl = data.retry_url;
                            }

                            this.fail('expired', @js(__('QR code expired')), data.message);

                            return;
                        }

                        if (data.state === 'review') {
                            this.fail('review', @js(__('Payment needs review')), data.message);

                            return;
                        }

                        if (data.state === 'error') {
                            this.set('error', @js(__('Checking payment…')), data.message);

                            return;
                        }

                        if (this.remaining <= 0) {
                            this.set('pending',
                                @js(__('Final check…')),
                                @js(__('The QR time is over. If you already paid, we are still confirming it.')));

                            return;
                        }

                        this.set('pending', @js(__('Waiting for payment…')), data.message);
                    } catch (error) {
                        this.set('error',
                            @js(__('Still checking…')),
                            @js(__('Connection problem. We will keep trying automatically.')));
                    } finally {
                        this.checking = false;
                    }
                },

                set(state, title, detail) {
                    this.state = state;
                    this.title = title;
                    this.detail = detail || '';
                },

                /* A final state: stop polling, the QR will not change again. */
                fail(state, title, detail) {
                    this.set(state, title, detail);
                    this.stop();
                },

                succeed(redirect) {
                    this.set('paid',
                        @js(__('Payment confirmed')),
                        @js(__('Your course is unlocked. Opening it now…')));
                    this.stop();
                    this.tick();

                    if (redirect) {
                        // Long enough for the tick to finish drawing.
                        setTimeout(() => { window.location.href = redirect; }, 1200);
                    }
                },

                /* ---------- copy invoice ---------- */

                async copyInvoice() {
                    try {
                        await navigator.clipboard.writeText(this.invoiceNo);
                        this.copied = true;
                        setTimeout(() => { this.copied = false; }, 1500);
                    } catch (e) {
                        // Clipboard unavailable (http, or permission refused).
                    }
                },
            }));
        });
    </script>

</x-classroom-layout>
