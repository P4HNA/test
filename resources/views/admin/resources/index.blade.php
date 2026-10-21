<x-admin-layout title="Resources" active="resources">

    <div style="display:grid; gap:24px">

        <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:16px">
            <div>
                <h1 class="ad-h1">Resources</h1>
                <p class="ad-muted ad-sm" style="margin:8px 0 0; max-width:66ch">
                    Design files students can download from the
                    <a href="{{ route('resources.index') }}" class="ad-link" target="_blank" rel="noopener">Resources page</a>:
                    mockups, presets, fonts, templates. Upload a file or paste a link to where it lives.
                </p>
            </div>
            <a href="{{ route('admin.resources.create') }}" class="ad-btn ad-btn-primary">
                <x-admin.icon name="plus" class="ad-i18" /> Add resource
            </a>
        </div>

        @if ($resources->isEmpty())
            <div class="ad-card" style="padding:56px 24px; text-align:center">
                <span class="ad-kpi-icon" style="margin:0 auto 16px"><x-admin.icon name="archive" /></span>
                <p class="ad-h2" style="font-size:17px">No resources yet</p>
                <p class="ad-muted ad-sm" style="margin:6px auto 20px; max-width:46ch">
                    Share your PSD mockups, Illustrator kits, Khmer fonts or Figma files. Students see them on the Resources page.
                </p>
                <a href="{{ route('admin.resources.create') }}" class="ad-btn ad-btn-primary">Add the first resource</a>
            </div>
        @else
            <div class="ad-callout" role="status">
                <x-admin.icon name="download" class="ad-i18" style="flex:none; margin-top:1px; color:var(--text-3)" />
                <div>
                    <strong>{{ number_format($totalDownloads) }} {{ Str::plural('download', $totalDownloads) }}</strong>
                    <span class="ad-muted">across {{ $resources->count() }} {{ Str::plural('resource', $resources->count()) }}, {{ $resources->where('is_published', true)->count() }} visible to students.</span>
                </div>
            </div>

            <div class="ad-card">
                <div class="ad-scroll-x">
                    <table class="ad-table">
                        <thead>
                            <tr>
                                <th>Resource</th>
                                <th>Category</th>
                                <th>File</th>
                                <th style="text-align:right">Downloads</th>
                                <th style="text-align:right">Bookmarks</th>
                                <th>Status</th>
                                <th style="text-align:right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($resources as $r)
                                <tr>
                                    <td class="ad-col-main">
                                        <a href="{{ route('admin.resources.edit', $r) }}" class="ad-row-link" style="display:flex; align-items:center; gap:12px">
                                            <img src="{{ $r->pictureSrc() }}" alt="" width="64" height="40" loading="lazy" style="width:64px; height:40px; object-fit:cover; border-radius:6px; flex:none; background:var(--soft)">
                                            <span style="min-width:0">
                                                <span class="ad-row-title" style="display:block">{{ $r->title_en }}</span>
                                                <span class="ad-muted ad-xs" style="display:block">{{ $r->software }} · {{ $r->type }}</span>
                                            </span>
                                        </a>
                                    </td>
                                    <td class="ad-sm">{{ $r->category }}</td>
                                    <td class="ad-sm">
                                        @if (filled($r->file_path))
                                            <span style="display:block">{{ Str::limit($r->file_name, 28) }}</span>
                                            <span class="ad-muted ad-xs">{{ $r->sizeLabel() }}</span>
                                        @elseif (filled($r->external_url))
                                            <span class="ad-badge blue">Link</span>
                                        @else
                                            <span class="ad-badge warn">Missing</span>
                                        @endif
                                    </td>
                                    <td class="ad-sm" style="text-align:right">{{ number_format($r->downloads_count) }}</td>
                                    <td class="ad-sm" style="text-align:right">{{ number_format($r->bookmarked_by_count) }}</td>
                                    <td>
                                        <div style="display:grid; justify-items:start; gap:4px">
                                            <span class="ad-badge {{ $r->is_published ? 'ok dot' : '' }}">{{ $r->is_published ? 'Visible' : 'Hidden' }}</span>
                                            @if ($r->is_featured)
                                                <span class="ad-xs" style="color:var(--accent); font-weight:600">Featured</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:6px; justify-content:flex-end; align-items:center; white-space:nowrap">
                                            <a href="{{ route('admin.resources.edit', $r) }}" class="ad-btn ad-btn-sm">
                                                <x-admin.icon name="pencil" class="ad-i16" /> Edit
                                            </a>
                                            <form method="POST" action="{{ route('admin.resources.toggle', $r) }}">
                                                @csrf @method('PATCH')
                                                <button type="submit" class="ad-link-btn" aria-label="{{ $r->is_published ? 'Hide' : 'Show' }} {{ $r->title_en }}">
                                                    {{ $r->is_published ? 'Hide' : 'Show' }}
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
