{{-- Activity post cards (.ac-post), shared by the Activity page and the Profile's Activity tab. --}}
<style>
.ac-list { display: flex; flex-direction: column; gap: .75rem; }
.ac-post { padding: 1rem; border-radius: 1rem; background: var(--card); border: 1px solid var(--line); box-shadow: 0 1px 2px rgba(0, 0, 0, .05); transition: all .2s ease; }
@media (min-width: 640px) { .ac-post { padding: 1.25rem; } }
.ac-post:hover { border-color: rgba(24, 119, 242, .4); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, .1), 0 2px 4px -2px rgba(0, 0, 0, .1); }
html.dark .ac-post:hover { border-color: rgba(69, 149, 255, .4); }
.ac-top { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; }
.ac-who { display: flex; align-items: center; gap: .75rem; min-width: 0; }
.ac-av { position: relative; flex-shrink: 0; width: 2.5rem; height: 2.5rem; }
.ac-av .pd-avatar, .ac-av img, .ac-av .hm-avatar { width: 2.5rem !important; height: 2.5rem !important; border-radius: 9999px; box-shadow: 0 0 0 1px var(--line); object-fit: cover; }
.ac-av img.logo { background: #fff; padding: .25rem; }
.ac-type { position: absolute; right: -.25rem; bottom: -.25rem; width: 1.25rem; height: 1.25rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; background: var(--card); border: 1px solid var(--line); box-shadow: 0 1px 2px rgba(0, 0, 0, .05); }
.ac-line { display: flex; flex-wrap: wrap; align-items: center; gap: .375rem; }
.ac-line .n { font-size: .875rem; line-height: 1.25rem; font-weight: 600; color: var(--ink); }
.ac-line .role { padding: 0 .375rem; border-radius: .375rem; background: #E7F3FF; color: #1877F2; font-size: 10px; font-weight: 500; }
html.dark .ac-line .role { background: rgba(69, 149, 255, .15); color: #4595FF; }
.ac-line .act { font-size: .75rem; line-height: 1rem; color: var(--mut); }
.ac-time { margin-top: .125rem; display: flex; align-items: center; gap: .25rem; font-size: 11px; color: var(--mut); }
.ac-badge { display: none; flex-shrink: 0; padding: .25rem .625rem; border-radius: 9999px; background: #F1F5F9; border: 1px solid var(--line); font-size: 11px; font-weight: 500; color: var(--ink); white-space: nowrap; }
html.dark .ac-badge { background: #242629; }
@media (min-width: 640px) { .ac-badge { display: inline-flex; } }
.ac-target { margin-top: .875rem; display: flex; align-items: center; gap: .875rem; padding: .75rem; border-radius: .75rem; background: #F5F7FA; border: 1px solid rgba(228, 230, 235, .7); }
html.dark .ac-target { background: #242629; border-color: #2E3034; }
.ac-target img { width: 3.5rem; height: 3.5rem; flex-shrink: 0; border-radius: .5rem; object-fit: cover; border: 1px solid rgba(0, 0, 0, .05); }
html.dark .ac-target img { border-color: rgba(255, 255, 255, .05); }
@media (min-width: 640px) { .ac-target img { width: 4rem; height: 4rem; } }
.ac-target > div { flex: 1; min-width: 0; }
.ac-target h4 { margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .75rem; line-height: 1rem; font-weight: 700; color: var(--ink); }
@media (min-width: 640px) { .ac-target h4 { font-size: .875rem; line-height: 1.25rem; } }
.ac-target p { margin: .125rem 0 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 11px; color: var(--mut); }
@media (min-width: 640px) { .ac-target p { font-size: .75rem; } }
.ac-view { flex-shrink: 0; display: inline-flex; align-items: center; gap: .25rem; padding: .375rem .75rem; border-radius: .5rem; background: #1877F2; color: #fff; font-size: .75rem; line-height: 1rem; font-weight: 600; text-decoration: none; box-shadow: 0 1px 2px rgba(0, 0, 0, .05); transition: background .15s ease; }
.ac-view:hover { background: #166FE5; }
.ac-foot { margin-top: .75rem; padding-top: .625rem; border-top: 1px solid var(--line); display: flex; align-items: center; justify-content: space-between; font-size: .75rem; line-height: 1rem; color: var(--mut); }
.ac-like { display: inline-flex; align-items: center; gap: .375rem; padding: .25rem .375rem; border: 0; border-radius: .375rem; background: transparent; font: inherit; color: inherit; text-decoration: none; cursor: pointer; transition: all .15s ease; }
.ac-like:hover { background: #F1F5F9; }
html.dark .ac-like:hover { background: #242629; }
.ac-like[aria-pressed="true"] { color: #1877F2; font-weight: 600; }
html.dark .ac-like[aria-pressed="true"] { color: #4595FF; }
.ac-like[aria-pressed="true"] svg { fill: currentColor; }
.ac-like.is-pop svg { animation: hm-label-pop 420ms cubic-bezier(.34, 1.56, .64, 1) both; }
.ac-foot .k { font-size: 11px; }
.ac-empty { padding: 4rem 1rem; text-align: center; border-radius: 1rem; border: 1px dashed var(--line); color: var(--mut); font-size: .875rem; }
</style>
