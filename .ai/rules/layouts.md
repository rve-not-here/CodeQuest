---
paths:
  - 'resources/views/layouts/**'
---

# Layouts

## Application shell uses @extends('layouts.app')
The app shell lives at resources/views/layouts/app.blade.php and pages extend it via @extends('layouts.app') with @section('title')/@section('content'). Do not use <x-app-layout>. Role (student/instructor) is passed as the second arg: @extends('layouts.app', ['role' => 'instructor']).

## Teacher screens use the Engineer's Log shell (no sidebar)
`$teacherShell` (role teacher/instructor, not standalone/workspace) makes the layout render `body.atlas-ui.teacher-ui` with `sheet-column`, the same `components.student-header` shell and no `<aside>`. Teacher destinations come from `$instructorItems` in app.blade.php: primary = Students / My Classrooms / Reports / Needs Attention, secondary disclosure "Teaching tools" = Learning Activity / Course Analytics. There is deliberately NO "Student Progress" nav item: the roster is the picker, and per-student detail is reached via roster deep-links plus the back-link on the page. Keep `$atlasShell` covering both student and teacher roles; only admin still renders `console-ui` + `console-sidebar` until the admin phase converts it.

## Shell main is a flex child and needs min-w-0
`main` in layouts/app.blade.php is the flexible column child of `div.flex.min-h-screen` (via the `div.flex-1.flex.flex-col.min-w-0` wrapper), so it must keep `min-w-0`. Without it, a flex item's min-width resolves to min-content and any wide table/grid inside (roster, panels) inflates the whole page width instead of fitting the viewport. This was the root cause of the /students page overflow at 360/390/430 in US-610.

## Student and teacher shells use one top-level navigation
Student and teacher screens use the application top bar for their primary destinations, a secondary disclosure, notifications and account. Do not add a persistent student or teacher sidebar or duplicate these destinations. Lesson and challenge screens may add compact contextual back navigation below the app bar.

## Admin sidebar is layout-owned (no per-page nav)
layouts/app.blade.php builds $adminNavGroups / $adminNavCurrent and shows the <aside class=console-sidebar> only for role=admin at >=1024px (body.console-ui--admin hides the top-bar nav for admins at all widths). Add new admin destinations to $adminNavGroups — never to individual pages — and keep the groups Overview/Manage/Evidence/System.
