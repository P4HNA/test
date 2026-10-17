<x-admin-layout title="Promotions" active="promotions">

    @php
        $statusBadge = [
            'live' => ['ok dot', 'Live'],
            'scheduled' => ['blue', 'Scheduled'],
            'ended' => ['', 'Ended'],
            'off' => ['', 'Off'],
        ];
    @endphp

    <div style="display:grid; gap:24px">

        <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:16px">
            <div>
                <h1 class="ad-h1">Promotions</h1>
                <p class="ad-muted ad-sm" style="margin:8px 0 0; max-width:66ch">
                    A <strong style="color:var(--text)">discount</strong> lowers the price students pay at checkout while it runs.
                    An <strong style="color:var(--text)">announcement</strong> is a message only. The homepage shows one at a time.
                </p>
            </div>
            <a href="{{ route('admin.promotions.create') }}" class="ad-btn ad-btn-primary">
                <x-admin.icon name="plus" class="ad-i18" /> New promotion
            </a>
        </div>

        {{-- What visitors see right now: the first question anyone has on this page. --}}
        <div class="ad-callout" role="status">
            <x-admin.icon name="eye" class="ad-i18" style="flex:none; margin-top:1px; color:var(--text-3)" />
            <div>
                @if ($onHomepage)
                    <strong>On the homepage now:</strong> "{{ $onHomepage->title_en }}"
                    @if ($onHomepage->ends_at)
                        <span class="ad-muted">· until {{ $onHomepage->ends_at->format('j M, H:i') }}</span>
                    @endif
                @else
                    <strong>Nothing is promoted on the homepage.</strong>
                    <span class="ad-muted">Nothing live is set to show there, so visitors see the page as usual.</span>
                @endif
            </div>
        </div>

        @if ($promotions->isEmpty())
            <div class="ad-card" style="padding:56px 24px; text-align:center">
                <span class="ad-kpi-icon" style="margin:0 auto 16px"><x-admin.icon name="tag" /></span>
                <p class="ad-h2" style="font-size:17px">No promotions yet</p>
                <p class="ad-muted ad-sm" style="margin:6px auto 20px; max-width:46ch">
                    Run a sale on one course, a whole category or everything, or share news on the homepage and the Course Library.
                </p>
                <a href="{{ route('admin.promotions.create') }}" class="ad-btn ad-btn-primary">Create a promotion</a>
            </div>
        @else
            <div class="ad-card">
                <div class="ad-scroll-x">
                    <table class="ad-table">
                        <thead>
                            <tr>
                                <th>Promotion</th>
                                <th>Offer</th>
                                <th>Applies to</th>
                                <th>When</th>
                                <th>Status</th>
                                <th style="text-align:right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($promotions as $p)
                                @php
                                    $status = $p->status();
                                    [$tone, $label] = $statusBadge[$status];
                                    $isShowing = $onHomepage?->is($p);
                                @endphp
                                <tr>
                                    <td class="ad-col-main">
                                        <a href="{{ route('admin.promotions.edit', $p) }}" class="ad-row-link" style="display:block">
                                            <span class="ad-row-title">{{ $p->title_en }}</span>
                                            @if ($p->message_en)
                                                <span class="ad-muted ad-xs" style="display:block; max-width:40ch; overflow:hidden; text-overflow:ellipsis; white-space:nowrap">{{ $p->message_en }}</span>
                                            @endif
                                        </a>
                                    </td>
                                    <td>
                                        @if ($p->isDiscount())
                                            <span class="ad-badge accent">{{ $p->discount_percent }}% off</span>
                                        @else
                                            <span class="ad-badge">Announcement</span>
                                        @endif
                                    </td>
                                    <td class="ad-sm" style="white-space:normal; min-width:140px">{{ $p->targetLabel() }}</td>
                                    <td class="ad-sm">
                                        <span style="display:block">
                                            {{ $p->starts_at ? $p->starts_at->format('j M, H:i') : 'Right away' }}
                                        </span>
                                        <span class="ad-muted ad-xs" style="display:block">
                                            {{ $p->ends_at ? 'until '.$p->ends_at->format('j M, H:i') : 'until turned off' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:grid; justify-items:start; gap:4px">
                                            <span class="ad-badge {{ $tone }}">{{ $label }}</span>
                                            @if ($isShowing)
                                                <span class="ad-xs" style="color:var(--success-text); font-weight:600">On the homepage</span>
                                            @elseif ($status === 'live' && ! $p->show_on_home)
                                                <span class="ad-muted ad-xs">Not on the homepage</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:6px; justify-content:flex-end; align-items:center; white-space:nowrap">
                                            <a href="{{ route('admin.promotions.edit', $p) }}" class="ad-btn ad-btn-sm">
                                                <x-admin.icon name="pencil" class="ad-i16" /> Edit
                                            </a>
                                            <form method="POST" action="{{ route('admin.promotions.toggle', $p) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="ad-link-btn" aria-label="{{ $p->is_active ? 'Turn off' : 'Turn on' }} {{ $p->title_en }}">
                                                    {{ $p->is_active ? 'Turn off' : 'Turn on' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

</x-admin-layout>
