{{-- One activity post card. Needs $post (from App\Support\ActivityFeed); styles in _activity-styles, likes in _activity-likes. --}}
@php
    $acKm = app()->getLocale() === 'km';
    $acL = fn (string $en, string $km) => $acKm ? $km : $en;
    $acIcon = fn (string $name, string $class = 'i4') => \App\Support\LiveIcon::svg($name, $class);
    [$glyph, $tone] = [
        'lesson' => ['play', '#10B981'],
        'course' => ['sparkles', '#1877F2'],
        'news' => ['bell', '#F59E0B'],
        'cert' => ['award', '#1877F2'],
        'done' => ['check-circle', '#3B82F6'],
        'join' => ['user', '#4DA3FF'],
        'task' => ['file-text', '#F97316'],
        'quiz' => ['trophy', '#F59E0B'],
        'work' => ['check-circle', '#10B981'],
    ][$post['type']] ?? ['award', '#1877F2'];
    $actor = $post['actor'];
@endphp
<article class="ac-post fx-spot" data-fx data-key="{{ $post['key'] }}">
    <div class="ac-top">
        <div class="ac-who">
            <div class="ac-av">
                @if ($actor['logo'] ?? false)
                    <img class="logo" src="{{ asset('images/logo-mark-96.png') }}" alt="" width="40" height="40">
                @elseif ($actor['user'])
                    <x-user-avatar :user="$actor['user']" :size="40" />
                @else
                    <span class="hm-avatar">{!! \App\Support\LiveIcon::blankProfile() !!}</span>
                @endif
                <span class="ac-type" style="color: {{ $tone }}">{!! $acIcon($glyph, 'i35') !!}</span>
            </div>
            <div style="min-width:0">
                <div class="ac-line">
                    <span class="n">{{ $actor['name'] }}</span>
                    @if (filled($actor['role']))
                        <span class="role">{{ ($actor['you'] ?? false) ? $acL('You', 'អ្នក') : $actor['role'] }}</span>
                    @endif
                    <span class="act {{ $acKm ? 'hm-km' : '' }}">{{ $post['action'] }}</span>
                </div>
                <div class="ac-time">{!! $acIcon('clock', 'i3') !!}<span>{{ $post['ago'] }}</span></div>
            </div>
        </div>
        <span class="ac-badge {{ $acKm ? 'hm-km' : '' }}">{{ $post['badge'] }}</span>
    </div>

    <div class="ac-target">
        @if ($post['thumb'])
            <img src="{{ $post['thumb'] }}" alt="" loading="lazy" decoding="async">
        @endif
        <div>
            <h4>{{ $post['title'] }}</h4>
            @if (filled($post['subtitle']))
                <p>{{ $post['subtitle'] }}</p>
            @endif
        </div>
        @if ($post['url'])
            <a href="{{ $post['url'] }}" class="ac-view fx-btn"><span class="{{ $acKm ? 'hm-km' : '' }}">{{ $acL('View', 'ចូលមើល') }}</span>{!! $acIcon('arrow-up-right', 'i3') !!}</a>
        @endif
    </div>

    <div class="ac-foot">
        @auth
            <button type="button" class="ac-like" data-like aria-pressed="{{ $post['liked'] ? 'true' : 'false' }}" aria-label="{{ $acL('Like', 'ចូលចិត្ត') }}">
                {!! $acIcon('thumbs-up', 'i35') !!}<span data-count>{{ $post['likes'] }}</span>
            </button>
        @else
            <a href="{{ route('login') }}" class="ac-like" aria-label="{{ $acL('Sign in to like', 'ចូលគណនីដើម្បីចូលចិត្ត') }}">
                {!! $acIcon('thumbs-up', 'i35') !!}<span>{{ $post['likes'] }}</span>
            </a>
        @endauth
        <span class="k {{ $acKm ? 'hm-km' : '' }}">{{ $acL('Learning activity', 'សកម្មភាពសិក្សា') }}</span>
    </div>
</article>
