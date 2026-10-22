---
paths:
  - resources/views/admin/dashboard.blade.php
  - 'resources/views/admin/**'
---

# Admin

## Admin Console sheet: register grid, Operations panel, full-width audit
Dashboard composition: .admin-register (2x2 panels, gap 1px over a line-colored background so compartments share hairlines; collapses to 1 col at <=1023.9px), then the Operations panel (Classrooms group + System operations links, 1 col <=767.9px), then the full-width Recent System Activity table. AdminDashboardTest pins: the strings Users/Curriculum/Assessments/Learning/Recent System Activity, data-metric dd regex, the audit caption/th markup, and all seven admin destination routes inside <main>. Audit table: table-layout fixed, th widths 14%/auto/6.75rem/9.5rem, verdict+timestamp rail right-aligned at >=768px, .admin-audit-summary capped at 72ch, badges need whitespace-nowrap.

## Admin pages are records sheets, not console panels
Every admin view renders inside the atlas shell (`body.atlas-ui.admin-ui`) as a records sheet: running head `Records · <page> · Platform scope` with back/forward links or a scope stamp, eyebrow + h1 + lede, optional `.log-facts` strip, then `.atlas-record-group` sections holding `.atlas-register-table` registers and `.atlas-form` editors. `<x-page-header>` / `<x-panel>` no longer exist — do not reintroduce console chrome, `.admin-*` tables/toolbars, `.admin-crumbs`, or a sidebar. Full grammar lives in `.ai/rules/views.md` (Admin console is a records sheet too) and `.ai/rules/resources-css.md`.

## Admin test bindings survive restyling
AdminDashboardTest pins strings (Users / Curriculum / Assessments / Learning / Recent System Activity), the `data-metric` dd regex, the audit `<caption>`/`th` markup, and all seven admin destination routes inside `<main>`; AdminUserTest pins `<caption>Account directory</caption>` plus the row-header `th[scope="row"][data-label="User"]`. Grep the Admin*Test and ClassroomManagementTest assertions before restructuring a view, and keep `data-metric` on any figure whose text starts with digits.

## Admin page titles are visible; the sr-only h1 is the title
Admin views author the page name as `<h1 class="sr-only">` immediately after the running head, then group headings. `body.admin-ui .atlas-records > h1.sr-only` reveals that h1 as a 24–30px display heading, so the running head carries scope and the h1 carries the page. Do not add a second title block or hide it again. Teacher sheets use the visible `.atlas-records-heading` — the old rule that hid staff headings (`body.admin-ui/.teacher-ui .atlas-records-heading { clip... }`) was removed; never restore it. Long audit summaries (>220 chars) render as `.atlas-audit-summary` `<details>` with the full text inside.
