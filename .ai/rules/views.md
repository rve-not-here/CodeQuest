---
paths:
  - 'resources/views/**'
  - resources/views/dashboard.blade.php
  - resources/views/students.blade.php
---

# Views

## Editor/preview reuse + small-viewport verification trap
Reuse window.CodeQuest.CodeMirror via the cq:codemirror-ready event; preview iframes are sandboxed (allow-scripts only), srcdoc, no server call on RUN (ADR-0002). Headless chromium clamps --window-size to 500px min, so verify 360/390/430 via same-origin iframes sized exactly. @vite emits absolute URLs from app.url, so static-page verification must run on that host. Never let a probe div carry unbroken JSON tokens — they lay out ~557px and fabricate phantom page overflow.

## Completion banner keys off currentCourse null, not mission exhaustion
The dashboard "Course complete / Awaiting new directives" banner must render only when the derived current course is null (i.e. every active course has had its Boss Challenge passed — DashboardService::currentCourse skips hasPassed). Do NOT add `|| $nextMission === null` back: finishing all missions of a course yields a null next mission while the Boss Challenge is still outstanding, so that old condition told students they had "cleared every active course" prematurely. Caught and fixed by AssessmentIntegrationTest (US-414).

## students.blade.php = the staff roster sheet (Engineer's Log)
students.blade.php (US-609) is the teacher/admin roster, built with records grammar (`.atlas-records`): running head `Records · Students · Classroom scope|Platform scope`; heading "Students" + lede; 4-fact `.log-facts` strip (Total students / Active · 14d / Courses in progress / Assessments passed, the last three linking to activity or course-analytics); `.atlas-attention-callout` (warning tone only when `data-state="flagged"`, neutral at 0, CTA swaps primary/secondary); "Assessment summary" group (per-course meta counts + pass rate figure, empty note "No active course with missions to summarize."); "Student roster" group with `.atlas-index-filters`, `.atlas-register-table` (caption "Current learning evidence", `th[scope=row]` student link, state stamps instead of uppercase text) and `.atlas-records-pager`; "Recent activity" group (compact `.atlas-record-entry` rows, empty note "No learning activity in the last 14 days."). Labels are sentence case — CSS carries the caps. Blade trap: multi-line inline @php(...) does NOT compile (it needs the single-line form or a @php ... @endphp block) — use @php/@endphp for arrays such as the competency label.

## Responsive grids need an explicit mobile column count
Any grid with a responsive breakpoint (e.g. `grid gap-4 md:grid-cols-2`) MUST declare `grid-cols-1` (or another explicit mobile template). With `grid-template-columns` unset, implicit tracks size to the item's max-content, and nowrap/truncate labels (activity beats, roster cells) blow the panel out to ~700px+ on 360–430px viewports — no `overflow` anywhere shows it because the track itself grows. Guard: check every `class="grid` in the view. US-610: students/dashboard/mission all had this latent bug.

## Responsive tables use .table-stack + data-label per cell
Data tables that must adapt at <768px use `class="table-stack atlas-register-table"` on the <table> and a `data-label` attribute on every cell (the label shown above the value in the stacked-card layout; `td::before { content: attr(data-label) }`). The row label is a `<th scope="row" data-label="…">` — `.table-stack` block-displays `th` too, so it becomes the card title. Applied to the /students roster, the three /student-progress tables, and report tables. Keep the <thead> in the DOM (semantics) — the CSS hides it. Internal `overflow-x-auto` scrolling on a table is NOT an acceptable substitute under §50.0.

## admin/activity.blade.php mirrors the /activity view
admin/activity.blade.php (US-709) mirrors activity.blade.php's structure: GET filter form (actor/action/result + from/to date inputs) posting to route('admin.activity'), error block via x-status-message, then an EVENT LOG panel listing who(admin_username)/what(action label + summary)/result badge (success / failed variants)/target badge/when. Reads $filters via ?? '' fallbacks because AdminActivityFeedRequest::safe() omits absent keys (no prepareForValidation anchoring here, unlike ActivityFeedRequest). Pagination footer shows SHOWING PAGE x OF y + PREV/NEXT links exactly as activity.blade.php.

## Dashboard Incoming Transmissions panel stays a teaser, links only from notificationLinks map
US-810: the 'Incoming Transmissions' panel is the last item in the dashboard grid — appended after System Activity so Continue Learning keeps the top-right/primary CTA slot untouched. It renders only the two newest priority rows (teaser, never the feed): no MARK READ forms, no pagination. Row titles link only when $notificationLinks[$id] is non-null (mirror of the center's linkFor map); unread rows get an amber NEW badge, read rows opacity-60. Unread line shows '{n} UNREAD' or 'INBOX CLEAR'; VIEW ALL → always links to route('notifications').

## Grant preview scripts only for JavaScript courses
Student code previews use srcdoc in a sandboxed iframe. Grant allow-scripts only when the authoritative course type is js/javascript; HTML and CSS previews use an empty sandbox. Never add allow-same-origin, parent navigation, forms, popups, or downloads without a verified lesson requirement and a parent-access regression.

## Engineer's Log design direction (2026-10-19)
Student/teacher/admin surfaces use the shared classes: btn btn-primary/btn-secondary/btn-ghost, panel, badge, terminal-input, page-header. The committed world is The Engineer's Log, recorded in .ai/rules/resources-css.md, with the direction contract at .impeccable/surfaces/resources-views-layouts-app-blade-php.md. Teacher and admin get a persistent left sidebar (console-sidebar). Empty states must be populated with real content, never placeholder-only panels.
