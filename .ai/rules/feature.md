---
paths:
  - tests/Feature/AssessmentIntegrationTest.php
  - tests/Feature/ProgressIntegrationTest.php
  - tests/Feature/JourneyIntegrationTest.php
  - 'tests/Feature/**'
  - tests/Feature/TeacherDashboardTest.php
  - tests/Feature/AdminAuthorizationTest.php
  - tests/Feature/AdminDashboardTest.php
  - tests/Feature/AdminMissionManagementTest.php
  - 'tests/Feature/*Assessment*.php'
  - tests/Feature/AdminSystemTest.php
  - tests/Feature/AdministrativeIntegrationTest.php
  - tests/Feature/StudentReminderTest.php
  - tests/Feature/NotificationSecurityTest.php
---

# Feature

## E2E journey test is the cross-story regression anchor (US-414)
AssessmentIntegrationTest exercises the whole progression chain over HTTP only: dashboard, mission.submit, assessment show/start/submit/retry across two ordered courses, asserting view state strings (SEALED/READY/INITIATE CHALLENGE/FAILED/CLEARED), persisted rows (the404_progress, the404_assessment_attempts, the404_xp_transactions), and the XP ledger sum. Missions pass when their validation token appears (single contains rule); a Boss Challenge passes when its grading-rule token appears with passing_score 70. Keep this file as the kinetic regression proof for future phases (next-course unlock, retry-then-pass, all-clear banner).

## ProgressIntegrationTest is the US-510 chain-propagation anchor
US-510 chain-propagation anchor: drives a single Boss Challenge pass through the real HTTP flow and asserts XP, competency DEMONSTRATED flip, first_course/full_clear awards, and RecommendationService slot change all read straight after that one submit POST returns — no reload/recompute. Companion to AssessmentIntegrationTest (the E2E journey anchor); both share the courseWithAssessment/passMission/passChallenge harness, so keep the two in sync when grading setup changes.

## JourneyIntegrationTest is the US-512 click-through journey anchor
US-512 journey anchor: one continuous real student journey over the actual GET/POST routes (login, dashboard resume link, mission.show/submit, assessment.show/start/submit, competency, recommendations, achievements/XpTransaction reads). Distinct from US-510 (fires one pass and checks service-level immediate consistency) and the AssessmentIntegrationTest E2E (mission+assessment states and sealing) — this walks the clickable journey and asserts rendered view state. Shares the courseWithAssessment harness; keep all three anchors in sync.

## Scope count-based response assertions to a panel region
Laravel has no assertSeeCount. When asserting how many times text appears in a rendered view (percentages, names, PASSED/FAILED labels), count inside a delimited slice of the content, not the whole page body: whole-page substr_count collides with unrelated panels that render the same tokens (progress-bar percents, challenge badges, course names). Use panelBodyBetween($content, 'panel-title">&lt;Heading&gt;') from StudentProgressTest (slices heading until the next &lt;section class="panel) and assert counts within it. US-604's attempt-log and only-assessed tests are the reference; US-605's Competency panel already proved the collision returns.

## Equivalence test proves the SQL aggregation matches student-facing predicates
CourseAnalyticsTest includes an equivalence test that re-derives every course's bucket counts and avg_completion per (student, course) with exact student-facing predicates (AssessmentService::hasPassed / isUnlocked, DashboardService::courseProgress) and asserts them against CourseAnalyticsService's SQL output across the whole fixture fleet. Engage = completed&#62;0 (courseProgress 'total' is the mission count, always positive — using total>0 falsely marks idle students engaged). Leap: avg_completion percents come from progress['percent'] which mirrors the analytics service's own rounding.

## Sharing ghost interactions: passed course clears current; isolation uses own course
US-608 fixtures: a student who ever passed a course's assessment is no longer current on it — current-course signals (boss_fail/repeat_fail/low_performance) never fire for a passed course, so tests for them use failed (co-fires boss_fail) or submitted/no-score (suppresses boss_fail) attempts, never passed ones. Students share one global progress sequence, so per-scenario isolation needs its own single active course (order_num) or unrelated scenarios interfere.

## TeacherDashboardTest conventions: current-course gating, rosterBody scoping, explicit created_at
TeacherDashboardTest conventions (US-609): (1) the needs-attention KPI = AttentionService::list()->count(), and attention signals fire only on the student's CURRENT course (first active course not in the passed set — the US-608 (a) semantics), so a fixture student who must light up the attention tile must PASS earlier courses first so their failing attempts land on their current course, or they are silently invisible. (2) the dashboard's Recent Activity strip echoes usernames of students with events in the 14-day window, so whole-page assertDontSee on a username only works when no fixtures produced recent activity; otherwise scope to the roster slice (rosterBody() helper, slicing from '<form method="GET"'). (3) assert hrefs with assertSee(route(...)) — the needle form 'href="'.route(...).'"' never matches because assertSee escapes the needle to &quot;. (4) AssessmentAttempt fixtures must set created_at explicitly (DB NOT NULL, model timestamps off) — default to now() when no at is given. (5) active-window boundary: daysAgo(14) = startOfDay-14d+6h lands INSIDE the window (active); daysAgo(15) is outside.

## TeacherAuthorizationTest enumerates every teacher-gated route (US-611)
TeacherAuthorizationTest must enumerate EVERY teacher-gated route (currently students, student-progress, activity, course-analytics, needs-attention, classrooms, classrooms.show) with route names + whether the route needs a bound student or classroom for Ok. When any phase adds a teacher route, extend this list in the same test method that registers the route — a route added outside the auth+teacher group is exactly the US-511 drift this file exists to catch, and a partial list silently drops coverage (it held only two routes when US-611 found four more). For needsClassroom routes, the FORBIDDEN-role tests (student/operator) must seed a real classroom and use its id: like admin.courses, implicit model binding resolves before the role gate, so a fabricated id produces 404 instead of the 403 being asserted.

## InstructorMonitoringIntegrationTest is the US-612 cross-domain anchor
InstructorMonitoringIntegrationTest is the US-612 cross-domain anchor: it proves a student's real HTTP actions (mission completions + a Boss Challenge pass) are visible on the teacher side in the same instant, read straight after each student POST returns - no reload/recompute. Shares the courseWithAssessment/passMission/passChallenge harness with AssessmentIntegrationTest, ProgressIntegrationTest, and JourneyIntegrationTest; keep grading setup in sync across all four. Teacher-page assertions must be scoped with rosterBody() (students page) and panelBodyBetween() (student-progress course-analytics panels); course-analytics panel titles render as 'panel-title truncate">&lt;NAME&gt;', not 'panel-title">'.

## Roster last-activity test fixture timestamps must be strictly separated
Test test_roster_last_activity_matches_the_activity_strip_beat_vocabulary proves the /students roster column equals TimelineService::events($student, 1)->first()['label'] (a Boss Challenge pass surfaces as "Boss Challenge passed: {title} (score N)") and the same label appears in the strip. Fixtures give mission completions and the passed attempt EXPLICIT, well-separated timestamps, so the challenge beat is deterministically newest — do not write this assertion against same-second fixtures inside the same HTTP flow (the section-completion beat can win a same-second tie).

## Teacher-page tests attach an ACTIVE classroom with the courses the student must be visible for
WithClassroomScope::classroomFor(teacher, students, courses) mirrors production scoping: a teacher-page or teacher-scoped-service test MUST attach a classroom before asserting teacher-visible content, and must pass the courses the students must appear for. Course-level scoping means a student enrolled in a classroom but sharing NO assigned course is invisible to a scoped teacher (StudentService::index drops them; the roster, deep-links, and feeds render the empty state). Tests that hit only the fleet services directly (no args) stay fleet-wide and need no fixture; teacher-HTTP and scoped-service assertions do. TeacherAuthorizationTest seeds real classrooms in the forbidden-role matrix for the same reason — implicit classroom binding resolves before the role gate, so a nonexistent id 404s instead of 403.

## Admin route enumeration test follows the teacher-audit pattern
AdminAuthorizationTest enumerates every admin-gated route (currently admin.dashboard) with route names, mirroring the US-611 teacher-audit discipline. When any Phase 7 story adds an admin route, extend adminRoutes() in the same change — a route registered outside the auth+admin group is the exact drift this file exists to catch. Account status tests live in AccountStatusTest (login refusal + immediate session lockout via EnsureUserIsActive); the admin/teacher/operator/student role matrix against admin routes is asserted here, operator-403 explicitly per §49.

## AdminDashboardTest fixtures: explicit attempt created_at, forceFill audit rows, data-metric regex
US-702 dashboard test conventions: AssessmentAttempt fixtures set created_at/passed_at/submitted_at explicitly (DB NOT NULL, model timestamps off). AdminAudit rows are seeded via forceFill (created_at not fillable) with explicit, separated timestamps so newest-first ordering is deterministic. View metric values are asserted with the metricValue() regex against data-metric="KEY" anchors on each stat tile — not whole-page substring counts (digit collisions). Section for the fixture course must exist so the analytics universe includes it.

## AdminMissionManagementTest: seed AchievementSeeder for real mission completions; anchor the no-retroactivity test
tests/Feature/AdminMissionManagementTest.php is the US-707 feature suite. Real mission completions via MissionService::submit() inside an admin-update test NEED $this->seed(AchievementSeeder::class) in setUp — otherwise AchievementService throws "Achievement catalog is missing slug 'first_challenge'" (mirrors tests/Unit/MissionServiceTest.php). Points-edit tests must compare against a captured $pointsBefore (section/challenge factories randomize points 30-150) and must $mission->refresh() before a later completion so the in-memory instance carries the new points. Anchor assertions that matter: a points edit leaves existing Progress pts_earned / XpTransaction amount / Activity pts rows at their ORIGINAL values (balance stays) and a NEW completion after the edit earns the new points — proving edits are never retroactive.

## Assessment non-retroactivity and seal tests
AdminAssessmentManagementTest anchors the non-retroactivity contract: a passing_score edit never rewrites recorded attempt verdicts (an old failed attempt stays failed even when its score now meets the new threshold), while a NEW submission after the edit is judged against the new threshold. It also proves sealing via status=locked blocks beginAttempt (isUnlocked false) without touching attempt rows, and that a 404/403 writes no audit row. AchievementSeeder is seeded in setUp.

## Mutating the default DB connection in a feature test requires finally-restore
When a test forces a failing DB (e.g. AdminSystemTest points database.default at an unreachable sqlite file), the default connection must be restored in a finally BEFORE the test ends: RefreshDatabase rolls the test database back through the default connection at teardown, and a poisoned default silently leaves the real in-memory sqlite connection with an open transaction — every later test then dies with "cannot start a transaction within a transaction". Also avoid assertDontSee('READABLE') when the page shows 'UNREADABLE' (substring match), and never Config::set('app.key') — the session layer encrypts with it and raises "Unsupported cipher" before rendering.

## adminRoutes() is bound to the live route table, write routes 403 all non-admin roles
US-712 added two fleet-wide drift guards against the live route table. test_every_admin_route_carries_the_admin_middleware_and_the_matrix_is_complete asserts (1) every route named admin.* has the 'admin' middleware in its chain, (2) every GET/HEAD admin route is enumerated in adminRoutes(), and (3) every non-matrix admin route is a POST/PUT write route exercised by its own management test. test_students_teachers_and_operators_are_forbidden_from_every_admin_write_route probes all six write routes (users.store, users.update, courses.update, courses.sections.update, courses.missions.update, courses.assessment.update) with all three rejected roles. Extend adminRoutes() and keep these green whenever a story registers an admin route.

## safeConfig gate test covers every secret category by name
The allowlist gate test must enumerate one representative key per §33.0 secret category (database password, app.key, session key, API secret, mail credential), not just the sentinels a render test happens to plant. Each must be refused by safeConfig(). The catch clause needs a use InvalidArgumentException import: an unqualified catch resolves to the current namespace and silently never matches the SPL exception, surfacing as a confusing test error.

## AdministrativeIntegrationTest is the US-713 fifth anchor; shares the harness
US-713 added the fifth harness anchor (AdministrativeIntegrationTest), required by §48.0 to run against real services and DB state, never mocks. It shares the courseWithAssessment/passMission/passChallenge helpers with the other four anchors — keep it in sync. Two tests: (1) admin edits via the real PUT routes (rename course, reorder sections, mission points, passing_score) mid-journey, then student flow + teacher monitoring read the renamed catalog; (2) the US-705 revisit — locking a course mid-journey 403s mission.show/submit and seals the assessment, records no new progress/XP, drops the course from DashboardService::currentCourse, then unlocking restores mission access and the completion chain cleanly. Admin payloads are built by coursePayload/sectionPayload/missionPayload/assessmentPayload passing current rows back with only the intended override.

## AdminAnnouncementTest pins lifecycle invariants
For an announcement lifecycle test, pin the invariants with the DB as source of truth: published_at never rewritten (assert via assertEquals on the same stored Carbon value, NOT assertSame — DB reads yield distinct Carbon instances), double-publish leaves published_at untouched and no new system_announcement rows, editing a published announcement keeps the original delivered title and published_at. Ordering assertion: list is newest-first, so assertLessThan expects ($olderAt, $newerAt).

## StudentReminderTest fixture conventions (base time + week proof)
US-809 suite conventions: absolute calendar-day math via a fixed base() (now()->startOfDay()->addHours(6)) + $this->travelTo() (resets via InteractsWithTestCaseLifecycle). The mandated proof — daily dashboard visits for a week with unchanged stalled state -> exactly one LEARNING_REMINDER — is test_a_stalled_current_course_reminds_exactly_once_across_a_week_of_daily_visits (asserts count==1 after EVERY day). Draft backdating is done by creating the fixture INSIDE travelTo($base->subDays(4)) so Eloquent's now() writes the past updated_at. Draft/learning rows are created directly by the service, never fixtures.

## NotificationSecurityTest locks fleet axes, not one surface
US-811 suite: verifies announcement store/publish ignore client recipient fields (axis 3), notification rows and both render surfaces (center + dashboard teaser) never carry a password hash or solution code and never echo the data payload (axis 4), and every write route rides web-group PreventRequestForgery with no configured exceptions (axis 6). Keep it as the structural home when extending the surface.
