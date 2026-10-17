<x-guest-layout>
    @include('partials._auth-motion')

    {{-- Plain CSS (not Tailwind "disabled:" utilities) so the waiting state
         shows even with an older compiled stylesheet. --}}
    <style>
        /* !important: the pd-slide entrance animation otherwise holds opacity at 1. */
        .pd-resend:disabled { opacity: .5 !important; cursor: not-allowed; transform: none; }
    </style>

    {{-- Light-only, like every other sign-in screen: fixed paper/ink colours, not the theme tokens. --}}
    <div class="min-h-screen w-full relative md:h-screen md:overflow-hidden" style="background:#FFFFFF; color:#0A0A0A;">

        <div class="w-full flex flex-col md:h-full md:flex-row">

            <!-- Left: story column -->
            <div class="md:w-1/2 flex flex-col justify-center px-10 md:px-20 py-14 relative">
                {{-- The same logo as every other page, and a way back to the homepage. --}}
                <x-brand :href="route('home')" :size="34" class="pd-rise self-start mb-10" />


                <h1 class="pd-rise font-display font-extrabold text-4xl md:text-5xl leading-[1.05] mb-5 max-w-md" style="animation-delay:.22s">
                    {{ __('Check your inbox.') }}
                </h1>

                <p class="pd-rise text-base max-w-sm mb-10" style="color:#5E5E5E; animation-delay:.32s">
                    {{ __('We sent a verification link to') }}
                    <strong class="break-all" style="color:#0A0A0A">{{ $email }}</strong>.
                    {{ __('Click it to unlock your courses. The link works for :minutes minutes.', ['minutes' => config('auth.verification.expire', 60)]) }}
                </p>

                <div class="hidden md:flex items-center gap-3 max-w-xs">
                    <div class="pd-stamp w-11 h-11 rounded-full flex items-center justify-center font-hand text-lg shrink-0"
                        style="border:2px solid #0062EB; color:#0062EB; --pd-tilt:-6deg;">
                        ✉
                    </div>
                    <p class="pd-rise font-hand text-lg leading-snug" style="color:#5E5E5E; animation-delay:1.05s">
                        {{ __('one quick click') }}
                    </p>
                </div>
            </div>

            <!-- Divider -->
            <div class="pd-divider hidden md:block w-px" style="background:#E6E6EA"></div>

            <!-- Right: actions column -->
            <div class="md:w-1/2 flex items-center justify-center px-6 md:px-20 py-14 md:overflow-y-auto">
                <div class="w-full max-w-sm">

                    @if (session('status') === 'verification-link-sent')
                        <p class="pd-slide mb-5 text-sm font-medium" style="color:#15803D; animation-delay:.3s" role="status">
                            {{ __('A new verification link is on its way to your inbox.') }}
                        </p>
                    @endif

                    @if ($errors->has('verification'))
                        <p class="pd-slide mb-5 text-sm font-medium" style="color:#B42318; animation-delay:.3s" role="alert">
                            {{ $errors->first('verification') }}
                        </p>
                    @endif

                    <p class="pd-slide text-sm mb-6" style="color:#5E5E5E; animation-delay:.38s">
                        {{ __("Can't find it? Check your spam or promotions folder, or send a new link.") }}
                    </p>

                    {{--
                        The 60-second cooldown is enforced on the server
                        (EmailVerificationNotificationController); this only
                        mirrors it. The remaining time comes from the server,
                        so it stays right after a refresh.
                    --}}
                    <form method="POST" action="{{ route('verification.send') }}"
                        x-data="{
                            left: {{ (int) $resendAvailableIn }},
                            deadline: Date.now() + {{ (int) $resendAvailableIn }} * 1000,
                            busy: false,
                            timer: null,
                            tick() {
                                this.left = Math.max(0, Math.ceil((this.deadline - Date.now()) / 1000));
                                if (this.left === 0) clearInterval(this.timer);
                            },
                        }"
                        x-init="if (left > 0) timer = setInterval(() => tick(), 250)"
                        x-on:submit="if (busy || left > 0) { $event.preventDefault() } else { busy = true }"
                        x-on:pageshow.window="busy = false"
                    >
                        @csrf
                        <button type="submit"
                            class="pd-slide pd-btn pd-resend w-full text-sm font-medium py-3 hover:opacity-85"
                            style="background:#0A0A0A; color:#FFFFFF; animation-delay:.46s"
                            @disabled($resendAvailableIn > 0)
                            x-bind:disabled="busy || left > 0"
                        >
                            <span x-text="busy ? @js(__('Sending…')) : (left > 0 ? @js(__('Resend link in')) + ' ' + left + 's' : @js(__('Resend verification email')))">
                                {{ $resendAvailableIn > 0 ? __('Resend link in').' '.$resendAvailableIn.'s' : __('Resend verification email') }}
                            </span>
                        </button>
                    </form>

                    <div class="pd-slide flex flex-wrap items-center justify-between gap-3 text-sm mt-8" style="color:#5E5E5E; animation-delay:.54s">
                        <a href="{{ route('profile.edit') }}" class="pd-link font-medium hover:opacity-70" style="color:#0062EB">
                            {{ __('Wrong email? Change it') }}
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="pd-link underline hover:opacity-70">
                                {{ __('Log out') }}
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
