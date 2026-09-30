---
paths:
  - routes/web.php
  - 'routes/**'
---

# Routes

## Auth
`/login` (GET showLogin / POST login) is guest-only and `/logout` (POST) plus `/dashboard` (GET) are auth-only. Login POST handles both student+teacher redirects via AuthController::homeFor(); the guest middleware already redirects authenticated users away from /login (no manual check needed).

## Phase 1 standby shell routes
Phase 1 registered placeholder shells (StandbyController) for learning-path, missions, assessments, timeline, competency, achievements (student) + students, student-progress, activity (instructor). These are shells only — replace with real feature controllers in later phases, keep the same route names. US-601 moved 'students' and 'student-progress' out of the guest-accessible standby section into the auth+teacher group (closing a pre-existing gap — guests could previously open the instructor shells). US-602 realized '/students' as a real StudentController page (StudentOverviewTest); US-603 realized '/student-progress' as StudentProgressController with an OPTIONAL {student?} path param (bare visit redirects to the roster; controller-level 404 for non-student targets). US-606 realized '/activity' as ActivityController (see below) — StandbyController still serves only the 'achievements' shell. ShellTest's standby list now contains only achievements.

## Dashboard
`/dashboard` is now a real, auth-protected route served by DashboardController (Phase 2), no longer a standby shell. Do not re-add a placeholder for it.

## /timeline is a real, auth-protected route; rejects user-scoping parameters (IDOR)
TimelineController (US-505) replaced the /timeline standby shell (route name kept) and moved it into the auth middleware group. It takes NO user identifier: a request carrying any user-scoping parameter (user_id, userId, user, student, owner) is abort(403) — an IDOR probe must FAIL, not be silently ignored (§17). TimelineService queries are keyed only by the authenticated user. ShellTest's standby list no longer contains timeline (like assessments in US-413).

## /xp-ledger is a real, auth-protected route; rejects user-scoping parameters (IDOR)
XpLedgerController (US-506) serves GET /xp-ledger name 'xp-ledger' inside the auth group and replaced no standby shell (none existed). It takes NO user identifier, mirrors the TimelineController guard: a request carrying any of user_id/userId/user/student/owner is abort(403) before any user resolution — an IDOR probe must FAIL. Rows are keyed only by the authenticated user. 'XP Ledger' is a student nav item (✦, under System).

## /competency is a real, auth-protected route; rejects user-scoping parameters (IDOR)
CompetencyController (US-507) serves GET /competency name 'competency' inside the auth group, replacing the standby shell (name kept). Purge pattern like /timeline and /xp-ledger: any request carrying user_id/userId/user/student/owner is abort(403) before resolution; rows keyed only by the authenticated user. ShellTest's standby list no longer contains competency (real route now).

## /recommendations is a real, auth-protected route; rejects user-scoping parameters (IDOR)
RecommendationsController (US-509) serves GET /recommendations name 'recommendations' inside the auth group and replaced no standby shell (none existed). Same purge posture as /timeline, /xp-ledger, /competency: any request carrying user_id/userId/user/student/owner is abort(403) before resolution; recommendations keyed only by the authenticated user. 'Recommendations' is a student nav item (✴, under System) in layouts/app.blade.php.

## Teacher area gate: auth + 'teacher' middleware; data scope, not route scope
The teacher area (US-601) is gated by the 'teacher' middleware alias (EnsureUserIsTeacherOrAdmin) registered in bootstrap/app.php and applied via Route::middleware(['auth', 'teacher']). Guest requests are redirected to login by the auth middleware; any authenticated role other than teacher or admin (student, operator) gets abort(403, 'This area is restricted to teachers.'). The ROUTE gate stays role-based and system-wide — every teacher/admin may open every teacher route. The DATA each page renders is scoped in depth by ClassroomAccessService: a teacher monitors only the students and courses of their own ACTIVE classrooms, an admin sees the fleet. Classroom / Enrollment Authorization is a data-scoping layer, never a second route gate. Every route added by US-602..US-610 must be added INSIDE this auth+teacher group (or otherwise apply the 'teacher' middleware); never register a Phase 6 teacher route outside it.

## US-611 audits teacher-gate coverage across US-602..US-610
US-611 (Phase 6 security-review story) must explicitly re-verify that EVERY route added in US-602..US-610 carries the 'teacher' middleware — the same audit discipline US-511 taught in Phase 5, where two of six routes silently shipped without the IDOR guard and only a dedicated audit caught it. Do not wait for an incidental catch; the audit story checks the teacher-gate coverage across the whole phase as a standing checklist item.

## /activity is a real, teacher-gated route; 'student' is a legit filter, the other spellings are IDOR probes
ActivityController (US-606) serves GET /activity name 'activity' inside the auth+teacher group, replacing the standby shell (name kept). Unlike the purge-everything posture of the student pages above, the teacher feed's server-side 'student' filter is legitimate here: the 'teacher' gate is system-wide, so a filter can only narrow the roster the feed spans. The request must still reject the OTHER user-scoping spellings (user_id, userId, user, owner) with abort(403) before any resolution; timestamps (window/order) are server-computed and never accepted. The 'student' filter is validated server-side (nullable integer, exists in the404_users). The feed is composed by TimelineService::feed() (shared beat vocabulary with the student timeline), filtered server-side through ActivityFeedRequest (validated student/course/type/from/to; window defaulted to the last DEFAULT_WINDOW_DAYS days ending today, and the span capped at MAX_WINDOW_DAYS = 90 in withValidator), and paginated through the LengthAwarePaginator.

## /course-analytics is a real, teacher-gated route; rejects user-scoping probes
/course-analytics (name 'course-analytics', US-607) is a real route inside the teacher group, replacing a StandbyController shell. Like /activity, it accepts no routing params and rejects every path/query spelling that would scope a user (user_id, userId, user, student, owner) with 403.

## /students is the Teacher Dashboard (US-609); no new route
US-609 realized the Teacher Dashboard ON the established /students route (route name 'students' kept) — it is the teacher landing page (AuthController::homeFor sends teachers/admin there after login) and the student roster was already teacher-gated. No new route, no nav changes, no parallel dashboard. /dashboard is the shared auth-only STUDENT page, not teacher-eligible. The dashboard enriches the existing page: KPI row + Recent Activity panel + Assessment Summary panel above the unchanged roster filter form/table. This follows the routes.md 'no parallel mechanism' rule — any future teacher summary must live inside the auth+teacher group.

## Classroom / Enrollment Authorization: teacher read routes + admin CRUD (data-scoping layer)
Corrected model: teacher visibility derives ONLY from ACTIVE classroom membership pivots (teachers ∩ students ∩ courses per ACTIVE classroom; course-level scoping — a student is visible only for the courses actually shared with the teacher). Read side (auth+teacher group): GET /classrooms (name 'classrooms', ClassroomController::index → ClassroomAccessService::classroomsFor) lists an admin's every classroom and a teacher's own ACTIVE teaching assignments; GET /classrooms/{classroom} (name 'classrooms.show', ClassroomController::show) authorizes through ClassroomPolicy::view (a teacher their own ACTIVE classroom, an admin any — inactive/foreign gets 403). Neither route takes a user-scoping parameter. Write side (auth+admin group): admin.classrooms + .create/.store/.edit/.update and the three membership assignment POSTs admin.classrooms.teachers/.students/.courses — all writes run through ClassroomService and record an audit row for every applied change and every refusal. classroom.status (active|inactive) is an ACCESS GATE like course.status: deactivating removes teacher visibility on the spot without touching pivots, progress, or history; reactivating restores it. No delete/destroy route (§14 no-destructive posture). Bound {classroom} MUST exist in tests: like admin.courses, implicit route model binding resolves BEFORE the role gate, so a fabricated id 404s instead of 403 — authorization tests seed a real classroom in the forbidden-role matrix.

## Admin area gate: auth + 'admin' middleware, fully separate from teacher group
Phase 7 admin area (US-701) lives in its own Route::middleware(['auth', 'admin']) group in routes/web.php, one alias 'admin' (EnsureUserIsAdmin, admin role only) registered in bootstrap/app.php. Admin and teacher are NOT the same group: teacher/student/operator all get 403 on /admin routes, only 'admin' passes. Every route added by US-702..US-713 must land inside this admin group; never register an admin route in the teacher group or without the 'admin' middleware. AuthController::homeFor sends 'admin' to route('admin.dashboard') after login (teacher still → students).

## admin.courses routes registered inside the admin group (US-705)
US-705 route set inside the auth + 'admin' group: GET admin.courses (index, order_num-ordered listing), GET admin.courses.edit ({course}), PUT admin.courses.update ({course}). Bound {course} resources MUST exist in tests: implicit route model binding resolves BEFORE the role gate, so a fabricated id 404s instead of 403 — AdminAuthorizationTest seeds a course fixture in each matrix test and reuses the real course id in the admin-access test. There is intentionally NO admin.courses.create/store/destroy route (create/delete out of scope for Phase 7). AdminCourseController (index/edit/update) stays thin; writes go through CourseService::update.

## admin.courses.sections routes nested under course, cross-course section 404 + service refusal (US-706)
US-706 route set inside the auth + 'admin' group, nested under the course so the Course→Section→Mission hierarchy is explicit in the URL: GET admin.courses.sections ({course}), GET admin.courses.sections.edit ({course},{section}), PUT admin.courses.sections.update ({course},{section}). Bound resources MUST exist in tests (implicit binding resolves before the role gate) and the section MUST belong to the bound course — the controller aborts 404 on a cross-course section and SectionService also refuses at the service layer. No create/store/destroy for sections (same §14 no-destructive posture as courses). AdminSectionController (index/edit/update) stays thin; writes go through SectionService.

## Admin mission routes nest under /admin/courses/{course}/missions (US-707)
US-707 adds admin.courses.missions (GET list), admin.courses.missions.edit (GET), admin.courses.missions.update (PUT) nested under /admin/courses/{course}/missions... — the mission/course ownership is enforced both at the controller (404 on a mission whose course_id differs from the bound course) and in AdminMissionService. Keep these three route names — they replace the AdminMaintenance/ActionButton-era placeholder shells. No create/store or delete/destroy routes (§14 no-destructive posture; only the listed missions of one course are shown).

## Assessment admin routes are singular (one per course)
US-708 added three SINGULAR routes in the admin group: admin.courses.assessment (GET index), admin.courses.assessment.edit (GET), admin.courses.assessment.update (PUT). Singular because a course has exactly one Boss Challenge (unique course_id constraint), unlike the plural sections/missions collections. Edit/update 404 on a cross-course assessment.

## admin.activity route: admin-gated, not course-nested
US-709 added Route::get('/admin/activity', AdminActivityController::class)->name('admin.activity') INSIDE the auth+admin middleware group. It is deliberately not nested under a course — the trail spans every admin mutation. Extend AdminAuthorizationTest::adminRoutes() with ['name' => 'admin.activity'] (its uriFor needs no bound resource) and add an Audit Trail nav item to $adminItems in layouts/app.blade.php pointing at admin.activity.

## admin.analytics is the read-only system-wide analytics route
admin.analytics (GET /admin/analytics, AdminAnalyticsController, US-710) sits inside the auth+admin group, is NOT course-nested (fleet-wide), and is strictly read-only per §30.0: the XI ledger has no admin CRUD path. AdminAnalyticsController rejects user_id/userId/user/student/owner with 403 before any data work, same posture as CourseAnalyticsController. Add further read-only fleet analytics to this page rather than new routes, and keep any future XP writes out of this story's surface.

## admin.system is read-only, GET-only, IDOR-guarded
US-711 added GET /admin/system (name admin.system) inside the auth+admin group like admin.analytics: no writes, same verbatim user-scoping probe rejections (user_id/userId/user/student/owner -> 403 before any data work via AdminSystemController). Guard it the same way if it gains siblings. Secret-boundary tests live in AdminSystemTest: real DB failure via a bad default connection (restore in finally — RefreshDatabase rolls back through the default connection), config-secret sentinels never rendered, safeConfig reflection rejects app.key.

## Notification read-state POST routes + sidebar nav link
US-803 added POST /notifications/read-all (notifications.read-all) and POST /notifications/{notification}/read (notifications.read, whereNumber) INSIDE the existing auth group, alongside GET /notifications (name kept). whereNumber keeps the route from binding a non-numeric id; run it BEFORE the GET route so read-all/read paths cannot collide with the identifier-less center. US-802 added a Notifications nav item (✉) to the student, instructor, and admin sidebar item arrays in layouts/app.blade.php (the center is auth-group and role-agnostic).

## admin.announcements route + verb set
admin.announcements, .create, .store, .edit (GET), .update (PUT), .publish (POST), .archive (POST) all live in the auth+admin group after the admin.user.index registration. Lifecycle transitions use POST (publish/archive) since they are mutations without a conventional REST verb.

## Student academic routes require current student role
US-1102: student dashboard, learning/progression, mission and assessment actions, academic self-views, and student report export require auth plus the student role middleware. Notifications and logout remain available to every active authenticated role. Role middleware runs before implicit model binding to avoid resource-existence leaks; guests still authenticate first.
