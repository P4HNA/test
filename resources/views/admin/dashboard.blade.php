<x-admin-layout title="Dashboard" active="dashboard" :live="6000">

    <div x-data="dashboard(@js($initial))" style="display:grid; gap:24px">

        {{-- ---------- Page header ---------- --}}
        <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:16px">
            <div>
                <h1 class="ad-h1">Welcome back, {{ Str::before(auth()->user()->name, ' ') }}!</h1>
                <p class="ad-muted ad-sm" style="margin:8px 0 0">
                    This page updates by itself.
                    <span x-text="ago < 2 ? 'Just updated.' : 'Updated ' + ago + ' seconds ago.'">Just updated.</span>
                </p>
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:10px">
                <a href="{{ route('admin.users') }}" class="ad-btn"><x-admin.icon name="users" class="ad-i18" /> Students</a>
                <a href="{{ route('admin.courses.create') }}" class="ad-btn ad-btn-primary"><x-admin.icon name="plus" class="ad-i18" /> New course</a>
            </div>
        </div>

        {{-- ---------- Needs attention ----------
             The first question an admin has: is anything waiting on me? Each
             line says what is wrong, why it matters, and links to the fix. --}}
        <section class="ad-card" aria-labelledby="attention-title">
            <div class="ad-card-h" style="padding-bottom:4px">
                <h2 id="attention-title" class="ad-h2">Needs attention</h2>
                <span class="ad-badge" :class="d.attention.length ? 'warn' : 'ok'"
                    x-text="d.attention.length ? d.attention.length + (d.attention.length === 1 ? ' item' : ' items') : 'All clear'"></span>
            </div>
            <div style="padding:6px 8px 10px" aria-live="polite">
                <template x-for="a in d.attention" :key="a.key">
                    <div class="ad-attn">
                        <span class="ad-attn-icon" :class="a.tone === 'warn' ? 'is-warn' : ''">
                            <template x-if="a.tone === 'warn'"><span><x-admin.icon name="alert" class="ad-i18" /></span></template>
                            <template x-if="a.tone !== 'warn'"><span><x-admin.icon name="eye" class="ad-i18" /></span></template>
                        </span>
                        <p class="ad-sm" style="margin:0; flex:1; min-width:0"><strong x-text="fmt(a.count)"></strong> <span x-text="a.text"></span></p>
                        <a :href="a.url" class="ad-btn ad-btn-sm"><span x-text="a.action"></span> <x-admin.icon name="chevron-right" class="ad-i16" /></a>
                    </div>
                </template>
                <div class="ad-attn" x-show="!d.attention.length">
                    <span class="ad-attn-icon is-ok"><x-admin.icon name="check" class="ad-i18" /></span>
                    <p class="ad-sm ad-muted" style="margin:0">Nothing is waiting on you. No payments to review, every lesson has a video, and every course is published.</p>
                </div>
            </div>
        </section>

        {{-- ---------- KPI cards ---------- --}}
        <div class="ad-grid ad-cols-sm-2 ad-cols-xl-4">
            @foreach ($initial['kpis'] as $key => $kpi)
            <div class="ad-card" style="padding:18px 20px" :class="{ 'ad-flash': flash['{{ $key }}'] }">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px">
                    <div>
                        <p class="ad-sm ad-muted" style="margin:0; font-weight:600">{{ $kpi['label'] }}</p>
                        <p class="ad-kpi-value" x-text="fmt(d.kpis['{{ $key }}'].value)">{{ $kpi['value'] }}</p>
                    </div>
                    <span class="ad-kpi-icon"><x-admin.icon :name="$kpi['icon']" /></span>
                </div>
                <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:12px; margin-top:16px">
                    <div style="min-width:0">
                        <template x-if="d.kpis['{{ $key }}'].delta !== null">
                            <span class="ad-chip" :class="d.kpis['{{ $key }}'].delta >= 0 ? 'up' : 'down'"
                                x-text="(d.kpis['{{ $key }}'].delta > 0 ? '+' : '') + d.kpis['{{ $key }}'].delta + '%'"></span>
                        </template>
                        <p class="ad-muted ad-xs" style="margin:6px 0 0" x-text="d.kpis['{{ $key }}'].foot">{{ $kpi['foot'] }}</p>
                    </div>
                    <div class="ad-spark" x-html="spark(d.kpis['{{ $key }}'].spark)"></div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- ---------- Chart and categories ---------- --}}
        <div class="ad-grid ad-cols-xl-3">
            <div class="ad-card ad-span-xl-2">
                <div class="ad-card-h">
                    <div>
                        <p class="ad-h2">Students and enrollments</p>
                        <p class="ad-muted ad-xs" style="margin:2px 0 0" x-text="'Last ' + d.chart.days + ' days'"></p>
                    </div>
                    <div style="display:inline-flex; gap:4px; padding:3px; border-radius:11px; background:var(--soft)">
                        <template x-for="n in [7, 14, 30]" :key="n">
                            <button type="button" class="ad-btn ad-btn-sm" @click="setRange(n)" x-text="n + ' days'"
                                :style="days === n ? 'background:var(--card)' : 'background:transparent; border-color:transparent'"></button>
                        </template>
                    </div>
                </div>
                <div class="ad-card-b">
                    <div style="display:flex; flex-wrap:wrap; gap:18px; margin-bottom:8px" class="ad-sm">
                        <span style="display:inline-flex; align-items:center; gap:8px">
                            <span style="width:10px; height:10px; border-radius:3px; background:var(--accent)"></span>
                            <strong x-text="d.chart.totals[0]"></strong> <span class="ad-muted">new students</span>
                        </span>
                        <span style="display:inline-flex; align-items:center; gap:8px">
                            <span style="width:10px; height:10px; border-radius:3px; background:var(--blue)"></span>
                            <strong x-text="d.chart.totals[1]"></strong> <span class="ad-muted">enrollments</span>
                        </span>
                    </div>
                    <div class="ad-chart" x-ref="area" role="img" aria-label="Chart of new students and enrollments per day"></div>
                </div>
            </div>

            <div class="ad-card">
                <div class="ad-card-h">
                    <p class="ad-h2">Courses by category</p>
                </div>
                <div class="ad-card-b" style="display:flex; flex-direction:column; align-items:center; gap:18px">
                    <div x-ref="donut" style="width:168px; height:168px"></div>
                    <ul style="list-style:none; margin:0; padding:0; width:100%; display:grid; gap:10px">
                        <template x-for="(l, i) in d.categories" :key="l.label">
                            <li class="ad-sm" style="display:flex; align-items:center; gap:10px">
                                <span style="width:10px; height:10px; border-radius:3px" :style="'background:var(--c' + ((i % 5) + 1) + ')'"></span>
                                <span style="flex:1" x-text="l.label"></span>
                                <strong x-text="l.count"></strong>
                            </li>
                        </template>
                    </ul>
                    <p class="ad-muted ad-sm" style="margin:0" x-show="!d.categories.length">No courses yet.</p>
                </div>
            </div>
        </div>

        {{-- ---------- Latest enrollments, video library, quick actions ---------- --}}
        <div class="ad-grid ad-cols-xl-3">
            <div class="ad-card ad-span-xl-2">
                <div class="ad-card-h">
                    <div>
                        <p class="ad-h2">Latest enrollments</p>
                        <p class="ad-muted ad-xs" style="margin:2px 0 0">Appears here the moment a student joins a course.</p>
                    </div>
                    <a href="{{ route('admin.users') }}" class="ad-btn ad-btn-sm">All students</a>
                </div>
                <div style="padding:10px 10px 14px">
                    <template x-for="a in d.activity" :key="a.id">
                        <a :href="a.url" class="ad-menu-item" style="gap:12px; padding:10px; border-radius:10px">
                            <span class="ad-mark" :style="'background:' + a.bg + '; color:' + a.fg" x-text="a.mark"></span>
                            <div style="min-width:0; flex:1">
                                <p style="margin:0"><strong x-text="a.name"></strong> <span class="ad-muted">joined</span></p>
                                <p class="ad-muted ad-xs" style="margin:2px 0 0; overflow:hidden; text-overflow:ellipsis" x-text="a.course"></p>
                            </div>
                            <span class="ad-muted ad-xs" style="white-space:nowrap" x-text="a.when"></span>
                        </a>
                    </template>
                    <p class="ad-muted ad-sm" style="margin:0; padding:28px 12px; text-align:center" x-show="!d.activity.length">No enrollments yet.</p>
                </div>
            </div>

            <div style="display:grid; gap:20px; align-content:start">
                <div class="ad-card">
                    <div class="ad-card-h">
                        <p class="ad-h2">Video library</p>
                        <span class="ad-kpi-icon" style="width:34px; height:34px; border-radius:10px"><x-admin.icon name="database" class="ad-i18" /></span>
                    </div>
                    <div class="ad-card-b">
                        <p class="ad-kpi-value" style="margin:0" x-text="d.storage.used"></p>
                        <p class="ad-muted ad-sm" style="margin:6px 0 0"
                            x-text="d.storage.files + (d.storage.files === 1 ? ' video file uploaded' : ' video files uploaded')"></p>
                        <p class="ad-muted ad-xs" style="margin:14px 0 0" x-show="d.storage.free" x-text="d.storage.free + ' free on this server'"></p>
                    </div>
                </div>

                <div class="ad-card">
                    <div class="ad-card-h">
                        <p class="ad-h2">Quick actions</p>
                    </div>
                    <div class="ad-card-b" style="display:grid; gap:10px">
                        <a href="{{ route('admin.courses.create') }}" class="ad-btn" style="justify-content:flex-start"><x-admin.icon name="plus" class="ad-i18" /> Create a course</a>
                        <a href="{{ route('admin.courses') }}" class="ad-btn" style="justify-content:flex-start"><x-admin.icon name="upload" class="ad-i18" /> Upload a lesson video</a>
                        <a href="{{ route('admin.users') }}" class="ad-btn" style="justify-content:flex-start"><x-admin.icon name="key" class="ad-i18" /> Reset a student password</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---------- Popular courses and new students ---------- --}}
        <div class="ad-grid ad-cols-xl-3">
            <div class="ad-card ad-span-xl-2">
                <div class="ad-card-h">
                    <div>
                        <p class="ad-h2">Most popular courses</p>
                        <p class="ad-muted ad-xs" style="margin:2px 0 0">Ranked by enrollments. Open one to edit it or add videos.</p>
                    </div>
                    <a href="{{ route('admin.courses') }}" class="ad-btn ad-btn-sm">All courses</a>
                </div>
                <div style="padding:10px 10px 14px">
                    <template x-for="c in d.courses" :key="c.id">
                        <a :href="c.url" class="ad-menu-item" style="gap:14px; padding:10px; border-radius:10px">
                            <span class="ad-mark" :style="'background:' + c.bg + '; color:' + c.fg" x-text="c.mark"></span>
                            <span style="min-width:0; flex:1">
                                <span style="display:block; font-weight:600" x-text="c.title"></span>
                                <span class="ad-muted ad-xs" style="display:block" x-text="c.category + ', ' + c.lessons + (c.lessons === 1 ? ' lesson' : ' lessons')"></span>
                            </span>
                            <span class="ad-only-sm" style="width:140px">
                                <span class="ad-bar" style="display:block"><span :style="'width:' + pct(c) + '%'"></span></span>
                                <span class="ad-muted ad-xs" style="display:block; margin-top:4px" x-text="c.enrollments + (c.enrollments === 1 ? ' student' : ' students')"></span>
                            </span>
                            <span class="ad-badge" :class="c.free ? 'ok' : 'blue'" x-text="c.free ? 'Free' : c.price_label"></span>
                        </a>
                    </template>
                    <p class="ad-muted ad-sm" style="margin:0; padding:28px 12px; text-align:center" x-show="!d.courses.length">
                        No courses yet. <a href="{{ route('admin.courses.create') }}" style="color:var(--accent); font-weight:600">Create the first one.</a>
                    </p>
                </div>
            </div>

            <div class="ad-card">
                <div class="ad-card-h">
                    <div>
                        <p class="ad-h2">New students</p>
                        <p class="ad-muted ad-xs" style="margin:2px 0 0">Latest sign-ups</p>
                    </div>
                </div>
                <div style="padding:10px 10px 14px">
                    <template x-for="u in d.recent" :key="u.id">
                        <a :href="u.url" class="ad-menu-item" style="gap:12px; padding:10px; border-radius:10px">
                            <span class="ad-avatar" x-text="u.name.charAt(0).toUpperCase()"></span>
                            <div style="min-width:0; flex:1">
                                <p style="margin:0; font-weight:600; overflow:hidden; text-overflow:ellipsis" x-text="u.name"></p>
                                <p class="ad-muted ad-xs" style="margin:0; overflow:hidden; text-overflow:ellipsis" x-text="u.email"></p>
                            </div>
                            <span class="ad-muted ad-xs" style="white-space:nowrap" x-text="u.when"></span>
                        </a>
                    </template>
                    <p class="ad-muted ad-sm" style="margin:0; padding:28px 12px; text-align:center" x-show="!d.recent.length">No students yet.</p>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        var AdminCharts = {
            /* Smooth two-line chart with a hover tooltip, drawn as plain SVG so it needs no library.
               s = { labels: [...], series: [ { name, values: [...] }, { name, values: [...] } ] } */
            area: function(el, s) {
                if (!el || !s || !s.series || !s.series.length || !s.series[0].values.length) return;

                var w = Math.max(280, el.clientWidth), h = 260, L = 34, R = 10, T = 14, B = 28;
                var n = s.series[0].values.length;
                var all = [];
                s.series.forEach(function(x) { all = all.concat(x.values); });
                var top = Math.ceil(Math.max.apply(null, all.concat([3])) / 3) * 3;   // 0, 1/3, 2/3, top: whole numbers
                var iw = w - L - R, ih = h - T - B;
                var x = function(i) { return L + (n === 1 ? iw / 2 : (i * iw) / (n - 1)); };
                var y = function(v) { return T + ih - (v / top) * ih; };

                var curve = function(values) {
                    var pts = values.map(function(v, i) { return [x(i), y(v)]; });
                    var d = 'M' + pts[0][0] + ',' + pts[0][1];
                    for (var i = 1; i < pts.length; i++) {
                        var cx = (pts[i - 1][0] + pts[i][0]) / 2;
                        d += ' C' + cx + ',' + pts[i - 1][1] + ' ' + cx + ',' + pts[i][1] + ' ' + pts[i][0] + ',' + pts[i][1];
                    }
                    return { d: d, pts: pts };
                };

                var first = curve(s.series[0].values);
                var second = s.series[1] ? curve(s.series[1].values) : null;
                var area = first.d + ' L' + first.pts[n - 1][0] + ',' + (T + ih) + ' L' + first.pts[0][0] + ',' + (T + ih) + ' Z';

                var grid = '';
                for (var t = 0; t <= 3; t++) {
                    var gy = y((top * t) / 3);
                    grid += '<line class="ch-grid" x1="' + L + '" x2="' + (w - R) + '" y1="' + gy + '" y2="' + gy + '"/>' +
                        '<text class="ch-axis" x="' + (L - 8) + '" y="' + (gy + 4) + '" text-anchor="end">' + Math.round((top * t) / 3) + '</text>';
                }

                var step = Math.ceil(n / Math.max(2, Math.floor(iw / 72)));
                var labels = '';
                for (var k = 0; k < n; k += step) {
                    labels += '<text class="ch-axis" x="' + x(k) + '" y="' + (h - 8) + '" text-anchor="middle">' + s.labels[k] + '</text>';
                }

                el.innerHTML =
                    '<svg viewBox="0 0 ' + w + ' ' + h + '" width="' + w + '" height="' + h + '">' +
                    '<defs><linearGradient id="adArea" x1="0" y1="0" x2="0" y2="1"><stop offset="0" class="ch-stop-a"/><stop offset="1" class="ch-stop-b"/></linearGradient></defs>' +
                    grid + '<path d="' + area + '" fill="url(#adArea)"/>' +
                    (second ? '<path class="ch-line2" d="' + second.d + '"/>' : '') +
                    '<path class="ch-line" d="' + first.d + '"/>' + labels +
                    '<line class="ch-guide" y1="' + T + '" y2="' + (T + ih) + '" style="display:none"/>' +
                    '<circle class="ch-dot" r="5" style="display:none"/><circle class="ch-dot2" r="5" style="display:none"/></svg>' +
                    '<div class="ad-tip" style="display:none"></div>';

                var svg = el.querySelector('svg'), guide = svg.querySelector('.ch-guide'), dot = svg.querySelector('.ch-dot'),
                    dot2 = svg.querySelector('.ch-dot2'), tip = el.querySelector('.ad-tip');

                svg.onmousemove = function(ev) {
                    var box = svg.getBoundingClientRect();
                    var i = Math.round(((ev.clientX - box.left) * (w / box.width) - L) / iw * (n - 1));
                    i = Math.max(0, Math.min(n - 1, i));
                    guide.setAttribute('x1', first.pts[i][0]); guide.setAttribute('x2', first.pts[i][0]);
                    dot.setAttribute('cx', first.pts[i][0]); dot.setAttribute('cy', first.pts[i][1]);
                    if (second) { dot2.setAttribute('cx', second.pts[i][0]); dot2.setAttribute('cy', second.pts[i][1]); }
                    tip.textContent = s.labels[i] + ':  ' + s.series.map(function(x) { return x.values[i] + ' ' + x.name.toLowerCase(); }).join(',  ');
                    tip.style.left = first.pts[i][0] * (box.width / w) + 'px';
                    tip.style.top = Math.min.apply(null, [first.pts[i][1], second ? second.pts[i][1] : h]) * (box.width / w) + 'px';
                    guide.style.display = dot.style.display = tip.style.display = '';
                    dot2.style.display = second ? '' : 'none';
                };
                svg.onmouseleave = function() { guide.style.display = dot.style.display = dot2.style.display = tip.style.display = 'none'; };
            },

            donut: function(el, items) {
                if (!el) return;
                var size = 168, r = 62, c = 2 * Math.PI * r, mid = size / 2;
                var total = items.reduce(function(a, b) { return a + b.count; }, 0);
                var arcs = '', offset = 0;

                items.forEach(function(it, i) {
                    var len = (it.count / total) * c;
                    var dash = Math.max(0, len - (items.length > 1 ? 3 : 0));
                    arcs += '<circle cx="' + mid + '" cy="' + mid + '" r="' + r + '" fill="none" stroke-width="18" style="stroke:var(--c' + ((i % 5) + 1) + ')"' +
                        ' stroke-dasharray="' + dash + ' ' + (c - dash) + '" stroke-dashoffset="' + (-offset) + '" transform="rotate(-90 ' + mid + ' ' + mid + ')"/>';
                    offset += len;
                });

                el.innerHTML = '<svg viewBox="0 0 ' + size + ' ' + size + '" width="' + size + '" height="' + size + '" role="img" aria-label="Courses by category">' +
                    '<circle class="ch-donut-bg" cx="' + mid + '" cy="' + mid + '" r="' + r + '"/>' + arcs +
                    '<text class="ch-center" x="' + mid + '" y="' + (mid + 6) + '" text-anchor="middle">' + total + '</text>' +
                    '<text class="ch-axis" x="' + mid + '" y="' + (mid + 24) + '" text-anchor="middle">' + (total === 1 ? 'course' : 'courses') + '</text></svg>';
            }
        };

        function dashboard(initial) {
            return {
                d: initial,
                days: initial.chart.days,
                ago: 0,
                flash: {},

                init() {
                    var self = this;
                    window.addEventListener('admin-live', function(e) { self.receive(e.detail); });
                    window.addEventListener('resize', function() { self.draw(); });
                    setInterval(function() { self.ago++; }, 1000);
                    this.$nextTick(function() { self.draw(); });
                },

                /* New numbers arrived: flash the cards whose value changed, then redraw the charts. */
                receive(next) {
                    var self = this;
                    Object.keys(next.kpis).forEach(function(k) {
                        var was = self.d.kpis[k];
                        if (was && was.value !== next.kpis[k].value) {
                            self.flash[k] = true;
                            setTimeout(function() { self.flash[k] = false; }, 1500);
                        }
                    });
                    this.d = next;
                    this.ago = 0;
                    this.days = next.chart.days;
                    this.$nextTick(function() { self.draw(); });
                },

                draw() {
                    AdminCharts.area(this.$refs.area, this.d.chart);
                    AdminCharts.donut(this.$refs.donut, this.d.categories);
                },

                setRange(n) {
                    this.days = n;
                    AdminLive.setDays(n);
                },

                fmt(n) { return new Intl.NumberFormat().format(n); },

                pct(c) {
                    var max = Math.max.apply(null, this.d.courses.map(function(x) { return x.enrollments; }).concat([1]));
                    return Math.round((c.enrollments / max) * 100);
                },

                spark(values) {
                    if (!values || values.length < 2) return '';
                    var max = Math.max.apply(null, values.concat([1]));
                    var pts = values.map(function(v, i) {
                        return ((i / (values.length - 1)) * 96).toFixed(1) + ',' + (29 - (v / max) * 26).toFixed(1);
                    });
                    return '<svg viewBox="0 0 96 32" width="96" height="32" aria-hidden="true"><polyline fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" points="' + pts.join(' ') + '"/></svg>';
                }
            };
        }
    </script>
    @endpush
</x-admin-layout>
