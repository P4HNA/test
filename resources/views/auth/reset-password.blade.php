<x-guest-layout>
    @include('partials._auth-motion')

    <div class="min-h-screen w-full relative md:h-screen md:overflow-hidden" style="background:#FFFFFF; color:#0A0A0A;">

        <div class="w-full flex flex-col md:h-full md:flex-row">

            <!-- Left: story column -->
            <div class="md:w-1/2 flex flex-col justify-center px-10 md:px-20 py-14 relative">
                {{-- The same logo as every other page, and a way back to the homepage. --}}
                <x-brand :href="route('home')" :size="34" class="pd-rise self-start mb-10" />


                <h1 class="pd-rise font-display font-extrabold text-4xl md:text-5xl leading-[1.05] mb-5 max-w-md" style="animation-delay:.22s">
                    {{ __('Choose a new password.') }}
                </h1>

                <p class="pd-rise text-base max-w-sm mb-10" style="color:#5E5E5E; animation-delay:.32s">
                    {{ __('Almost there. Set a new password below and you\'ll be back in your classroom.') }}
                </p>
            </div>

            <!-- Divider -->
            <div class="pd-divider hidden md:block w-px" style="background:#E6E6EA"></div>

            <!-- Right: form column -->
            <div class="md:w-1/2 flex items-center justify-center px-6 md:px-20 py-14 md:overflow-y-auto">
                <div class="w-full max-w-sm">

                    <form method="POST" action="{{ route('password.store') }}" class="space-y-6">
                        @csrf

                        <!-- Password Reset Token -->
                        <input type="hidden" name="token" value="{{ $request->route('token') }}">

                        <div class="pd-field pd-slide" style="animation-delay:.38s">
                            <label for="email" class="pd-label block text-xs font-medium uppercase tracking-wide mb-2" style="color:#5E5E5E">{{ __('Email Address') }}</label>
                            <div class="pd-input-wrap @error('email') pd-shake @enderror">
                                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
                                    class="pd-input w-full bg-transparent py-2 text-[15px] focus:outline-none"
                                    style="border:none; border-bottom:1.5px solid #E6E6EA; color:#0A0A0A; outline:none !important; box-shadow:none !important;"
                                    placeholder="{{ __('you@example.com') }}" />
                                <span class="pd-line" aria-hidden="true"></span>
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>

                        <div class="pd-field pd-slide" style="animation-delay:.46s">
                            <label for="password" class="pd-label block text-xs font-medium uppercase tracking-wide mb-2" style="color:#5E5E5E">{{ __('New Password') }}</label>
                            <div class="pd-input-wrap @error('password') pd-shake @enderror" x-data="{ show: false }">
                                <input id="password" type="password" name="password" required autocomplete="new-password"
                                    x-bind:type="show ? 'text' : 'password'"
                                    class="pd-input pd-has-eye w-full bg-transparent py-2 text-[15px] focus:outline-none"
                                    style="border:none; border-bottom:1.5px solid #E6E6EA; color:#0A0A0A; outline:none !important; box-shadow:none !important;"
                                    placeholder="{{ __('At least 8 characters') }}" />
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

                        <div class="pd-field pd-slide" style="animation-delay:.54s">
                            <label for="password_confirmation" class="pd-label block text-xs font-medium uppercase tracking-wide mb-2" style="color:#5E5E5E">{{ __('Confirm Password') }}</label>
                            <div class="pd-input-wrap @error('password_confirmation') pd-shake @enderror" x-data="{ show: false }">
                                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                                    x-bind:type="show ? 'text' : 'password'"
                                    class="pd-input pd-has-eye w-full bg-transparent py-2 text-[15px] focus:outline-none"
                                    style="border:none; border-bottom:1.5px solid #E6E6EA; color:#0A0A0A; outline:none !important; box-shadow:none !important;"
                                    placeholder="{{ __('Type it again') }}" />
                                <button type="button" class="pd-eye" x-on:click="show = !show"
                                    x-bind:aria-pressed="show.toString()"
                                    x-bind:aria-label="show ? @js(__('Hide password')) : @js(__('Show password'))"
                                    aria-label="{{ __('Show password') }}" aria-controls="password_confirmation">
                                    <svg x-show="!show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg x-show="show" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.9 4.2A10.4 10.4 0 0 1 12 4c6.4 0 10 8 10 8a17.6 17.6 0 0 1-2.9 4.1M6.6 6.6C3.9 8.4 2 12 2 12s3.6 8 10 8a9.7 9.7 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/><path d="M2 2l20 20"/></svg>
                                </button>
                                <span class="pd-line" aria-hidden="true"></span>
                            </div>
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                        </div>

                        <button type="submit"
                            class="pd-slide pd-btn w-full text-sm font-medium py-3 hover:opacity-85"
                            style="background:#0A0A0A; color:#FFFFFF; animation-delay:.62s">
                            {{ __('Reset password') }}
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
