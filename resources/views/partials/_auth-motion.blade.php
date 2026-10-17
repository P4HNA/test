{{-- Shared motion + type for the auth pages. Included by login + register.
     Path matters: both pages do @include('partials._auth-motion'), so this file
     must live at resources/views/partials/_auth-motion.blade.php --}}

{{-- The auth pages use .font-display / .font-hand, but layouts/guest.blade.php
     never loads these families. Pulling them in here keeps the partial the single
     place the auth pages get their look from. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800&family=Archivo+Expanded:wght@700;800;900&family=Caveat:wght@500;600&family=Kantumruy+Pro:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* ---- Type ---- */
    body { font-family: 'Archivo', 'Kantumruy Pro', system-ui, sans-serif; }
    .font-display { font-family: 'Archivo Expanded', 'Kantumruy Pro', system-ui, sans-serif; }
    .font-hand    { font-family: 'Caveat', 'Kantumruy Pro', cursive; }

    /* ---- Entrance keyframes ---- */
    @keyframes pd-rise  { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
    @keyframes pd-slide { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: none; } }
    @keyframes pd-crop  { from { opacity: 0; transform: scale(.35); }       to { opacity: 1; transform: none; } }
    @keyframes pd-grow  { from { transform: scaleY(0); }                    to { transform: scaleY(1); } }
    @keyframes pd-stamp {
        0%   { opacity: 0; transform: scale(.55) rotate(-26deg); }
        60%  { opacity: 1; transform: scale(1.09) rotate(5deg); }
        100% { opacity: 1; transform: scale(1) rotate(var(--pd-tilt, -6deg)); }
    }
    @keyframes pd-shake {
        10%, 90% { transform: translateX(-2px); }
        20%, 80% { transform: translateX(3px); }
        30%, 50%, 70% { transform: translateX(-4px); }
        40%, 60% { transform: translateX(4px); }
    }

    /* ---- Entrance classes (delays are set inline, per element) ---- */
    .pd-rise    { animation: pd-rise .75s cubic-bezier(.19, 1, .22, 1) both; }
    .pd-slide   { animation: pd-slide .75s cubic-bezier(.19, 1, .22, 1) both; }
    .pd-crop    { animation: pd-crop .5s ease-out both; }
    .pd-divider { transform-origin: top; animation: pd-grow .9s cubic-bezier(.19, 1, .22, 1) .25s both; }
    .pd-stamp   { animation: pd-stamp .8s cubic-bezier(.19, 1, .22, 1) .8s both; }

    /* ---- Fields ---- */
    .pd-field .pd-label { transition: color .25s ease; }
    .pd-field:focus-within .pd-label { color: #0A0A0A; }

    .pd-input-wrap { position: relative; }

    /* Show/hide password toggle (plain CSS so it works with any compiled stylesheet) */
    .pd-input-wrap .pd-has-eye { padding-right: 2.5rem; }
    .pd-eye {
        position: absolute; right: 0; top: 50%; transform: translateY(-50%);
        width: 2.25rem; height: 2.25rem;
        display: inline-flex; align-items: center; justify-content: center;
        background: transparent; border: 0; border-radius: 999px;
        color: #5E5E5E; cursor: pointer; transition: color .2s ease;
    }
    .pd-eye:hover { color: #0A0A0A; }
    .pd-eye:focus-visible { outline: 2px solid #0062EB; outline-offset: 1px; }
    [x-cloak] { display: none !important; }
    /* Red rule that wipes in from the left over the resting grey border */
    .pd-line {
        position: absolute; left: 0; right: 0; bottom: -1.5px; height: 1.5px;
        background: #0062EB; transform: scaleX(0); transform-origin: left;
        transition: transform .4s cubic-bezier(.19, 1, .22, 1);
    }
    .pd-field:focus-within .pd-line { transform: scaleX(1); }

    .pd-input::placeholder { opacity: .55; transition: opacity .25s ease; }
    .pd-input:focus::placeholder { opacity: .28; }

    /* Applied only to a field that came back with a validation error */
    .pd-shake { animation: pd-shake .5s ease both; }

    /* ---- Buttons ---- */
    .pd-btn { transition: transform .2s ease, opacity .2s ease, background-color .25s ease, border-color .25s ease; }
    .pd-btn:hover  { transform: translateY(-2px); }
    .pd-btn:active { transform: translateY(0) scale(.99); }
    .pd-google:hover { background: #F4F4F6 !important; border-color: #0A0A0A !important; }

    /* Arrow nudge on the small text links */
    .pd-link { transition: opacity .2s ease, letter-spacing .2s ease; }

    @media (prefers-reduced-motion: reduce) {
        .pd-rise, .pd-slide, .pd-crop, .pd-divider, .pd-stamp, .pd-shake {
            animation: none !important; opacity: 1 !important; transform: none !important;
        }
        .pd-btn, .pd-line, .pd-label { transition: none !important; }
        .pd-line { transform: scaleX(0); }
    }
</style>
