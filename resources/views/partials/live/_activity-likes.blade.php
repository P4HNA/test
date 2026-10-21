{{-- Like buttons on .ac-post cards inside any [data-ac-list]. --}}
@auth
    <script>
        (function () {
            'use strict';
            var lists = document.querySelectorAll('[data-ac-list]');
            if (!lists.length) return;
            var url = @json(route('activity.like'));
            var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            function paint(btn, liked, count) {
                btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
                btn.querySelector('[data-count]').textContent = count;
            }

            function onClick(e) {
                var btn = e.target.closest('[data-like]');
                if (!btn || btn.disabled) return;
                var key = btn.closest('.ac-post').getAttribute('data-key');
                var was = btn.getAttribute('aria-pressed') === 'true';
                var before = parseInt(btn.querySelector('[data-count]').textContent, 10) || 0;
                paint(btn, !was, Math.max(0, before + (was ? -1 : 1)));   // show it straight away
                btn.classList.remove('is-pop'); void btn.offsetWidth; btn.classList.add('is-pop');
                if (!was && window.fxBurst) fxBurst(btn.querySelector('svg'), '#1877F2');
                btn.disabled = true;
                fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ key: key })
                }).then(function (r) { if (!r.ok) throw r; return r.json(); })
                  .then(function (data) { paint(btn, data.liked, data.count); })
                  .catch(function () { paint(btn, was, before); })
                  .finally(function () { btn.disabled = false; });
            }

            lists.forEach(function (list) { list.addEventListener('click', onClick); });
        })();
    </script>
@endauth
