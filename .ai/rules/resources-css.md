---
paths:
  - 'resources/css/**'
---

# Resources Css

## Console classes live in one app.css block
The admin console shell (.console-sidebar*, .admin-toolbar*, .panel-pagination, .admin-row-link*, .admin-crumbs, .admin-field-help, .admin-beat-*, .panel:has(> .admin-form)) is declared once in app.css after the admin-management block. Admin table column proportions are set with .admin-table:has([data-label=...]) thead th width rules. Do not re-declare these in student.css.
