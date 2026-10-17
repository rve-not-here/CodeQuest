---
paths:
  - 'resources/css/**'
---

# Resources Css

## Console classes live in one app.css block
The admin console shell (.console-sidebar*, .admin-toolbar*, .panel-pagination, .admin-row-link*, .admin-crumbs, .admin-field-help, .admin-beat-*, .panel:has(> .admin-form)) is declared once in app.css after the admin-management block. Admin table column proportions are set with .admin-table:has([data-label=...]) thead th width rules. Do not re-declare these in student.css.

## SaaS design direction (2026-10)
CodeQuest frontend now follows a conventional SaaS look: Inter UI type, indigo primary #4F46E5, slate-50 ground, white cards with 8px radius and soft shadow, normal-case sans labels. Foundry Specimen tokens (phosphor, void, amber, cyan, etc.) remain only as legacy aliases. Do not reintroduce CRT/glow/scanline/uppercase-mono styling. See DESIGN.md for the full spec.
