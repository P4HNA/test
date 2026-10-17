import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

/* Brand wordmark shine (components/brand, styles in app.css).
   Moves the highlight inside the letters to follow the mouse, so the
   wordmark reads like a surface catching light. The light trails the
   pointer a little (eased each frame) rather than snapping to it, which is
   what makes it feel like a reflection. Mouse only: touch screens have no
   hover, and the letters simply stay as they are. The loop runs only while
   the light is still moving, and only the shine layer is touched. */
function brandShine() {
    if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    document.querySelectorAll('[data-pd-brand]').forEach((link) => {
        const word = link.querySelector('.pd-brand-word');
        const shine = link.querySelector('.pd-brand-shine');
        if (!word || !shine) return;

        let frame = 0;
        let x = 0;
        let y = 0;
        let targetX = 0;
        let targetY = 0;

        const paint = () => {
            x += (targetX - x) * 0.22;
            y += (targetY - y) * 0.22;
            shine.style.setProperty('--sx', `${x.toFixed(1)}px`);
            shine.style.setProperty('--sy', `${y.toFixed(1)}px`);
            const moving = Math.abs(targetX - x) + Math.abs(targetY - y) > 0.3;
            frame = moving ? requestAnimationFrame(paint) : 0;
        };

        // On entry the light starts under the pointer instead of sliding in
        // from wherever it was last.
        const aim = (event, jump) => {
            const box = word.getBoundingClientRect();
            targetX = event.clientX - box.left;
            targetY = event.clientY - box.top;
            if (jump) {
                x = targetX;
                y = targetY;
            }
            if (!frame) frame = requestAnimationFrame(paint);
        };

        link.addEventListener('pointerenter', (event) => aim(event, true));
        link.addEventListener('pointermove', (event) => aim(event, false));
    });
}

/* iOS Safari only shows :active (pressed) styles once the page listens for
   touches. A passive, empty listener turns them on without touching scroll. */
document.addEventListener('touchstart', () => {}, { passive: true });

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', brandShine);
} else {
    brandShine();
}
