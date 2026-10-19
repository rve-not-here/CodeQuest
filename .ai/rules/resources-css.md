---
paths:
  - 'resources/css/**'
---

# Resources Css

## Console classes live in one app.css block
The admin console shell (.console-sidebar*, .admin-toolbar*, .panel-pagination, .admin-row-link*, .admin-crumbs, .admin-field-help, .admin-beat-*, .panel:has(> .admin-form)) is declared once in app.css after the admin-management block. Admin table column proportions are set with .admin-table:has([data-label=...]) thead th width rules. Do not re-declare these in student.css.

## Structure and utilities (Engineer's Log)
Tailwind v4 CSS-first: no tailwind.config.*; the system lives in `@theme` in resources/css/app.css and the tokens are the real colour authority in both themes — never hard-code palette hexes in blades. Light and dark are one palette under two lights; dark mode is a class strategy on `<html>` with a no-flash inline script, system default and an explicit Light/Dark/System toggle. Fonts load through the laravel-vite-plugin `bunny()` helper (resources/js/app.js / app.css), never a hand-written hosted stylesheet. After editing app.css, re-run `npm run build` or the served manifest CSS won't change; run `npm run build` before declaring a visual change complete.

## .table-stack responsive utility lives in @layer components
The `.table-stack` media block (@media max-width: 767.9px, hides thead, block-displays tr/td as cards, td::before renders data-label) is defined inside `@layer components` in resources/css/app.css. It needs NO Tailwind config — it's plain CSS. Any new responsive-table behavior must extend this utility, not add per-blade duplicates.

## Engineer's Log visual world (seed:914c026a6b42634d)
CodeQuest's committed visual world is The Engineer's Log (user-pinned 2026-10-19; full contract at .impeccable/surfaces/resources-views-layouts-app-blade-php.md). Computation-pad ground #E9EDDF under an 8px minor/40px major grid at 5-9% ink; ink #1F242C; margin red #B23A2E; stamp violet #4E4C9A; pass green #2E6B4F; warning amber #8A6114. Dark theme is required and first-class: system default plus an explicit Light/Dark/System toggle (desk #14161B, ink #E6E9DE); no-flash inline script. Type: Barlow (prose), Barlow Condensed (drafted labels/display), Fragment Mono (codes/values). Radius <=3px; no glass, gradient, glow or CRT atmosphere; transitions <=200ms with prefers-reduced-motion cancels. The Foundry Specimen and the 2026-10 SaaS directions are dead - do not reintroduce their tokens.
