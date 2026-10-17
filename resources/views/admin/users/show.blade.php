<x-admin-layout :title="$user->name" active="users" :live="20000">

    @php
        // If the password form failed validation, open the dialog again.
        $reopen = old('user_id') && old('user_id') === $user->uuid && ($errors->has('password') || $errors->has('current_password'))
            ? ['id' => $user->uuid, 'name' => $user->name, 'email' => $user->email]
            : null;
        $isMe = $user->is(auth()->user());
        $online = $lastSeen && $lastSeen >= now()->subMinutes(5)->timestamp;
        $lessonsDone = $courses->sum('completed');
        $money = fn (string $currency, $value) => $currency === 'KHR' ? number_format($value).' ៛' : '$'.number_format($value, 2);
    @endphp

    <div x-data="studentsPage(@js($reopen), @js(route('admin.users.password', '__ID__')), @js(route('admin.users.ban', '__ID__')))" style="display:grid; gap:24px">

        {{-- ---------- Header ---------- --}}
        <div style="display:grid; gap:16px">
            <a href="{{ route('admin.users') }}" class="ad-back">
                <x-admin.icon name="arrow-left" class="ad-i16" /> Students
            </a>

            <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:16px">
                <div style="display:flex; align-items:center; gap:16px; min-width:0">
                    <span class="ad-avatar" style="width:56px; height:56px; font-size:20px">{{ Str::upper(Str::substr($user->name, 0, 1)) }}</span>
                    <div style="min-width:0">
                        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px">
                            <h1 class="ad-h1" style="overflow-wrap:anywhere">{{ $user->name }}</h1>
                            @if ($user->isAdmin())
                            <span class="ad-badge accent">Admin</span>
                            @else
                            <span class="ad-badge">Student</span>
                            @endif
                            @if ($user->isBanned())
                            <span class="ad-badge bad dot">Suspended</span>
                            @endif
                            @unless ($user->hasVerifiedEmail())
                            <span class="ad-badge warn" title="They have not clicked the link in their verification email yet, so they cannot open lessons.">Email not verified</span>
                            @endunless
                        </div>
                        <p class="ad-muted ad-sm" style="margin:6px 0 0; overflow-wrap:anywhere">
                            {{ $user->email }}@if ($user->phone) · {{ $user->phone }}@endif
                        </p>
                        <p class="ad-muted ad-xs" style="margin:4px 0 0">
                            Joined {{ $user->created_at?->format('j M Y') }}
                            ·
                            @if ($online)
                            <span style="color:var(--success-text); font-weight:600">Online now</span>
                            @elseif ($lastSeen)
                            Last active {{ \Carbon\Carbon::createFromTimestamp($lastSeen)->diffForHumans() }}
                            @else
                            Last active: not recorded
                            @endif
                        </p>
                    </div>
                </div>

                <div style="display:flex; flex-wrap:wrap; gap:8px">
                    <button type="button" class="ad-btn" @click="openPw(@js($user->uuid), @js($user->name), @js($user->email))">
                        <x-admin.icon name="key" class="ad-i16" /> Change password
                    </button>
                    @unless ($isMe)
                    @if ($user->isBanned())
                    <form method="POST" action="{{ route('admin.users.unban', $user) }}"
                        data-confirm="{{ $user->name }} will be able to sign in again with their existing password." data-confirm-title="Let {{ $user->name }} sign in again?" data-confirm-ok="Unsuspend" data-confirm-tone="primary">
                        @csrf @method('PATCH')
                        <button type="submit" class="ad-btn"><x-admin.icon name="undo" class="ad-i16" /> Unsuspend</button>
                    </form>
                    @else
                    <button type="button" class="ad-btn" @click="openBan(@js($user->uuid), @js($user->name))">
                        <x-admin.icon name="lock" class="ad-i16" /> Suspend
                    </button>
                    @endif
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                        data-confirm="{{ $user->name }}’s enrollments and progress are deleted. Past payments are kept for the books. This cannot be undone." data-confirm-title="Remove {{ $user->name }} permanently?" data-confirm-ok="Remove account" data-confirm-tone="danger">
                        @csrf @method('DELETE')
                        <button type="submit" class="ad-btn ad-btn-danger"><x-admin.icon name="trash" class="ad-i16" /> Remove</button>
                    </form>
                    @endunless
                </div>
            </div>

            @if ($user->isBanned())
            <div class="ad-callout warn" role="status">
                <x-admin.icon name="lock" class="ad-i18" />
                <div>
                    <strong>Suspended {{ $user->banned_at?->format('j M Y') }}.</strong>
                    They cannot sign in, by password or Google. Their courses, progress and payments are kept.
                    @if ($user->ban_reason)
                    <br><span class="ad-muted">Reason: {{ $user->ban_reason }}</span>
                    @endif
                </div>
            </div>
            @endif
        </div>

        {{-- ---------- At a glance ---------- --}}
        {{-- Three small numbers: one row at every width, so they never push the courses off a phone screen. --}}
        <div class="ad-grid" style="grid-template-columns:repeat(3, minmax(0, 1fr)); gap:clamp(8px, 2vw, 16px)">
            <div class="ad-card" style="padding:clamp(12px, 2.4vw, 16px) clamp(12px, 2.6vw, 20px)">
                <p class="ad-sm ad-muted" style="margin:0; font-weight:600">Courses</p>
                <p class="ad-kpi-value" style="margin-bottom:0">{{ $courses->count() }}</p>
            </div>
            <div class="ad-card" style="padding:clamp(12px, 2.4vw, 16px) clamp(12px, 2.6vw, 20px)">
                <p class="ad-sm ad-muted" style="margin:0; font-weight:600">Lessons completed</p>
                <p class="ad-kpi-value" style="margin-bottom:0">{{ $lessonsDone }}</p>
            </div>
            <div class="ad-card" style="padding:clamp(12px, 2.4vw, 16px) clamp(12px, 2.6vw, 20px)">
                <p class="ad-sm ad-muted" style="margin:0; font-weight:600">Certificates</p>
                <p class="ad-kpi-value" style="margin-bottom:0">{{ $courses->whereNotNull('certificate')->count() }}</p>
            </div>
        </div>

        <div class="ad-grid ad-cols-xl-3">

            {{-- ---------- Courses and progress ---------- --}}
            <section class="ad-card ad-span-xl-2" aria-labelledby="courses-title">
                <div class="ad-card-h">
                    <div>
                        <h2 id="courses-title" class="ad-h2">Courses</h2>
                        <p class="ad-muted ad-xs" style="margin:2px 0 0">What they can open, and how far they got.</p>
                    </div>
                </div>

                <div style="padding:8px 8px 4px">
                    @forelse ($courses as $row)
                    @php
                        $c = $row['course'];
                        $sw = $c->swatch();
                        $title = $c->localized('title');
                        $last = $row['enrollment']->lastLesson;
                    @endphp
                    <div class="ad-access-row">
                        <span class="ad-mark" style="background:{{ $sw['bg'] }}; color:{{ $sw['fg'] }}">{{ $c->mark }}</span>

                        <div style="min-width:0; flex:1; display:grid; gap:6px">
                            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:6px 8px">
                                <a href="{{ route('admin.courses.edit', $c) }}" class="ad-row-link" style="min-width:0">
                                    <span class="ad-row-title">{{ $title }}</span>
                                </a>
                                @if ($row['source'] === 'free')
                                <span class="ad-badge">Free course</span>
                                @elseif ($row['source'] === 'paid')
                                <span class="ad-badge ok dot">Paid</span>
                                @else
                                <span class="ad-badge warn" title="A paid course with no paid invoice on record: an admin gave it, or it was added before payments were recorded.">Given by hand</span>
                                @endif
                                @unless ($c->isPublished())
                                <span class="ad-badge blue">Draft</span>
                                @endunless
                            </div>

                            <div style="display:flex; align-items:center; gap:10px">
                                <span class="ad-bar" style="flex:1; max-width:260px" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $row['percent'] }}" aria-label="Progress in {{ $title }}">
                                    <span style="width:{{ $row['percent'] }}%"></span>
                                </span>
                                <span class="ad-xs" style="white-space:nowrap">
                                    <strong>{{ $row['completed'] }}</strong> <span class="ad-muted">of {{ $row['total'] }} {{ Str::plural('lesson', $row['total']) }} · {{ $row['percent'] }}%</span>
                                </span>
                            </div>

                            <p class="ad-muted ad-xs" style="margin:0">
                                Joined {{ $row['enrollment']->enrolled_at?->format('j M Y') ?? 'date not recorded' }}
                                @if ($last) · Last opened "{{ $last->localized('title') }}" @endif
                                @if ($row['invoice']) · Invoice <span class="pd-mono">{{ $row['invoice']->invoice_no }}</span> @endif
                                @if ($row['certificate']) · <span style="color:var(--success-text); font-weight:600">Certificate {{ $row['certificate']->code }}</span> @endif
                            </p>
                        </div>

                        @php
                            $confirm = 'Take '.$title.' away from '.$user->name.'? They will no longer be able to open its lessons. Their progress is kept, so giving it back later picks up where they left off.';
                            if ($row['source'] === 'paid') {
                                $confirm .= "\n\nThey paid for this course (invoice ".$row['invoice']->invoice_no."). This does NOT refund them; refunds are done from Payments.";
                            }
                        @endphp
                        <form method="POST" action="{{ route('admin.users.enrollments.revoke', [$user, $c]) }}"
                            data-confirm="{{ $confirm }}" data-confirm-title="Take this course away?" data-confirm-ok="Remove access" data-confirm-tone="danger">
                            @csrf @method('DELETE')
                            <button type="submit" class="ad-btn ad-btn-sm ad-btn-danger" style="box-shadow:none; background:transparent" aria-label="Remove access to {{ $title }}">
                                Remove access
                            </button>
                        </form>
                    </div>
                    @empty
                    <div style="padding:32px 16px; text-align:center">
                        <p class="ad-h2" style="font-size:15px">No courses yet</p>
                        <p class="ad-muted ad-sm" style="margin:4px auto 0; max-width:44ch">They have not joined a course. You can give them one below.</p>
                    </div>
                    @endforelse
                </div>

                {{-- Give a course by hand --}}
                <div class="ad-card-b" style="border-top:1px solid var(--line); margin-top:8px">
                    @if ($grantable->isEmpty())
                    <p class="ad-muted ad-sm" style="margin:0">They already have every course.</p>
                    @else
                    <form method="POST" action="{{ route('admin.users.enrollments.grant', $user) }}"
                        x-data="{ busy: false }" x-on:submit="if (busy) { $event.preventDefault() } else { busy = true }"
                        style="display:grid; gap:8px">
                        @csrf
                        <label for="grant-course" class="ad-label" style="margin:0">Give a course</label>
                        <div style="display:flex; flex-wrap:wrap; gap:8px">
                            <select id="grant-course" name="course_id" class="ad-select" style="flex:1 1 220px; min-width:0; width:100%" required
                                aria-describedby="grant-hint{{ $errors->has('course_id') ? ' grant-error' : '' }}"
                                @error('course_id') aria-invalid="true" @enderror>
                                <option value="">Choose a course…</option>
                                @foreach ($grantable as $g)
                                <option value="{{ $g->id }}" @selected((int) old('course_id') === $g->id)>
                                    {{ $g->localized('title') }} ({{ $g->isFree() ? 'free' : $g->priceLabel() }}){{ $g->isPublished() ? '' : ', draft' }}
                                </option>
                                @endforeach
                            </select>
                            <button type="submit" class="ad-btn ad-btn-primary" x-bind:disabled="busy">
                                <x-admin.icon name="plus" class="ad-i16" /> Give access
                            </button>
                        </div>
                        <p id="grant-hint" class="ad-hint" style="margin:0">For a cash payment, a scholarship, or to sort out a payment problem. No payment is recorded.</p>
                        @error('course_id')
                        <p id="grant-error" class="ad-error" style="margin:0">{{ $message }}</p>
                        @enderror
                    </form>
                    @endif
                </div>
            </section>

            {{-- ---------- Payments ---------- --}}
            <section class="ad-card" style="align-self:start" aria-labelledby="payments-title">
                <div class="ad-card-h">
                    <div>
                        <h2 id="payments-title" class="ad-h2">Payments</h2>
                        <p class="ad-muted ad-xs" style="margin:2px 0 0">
                            @php
                                $paidByCurrency = $payments->where('status', 'paid')->groupBy('currency')->map->sum('amount');
                            @endphp
                            @if ($paidByCurrency->isEmpty())
                            Nothing paid yet.
                            @else
                            Paid in total:
                            {{ $paidByCurrency->map(fn ($sum, $cur) => $money((string) $cur, $sum))->join(' + ') }}
                            @endif
                        </p>
                    </div>
                </div>
                <div style="padding:8px 8px 12px">
                    @forelse ($payments as $p)
                    <a href="{{ route('admin.payments', array_filter(['q' => $p->invoice_no, 'status' => $p->isArchived() ? 'archived' : null])) }}" class="ad-menu-item" style="align-items:flex-start; border-radius:10px">
                        <span style="min-width:0; flex:1">
                            <span class="pd-mono" style="display:block; font-size:12px; font-weight:600">{{ $p->invoice_no }}</span>
                            <span class="ad-muted ad-xs" style="display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap">{{ $p->courseTitle() }}</span>
                            <span class="ad-muted ad-xs" style="display:block">{{ ($p->paid_at ?? $p->created_at)?->format('j M Y, H:i') }}@if ($p->isArchived()) · archived @endif</span>
                        </span>
                        <span style="display:grid; justify-items:end; gap:4px">
                            <span class="ad-sm" style="font-weight:600; font-variant-numeric:tabular-nums">{{ $p->formattedAmount() }}</span>
                            <span class="ad-badge dot {{ $p->statusTone() }}">{{ $p->statusLabel() }}</span>
                        </span>
                    </a>
                    @empty
                    <p class="ad-muted ad-sm" style="margin:0; padding:24px 12px; text-align:center">No payments. They have not started a checkout.</p>
                    @endforelse
                </div>
            </section>
        </div>

        @include('admin.users._dialogs')
    </div>

</x-admin-layout>
