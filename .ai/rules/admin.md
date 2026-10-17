---
paths:
  - resources/views/admin/dashboard.blade.php
  - 'resources/views/admin/**'
---

# Admin

## Admin Console sheet: register grid, Operations panel, full-width audit
Dashboard composition: .admin-register (2x2 panels, gap 1px over a line-colored background so compartments share hairlines; collapses to 1 col at <=1023.9px), then the Operations panel (Classrooms group + System operations links, 1 col <=767.9px), then the full-width Recent System Activity table. AdminDashboardTest pins: the strings Users/Curriculum/Assessments/Learning/Recent System Activity, data-metric dd regex, the audit caption/th markup, and all seven admin destination routes inside <main>. Audit table: table-layout fixed, th widths 14%/auto/6.75rem/9.5rem, verdict+timestamp rail right-aligned at >=768px, .admin-audit-summary capped at 72ch, badges need whitespace-nowrap.

## Admin console page anatomy: header, crumbs, panels
Every admin page opens with <x-page-header title subtitle>. Drill-downs put a .admin-crumbs nav above it instead of back buttons. List pages = <x-panel> wrapping a .admin-toolbar filter form, the .admin-table, and .panel-pagination. Form pages = <x-panel> directly wrapping form.admin-form (CSS caps panels holding a form to 56rem). Row actions use .admin-row-link (+ --danger), field annotations use .admin-field-help. Admin*Test + ClassroomManagementTest pin many exact strings/selectors — grep them before restructuring a view.
