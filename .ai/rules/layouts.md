---
paths:
  - 'resources/views/layouts/**'
  - resources/views/layouts/app.blade.php
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

## The theme is resolved by timestamp: the newer of device and account wins
The head script applies the theme before first paint and compares two sources by when the choice was made: the device (localStorage cq-theme plus cq-theme-at, written by the toggle in app.js) and the account (the server value plus its saved_at). The newer wins. A student who picks a theme and navigates immediately keeps that choice even if the account write has not landed yet, and a choice made on another device still converges here. Never make the account value unconditionally win and never overwrite the device value without its timestamp.

## A device choice without a timestamp is still the student's own choice
The head script compares the device's choice and the account's by when they were made; the newer wins. A device value with NO timestamp was written before the account learned about time: it is the student's own choice, so it is honoured, stamped from now on, and flagged (data-theme-sync="device") so the client carries it to the account. Never discard a device choice for an older account value, and never let the account win over a choice the student just made.

## The record popover keeps its authored groups
`$secondaryGroups` in layouts/app preserves the heading rows authored in `$studentItems`/`$instructorItems`/`$adminItems`; `components.student-header` prints a group label only when it differs from the disclosure label (the first student group is "Learning record", the same as the trigger, so it is not repeated). The desktop popover must stay one `<nav class="atlas-popover" aria-label="…">` so RoleNavigationTest's section slice keeps seeing every link; the mobile menu prints every group label. Do not flatten the popover back into an eleven-link list.


## Student secondary nav is not a link dump (2026-10-22)
The Learning record disclosure deliberately omits Section Progress, Challenge Index and Notifications. Section Progress is linked from the Course Progress sheet; Challenge Index remains a real route reached from Timeline/Boss contexts; Notifications is the shell bell. Keep the `$secondaryGroups` headings and the `<nav class="atlas-popover" aria-label="Learning record">` wrapper for RoleNavigationTest. When adding a student surface, first ask which existing feature owns the path.


## Guest cover teaches the whole system (2026-10-22)
The public welcome now lists the documented feature set, not four entries: Lessons, Knowledge Checks, Coding Challenges, Boss Challenges, Learning path, Learning timeline, Performance summary. Below the two-column body, a full-width "The learning loop" band (Study → Check → Practice → Prove) explains the sequence and writes a single-record sentence. Keep the loop copy tied to the real flow: Knowledge Check before the challenge opens, required Coding Challenges before the Boss. The login access register lists the same six destinations (Lessons & Knowledge Checks combined) so a visitor sees the complete product before signing in. Both covers must stay within the viewport at 1440 (the loop may sit below the first fold) and must not introduce a new card grid.


## The bell carries the unread count, safely (2026-10-22)
The shell computes `$unreadNotificationCount` for authenticated users through `NotificationService::unreadCount()` and prints an accessible label (`Notifications, N unread`) plus a `.atlas-notification-count` badge (9+ cap). The query is wrapped in `try/catch` and falls back to 0: the admin system-status page must still render a 200 when the database is deliberately unreachable. Never remove the fallback; AdminSystemTest asserts the database-failure page is a real 200.


## Student account sign-out is a dialog, never a bare POST (2026-10-22)
The account menu's Sign out button is a real submit button inside `#logout-form` (so no-JS still logs out) with `data-dialog-open="logout-dialog"`; with JS it opens the shared `.atlas-dialog` confirmation. The dialog's confirm button submits `#logout-form` via the `form` attribute. Cancel returns focus to the account summary when the disclosure has closed; the trigger path stays. Do not move the logout form outside the account details (RoleNavigationTest slices it) and do not remove the no-JS submit.


## How-it-works dialog (2026-10-22)
The account menu and the Dashboard objective head both expose `data-dialog-open="how-codequest-works"`. The shared `.atlas-dialog` explains Study → Check → Practice → Prove and the XP/record model. It is never auto-opened; the generic dialog controller handles focus, Escape, backdrop close and focus return. Keep the sign-out dialog and no-JS logout submit unchanged.
