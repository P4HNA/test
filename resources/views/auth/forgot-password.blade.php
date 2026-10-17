<x-guest-layout>
    @include('partials._auth-motion')

    <div class="min-h-screen w-full relative md:h-screen md:overflow-hidden" style="background:#FFFFFF; color:#0A0A0A;">

        <div class="w-full flex flex-col md:h-full md:flex-row">

            <!-- Left: story column -->
            <div class="md:w-1/2 flex flex-col justify-center px-10 md:px-20 py-14 relative">
                {{-- The same logo as every other page, and a way back to the homepage. --}}
                <x-brand :href="route('home')" :size="34" class="pd-rise self-start mb-10" />


                <h1 class="pd-rise font-display font-extrabold text-4xl md:text-5xl leading-[1.05] mb-5 max-w-md" style="animation-delay:.22s">
                    {{ __('Forgot your password?') }}
                </h1>

                <p class="pd-rise text-base max-w-sm mb-10" style="color:#5E5E5E; animation-delay:.32s">
                    {{ __('No problem. Tell us your email address and we\'ll send you a link to choose a new one.') }}
                </p>

                <div class="hidden md:flex items-center gap-3 max-w-xs">
                    <div class="pd-stamp w-11 h-11 rounded-full flex items-center justify-center font-hand text-lg shrink-0"
                        style="border:2px solid #0062EB; color:#0062EB; --pd-tilt:-6deg;">
                        ok!
                    </div>
                    <p class="pd-rise font-hand text-lg leading-snug" style="color:#5E5E5E; animation-delay:1.05s">
                        it happens to everyone
                    </p>
                </div>
            </div>

            <!-- Divider -->
            <div class="pd-divider hidden md:block w-px" style="background:#E6E6EA"></div>

            <!-- Right: form column -->
            <div class="md:w-1/2 flex items-center justify-center px-6 md:px-20 py-14 md:overflow-y-auto">
                <div class="w-full max-w-sm">

                    <x-auth-session-status class="pd-slide mb-5 text-sm" :status="session('status')" style="animation-delay:.3s" />

                    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                        @csrf

                        <div class="pd-field pd-slide" style="animation-delay:.46s">
                            <label for="email" class="pd-label block text-xs font-medium uppercase tracking-wide mb-2" style="color:#5E5E5E">{{ __('Email Address') }}</label>
                            <div class="pd-input-wrap @error('email') pd-shake @enderror">
                                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                                    class="pd-input w-full bg-transparent py-2 text-[15px] focus:outline-none"
                                    style="border:none; border-bottom:1.5px solid #E6E6EA; color:#0A0A0A; outline:none !important; box-shadow:none !important;"
                                    placeholder="{{ __('you@example.com') }}" />
                                <span class="pd-line" aria-hidden="true"></span>
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>

                        <button type="submit"
                            class="pd-slide pd-btn w-full text-sm font-medium py-3 hover:opacity-85"
                            style="background:#0A0A0A; color:#FFFFFF; animation-delay:.54s">
                            {{ __('Email password reset link') }}
                        </button>
                    </form>

                    <p class="pd-slide text-sm mt-8" style="color:#5E5E5E; animation-delay:.62s">
                        {{ __('Remembered it after all?') }}
                        <a href="{{ route('login') }}" class="pd-link font-medium hover:opacity-70" style="color:#0062EB">{{ __('Back to log in') }}</a>
                    </p>

                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
