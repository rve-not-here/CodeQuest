---
paths:
  - 'resources/views/layouts/**'
---

# Layouts

## Application shell uses @extends('layouts.app')
The app shell lives at resources/views/layouts/app.blade.php and pages extend it via @extends('layouts.app') with @section('title')/@section('content'). Do not use <x-app-layout>. Role (student/instructor) is passed as the second arg: @extends('layouts.app', ['role' => 'instructor']).

## Teacher screens use the Engineer's Log shell (no sidebar)
`$teacherShell` (role teacher/instructor, not standalone/workspace/guest) makes the layout render `body.atlas-ui.teacher-ui` with `sheet-column`, the same `components.student-header` shell and no `<aside>`. Teacher destinations come from `$instructorItems` in app.blade.php: primary = Students / My Classrooms / Reports / Needs Attention, secondary disclosure "Teaching tools" = Learning Activity / Course Analytics. There is deliberately NO "Student Progress" nav item: the roster is the picker, and per-student detail is reached via roster deep-links plus the back-link on the page. `$atlasShell` is simply "any authenticated, non-guest page", so students, teachers, admins and operators all get the sheet column.

## Shell main is a flex child and needs min-w-0
`main` in layouts/app.blade.php is the flexible column child of `div.flex.min-h-screen` (via the `div.flex-1.flex.flex-col.min-w-0` wrapper), so it must keep `min-w-0`. Without it, a flex item's min-width resolves to min-content and any wide table/grid inside (roster, panels) inflates the whole page width instead of fitting the viewport. This was the root cause of the /students page overflow at 360/390/430 in US-610.

## Student and teacher shells use one top-level navigation
Student and teacher screens use the application top bar for their primary destinations, a secondary disclosure, notifications and account. Do not add a persistent student or teacher sidebar or duplicate these destinations. Lesson and challenge screens may add compact contextual back navigation below the app bar.

## Admin is a top-bar shell too (no sidebar)
`$adminShell` (role admin, not standalone/workspace/studentPrototype/guest) renders `body.atlas-ui.admin-ui` with the same header component and the sheet column. The console sidebar is gone: `$adminNavGroups` / `$adminNavCurrent` no longer exist. Admin destinations come from `$adminItems` (primary = Users / Classrooms / Courses / Activity, secondary disclosure "Operations" = Announcements / Analytics / System / System report). `$isCurrent` prefix-matches `route.` so sub-routes (create/edit/reports) keep their parent item marked. Add new admin destinations to `$adminItems` — never to individual pages.

## Guest pages opt out of every shell
`@extends('layouts.app', ['standalone' => true, 'guest' => true, 'role' => 'student'])` marks a public page. `$guestShell` forces `$studentPrototype`/`$teacherShell`/`$adminShell` false, sets `$isGuest` (body `guest-ui`) and hides the header, footer and sheet column, so `welcome`, `auth/login` and any future public page control their own full-page layout inside `@section('content')`. Always pass `guest` as well as `standalone` — standalone alone does not stop an authenticated student/admin from getting the atlas chrome. Error pages do not use this layout at all (see errors/layout.blade.php, which is asset-free).

## Account theme wins over localStorage before paint
layouts/app computes $themePreference for the authenticated user; the pre-paint script uses the saved account value when present, writes it into localStorage, and otherwise falls back to the device value. The toggle POSTs to theme.update for authenticated users only. Error pages stay localStorage-only on purpose (they must not query the DB during a 500), so the write-through is what keeps them in sync.
