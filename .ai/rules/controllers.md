---
paths:
  - 'app/Http/Controllers/**'
  - app/Http/Controllers/CompetencyController.php
  - app/Http/Controllers/StandbyController.php
  - app/Http/Controllers/NotificationController.php
  - app/Http/Controllers/StudentController.php
  - app/Http/Controllers/DashboardController.php
---

# Controllers

## FormRequest::safe() takes an array of keys in Laravel 13
`FormRequest::safe()` now requires `?array` keys — passing a string (e.g. `$request->safe('username')`) throws a TypeError. Use `$request->safe(['username'])` or `$request->safe()->only('username')`.

## Assessment controller ownership discipline
AssessmentController uses state-flag flash keys (assessment_locked/_error/_success/_info, title+message arrays). Never accept client-authoritative fields: submit validates only `code`, resolves the authenticated user's own latest attempt, and grading_rule/score/verdict are never accepted or echoed. Catches AssessmentNotUnlockedException/AssessmentAttemptStateException (flash) and AssessmentAttemptAccessDeniedException (abort 403). Route names: assessments (index), assessment.show/.start/.submit/.retry.

## TimelineController user-scoping parameter guard order
In TimelineController::__invoke, the abort(403) guard for user-scoping query parameters runs BEFORE any user resolution / view build, so a parameterized IDOR request never reaches the data layer. Keep the guard first. auth()->user() is resolved after the guard; events() is called with the authenticated user only.

## CompetencyController mirrors the Timeline/XpLedger guard-order idiom
Same invariant as TimelineController: the abort(403) user-scoping guard runs BEFORE user resolution / view build so an IDOR probe never reaches the data layer; auth()->user() is resolved after the guard; CompetencyService::overview($user) is called with the authenticated user only. Page deliberately shows no XP badge (competency never reads XP, §23.0).

## Six scoped pages share one verbatim IDOR guard
All six student-scoped GET pages (progress, section-progress, timeline, xp-ledger, competency, recommendations) must share the exact same IDOR guard: reject any of ['user_id','userId','user','student','owner'] via $request->hasAny() with abort(403) BEFORE resolving auth()->user(). US-511 found /progress and /section-progress missing it; when a new scoped page lands, copy the guard verbatim, never grow a new spelling set.

## Student overview extends the IDOR guard family to the teacher area (US-602)
StudentController (US-602) is the first teacher-facing student-data page and keeps the Phase 5 guard posture: the SAME five-spelling reject set ['user_id','userId','user','student','owner'] via $request->hasAny() + abort(403), run BEFORE any user resolution. Only the message differs ('Student overview takes no student identifier.') because the teacher list legitimately spans all students — never grow the spelling set, never let a user-scoping probe be silently ignored. When Phase 6 later adds per-student pages (US-6xx), they must keep the same five-spelling set too.

## Per-student teacher-area pages bind the student via an optional path param (US-603)
Phase 6 per-student detail pages (student-progress, US-603; US-604+ siblings) take their identifier as an OPTIONAL {} path param — /student-progress/{student?} — and authorize through the 'teacher' middleware (confirmed system-wide visibility) plus a $student->role !== 'student' abort(404). This is NOT the five-spelling IDOR guard, which stays reserved for identifier-less teacher pages like /students: a bound path param is the intended identifier, not a probe. Sections are added to the SAME page rather than new routes (US-604 Assessment Performance, US-605 Competency). Bare visit redirects to the roster. The param MUST stay optional even though the sidebar no longer links it (US-604 removed the standalone "Student Progress" item): the optional route is what turns a bare /student-progress hit into a friendly redirect to the roster instead of a 404. Never make it required.

## Standby shells must derive the layout role from the authenticated user
StandbyController passes 'role' => auth user's role (instanceof User ? role : 'student' for guests). Do NOT hardcode 'student' — it forces the student nav for authenticated teachers/operators on shells like /activity. The standby view (@extends('layouts.app', ['role' => $role ?? 'student'])) must not override with a literal either.

## /activity accepts a validated student filter, but rejects the other spellings (US-606)
ActivityController (US-606) diverges from the five-spelling purge on PURPOSE: the cross-student teacher feed legitimately takes a server-side 'student' filter, and since the 'teacher' gate is system-wide a filter can only NARROW the roster — not reach another user. So 'student' is validated (nullable integer, exists in the404_users) and passed to TimelineService::feed(), while the OTHER user-scoping spellings user_id/userId/user/owner are still abort(403) BEFORE resolution, via $request->hasAny(). Timestamps (window/order) are server-computed, never accepted. The controller passes filters + the page's paginator query into the feed and renders the activity view; it builds no events itself (business logic stays in the service). ActivityFeedTest locks the spelling list and the server-side rejection of bad filters.

## CourseAnalyticsController adds the teacher-area IDOR guard family
CourseAnalyticsController (US-607) is a no-input read page. It mirrors the TimelineController/ActivityController discipline: it stays behind the auth + 'teacher' middleware and rejects every user-scoping query-parameter spelling (user_id, userId, user, student, owner) with 403 — there is no per-student scope on this page.

## Teacher-area (auth+teacher) controllers are read-only by construction (US-611)
The entire auth+teacher area (all US-602..US-610 controllers of /students, /student-progress, /activity, /course-analytics, /needs-attention and the dashboard composition) is read-only by construction: every handler is GET|HEAD and performs no DB writes; the only write paths in the app live in AssessmentService (begin/submit/evaluate/retry) behind the STUDENT assessment POST routes (assessment.start/.submit/.retry). A write method added to any teacher-area controller or route is a US-611 regression — verify route:list stays all-GET/HEAD for the teacher group.

## Mission route group and Boss Challenge gate on course.status; sealed = 403/Challenge sealed (US-705)
course.status is an access gate (US-705 confirmed), not a publication flag: 'locked' and 'draft' seal the course. MissionController::ensureCourseActive() aborts 403 on all five mission actions (show/submit/draft/hint/reveal) BEFORE any user resolution or write. AssessmentController needs no new logic: AssessmentService::isUnlocked now requires course.status === 'active', so assessment show/start/retry already render the existing 'Challenge sealed' flash via AssessmentNotUnlockedException on a locked/draft course. Any future route that opens course content to students must run the same gate; never let a direct mission/assessment URL bypass a sealed course. Progress recorded before the seal is preserved.

## AdminActivityController: probe guard + validated feed only
AdminActivityController (US-709) is the /admin/activity read side: it mirrors ActivityController exactly — rejects user_id/userId/user/owner probes with 403 BEFORE data-layer work, then passes ONLY AdminActivityFeedRequest::safe(['actor','action','result','from','to']) into AdminAuditService::feed. 'actor' is a legit validated filter here (admin-gated, system-wide, narrows only), like 'student' on /activity. The audit trail MUST be rendered through AdminAuditService::feed — never AdminAudit::query() in a controller, and never by adding audit rows to TimelineService.

## NotificationController keeps the five-spelling IDOR guard, scoped to the caller
NotificationController (US-801) is the /notifications center inside the auth group, scoped only to the authenticated user. It keeps the SAME five-spelling IDOR guard verbatim (user_id/userId/user/student/owner via $request->hasAny() + abort(403)) before any user resolution — never grow the spelling set. No route or parameter may retrieve another user's notification.

## Notification mark-read POSTs: path-bound id, service-scoped, uniformly silent
NotificationController gained markRead/markAllRead (US-803). The GET center keeps the five-spelling hasAny() 403 guard. POST /notifications/{notification}/read takes the id as a path param (not implicit model binding — a bound foreign row would distinguish 404-vs-owned), typed int + whereNumber; ownership runs inside NotificationService::markAsRead via findForUser, so a foreign or nonexistent id is a silent redirect with no success flash, identical to an already-read duplicate (no 404/403 oracle). markAllRead mirrors it with a count flash only when >0. These are the second auth-group write family (the first being the student assessment POSTs); the teacher area stays read-only.

## US-806 carve-out: the teacher dashboard sync writes on an otherwise read-only GET
The Phase 6 read-only invariant (teacher-area handlers are GET|HEAD with no DB writes, US-611) now has exactly ONE deliberate exception: StudentController::__invoke calls AttentionNotificationService::syncFor($user) on GET /students, writing that teacher's teacher_attention rows (US-806). The IDOR guard still runs first and the write happens only after page composition. Never add other writes to teacher-area handlers; the exception must stay this narrow.

## Dashboard write carve-out extends the US-806 pattern to the student dashboard
US-809 added the SECOND deliberate write side-effect on an otherwise read-only GET: DashboardController::__invoke calls StudentReminderService::syncFor($user) after composing page data, exactly like StudentController + AttentionNotificationService (US-806). Keep the carve-out this narrow — the teacher area stays the only other write family.

## Dashboard notification teaser = display-only composition over NotificationService reads
US-810: the dashboard notification teaser is a display-only composition over the existing read paths (NotificationService::forUser / unreadCount / linkFor) — no producer and no write. PRIORITY_TYPES (assessment_unlocked, learning_reminder, draft_reminder, teacher_attention, system_announcement) stays a local controller const; event-feedback types are excluded so the panel never competes with Continue Learning. The composition runs AFTER the US-809 reminder sync so a just-emitted reminder surfaces same-visit. Links are computed via the same linkFor() map the notification center builds — never render a raw URL from data on the dashboard.

## Dashboard applies the user-scoping probe guard
US-811: DashboardController::__invoke accepts Request and rejects the five-spelling user-scoping family (user_id, userId, user, student, owner) with 403 before any resolution, mirroring NotificationController. In this app every caller-scoped surface (student dashboard, notification center) must answer a scoping probe with 403, never silently ignore it.

## Student Dashboard excludes the notification feed
The Dashboard prioritizes the next learning action and must not compose a notification-center teaser. Notifications live at the dedicated top-nav destination. Recent learning activity excludes login/logout events; notification producers and services remain unchanged.
