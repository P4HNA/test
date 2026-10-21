{{--
    Effects for the live-design pages; the looks are in _styles under "Effects kit".
    Everything is opt-in by class or attribute, and nothing waits on this script to
    be readable: only content still below the fold is held back for its reveal.
--}}
<script>
    (function () {
        'use strict';
        var root = document.documentElement;
        root.classList.add('fx-js');
        var still = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;
        var fine = window.matchMedia && matchMedia('(hover: hover) and (pointer: fine)').matches;

        /* ---- reveal on scroll, count-ups and filling bars ---- */
        function countUp(el) {
            var text = el.textContent;
            var m = text.match(/^(\D*)(\d[\d,]*)(.*)$/);
            if (!m) return;
            var to = parseInt(m[2].replace(/,/g, ''), 10);
            if (!to) return;
            var start = null, dur = Math.min(1400, 500 + to * 12);
            function step(t) {
                if (!start) start = t;
                var k = Math.min(1, (t - start) / dur);
                var v = Math.round(to * (1 - Math.pow(1 - k, 3)));
                el.textContent = m[1] + (m[2].indexOf(',') > -1 ? v.toLocaleString('en-US') : v) + m[3];
                if (k < 1) requestAnimationFrame(step);
            }
            requestAnimationFrame(step);
        }

        function arm() {
            if (still || !('IntersectionObserver' in window)) return;
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (!e.isIntersecting) return;
                    var el = e.target;
                    io.unobserve(el);
                    if (el.hasAttribute('data-fx-count')) { countUp(el); return; }
                    el.classList.remove('fx-wait');
                    if (el.hasAttribute('data-fx')) {
                        el.classList.add('fx-in');
                        el.addEventListener('animationend', function done() { el.classList.remove('fx-in'); el.removeEventListener('animationend', done); });
                    }
                });
            }, { rootMargin: '0px 0px -6% 0px', threshold: 0.06 });

            var fold = window.innerHeight * 0.94;
            document.querySelectorAll('[data-fx]').forEach(function (el) {
                if (el.dataset.fxArmed) return;
                el.dataset.fxArmed = '1';
                if (el.getBoundingClientRect().top < fold) return;
                var sibs = el.parentElement ? [].filter.call(el.parentElement.children, function (c) { return c.hasAttribute('data-fx'); }) : [];
                el.style.setProperty('--fx-d', (Math.max(0, sibs.indexOf(el)) % 4) * 80 + 'ms');
                el.classList.add('fx-wait');
                io.observe(el);
            });
            document.querySelectorAll('[data-fx-bar]').forEach(function (el) {
                if (el.dataset.fxArmed) return;
                el.dataset.fxArmed = '1';
                el.classList.add('fx-wait');
                io.observe(el);
            });
            document.querySelectorAll('[data-fx-count]').forEach(function (el) {
                if (el.dataset.fxArmed) return;
                el.dataset.fxArmed = '1';
                io.observe(el);
            });
        }
        arm();
        window.fxArm = arm;

        /* ---- spotlight and tilt follow the pointer ---- */
        var raf = 0, last = null;
        document.addEventListener('pointermove', function (e) {
            if (e.pointerType !== 'mouse') return;
            last = e;
            if (raf) return;
            raf = requestAnimationFrame(function () {
                raf = 0;
                var t = last.target;
                var spot = t.closest && t.closest('.fx-spot');
                if (spot) {
                    var r = spot.getBoundingClientRect();
                    spot.style.setProperty('--mx', (last.clientX - r.left) + 'px');
                    spot.style.setProperty('--my', (last.clientY - r.top) + 'px');
                }
                var tilt = !still && fine && t.closest && t.closest('.fx-tilt');
                if (tilt) {
                    var b = tilt.getBoundingClientRect();
                    var x = (last.clientX - b.left) / b.width - 0.5, y = (last.clientY - b.top) / b.height - 0.5;
                    tilt.style.setProperty('--rx', (-y * 7).toFixed(2) + 'deg');
                    tilt.style.setProperty('--ry', (x * 9).toFixed(2) + 'deg');
                }
                var par = !still && t.closest && t.closest('[data-fx-parallax]');
                if (par) {
                    var p = par.getBoundingClientRect();
                    par.style.setProperty('--px', (((last.clientX - p.left) / p.width) - 0.5).toFixed(3));
                    par.style.setProperty('--py', (((last.clientY - p.top) / p.height) - 0.5).toFixed(3));
                }
            });
        }, { passive: true });
        document.addEventListener('pointerout', function (e) {
            var tilt = e.target.closest && e.target.closest('.fx-tilt');
            if (tilt && !tilt.contains(e.relatedTarget)) { tilt.style.setProperty('--rx', '0deg'); tilt.style.setProperty('--ry', '0deg'); }
        });

        /* ---- ripple from where a button is pressed ---- */
        document.addEventListener('pointerdown', function (e) {
            var btn = e.target.closest && e.target.closest('.fx-btn');
            if (!btn || still) return;
            var r = btn.getBoundingClientRect(), size = Math.max(r.width, r.height) * 2.2;
            var dot = document.createElement('span');
            dot.className = 'fx-ripple';
            dot.style.cssText = 'width:' + size + 'px;height:' + size + 'px;left:' + (e.clientX - r.left - size / 2) + 'px;top:' + (e.clientY - r.top - size / 2) + 'px';
            btn.appendChild(dot);
            setTimeout(function () { dot.remove(); }, 700);
        });

        /* ---- a burst of sparks, for likes, bookmarks and wins ---- */
        window.fxBurst = function (el, color) {
            if (still || !el) return;
            var r = el.getBoundingClientRect();
            var box = document.createElement('span');
            box.className = 'fx-burst';
            box.style.left = (r.left + r.width / 2) + 'px';
            box.style.top = (r.top + r.height / 2) + 'px';
            if (color) box.style.setProperty('--fx-c', color);
            for (var i = 0; i < 12; i++) {
                var s = document.createElement('i');
                var a = (Math.PI * 2 * i) / 12 + Math.random() * 0.4, d = 22 + Math.random() * 22;
                s.style.setProperty('--dx', (Math.cos(a) * d).toFixed(1) + 'px');
                s.style.setProperty('--dy', (Math.sin(a) * d).toFixed(1) + 'px');
                s.style.animationDelay = (Math.random() * 60) + 'ms';
                box.appendChild(s);
            }
            document.body.appendChild(box);
            setTimeout(function () { box.remove(); }, 900);
        };
    })();
</script>
