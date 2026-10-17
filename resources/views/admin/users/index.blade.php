<x-admin-layout title="Students" active="users" :live="20000">

    @php
        // If the password form failed validation, open the dialog again for the same person.
        $reopen = null;
        if (old('user_id') && ($errors->has('password') || $errors->has('current_password'))) {
            $again = $users->getCollection()->firstWhere('uuid', old('user_id'));
            $reopen = $again ? ['id' => $again->uuid, 'name' => $again->name, 'email' => $again->email] : null;
        }
        $fiveMinutesAgo = now()->subMinutes(5)->timestamp;
        $tabs = ['all' => 'Everyone', 'student' => 'Students', 'admin' => 'Admins'];
        // Only worth a tab once someone is actually suspended.
        if ($counts['suspended'] > 0 || $role === 'suspended') {
            $tabs['suspended'] = 'Suspended';
        }
    @endphp

    <div x-data="studentsPage(@js($reopen), @js(route('admin.users.password', '__ID__')), @js(route('admin.users.ban', '__ID__')))" style="display:grid; gap:24px">

        <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:16px">
            <div>
                <h1 class="ad-h1">Students</h1>
                <p class="ad-muted ad-sm" style="margin:8px 0 0">
                    @if ($q !== '')
                    {{ $users->total() }} {{ Str::plural('result', $users->total()) }} for "{{ $q }}".
                    <a href="{{ route('admin.users', array_filter(['role' => $role !== 'all' ? $role : null])) }}" style="color:var(--accent); font-weight:600">Clear search</a>
                    @else
                    Open a person to see their courses, progress and payments, or to give them a course.
                    @endif
                </p>
            </div>

            <nav class="ad-seg" aria-label="Filter people">
                @foreach ($tabs as $key => $label)
                <a href="{{ route('admin.users', array_filter(['role' => $key !== 'all' ? $key : null, 'q' => $q !== '' ? $q : null])) }}"
                    @if ($role === $key) aria-current="page" @endif>
                    {{ $label }} <span>{{ number_format($counts[$key]) }}</span>
                </a>
                @endforeach
            </nav>
        </div>

        <div class="ad-card">
            <div class="ad-scroll-x">
                <table class="ad-table">
                    <thead>
                        <tr>
                            <th>Person</th>
                            <th>Role</th>
                            <th>Courses</th>
                            <th>Lessons</th>
                            <th>Certificates</th>
                            <th>Activity</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $u)
                        @php
                            $seen = $lastSeen[$u->id] ?? null;
                        @endphp
                        <tr>
                            <td class="ad-col-main">
                                <a href="{{ route('admin.users.show', $u) }}" class="ad-row-link">
                                    <span class="ad-avatar">{{ Str::upper(Str::substr($u->name, 0, 1)) }}</span>
                                    <span style="min-width:0">
                                        <span class="ad-row-title">{{ $u->name }}</span>
                                        <span class="ad-muted ad-xs" style="display:block">{{ $u->email }}@if ($u->phone), {{ $u->phone }}@endif</span>
                                    </span>
                                </a>
                            </td>
                            <td>
                                {{-- A wrapper, not display:flex on the <td>: a flex cell stops being a
                                     table cell, which broke the row's borders and heights. --}}
                                <div style="display:flex; flex-wrap:wrap; gap:6px">
                                    @if ($u->isAdmin())
                                    <span class="ad-badge accent">Admin</span>
                                    @else
                                    <span class="ad-badge">Student</span>
                                    @endif
                                    @if ($u->isBanned())
                                    <span class="ad-badge bad dot" title="{{ $u->ban_reason ?: 'No reason given' }}">Suspended</span>
                                    @endif
                                </div>
                            </td>
                            <td>{{ $u->enrollments_count }}</td>
                            <td>{{ $u->lesson_completions_count }}</td>
                            <td>{{ $u->certificates_count }}</td>
                            {{-- Last seen first (what you usually need), joined underneath. --}}
                            <td class="ad-sm">
                                @if ($seen && $seen >= $fiveMinutesAgo)
                                <span class="ad-badge ok"><span class="ad-dot is-live"></span> Online now</span>
                                @elseif ($seen)
                                <span style="display:block">Active {{ \Carbon\Carbon::createFromTimestamp($seen)->diffForHumans() }}</span>
                                @else
                                <span class="ad-muted" style="display:block">Not seen yet</span>
                                @endif
                                <span class="ad-muted ad-xs" style="display:block; margin-top:2px" title="{{ $u->created_at }}">Joined {{ $u->created_at?->diffForHumans() }}</span>
                            </td>
                            <td style="text-align:right">
                                {{-- The everyday actions stay on the row. Removing an account is
                                     permanent and rare, so it lives on the person's page, where you
                                     can see their courses and payments before you decide. --}}
                                <div style="display:inline-flex; gap:6px; justify-content:flex-end; white-space:nowrap">
                                    <button type="button" class="ad-btn ad-btn-sm" aria-label="Change password for {{ $u->name }}"
                                        @click="openPw(@js($u->uuid), @js($u->name), @js($u->email))">
                                        <x-admin.icon name="key" class="ad-i16" /> Change
                                    </button>

                                    @unless ($u->is(auth()->user()))
                                    @if ($u->isBanned())
                                    <form method="POST" action="{{ route('admin.users.unban', $u) }}"
                                        data-confirm="{{ $u->name }} will be able to sign in again with their existing password." data-confirm-title="Let {{ $u->name }} sign in again?" data-confirm-ok="Unsuspend" data-confirm-tone="primary">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="ad-btn ad-btn-sm" aria-label="Unsuspend {{ $u->name }}">
                                            <x-admin.icon name="undo" class="ad-i16" /> Unsuspend
                                        </button>
                                    </form>
                                    @else
                                    <button type="button" class="ad-btn ad-btn-sm" aria-label="Suspend {{ $u->name }}"
                                        @click="openBan(@js($u->uuid), @js($u->name))">
                                        <x-admin.icon name="lock" class="ad-i16" /> Suspend
                                    </button>
                                    @endif
                                    @endunless
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="ad-muted" style="padding:48px 20px; text-align:center">
                                @if ($q !== '')
                                Nobody matches "{{ $q }}". Try part of their email instead.
                                @elseif ($role === 'suspended')
                                Nobody is suspended. Everyone can sign in.
                                @else
                                No accounts here yet. They appear as soon as someone registers.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($users->hasPages())
        <div>{{ $users->links() }}</div>
        @endif

        @include('admin.users._dialogs')
    </div>

</x-admin-layout>
