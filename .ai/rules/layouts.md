---
paths:
  - 'resources/views/layouts/**'
---

# Layouts

## Application shell uses @extends('layouts.app')
The app shell lives at resources/views/layouts/app.blade.php and pages extend it via @extends('layouts.app') with @section('title')/@section('content'). Do not use <x-app-layout>. Role (student/instructor) is passed as the second arg: @extends('layouts.app', ['role' => 'instructor']).

## Teacher sidebar has no standalone "Student Progress" item (US-604)
The instructor sidebar (instructorItems in app.blade.php) lists Dashboard / Students / System → Assessments, Competency, Learning Activity. There is deliberately NO "Student Progress" nav item: there is no all-students-progress landing page, the roster is the picker, and per-student detail is reached via the roster's deep-links plus the "◀ ALL STUDENTS" back-link on the page. Keep it that way when adding per-student sections (US-604+).

## Shell main is a flex child and needs min-w-0
`main` in layouts/app.blade.php is the flexible column child of `div.flex.min-h-screen` (via the `div.flex-1.flex.flex-col.min-w-0` wrapper), so it must keep `min-w-0`. Without it, a flex item's min-width resolves to min-content and any wide table/grid inside (roster, panels) inflates the whole page width instead of fitting the viewport. This was the root cause of the /students page overflow at 360/390/430 in US-610.

## Student shell uses one top-level navigation
Student screens use the application top bar for Dashboard, Learning Path, Notifications, and Account. Do not add a persistent student sidebar or duplicate these destinations. Lesson and challenge screens may add compact contextual back navigation below the app bar.
