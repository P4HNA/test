{{--
    Telegram QR popup. Include once per page; any trigger opens it with
        x-on:click="$dispatch('open-telegram-qr')"
    Built on a native <dialog> opened with showModal(), so the browser
    supplies the modal behaviour: background made inert, focus kept inside,
    Escape to close, and a ::backdrop. Alpine only handles the page-level
    extras: scroll lock, backdrop click, and returning focus to the trigger.
--}}
<div
    x-data="{
        trigger: null,
        open() {
            this.trigger = document.activeElement;
            document.documentElement.style.overflow = 'hidden';
            this.$refs.dialog.showModal();
        },
        closed() {
            document.documentElement.style.overflow = '';
            this.trigger?.focus();
            this.trigger = null;
        },
    }"
    x-on:open-telegram-qr.window="open()"
>
    <dialog
        x-ref="dialog"
        class="pd-qr-dialog"
        aria-labelledby="telegram-qr-title"
        aria-describedby="telegram-qr-desc"
        x-on:close="closed()"
        x-on:click="if ($event.target === $el) $el.close()"
    >
        <div class="pd-qr-panel">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs uppercase tracking-wide mb-1" style="color:var(--redline)">{{ __('Contact') }}</p>
                    <h2 id="telegram-qr-title" class="font-display font-bold text-xl leading-tight">{{ __('Join us on Telegram') }}</h2>
                </div>
                <button
                    type="button"
                    class="pd-qr-close"
                    autofocus
                    aria-label="{{ __('Close Telegram QR code') }}"
                    x-on:click="$refs.dialog.close()"
                >
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <p id="telegram-qr-desc" class="mt-2 text-sm leading-relaxed" style="color:var(--graphite)">
                {{ __('Scan this QR code with your phone to contact us on Telegram.') }}
                <span class="sr-only">{{ __('Press Escape or use the Close button to return to the page.') }}</span>
            </p>

            <div class="pd-qr-plate mt-5">
                <img
                    src="{{ asset('images/telegram-qr.jpg') }}"
                    width="1098"
                    height="1280"
                    loading="lazy"
                    decoding="async"
                    alt="Telegram QR code for @@PISETH_YOEURN. Scan it with the Telegram app to start a chat with Piseth.Design."
                >
            </div>

            <p class="mt-4 text-center text-sm font-medium">@@PISETH_YOEURN</p>
        </div>
    </dialog>
</div>

@once
<style>
    .pd-qr-dialog {
        margin: auto;
        padding: 0;
        border: 1px solid var(--mist);
        border-radius: 2px;
        background: var(--paper);
        color: var(--ink);
        width: min(400px, calc(100vw - 32px));
        max-height: calc(100dvh - 32px);
        overflow-y: auto;
        box-shadow: 0 24px 60px -20px rgba(0, 0, 0, .45);
    }

    .pd-qr-dialog::backdrop {
        background: rgba(21, 19, 15, .6);
    }

    .pd-qr-dialog[open] {
        animation: pd-qr-in .18s ease-out;
    }

    .pd-qr-dialog[open]::backdrop {
        animation: pd-qr-fade .18s ease-out;
    }

    .pd-qr-panel {
        padding: 1.5rem;
    }

    @media (max-width: 359px) {
        .pd-qr-panel {
            padding: 1.25rem 1rem;
        }
    }

    /* The QR stays on a light plate in both themes so phones can scan it. */
    .pd-qr-plate {
        background: #FFFFFF;
        border: 1px solid var(--mist);
        border-radius: 2px;
        padding: .75rem;
    }

    .pd-qr-plate img {
        display: block;
        width: 100%;
        height: auto;
        max-height: 55dvh;
        object-fit: contain;
    }

    .pd-qr-close {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border: 1px solid var(--mist);
        border-radius: 999px;
        color: var(--ink);
        background: transparent;
    }

    .pd-qr-close:hover {
        border-color: var(--ink);
    }

    .pd-qr-close:focus-visible,
    .pd-qr-trigger:focus-visible {
        outline: 2px solid var(--redline);
        outline-offset: 3px;
    }

    @keyframes pd-qr-in {
        from { opacity: 0; transform: translateY(8px) scale(.98); }
        to { opacity: 1; transform: none; }
    }

    @keyframes pd-qr-fade {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @media (prefers-reduced-motion: reduce) {
        .pd-qr-dialog[open],
        .pd-qr-dialog[open]::backdrop {
            animation: none;
        }
    }
</style>
@endonce
