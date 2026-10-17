<x-guest-layout>
    @include('partials._auth-motion')

    <div class="min-h-screen w-full relative md:h-screen md:overflow-hidden" style="background:#FFFFFF; color:#0A0A0A;">

        <div class="w-full flex flex-col md:h-full md:flex-row">

            <!-- Left: story column, plain paper, no color block -->
            <div class="md:w-1/2 flex flex-col justify-center px-10 md:px-20 py-14 relative">
                {{-- The same logo as every other page, and a way back to the homepage. --}}
                <x-brand :href="route('home')" :size="34" class="pd-rise self-start mb-10" />


                <h1 class="pd-rise font-display font-extrabold text-4xl md:text-5xl leading-[1.05] mb-5 max-w-md" style="animation-delay:.22s">
                    {{ __('WELLCOME BACK.') }}
                </h1>

                <p class="pd-rise text-base max-w-sm mb-10" style="color:#5E5E5E; animation-delay:.32s">
                    {{ __('Log in to pick up your courses and see what your reviewer left on your last submission.') }}
                </p>

                <!-- Annotation, hand-marked, echoes the homepage hero mockup -->
                <div class="hidden md:flex items-center gap-3 max-w-xs">
                    <div class="pd-stamp w-11 h-11 rounded-full flex items-center justify-center font-hand text-lg shrink-0"
                        style="border:2px solid #0062EB; color:#0062EB; --pd-tilt:-6deg;">
                        {{ __('hi!') }}
                    </div>
                    <p class="pd-rise font-hand text-lg leading-snug" style="color:#5E5E5E; animation-delay:1.05s">
                        {{ __('good to see u again') }}
                    </p>
                </div>
            </div>

            <!-- Divider -->
            <div class="pd-divider hidden md:block w-px" style="background:#E6E6EA"></div>

            <!-- Right: form column -->
            <div class="md:w-1/2 flex items-center justify-center px-6 md:px-20 py-14 md:overflow-y-auto">
                <div class="w-full max-w-sm">

                    <x-auth-session-status class="pd-slide mb-5 text-sm" :status="session('status')" style="animation-delay:.3s" />

                    @if ($errors->has('email') && str_contains($errors->first('email'), 'authenticate'))
                        <div class="pd-slide mb-5 text-sm text-red-600 font-medium" style="animation-delay:.3s">
                            {{ $errors->first('email') }}
                        </div>
                    @endif

                    <div class="space-y-3 mb-6">
                        <a href="{{ route('auth.social.redirect', 'google') }}"
                            class="pd-slide pd-btn pd-google w-full flex items-center justify-center gap-3 py-2.5 text-sm font-medium"
                            style="border:1px solid #E6E6EA; background:transparent; animation-delay:.38s">
                            <svg class="w-4 h-4" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.29 1.48-1.14 2.73-2.4 3.58v3h3.86c2.26-2.09 3.56-5.17 3.56-8.82z" />
                                <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.86-3c-1.08.72-2.45 1.16-4.07 1.16-3.13 0-5.78-2.11-6.73-4.96H1.29v3.09C3.26 21.3 7.31 24 12 24z" />
                                <path fill="#FBBC05" d="M5.27 14.29c-.25-.72-.38-1.49-.38-2.29s.14-1.57.38-2.29V6.62H1.29A11.96 11.96 0 000 12c0 1.93.46 3.76 1.29 5.38l3.98-3.09z" />
                                <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.94 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.29 6.62l3.98 3.09C6.22 6.86 8.87 4.75 12 4.75z" />
                            </svg>
                            {{ __('Continue with Google') }}
                        </a>
                    </div>

                    <div class="pd-slide flex items-center gap-3 mb-7" style="animation-delay:.46s">
                        <div class="flex-1 h-px" style="background:#E6E6EA"></div>
                        <span class="text-[10px] tracking-[0.2em] uppercase" style="color:#5E5E5E">{{ __('or use email') }}</span>
                        <div class="flex-1 h-px" style="background:#E6E6EA"></div>
                    </div>

                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf

                        <div class="pd-field pd-slide" style="animation-delay:.54s">
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

                        <div class="pd-field pd-slide" style="animation-delay:.62s">
                            <div class="flex items-baseline justify-between mb-2">
                                <label for="password" class="pd-label text-xs font-medium uppercase tracking-wide" style="color:#5E5E5E">{{ __('Password') }}</label>
                                @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="pd-link text-xs hover:opacity-70" style="color:#5E5E5E">{{ __('Forgot?') }}</a>
                                @endif
                            </div>
                            <div class="pd-input-wrap @error('password') pd-shake @enderror" x-data="{ show: false }">
                                <input id="password" type="password" name="password" required autocomplete="current-password"
                                    x-bind:type="show ? 'text' : 'password'"
                                    class="pd-input pd-has-eye w-full bg-transparent py-2 text-[15px] focus:outline-none"
                                    style="border:none; border-bottom:1.5px solid #E6E6EA; color:#0A0A0A; outline:none !important; box-shadow:none !important;"
                                    placeholder="{{ __('Enter your password') }}" />
                                <button type="button" class="pd-eye" x-on:click="show = !show"
                                    x-bind:aria-pressed="show.toString()"
                                    x-bind:aria-label="show ? @js(__('Hide password')) : @js(__('Show password'))"
                                    aria-label="{{ __('Show password') }}" aria-controls="password">
                                    <svg x-show="!show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg x-show="show" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.9 4.2A10.4 10.4 0 0 1 12 4c6.4 0 10 8 10 8a17.6 17.6 0 0 1-2.9 4.1M6.6 6.6C3.9 8.4 2 12 2 12s3.6 8 10 8a9.7 9.7 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/><path d="M2 2l20 20"/></svg>
                                </button>
                                <span class="pd-line" aria-hidden="true"></span>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-1" />
                        </div>

                        <label class="pd-slide flex items-center gap-2 cursor-pointer select-none" style="animation-delay:.7s">
                            <input id="remember_me" type="checkbox" name="remember" style="accent-color:#0062EB">
                            <span class="text-sm" style="color:#5E5E5E">{{ __('Remember me') }}</span>
                        </label>

                        <button type="submit"
                            class="pd-slide pd-btn w-full text-sm font-medium py-3 hover:opacity-85"
                            style="background:#0A0A0A; color:#FFFFFF; animation-delay:.78s">
                            {{ __('Log in') }}
                        </button>
                    </form>

                    @if (Route::has('register'))
                    <p class="pd-slide text-sm mt-8" style="color:#5E5E5E; animation-delay:.86s">
                        {{ __('New here?') }}
                        <a href="{{ route('register') }}" class="pd-link font-medium hover:opacity-70" style="color:#0062EB">{{ __('Create a free account') }}</a>
                    </p>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-guest-layout>