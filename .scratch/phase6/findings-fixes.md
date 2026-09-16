# Phase 6 — Findings & Fixes (running log)

Every genuine finding-and-fix discovered during Phase 6 earns its own line here,
in the same spirit as the US-511 IDOR drift in Phase 5, so the final phase report
can surface it instead of folding it silently into the story's changes.

Tracked risk items are listed under "Tracked risks" at the bottom, the same way
the US-511 drift and the mobile-width deferral carried explicit tracked mentions
into Phase 5's final report.

## Tracked risks

## US-610 — Off-canvas sidebar interaction untested at real desktop widths (deferred, tracked)
US-610's responsive pass verified layout at mobile=true widths (280–430px, where the off-canvas sidebar is out of flow) and at 1280px through computed widths of the animation-constrained `main`. What it did NOT verify end-to-end is the desktop off-canvas interaction itself: the `data-sidebar-trigger` menu button that slides the sidebar panel over the content on a real desktop viewport. The layout fix (`min-w-0` on the flex-child `main`, grid `grid-cols-1`) was asserted via computed styles, and the sidebar toggle is verified only by code-reading of the alpine trigger — never click-through tested on a desktop-width session. DEFERRED, TRACKED risk carried from US-610: exercise the toggle (open → content still fits → close) on a desktop-width headless session as part of a future desktop-interaction pass. US-611's security review does not touch navigation chrome, so it stays deferred here.

## US-603 — Standby shells hardcoded the student sidebar role (pre-existing gap, fixed)

Before US-603, both StandbyController ('role' => 'student') and standby.blade.php
(@extends('layouts.app', ['role' => 'student'])) forced the STUDENT navigation on every
standby shell — so an authenticated teacher or operator opening /activity (or, pre-US-601,
the instructor shells) saw the student sidebar instead of their own. US-603, as the owning
story of the last realized student shell (/student-progress), fixed both spots: the
controller now passes the authenticated user's real role ('student' for guests), and the
view no longer overrides the layout role. Locked in by ShellTest's new
test_standby_shells_reflect_the_authenticated_users_role. Genuine finding-and-fix — carries
its own line in the final report.

## US-602 — Full-table computation before pagination (spec §45.0, decided at 24-student scale)
The student overview computes rows for every matching student in memory (per-student
CourseProgressService::overview, CompetencyService::overview, isUnlocked, recentActivity —
~25-30 queries per student) before slicing to a page. Deliberate and correct-by-construction
because the current-course filters are derived, fine at the spec's example scale of 24
students. NAMED, TRACKED risk: if the roster grows, revisit before the per-student service
fan-out becomes a real cost (move the derived current-course/status filters to stored or
DB-computable form, preserve filter semantics). Recorded durably in .ai/rules/services.md.
Decision made with eyes open at this scale — not a passing note.

## US-601 — Guests could open the instructor shells (pre-existing gap, closed)

Moving `/students` and `/student-progress` into the `auth` + `teacher` group
closed a real pre-existing gap: before Phase 6, both instructor shells were
registered outside any auth group and were guest-accessible. Any unauthenticated
visitor could open the instructor standby pages. They are now auth+teacher-gated
with the rest of the teacher area. This is a genuine finding-and-fix and carries
its own line in the final report.
## US-606 — Explicit created_at is silently dropped by #[Fillable] on domain models

Activity and XpTransaction are #[Fillable([...])] and do not list created_at — so
`Activity::query()->create(['created_at' => ...])` silently discards the timestamp and
the column's useCurrent default stamps the row at insert time. Harmless in production
(sequential real-time inserts), but it torched test fixtures: a 35-event pagination
fixture wrote every row with the identical insert-second timestamp, so the feed's sort
was a no-op and page 1 rendered EVENT 35 first. Fixed in ActivityFeedTest by modeling
through forceFill(['created_at' => $at])->save(). Genuine finding-and-fix, carries its
own line in the final report: when a test or future code deliberately backdates a
create(), it must force-fill, never rely on the fillable list.

## US-606 — Feed computes-then-slices for heterogeneous compositions (spec §44/§45, named)
The teacher feed sorts and paginates composed beats in memory because the four sources
are heterogeneous (mixed tables, no SQL-level ordering or limit). The slice is bounded
by design: the default WINDOW only composes beats inside the last DEFAULT_WINDOW_DAYS
ending today, and student/type/date filters push into the volume sources (activity and
xp are `whereBetween`/`whereIn` windowed in SQL; assessment attempts are few rows).
Section-completion builds are bounded to students with progress rows inside the window.
Assessment-attempt windowing is in-memory (row count is tiny; beat time is passed_at vs
submitted_at per status). NAMED, TRACKED risk: at this scale the bounded in-memory slice
is fine; if activity volume or roster grows, revisit BEFORE the slice becomes a real cost
(page windows, or move to a persisted/queryable beat projection while keeping the shared
compose() beat vocabulary). Recorded durably in .ai/rules/services.md. Decision made with
eyes open — not a passing note.

## US-606 — Course filter excludes the Activity source (activity rows carry no course link)
under a course filter the feed drops the Activity source entirely: the404_activity has no
mission/course columns, so a mission-completion beat cannot be attributed to a course.
Excluding rather than mis-attributing is the documented behavior, and the course filter
STILL applies to XP (mission.course_id OR assessment.course_id pushdown) and assessment
attempts (assessment.course_id) and section beats (section->course_id in the per-user
loop). This is a spec §16 behavior decision, confirmed with the user, and locked in by
ActivityFeedTest's course-filter test asserting both the narrowing and the exclusion.

## US-606 — Window widening is itself client-servable; capped at MAX_WINDOW_DAYS (90)
The feed's in-memory slice is bounded by its WINDOW, so a client that widens the
window widens the slice. A malicious or careless teacher could set from=2000-01-01
and turn the bounded slice back into an unbounded scan over the whole history —
the same unbounded-computation shape as the US-602 roster issue, reachable via a
date parameter instead of roster size. Closed in US-606 follow-up: the span is
capped at TimelineService::MAX_WINDOW_DAYS = 90 (default window stays 14), enforced
in ActivityFeedRequest::withValidator as a normal validation error ('from' must not
exceed 90 days) BEFORE any feed query runs. Locked in by two ActivityFeedTest cases:
91-day span rejected, 90-day boundary accepted. MAX_WINDOW_DAYS is a named
product/bounds decision that must stay in sync with the feed's window semantics;
recorded durably in .ai/rules/services.md.

## US-607 — Analytics aggregate in SQL; equivalence test is the no-fan-out proof (§45 spirit)
The fleet view deliberately does NOT reuse the per-student PHP services (that is the
US-602 shape: ~25-30 queries per student → N×). CourseAnalyticsService computes every
metric over the whole fleet from three GROUP BY aggregations (mission counts, progress
rows, attempt rows) plus the assessment statuses — bounded, set-based, independent of
roster size. The price is that bucket formulas are re-encoded in SQL, so bucket
vocabulary must stay verbatim-synced with the student-facing predicates (COMPLETED =
hasPassed history, READY = isUnlocked, engaged = any Progress row, NOT-STARTED = fleet
minus the engaged union; pass rate over distinct terminal::passed|failed students, null
when none). CourseAnalyticsTest contains a dedicated equivalence test that re-derives
every bucket count and avg_completion per (student, course) with the exact student-facing services and asserts them against the SQL output across a 6-student fixture fleet — the
same "no duplicate formula" verdict as US-605, applied at fleet scale. NAMED, TRACKED
cross-check: if a student-facing predicate ever changes, the SQL copy MUST be updated or
the equivalence test fails loudly. Recorded durably in .ai/rules/services.md and
feature.md. Decision made with eyes open — not a passing note.

## US-607 — courseProgress 'total' is the mission count, not student progress (trap fixed)
DashboardService::courseProgress returns ['completed' => ..., 'total' => $course->missions()->count(), 'percent' => ...] — total is the course's mission count and is always positive, so an "engaged" test predicate written as `$progress['total'] > 0` classes IDLE students as in-progress and every other bucket shifts. The correct engaged predicate is `completed > 0 && total > 0` (and the analytics service derives engagement from actual Progress rows). Caught when the US-607 equivalence test initially mismatched the SQL output by exactly one in-progress student; fixed in the test and recorded durably in .ai/rules/feature.md. Genuine finding-and-fix, carries its own line in the final report.

## US-608 — Carbon 3 diffInDays is signed; day windows need abs (bug fixed)
The service's day windows (stall/inactivity/never-started) straight away produced negative or reversed values, and every attention test trying to pin a 14/21-day boundary failed. Root cause: Carbon 3 changed diffInDays to return the sign of the direction, so a date 14 days in the past returns -14. The boundary math (service's `daysSince()` and the test's re-derivation `expectedFor()`) now snaps both sides to start-of-day and takes `abs()` — whole calendar days, repeatable regardless of the time of day the comparison runs. Without this both the SQL and the equivalence re-derivation reported the same wrong numbers, so the equivalence test could not catch it; only the explicit boundary cases did.

## US-608 — Attention signals key off the CURRENT course only (design fact, traps fixed)
A student who ever passed a course's assessment is no longer current on it, so the three current-course signals (boss_fail, repeat_fail, low_performance) cannot fire for a passed course — those students belong to the next course or fall out of the list. Attempting to write "repeat_fail survives a later pass" style tests was wrong: they are unreachable by design. The green fixtures use FAILED attempts (which legitimately co-fire boss_fail) and SUBMITTED/no-score attempts (which suppress boss_fail while still counting completions) — no PASSED attempt is ever part of a current-course signal scenario. A second trap: students share one global progress sequence, so scenario isolation requires its own single active course or unrelated scenarios leak across tests.

## US-608 — Equivalent re-derivation had an un-representable case at shared edges (test refined)
At exactly two failed attempts or exactly 60% of passing, low_performance and repeat_fail share a boundary, and earlier assertions overrode one another. The "exactly sixty" case now asserts individually (low_performance NOT present, boss_fail present) instead of asserting a full signal array, and the isolation coverage was split into three focused tests (needs two scored attempts / ignores unscored submissions / needs a real pass bar). The equivalence test also gained the "current course exists but zero signals fire → student not listed" branch. These are test-shape fixes, not feature changes — the service behavior is unchanged.

## US-609 — The Teacher Dashboard is the composition story, not a new engine (§5.0/§52)
The system-wide summary lands on the established teacher landing page (/students, where
AuthController::homeFor sends teachers after login) rather than a new route: /dashboard is
the shared auth-only STUDENT page and is not teacher-gated, and the routes.md "no parallel
mechanism" rule forbids a second teacher dashboard mechanism. TeacherDashboardService
composes the roster (StudentService::index), the activity strip (TimelineService::feed —
identical beat vocabulary to /activity), and the assessment summary (CourseAnalyticsService
::overview — the same collection the analytics page renders). Only four counts are
genuinely new: total students; active students (>=1 learning event in the last
ACTIVE_WINDOW_DAYS = TimelineService::DEFAULT_WINDOW_DAYS (14) — a four-source SQL UNION
over progress/attempts/activity/xp with login/logout excluded, then fromSub +
distinct count); courses in progress (analytics rows whose buckets show in_progress or
assessment_ready > 0); assessments passed (count of passed attempts). The needs-attention
KPI reuses AttentionService::list()->count() so the tile and /needs-attention can never
disagree. The page still carries the roster below the summary, unchanged.

## US-609 — Dashboard tests must respect current-course gating to light the attention tile (trap fixed)
The needs-attention KPI inherits US-608's confirmed (a) semantics: signals fire only on the
student's CURRENT course (the first active course not in the passed set). The first fixture
put a failing student's failed attempts on a later course, so currentCourse() selected the
FIRST course (their true current, with no attempts) and the attention tile stayed dark —
the test expected 1 and got 0. Fixed by passing earlier courses first so the failing
attempts land on the student's actual current course. Not a service bug; a fixture
semantics trap worth recording (now in .ai/rules/feature.md).

## US-609 — Multi-line inline @php(...) does not compile in Blade (build trap fixed)
students.blade.php first declared the KPI link map as @php($links = [...]) across several
lines. Blade's inline @php only handles single-line expressions; the multi-line form was
left un-compiled as literal text, which cascaded into a "unexpected token endforeach"
ParseError at the roster loop further down the file. Fixed by converting to the @php ...
@endphp block form (the roster's existing style). Recorded durably in .ai/rules/views.md.

## US-610 — Responsive instructor UI (§49.0–§51.0)

Verified headless (chromium CDP vs. vite-served app on local SQLite, logged in as cpu_teacher): no page-level horizontal overflow on /students, /students?page=2, /student-progress/5, /student-progress/3, /activity, /course-analytics, /needs-attention at 360/390/430/1280 (28 combos green; confirm pass another 24 green).

Root causes fixed in order:
1. **`main` lacked `min-w-0`** (layouts/app.blade.php). As a flex-column child, its min-width auto → min-content; the /students roster's min-content (~717–800px) inflated the page to 738px at every mobile width. Added `min-w-0`.
2. **Grid tracks sized to max-content.** The dashboard summary grid `grid gap-4 md:grid-cols-2` had no explicit mobile template, so the implicit track took the nowrap activity-label max-content (~722px) and blew the panels out. Fixed with `grid-cols-1`. Same latent bug found in dashboard.blade.php:22 and mission.blade.php:71 — fixed alike. (This — not the roster — was the dominant overflow driver on /students; the roster table fit because main constrained it.)
3. **Tables forced internal horizontal scroll** (§50.0 violation). Added `.table-stack` responsive CSS (`@layer components` in app.css; hides thead <768px, stacked cards, `td::before[data-label]`), applied to the /students roster and the three /student-progress tables with `data-label` on every cell, and `flex-wrap gap-y-2` on the /students + /activity pagination rows.

CSS authored (not Tailwind utilities — Tailwind v4 can't do attribute-driven card transforms). Rebuilt assets after each edit (`npm run build`).

Verification method: CDP `Emulation.setDeviceMetricsOverride` (mobile=false so the sidebar stays off-canvas like real 360px desktops), login idempotent, navigate each page/width, measure `document.scrollingElement.scrollWidth` vs innerWidth + first-row/thead computed display (`none` at mobile = stacked; `table-header-group` at 1280 = restored table). Screenshots captured to /tmp/opencode/ev_*.png (not reviewed — model can't decode images; computed-style probes used instead).

Trap: headless page-render probes must re-assert device metrics/width after EVERY navigation, otherwise a stale layout can report desktop widths for a "360" run. (One shot.js reading showed trW 980 + thead visible for a 360 slot that a fresh identical navigation rendered stacked — a racy artifact, not a regression.)

Trap (seeding): Laravel factory `fake()->unique()` counters are process-global and definitions evaluate every attribute even when overridden → section/mission factories exhaust after ~10 creates (OverflowException "Maximum retries of 10000"). Use direct Model::create for bounded-id domains.

Cash-out: 457 tests / 1564 assertions still green (full suite); 0 responsive failures; rules recorded in .ai/rules/views.md, css.md, layouts.md.

## US-611 — Teacher security review: everything held; one test-coverage gap fixed

Fleet-wide audit across all six teacher-gated routes (/students, /student-progress/{student?}, /activity, /course-analytics, /needs-attention, plus the /students-as-dashboard composition). Every scoped guarantee held — no gate, IDOR, leak, or write-path defect was found:

- **Gate coverage (US-601 re-check):** all six routes are in the single `auth`+`teacher` group (route:list confirms the middleware chain); no Phase 6 route registered outside it. Guest hit on any of the six redirects to /login (live: all six final pathnames /login); student and operator get 403 on all six (live); teacher and admin get 200 on all six + page 2 (live).
- **Two IDOR postures, applied consistently:**
  - identifier-less pages (/students, /course-analytics, /needs-attention) reject all five spellings (user_id/userId/user/student/owner) with 403; /activity rejects the other four and keeps 'student' as a legitimately validated, narrow-only filter (spellings locked by ActivityFeedTest; cases live: 19 probes → 403).
  - path-bound posture on /student-progress/{student}: non-student targets → 404 (teacher id 1, nonexistent 999, non-numeric abc all 404 live); role !== 'student' → abort(404).
- **No protected-field leak:** every teacher page rendered with sentinels planted in the DB (SOLUTION_MARKER_C*, VALIDATE_MARKER_C*, GRADING_MARKER_C*) plus bcrypt-hash and field-name needles (solution_code/validate_rule/grading_rule) scanned across all 7 teacher pages × rendered bodies: zero hits. Models keep Mission::$hidden solution_code+validate_rule, Assessment #[Hidden(['grading_rule'])], User #[Hidden(['password'])]; assessment attempt history emits id/status/score/passed_at/submitted_at only.
- **Read-only:** all six teacher route targets are GET|HEAD only (route:list); zero write calls in any auth+teacher controller; the only write paths in the app are AssessmentService begin/submit/evaluate/retry behind the STUDENT assessment POST routes.
- **Uniform student rejection:** one gate (EnsureUserIsTeacherOrAdmin) shared by all six — confirmed as a single code path and asserted live per-route in one pass (403 × 6).

Gap fixed (test-coverage, not runtime): **TeacherAuthorizationTest enumerated only two of the six gated routes** ('students', 'student-progress'), so a later story could register a teacher route outside the group and the suite would not notice — the exact US-511 failure shape at the suite level. Expanded `teacherRoutes()` to all six with a needsStudent flag (route + bound-student variants for the Ok paths); lock-in recorded durably in .ai/rules/feature.md. Full suite now 457 tests / 1582 assertions green.

Observation (no defect): a student who was a guest on a teacher URL just before logging in is bounced there post-login by Laravel's intended-URL replay and then meets the 403 gate — corrected landing behavior, not an exposure (the gate still fires). Also verified on a rebuilt local SQLite fleet (25 users / 5 courses / 17 missions / 32 progress / 6 attempts / 26 activity rows) that the sentinel scan and gate matrix hold against seeded data, not just the empty test DB.

## US-612 — Cross-domain propagation anchor (no bug; one cross-surface inconsistency named, unfixed)

New anchor: **InstructorMonitoringIntegrationTest** drives ONE student through the real HTTP routes (two mission completions → roster IN PROGRESS 1/2 · 50% then READY 2/2 · 100% + XP ledger rows → one Boss Challenge pass) and reads the TEACHER side straight after each POST returns: dashboard strip ('Mission completed…', 'Boss Challenge completed…', PASS RATE 100%), Assessment Summary panel (✔ 1 · ▸ 0 · ◈ 0 · ○ 0), roster advance into the next course (IN PROGRESS 0/1 · 0%, LOCKED, DEMONSTRATED 1), per-student Assessment Performance (PASSED, 100%) and Competency (DEMONSTRATED, 2/2, PASSED) panels, and course-analytics alpha panel (✔ COMPLETED 1, 100% pass rate). Same-instant property holds end to end with no reload/recompute. Shares the courseWithAssessment/passMission/passChallenge harness (now feature.md-locked as a four-anchor set). Full suite now 458 tests / 1615 assertions green.

NAMED, UNFIXED observation from the anchor: the /students roster's "Last activity" column uses StudentService::lastActivity → DashboardService::recentActivity, which reads only the404_activity rows (mission completions and wrong submissions). A Boss Challenge pass (an XpTransaction + AssessmentAttempt, surfacing via TimelineService's four-source beat vocabulary) does NOT move that column, so a student whose last event was the challenge pass still shows the older 'Mission completed: …' row — while the very same page's Recent Activity strip, composed by TimelineService::feed, DOES show the pass. Two teacher surfaces on one page disagree about "last activity". Deliberately left unfixed: it is outside US-612's acceptance criteria, and changing it ripples through US-602's StudentService and its roster tests. Flagged here for a user decision (align the column to TimelineService beats, or rename it to reflect it is mission-activity-only).

## Fast-follow — Roster "Last activity" aligned to the TimelineService beat vocabulary (US-612 observation resolution)

User decision on the US-612 named observation: ALIGN, not relabel. StudentService::lastActivity now reads the newest TimelineService::events($student, 1) beat — the exact same four-source composition the Recent Activity strip renders — so the roster column shows a Boss Challenge pass as 'Boss Challenge passed: {title} (score N)' the same way the strip does. A teacher never has to know one column on the page silently excludes assessment events. Locked by TeacherDashboardTest::test_roster_last_activity_matches_the_activity_strip_beat_vocabulary: fixtures give mission completions and the passed attempt explicit, strictly separated timestamps (challenge beat deterministically newest — a same-second fixture can lose to the section-completion beat), then asserts the roster cell label, TimelineService's first beat, and the strip label all agree. Full suite after the change: 459 tests / 1621 assertions green. Rules recorded in .ai/rules/services.md and feature.md.
