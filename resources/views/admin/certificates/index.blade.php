<x-admin-layout title="Certificates" active="certificates">

    @php
        $tabs = [
            'pending' => 'Waiting',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'all' => 'All',
        ];
        $badge = [
            'pending' => ['warn', 'Waiting'],
            'approved' => ['ok dot', 'Approved'],
            'rejected' => ['bad', 'Rejected'],
        ];
        $counts['all'] = array_sum($counts);
    @endphp

    <div style="display:grid; gap:24px"
        x-data="{ rej: { open: false, id: null, name: '', action: '', note: @js(old('admin_note', '')) },
            openReject(id, name, action) { this.rej = { open: true, id: id, name: name, action: action, note: '' }; this.$nextTick(() => this.$refs.note.focus()); },
            closeReject() { this.rej.open = false; } }"
        @keydown.escape.window="rej.open && closeReject()">

        <div>
            <h1 class="ad-h1">Certificates</h1>
            <p class="ad-muted ad-sm" style="margin:8px 0 0; max-width:70ch">
                A student who finished a course sends the name to print and a Telegram username.
                <strong style="color:var(--text)">Open their Telegram, check it is really them</strong>, then approve.
                Approving issues the certificate in exactly the name they typed.
            </p>
        </div>

        <nav class="ad-seg" aria-label="Filter by status" style="justify-self:start; max-width:100%">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('admin.certificates', $key === 'pending' ? [] : ['status' => $key]) }}" @if ($status === $key) aria-current="page" @endif>
                    {{ $label }} <span>{{ number_format($counts[$key] ?? 0) }}</span>
                </a>
            @endforeach
        </nav>

        @if ($requests->isEmpty())
            <div class="ad-card" style="padding:56px 24px; text-align:center">
                <span class="ad-kpi-icon" style="margin:0 auto 16px"><x-admin.icon name="award" /></span>
                <p class="ad-h2" style="font-size:17px">
                    {{ $status === 'pending' ? 'Nothing is waiting' : 'No requests here' }}
                </p>
                <p class="ad-muted ad-sm" style="margin:6px auto 0; max-width:46ch">
                    When a student finishes a course and asks for a certificate, the request shows up here.
                </p>
            </div>
        @else
            <div class="ad-card">
                <div class="ad-scroll-x">
                    <table class="ad-table">
                        <thead>
                            <tr>
                                <th>Name on certificate</th>
                                <th>Telegram</th>
                                <th>Course</th>
                                <th>Account</th>
                                <th>Status</th>
                                <th style="text-align:right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requests as $r)
                                @php [$tone, $label] = $badge[$r->status] ?? ['', ucfirst($r->status)]; @endphp
                                <tr>
                                    <td class="ad-col-main">
                                        <span class="ad-row-title">{{ $r->real_name }}</span>
                                        <span class="ad-muted ad-xs" style="display:block">Sent {{ $r->updated_at->format('j M Y, H:i') }}</span>
                                    </td>
                                    <td class="ad-sm">
                                        <a href="{{ $r->telegramUrl() }}" target="_blank" rel="noopener noreferrer"
                                            style="color:var(--accent); font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:4px">
                                            &#64;{{ $r->telegram_username }}
                                            <x-admin.icon name="external" class="ad-i16" />
                                        </a>
                                    </td>
                                    <td class="ad-sm" style="white-space:normal; min-width:140px">{{ $r->course?->localized('title') ?? '(course removed)' }}</td>
                                    <td class="ad-sm" style="white-space:normal; min-width:150px">
                                        @if ($r->user)
                                            <a href="{{ route('admin.users.show', $r->user) }}" class="ad-row-link">{{ $r->user->name }}</a>
                                            <span class="ad-muted ad-xs" style="display:block">{{ $r->user->email }}</span>
                                        @else
                                            <span class="ad-muted">(account removed)</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="display:grid; justify-items:start; gap:4px">
                                            <span class="ad-badge {{ $tone }}">{{ $label }}</span>
                                            @if ($r->status === 'rejected' && $r->admin_note)
                                                <span class="ad-muted ad-xs" style="max-width:28ch; white-space:normal">{{ $r->admin_note }}</span>
                                            @endif
                                            @if ($r->reviewed_at && $r->status !== 'pending')
                                                <span class="ad-muted ad-xs">{{ $r->reviewer?->name ?? 'An admin' }} · {{ $r->reviewed_at->format('j M') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:8px; justify-content:flex-end; align-items:flex-start; white-space:nowrap">
                                            @if ($r->status !== 'approved')
                                                <form method="POST" action="{{ route('admin.certificates.approve', $r) }}"
                                                    data-confirm="Make sure you have checked {{ $r->real_name }} on Telegram. The certificate is printed in exactly the name they typed."
                                                    data-confirm-title="Issue a certificate to {{ $r->real_name }}?" data-confirm-ok="Approve" data-confirm-tone="primary">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="ad-btn ad-btn-sm ad-btn-primary">
                                                        <x-admin.icon name="check" class="ad-i16" /> Approve
                                                    </button>
                                                </form>

                                                <button type="button" class="ad-btn ad-btn-sm"
                                                    @click="openReject(@js($r->id), @js($r->real_name), @js(route('admin.certificates.reject', $r)))">Reject</button>
                                            @else
                                                <span class="ad-muted ad-xs" style="align-self:center">Certificate issued</span>
                                                <form method="POST" action="{{ route('admin.certificates.revoke', $r) }}"
                                                    data-confirm="The certificate for {{ $r->real_name }} is withdrawn and the request goes back to Waiting, so you can approve or reject it again."
                                                    data-confirm-title="Take back this approval?" data-confirm-ok="Take back" data-confirm-tone="danger">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="ad-btn ad-btn-sm">
                                                        <x-admin.icon name="undo" class="ad-i16" /> Undo
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div>{{ $requests->links() }}</div>
        @endif

        {{-- Reject: a proper dialog (a pop-over inside the table was clipped by it). --}}
        <div class="ad-modal-back" x-show="rej.open" x-cloak x-transition.opacity.duration.150ms @click.self="closeReject()">
            <div class="ad-modal" role="dialog" aria-modal="true" aria-labelledby="rej-title" style="max-width:460px"
                x-show="rej.open" x-transition:enter="ad-pop-enter" x-transition:enter-start="ad-pop-from" x-transition:enter-end="ad-pop-to"
                x-transition:leave="ad-pop-leave" x-transition:leave-start="ad-pop-to" x-transition:leave-end="ad-pop-from">
                <form method="POST" :action="rej.action">
                    @csrf @method('PATCH')
                    <div style="padding:22px 22px 4px">
                        <h2 id="rej-title" class="ad-h2" style="font-size:17px">Reject this request?</h2>
                        <p class="ad-muted ad-sm" style="margin:4px 0 0; overflow-wrap:anywhere">
                            For <strong x-text="rej.name"></strong>. The student reads your reason and can send it again.
                        </p>
                    </div>
                    <div style="padding:16px 22px 4px">
                        <label class="ad-label" for="rej-note">Reason</label>
                        <textarea id="rej-note" x-ref="note" x-model="rej.note" name="admin_note" class="ad-textarea" rows="4" maxlength="500" required
                            placeholder="e.g. The name does not match your ID."></textarea>
                        @error('admin_note')
                            <p class="ad-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div style="display:flex; flex-wrap:wrap; justify-content:flex-end; gap:10px; padding:16px 22px 22px">
                        <button type="button" class="ad-btn" @click="closeReject()">Cancel</button>
                        <button type="submit" class="ad-btn ad-btn-danger-solid">Reject request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</x-admin-layout>
