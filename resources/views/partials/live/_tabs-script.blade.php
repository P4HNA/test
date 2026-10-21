{{--
    Filter tabs on the live-design pages: the highlight springs to the
    clicked tab like jelly (squash and stretch, a soft trail, a shimmer and a
    few sparks) and the matching items show. Markup:

        <div class="hm-tabs" data-jelly-tabs data-target="#grid [data-category]">
            <div class="hm-tabs-track" role="tablist">
                <span class="hm-jelly-trail"></span>
                <span class="hm-jelly"><span class="hm-jelly-blob"></span><span class="hm-sparks"></span></span>
                <button role="tab" data-value="__all">All</button> ...
            </div>
        </div>

    Tabs with data-href navigate instead of filtering (server-side filters).
    Every change also fires a bubbling `jelly:change` event ({ value }) for
    pages that combine the tab with other filters (leave out data-target).
--}}
<script>
    (function () {
        'use strict';
        var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        function restart(el, cls) { if (!el) return; el.classList.remove(cls); void el.offsetWidth; el.classList.add(cls); }

        /* ---------- jelly filter tabs ----------
           The pill springs to the clicked tab with overshoot, squashes and
           stretches as it lands, a soft trail follows it, a shimmer sweeps
           across and a few sparks pop out. */
        var SPARKS = [[-29, -20], [29, -20], [-38, 3], [38, 3], [-19, 22], [19, 22]];
        document.querySelectorAll('[data-jelly-tabs]').forEach(function (root) {
            var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));
            var pill = root.querySelector('.hm-jelly');
            var trail = root.querySelector('.hm-jelly-trail');
            var blob = root.querySelector('.hm-jelly-blob');
            var sparks = root.querySelector('.hm-sparks');
            var items = function () { return root.hasAttribute('data-target') ? document.querySelectorAll(root.getAttribute('data-target')) : []; };
            var active = Math.max(0, tabs.findIndex(function (t) { return t.getAttribute('aria-selected') === 'true'; }));

            function place(animate) {
                var t = tabs[active];
                if (!t) return;
                if (!animate) root.classList.add('is-static');
                [pill, trail].forEach(function (el) {
                    el.style.width = t.offsetWidth + 'px';
                    el.style.transform = 'translateX(' + t.offsetLeft + 'px)';
                });
                if (!animate) { void root.offsetWidth; root.classList.remove('is-static'); }
            }

            function burst() {
                sparks.innerHTML = '';
                if (reduce) return;
                SPARKS.forEach(function (d) {
                    var s = document.createElement('i');
                    s.style.setProperty('--dx', d[0] + 'px');
                    s.style.setProperty('--dy', d[1] + 'px');
                    sparks.appendChild(s);
                });
            }

            function filter(value) {
                items().forEach(function (el) {
                    var show = value === '__all' || el.getAttribute('data-category') === value;
                    if (show && el.hidden) { el.hidden = false; restart(el, 'is-in'); }
                    else if (!show) { el.hidden = true; }
                });
            }

            function select(i, focus) {
                if (i === active) return;
                tabs[active].setAttribute('aria-selected', 'false');
                tabs[active].tabIndex = -1;
                active = i;
                var t = tabs[i];
                t.setAttribute('aria-selected', 'true');
                t.tabIndex = 0;
                if (focus) t.focus();
                place(true);
                restart(blob, 'is-jelly');
                restart(t.querySelector('span'), 'is-pop');
                burst();
                var href = t.getAttribute('data-href');
                if (href) { setTimeout(function () { window.location.href = href; }, reduce ? 0 : 260); return; }
                filter(t.getAttribute('data-value'));
                root.dispatchEvent(new CustomEvent('jelly:change', { bubbles: true, detail: { value: t.getAttribute('data-value') } }));
                if (root.scrollWidth > root.clientWidth) {
                    root.scrollTo({ left: t.offsetLeft - (root.clientWidth - t.offsetWidth) / 2, behavior: reduce ? 'auto' : 'smooth' });
                }
            }

            tabs.forEach(function (t, i) { t.addEventListener('click', function () { select(i); }); });
            root.addEventListener('keydown', function (e) {
                if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
                e.preventDefault();
                select((active + (e.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length, true);
            });

            place(false);
            if (window.ResizeObserver) new ResizeObserver(function () { place(false); }).observe(root.querySelector('.hm-tabs-track'));
            if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { place(false); });
        });
    })();
</script>
