# Phase 7 — Findings & Fixes (running log)

## US-706 — early design check: sections carry NO status, so the US-705 class of question cannot arise at section level

Before writing the CRUD layer, the section.status access-gate question was
checked explicitly (the user asked for it up front, given US-705's correction).
Finding: the404_sections has NO status column at all — real schema verified
against the 2026_09_04_000001_create_the404_sections_table migration (id,
course_id, order_num, title, description, created_at, updated_at), and a full
grep of app/ for status/visibility/unlock reads shows every gate in the
mission/assessment path sits on course.status (MissionController::
ensureCourseActive) or assessment.status (AssessmentService::isUnlocked) —
never on a section. Sections are pure structural grouping in the Course→Section→
Mission hierarchy; unit- and service-level reads over them (LearningPathService,
SectionProgressService, ResumeService, TimelineService section-completion)
traverse the hierarchy but gate nothing. So the answer is clean: add no
section-status gate and no section-status column; course.status remains the
single catalog gate beneath which sections and missions live. No correction was
needed, no behavior is changeable by the section story.

Every genuine finding-and-fix discovered during Phase 7 earns its own line here,
in the same spirit as the US-511 IDOR drift and the Phase 6 log entries, so the
final phase report can surface it instead of folding it silently into the
story's changes. Tracked risk items are listed under "Tracked risks".

## US-701 — phpstan-baseline.neon deleted during the story; regenerated from current code (agent error, recovered)

While running the analyser I ran `rm -f phpstan-baseline.neon` at the start of
an `composer run analyse` command by mistake, destroying the frozen US-501
baseline file (the repo is not under version control, so there was no copy to
restore). Recovery was a regeneration: `phpstan analyse --generate-baseline`
recomputed ignores from the current code state.

Why this is safe here: moments before the deletion, the analyser had run against
the intact baseline and reported exactly ONE new above-baseline error
(AuthController:33, `Auth::user()` nullability in the new deactivated-login
check), which I then fixed before regenerating. So the regenerated baseline
cannot conceal any new finding from Phase 5/6/7. It covers the same 16 legacy
files (AssessmentController, MissionController, the early models, and the
original services), and inspection confirms zero Phase 5-7 files appear in it.

Honest accounting: the frozen file was recorded at 58 ignore entries / 63
ignored errors (mtime 2026-09-05). The regenerated file holds 55 ignore entries
/ 60 ignored errors across the identical legacy file set. The delta cannot be
diffed because the original is gone; the run now passes with 0 errors and 0
unmatched ignores, and reportUnmatchedIgnoredErrors stays on. The US-501
posture (new findings fail the run) is preserved. A rule is recorded in
.ai/rules to never delete or regenerate the baseline manually.

## US-701 — Deactivated account: login refusal + immediate session lockout (§13/§14)

Two enforcement points landed together so a deactivation takes effect at both
boundaries, not one:

- Login itself refuses an inactive account AFTER credentials match, with a
  distinct 'account' error (not the generic invalid-credentials message), and
  signs out that fresh attempt. AuthTest/AccountStatusTest assert it.
- EnsureUserIsActive, appended to the web middleware group (runs after the
  session starts on every web request), logs a deactivated account out, kills
  the session, and bounces to login on the very next request. This is a
  deliberate deviation from "block future logins only": the user chose the
  stronger option so an admin deactivating an account in an incident does not
  wait for the session to expire. The account's progress, XP, attempts, and
  activity rows are untouched (status is a sign-in gate, not learning state).

Design notes that must hold: 'status' stays OUT of the User #[Fillable] list
(only the admin service may set it), and course/XP/competency services never
read user.status. The 2026_09_11 migration adds `enum status active|inactive
default active` so every existing account stays active on deploy; it runs only
on the test connection under RefreshDatabase and must NOT be applied to the
production system404 DB without explicit user approval.

## US-702 — Admin Dashboard: reuse over re-derivation, dedicated audit read-path

Delivered per §8.0 with four stat panels (Accounts, Curriculum, Assessments,
Learning) plus Recent System Activity, all server-derived at request time.

Reuse decisions (user directive: reuse existing services, no parallel formulas):
- countActiveStudents() and countPassedAssessments() changed from private to
  public on TeacherDashboardService so the admin console calls the SAME
  four-source active-window UNION and passed-attempt count the teacher page
  renders — one home, teacher and admin can never disagree.
- course_completions sums CourseAnalyticsService::overview() bucket 'completed'
  (distinct students who passed each ACTIVE course — the same universe as the
  analytics page; locked/draft/zero-mission courses are excluded by that
  service's definition).
- Only account/catalog counts are new (users by status, courses/sections/
  challenges, attempt-row totals) — single bounded queries, no fan-out.
- Recent system activity reads the dedicated the404_admin_audit table via
  AdminAuditService::recent (created_at desc, id desc). Deliberately NOT
  TimelineService — admin bookkeeping is outside the learning-beat vocabulary.

Additional notes:
- The sidebar now gives the admin role its own list (admin.dashboard) instead
  of inheriting the instructor list: every instructor item is teacher-gated, so
  403s would greet admins on each.
- The audit table has NO writers yet: US-702 ships the read path (empty state
  'No administrative actions have been recorded yet.'); AdminAuditService::
  record() and the action vocabulary/refusal logging land with US-709.
- Suite: 471 tests / 1677 assertions (was 468/1640). PHPStan 0/0, baseline
  untouched (no admin files absorbed).

## US-702 — Two pre-approval confirmations (recorded, both satisfied)

The user approved US-702 pending two explicit confirmations, both now recorded:

1. Cross-domain coupling rule: `.ai/rules/services.md` now explicitly flags
   countActiveStudents()/countPassedAssessments() as PUBLIC shared API consumed
   by AdminDashboardService — a teacher-focused change to them silently changes
   admin numbers. Guard: keep both public + single-source, re-run
   AdminDashboardTest on any change.
2. Empty-state test hardened: test_dashboard_shows_an_empty_state_when_no_audit_
   rows_exist now seeds a timeline-source Activity row with a distinctive
   message and asserts recent() is empty + the message never renders — so a
   recent() rewired to a timeline source FAILS instead of passing on a silently
   broken query. AdminDashboardTest: 3 tests / 39 assertions.

## US-703 — User Management (directory + create/edit), scope line drawn at role/status

Delivered per §9.0–§12.0: /admin/users directory (server-side search over
username/name, role filter, pagination, PER_PAGE=10), per-user detail view, and
create/edit workflows. All routes registered inside the auth+admin group.

Architecture / security decisions:
- UserService is the sole user write path. create() whitelists username/name/
  role/password (status stays on its 'active' column default; operator is
  unreachable via CREATABLE_ROLES). update() sets username/name and re-hashes
  password only when non-empty. Role AND status are immutable on existing users
  in this story — explicitly deferred to US-704, which owns self-protection and
  the last-admin guards (§13). The edit form renders role/status as read-only
  badges; the update FormRequest has no role/status rules, so a posted extra
  field never enters the validated payload.
- Password is always plaintext-in, fresh hash-out: both write FormRequests carry
  a rejectPreHashedPassword() closure that fails bcrypt/argon2-prefixed values,
  so a pre-hashed password is rejected (the model cast must never double-hash a
  client-supplied hash). min:8 on create/update (login itself has none).
- Directory "Last activity" reuses TimelineService::events per user (same
  four-source vocabulary as the timeline/roster) — no new activity mechanism.
  Null → '—' for accounts with no learning beats (typical for teachers/admins).
- Non-admin roles (student/teacher/operator) get 403 on all six admin.user
  routes; guests redirect to login (write routes too).
- Empty-state-panel and grid conventions follow AdminDashboardTest; directory
  uses the .table-stack + data-label responsive table.

Suite: 489 tests / 1806 assertions (was 471/1677; AdminUserTest 17 tests, two
AdminAuthorizationTest access methods parameterized by needsUser for
show/edit). PHPStan 0/0, baseline untouched.

## US-704 — Role/status write path with air-gapped guards; first AdminAuditService::record() writer

Delivered per §13: update() now carries optional role/status changes guarded by
two invariants — no admin may demote/deactivate THEMSELVES, and no change may
remove an active admin while the fleet sits at the 2-active-admin floor. Guards
run before ANY write, so a refused request leaves the target untouched (tested).

User decisions recorded up front:
- Last-admin guard = "retain ≥ 2 active admins". With exactly two active admins,
  A cannot demote/deactivate B (the matrix's "even acting on a different admin
  account" clause becomes real); with three, B is movable while ≥ 2 remain.
  Self-protection hard-blocks regardless of fleet count, checked first.
- Deletion is deliberately OUT OF SCOPE (§14/§5: no destructive deletion, never
  destroy educational records). There is no destroy route/UI/affordance; the
  matrix's "can't delete own account" holds by there being no delete path at
  all. If deletion is ever needed it requires its own explicit story — and the
  restrict FKs (assessment_attempts, xp_transactions, user_achievements,
  admin_audit.admin_user_id) confirm a delete would 500 on any account with
  history, so this boundary is load-bearing, not cosmetic.

Design:
- Validation is server-determined against exact sets: role → Rule::in(UserService::
  ROLES) (student/teacher/admin/operator — FULL set on existing accounts, so an
  existing account CAN be moved to operator even though create() stays
  CREATABLE_ROLES-only), status → active|inactive. UserService re-checks the
  sets (InvalidArgumentException, defense-in-depth) so no non-list value can
  ever reach the guarded write.
- UserProtectionException (RuntimeException, static factories, codebase pattern)
  carries the exact refusal messages, each asserted verbatim in tests: self
  demotion ("…own role away from admin"), self deactivation ("…deactivate your
  own account"), last-admin ("…fewer than two active admins").
- AdminAuditService::record() is the ONLY writer to the ledger that US-702 read
  (supersedes the US-702/US-709 "record() lands with US-709" note). Vocabulary:
  user.role.change / user.status.change. Every actual change → 'success' row;
  every refusal → 'failed' row (written before the exception throws, so blocked
  changes are never silent); an unchanged role/status writes NO row (no-op form
  submissions stay clean). admin_username is snapshotted at action time.
- UserService::update(User $actor, User $target, array $attributes) — the actor
  is now explicit, because the guards compare actor against target (self-detection)
  and the audit needs the acting username. Controller catches
  UserProtectionException → flash 'error' (new key) + redirect back.
- The edit form replaces read-only role/status badges with selects pre-selected
  to current values (safe no-op round-trips), and renders the refusal flash as a
  'CHANGE REJECTED' status-message box.

Contract change, flagged intentionally: AdminUserTest's
test_user_edit_cannot_change_role_or_status_even_when_posted asserted the
pre-US-704 immutability; it is rewritten to test_user_edit_applies_role_and_
status_when_posted (the same PUT now honors a posted role/status). Recorded in
this log so the change is visible, not folded in silently.

Tests: new tests/Feature/AdminRoleManagementTest — 12 tests / assertions covering
§49-matrix refusals, fleet-≥2 success paths, operator-upgrade, exact-set
validation, no-op-audit cleanliness, guard-before-write atomicity, and 403-gates
for non-admins (asserting those write NO audit row). 501 tests / 1858 assertions
(was 489/1806). PHPStan 0/0, baseline untouched.

## US-705 — Course Management: index + edit/update, order_num stays the single ordering key

Delivered per §15.0–§17.0: /admin/courses lists the catalog in order_num order
and the edit page updates name/slug/type/description/status/order_num. A §17.0
status change (active/locked/draft) writes ONLY the the404_courses row — never
the404_progress — and records an audit trail on the US-704 success/failed pattern.

Documented interaction (verified by code at the time, then REVERSED by the
user during §67 review — see the "US-705 CORRECTION" entry below, which is the
operative statement): course.status was initially treated as a catalog
publication flag, NOT a per-student unlock gate. Locking a course removes it
from every status='active' consumer (DashboardService::currentCourse,
CompetencyService, CourseAnalyticsService universe, AchievementService
full_clear, RecommendationService after-completion, AttentionService
current-course) — tested via currentCourse() moving to the next active course —
but mission pages under a locked/draft course remain reachable and completable
by URL. That open access was an UNRESOLVED gap during implementation. It is now
fixed: locked/draft seal mission and assessment access (see the correction
entry). The currentCourse() fall-through test and behavior PERSIST unchanged.

Scope line drawn from the brief: list + edit only. There is deliberately NO
create/store or delete/destroy (matches "listing with editable fields"; §14
no-destructive posture; the restrict FKs make course deletion non-trivial and it
was never requested).

Design notes:
- CourseService::update() runs guards BEFORE any write (§13): status must be in
  STATUSES (active|locked|draft), name/slug/type within length, order_num a
  non-negative integer. Refusals record a 'failed' audit row then throw
  InvalidArgumentException; successes record 'course.status.change' ("Status
  changed: active → locked") and/or 'course.update' with a compact
  "field → 'value'" change list; no-op round-trips write NO row.
- Two things tripped me while writing this and are now fixed + recorded:
  1. Bound admin routes need a REAL resource in the auth-matrix tests: implicit
     {course} binding resolves before the role gate, so a fabricated id 404s
     instead of 403 (the user routes only ever "worked" by accident — the
     forbidden actor was always id 1). AdminAuthorizationTest now seeds a course
     and reuses real ids; rule recorded in routes.md.
  2. PHPStan flagged is_int() in the service as always-true because the array-shape
     PHPDoc declared order_num:int. The service is a standalone layer that must
     accept unvalidated payloads (defense-in-depth mirroring UserService), so the
     honest contract is order_num: mixed with the runtime guard doing the work.

Tests: tests/Feature/AdminCourseManagementTest — 14 tests covering listing order,
full-field update + field audit row, status change + status audit row, progress
rows untouched by a status change, currentCourse() falling through to the next
active course, locked-course missions still 200 (the documented boundary), exact-set
status rejection, unique slug, kebab-case slug, non-negative integer order_num,
no-op audit cleanliness, service-layer refusal (failed row + throw), and 403-gates
for non-admins (asserting zero audit rows). 515 tests / 1923 assertions
(was 501/1858). PHPStan 0/0, baseline untouched.

## US-705 CORRECTION (same story cycle) — locked/draft courses are a real access gate, not a publication flag

US-705's initial deliverable treated course.status as a visibility/publication
flag and pinned the resulting behavior with a test asserting a locked course's
missions still returned 200 by direct URL. That was a gap found and initially
left unaddressed, not a decision, and the user sent it back through the §67
ambiguity process rather than accept the default.

Evidence that settled it: the platform ALREADY treats status as a hard access
gate at the assessment layer — AssessmentService::isUnlocked returns false when
assessment.status !== 'active', begin/retry throw AssessmentNotUnlockedException,
and the controller renders "Challenge sealed" — while nothing gated course
access at all. The same hole extended to assessments: a locked course whose
assessment was still 'active' stayed beginnable by URL. course.status was never
writable before US-705 (no service, no UI), so the "status is only ever a
filter" pattern was the absence of a writer more than a design. And the
unaddressed leak was integrity-bearing: a student with a guessed-or-shared
mission URL could keep earning progress rows, XP, and streak achievements under
an admin's explicit lock, and those rows keep flowing into teacher dashboards
and analytics as if the course were live.

Confirmed decision (user): Option 2. Locked and draft both seal. Implemented
together with the fix:

- MissionController::ensureCourseActive() aborts 403 across the FULL mission
  route group (show/submit/draft/hint/reveal) before any user resolution or
  write. The learning-path's dimmed-but-clickable links now resolve to 403 for
  sealed courses; no view change, the gate is authority.
- AssessmentService::isUnlocked() gains course.status === 'active' as a
  precondition, so assessment show/start/retry seal through the EXISTING
  "Challenge sealed" flash path (AssessmentNotUnlockedException) with no new UI.
- Progress recorded BEFORE the seal is untouched; only new use is blocked, so
  the §17 "never rewrite student progress" rule still holds.
- The pinned test was flipped and extended: test_locked_and_draft_courses_seal_
  mission_access_and_block_progress (403 on all five actions for both statuses,
  plus zero progress/XP rows) and test_locked_and_draft_courses_seal_the_boss_
  challenge (redirect + flash for show and start, zero attempt rows), both
  built so the student is otherwise eligible, proving the seal comes from course
  status and not the eligibility gate.
- CourseService/AdminCourseController docblocks, the admin edit-view subtitle,
  and the recorded .ai/rules (services.md, controllers.md) were rewritten from
  "publication flag" to access-gate semantics.

Full suite verification: 516 tests / 1947 assertions green (was 515/1923) — the
change reaches back to Phase 3/4 mission and assessment access, and nothing in
Phases 3–6 relied on open-by-URL behavior under a sealed course. PHPStan 0/0,
baseline untouched, pint clean.

## US-707 — Mission/Challenge Management: the three up-front checks, all answered from real schema/code

Before writing any CRUD code, the three questions the user asked to verify up
front were each confirmed against the actual migration files and app code — not
assumed:

1. **mission.status does not exist.** the404_missions has NO status column
   (verified against `2026_09_04_000000_create_the404_missions_table.php` and a
   full grep of app/ for status/visibility reads). Nothing gates on a mission
   row; the access gate for everything beneath a course sits on
   course.status (MissionController::ensureCourseActive, US-705) and
   assessment.status (AssessmentService::isUnlocked). So there is no
   mission-status field to manage and no US-705-class correction can arise.
   AdminMissionService's class docblock says exactly this.
2. **Points edits are NOT retroactive — confirmed by code, then locked with a
   test.** Points are snapshotted at completion time: MissionService::submit()
   writes pts_earned (the404_progress), pts (the404_activity), xpAwarded
   (:76/:87/:95), and XpService::awardCompletion() writes the amount into a
   the404_xp_transactions row (:86); the ledger balance is the SUM of those
   rows. Editing a mission's points NEVER rewrites recorded rows — only future
   completions see the new value. That is the intended behavior (not a gap),
   and it is now pinned by tests: a points edit leaves existing
   progress/XP/activity rows at their original values and a new completion
   after the edit earns the new points.
3. **section_id needs a same-course guard — real leak.** The FK alone does not
   stop a cross-course assignment: `add_section_id_to_the404_missions_table`
   adds a nullable unsignedInteger FK to the404_sections with no composite
   (course_id, section_id) constraint. A malformed request could therefore
   render a mission under a foreign course's section in LearningPathService.
   The guard is enforced at both layers: controller 404s a mission not
   belonging to the bound course, and AdminMissionService::update() refuses
   (failed audit row + InvalidArgumentException) a section whose course_id
   differs from the mission's. Tested at both HTTP and service layer.

## US-707 — Implementation: narrow edit-only write path, solution_code/validate_rule stay view-only

Delivered per §19: /admin/courses/{course}/missions lists one course's missions
(ORDER BY order_num, the single ordering key shared with the learning path),
missions/{mission}/edit updates the NINE editable fields. The write path is
deliberately narrower than the mission table:

- AdminMissionService is the ONLY admin write path for missions (new service —
  MissionService stays the student submit flow and is untouched).
- update() writes ONLY title, description, difficulty, points, order_num,
  section_id, hints, broken_code, target_html. solution_code and validate_rule
  are view-only this story (editing the reference solution/validation rules is
  deferred) and can NOT pass through the write path: the FormRequest has no
  rules for them, safe() strips them, and the service reads only allow-listed
  keys — a crafted payload cannot reach the mission row's protected fields
  (tested at HTTP and service layer).
- Guard-before-write discipline (same as CourseService/SectionService): mission
  must belong to the course, difficulty in DIFFICULTIES, points/order_num
  non-negative ints, section_id null or a same-course section. Refusals record
  a 'failed' audit row then throw; successes record 'mission.update' with a
  field-change summary; a no-op round-trip records NOTHING. No create/delete —
  §14 no-destructive posture holds; there is no store or destroy route.
- The 404-on-cross-course pattern from CourseController/AdminSectionController
  is mirrored (edit + update both 404 a mission not in the bound course).
- AdminMissionUpdateRequest co-erces section_id to int in
  prepareForValidation() — HTML <select> posts a numeric string, and the
  service's is_int guard would otherwise reject every real submission
  (ConvertEmptyStringsToNull turns "" into null for "NO SECTION").

One PHPStan finding worth recording: Larastan types Eloquent primary keys as
plain `int` (never `int<0,max>`), so `$mission->section_id = $section->id`
cannot satisfy a typed int<0,max>|null column no matter how the section was
resolved. The fix was an honest range guard (`if ($sectionId < 0) refuse`) that
keeps the validated INPUT value — the range-narrowed type then flows to the
assignment cleanly, no cast, no @phpstan-ignore.

Suite: 548 tests / 2105 assertions green (was 530/2010 at the end of US-706 —
note: the pre-US-707 baseline, NOT 516/1947 which was the pre-US-706 count at
the end of the US-705 correction). US-707's own delta is +18 tests / +95
assertions, exactly: AdminMissionManagementTest is a NEW file of 18 test
methods (83 assertions), and AdminAuthorizationTest gained NO new test methods
but added two route-loop entries (admin.courses.missions + admin.courses.
missions.edit) to its adminRoutes() loop, which each of its 5 methods iterates
(guest asserts 2 per route for the redirect, the other four 1 per route) for
+12 assertions. So the earlier draft of this line wrongly framed the suite as
"was 516/1947" and implied the two feature files' post-story TOTALS (23/149 =
18+5 tests / 83+66 assertions) were their delta — that double error created a
phantom 9-test / 9-assertion gap that does not exist. PHPStan 0/0, baseline
untouched, pint clean.

## US-708 — Assessment/Boss Challenge Management: the three up-front checks, all answered from real schema/code

1. **Write path scope (§24.0/§26.0)** — One course has exactly one assessment:
   `the404_assessments.course_id` is a UNIQUE column (the migration),
   `AssessmentService::createForCourse` is the only creator, and `existsForCourse`
   guards in `AssessmentSeeder`. So the admin surface is edit-only, single-record,
   nested under the course, and the routes are SINGULAR
   (`admin.courses.assessment` / `.edit` / `.update`) rather than a collection.
   Editable fields: title, description, instructions, passing_score, status.
   `grading_rule` stays view-only — authored through the controlled
   `AssessmentSeeder`, exactly the `solution_code`/`validate_rule` posture from
   US-707 — and is excluded from the FormRequest rules() so a crafted payload
   cannot smuggle it in.

2. **Is assessment.status an access gate?** — YES, confirmed from
   `AssessmentService::isUnlocked`: it requires BOTH `course.status === 'active'`
   AND `assessment.status === 'active'` (plus eligibility = all missions done).
   The "Challenge sealed" student path already keys off this. So status changes
   here mirror US-705's course.status: locking/drafting seals the challenge and
   never rewrites recorded attempts. Status is therefore recorded to the audit
   ledger as its own action (`assessment.status.change`), matching
   `CourseService`'s split.

3. **Is passing_score retroactive?** — NO, confirmed from
   `AssessmentService::evaluateAttempt`: it reads `$assessment->passing_score`
   LIVE at evaluation time and writes `score`/`status`/`passed_at` into the
   attempt row. Admin edits never touch `the404_assessment_attempts`, so an
   already-evaluated verdict stands even if a later threshold change would have
   flipped it; only FUTURE submissions are judged against the new threshold.
   Same non-retroactivity class as mission points (US-707). Test-anchored with a
   2-rule grading_rule (contains SIGNAL + contains BOOT): a submission matching
   one rule scores 50; passing_score 70 fails it, lowering to 40 keeps the old
   "failed" row at score 50/passed_at NULL, and a fresh retry of the same code
   passes.

## US-708 — Implementation: narrow edit-only write path, the404_assessment_attempts never written

- `AdminAssessmentService::update()` is the only admin write path. Guards run
  BEFORE any write (assessment must belong to the course, title non-empty ≤128,
  passing_score is_int ≥0, status ∈ active|locked|draft, defense-in-depth over
  the FormRequest); a refused value records a 'failed' audit row then throws
  InvalidArgumentException (controller flashes back). Per-field else-branch is
  the US-703 `array_key_exists` default, so a browser round-trip that sends the
  current value is preserved exactly.
- Field changes record `assessment.update` ("Assessment updated: <field→value>
  list"); a status change records `assessment.status.change` ("Assessment status
  changed: active → locked"); a no-op round-trip records NOTHING. grading_rule
  never appears in the summary (not writable).
- Coercion: a `<input type="number">` posts passing_score as a numeric string,
  which passes the 'integer' form rule but IS an `is_int` false at the service
  guard — so `AdminAssessmentUpdateRequest::prepareForValidation()` casts it to
  int first (same trap and same fix as section_id on missions, US-707).
- `CourseService::ordered()` gained `withCount('assessment')` so the course
  catalog shows a Challenge column (1 ▸ when present, — when absent) linking to
  `admin.courses.assessment`; the `assessment()` relation is a singular HasOne,
  so the count attribute is `assessment_count`.
- Added two GET route entries to AdminAuthorizationTest::adminRoutes()
  (`admin.courses.assessment` needsCourse; `admin.courses.assessment.edit`
  needsCourse + needsAssessment). Every matrix method now also seeds an
  Assessment for the fabricated id 1 to resolve (bound resources must exist:
  implicit binding runs before the role gate). Assertion math: guests +2/route,
  the other four roles +1/route, admin +2 → AdminAuthorizationTest's total rose
  66 → 78.
- New AdminAssessmentManagementTest (17 methods) covers: single-row index +
  empty state + course scoping, edit render, both HTTP and service-layer
  cross-course 404/refusal with 'failed' audit rows, full 5-field update with
  its two audit rows, crafted `grading_rule` payload ignored, passing_score and
  status validation refusals, no-op writes nothing, and — the §26.0/§26
  isolation anchors — a write never creates attempt rows, sealing via status
  locks isUnlocked w/o touching attempts, and the two non-retroactivity anchors
  (old verdict preserved; new submission uses the new threshold).
- Full suite before/after this story: 548 tests / 2105 → 565 tests / 2178
  assertions. Delta +17 tests / +73 assertions = AdminAssessmentManagementTest
  (17 methods) + AdminAuthorizationTest route-loop expansion (66 → 78, +12).
  PHPStan 0/0 with the baseline untouched; pint fixed 4 files (all mechanical).

## US-709 — Implementation: administrative audit trail, opened with a fleet-wide audit

- The story opened by auditing that EVERY mutation across US-704..US-708 writes
  both 'success' and 'failed' audit rows — a dedicated sweep, not per-story
  claims. Confirmed complete for courses (fields + status), sections, missions,
  assessments (fields + status), and the role/status guards. Two gaps surfaced,
  both in UserService, and both fixed before the page was built:
- GAP 1 (creation wrote nothing): `UserService::create()` had no audit side
  effect at all. Fixed by threading the acting admin in — signature is now
  `create(User $actor, array $attributes)`, sole caller AdminUserController::
  store() passes `auth()->user()` — and recording a 'user.create' success row
  (`"User created: username → 'x', name → 'y', role → 'z'"`) against the new
  account id. Creation is a mutation; it belongs on the trail, not just later
  role/status edits.
- GAP 2 (base-field edits wrote nothing): `UserService::update()` audited only
  role/status changes; username/name/password edits were silent. Fixed: base
  fields now record 'user.update' only when they ACTUALLY change (compare to the
  stored values; password counts as changed only when non-empty AND
  Hash::check fails against the stored hash), summary `"User updated: field →
  'value', password → (changed)"` — the password value is never echoed. A no-op
  round-trip writes NO row, matching CourseService's discipline.
- Bonus hardening on the same path: unknown role/status at the SERVICE layer
  previously threw InvalidArgumentException with NO audit row. Now they record a
  'failed' `user.role.change`/`user.status.change` row (`Refused: Unknown
  role/status 'x'.`) before throwing — the course/section/mission/assessment
  services already had this refuse-then-throw flow.
- Page build (US-709, §27.0): `AdminAuditService::feed(?actor, action, result,
  from, to, paginatorQuery)` reads the append-only table newest-first with each
  filter pushed into SQL; FEED_PER_PAGE 30; `actorOptions()` (admins) + `ACTIONS`
  constant drive the filter dropdown. `AdminActivityController`
  (+ `AdminActivityFeedRequest`) mirror ActivitiesController/ActivityFeedRequest:
  rejects user_id/userId/user/owner with 403 before data work, passes ONLY
  validated filters in, and — unlike the activity timeline — anchors no default
  date window because the trail is indexed table reads, not a computed span, so
  'from'/'to' stay optional and the view defaults them via `?? ''`. Route
  `admin.activity` sits inside the auth+admin group and is deliberately NOT
  course-nested (the ledger spans the fleet). View admin/activity.blade.php
  mirrors activity.blade.php: filter form + event-log panel (actor, action
  label + summary, SUCCESS/FAILED badge, target badge, timestamp) + pagination.
  "Audit Trail" nav item added to $adminItems.
- Tests: new AdminAuditTrailTest (11 methods: newest-first render, actor/action/
  result/date-window filters, inverted window, unknown rule values, non-existent
  actor id, the four 403 probe spellings, 50-row pagination, empty state) seeds
  rows via forceFill with explicit separated timestamps (created_at not
  fillable). AdminUserTest gained 5 methods covering both gaps: create audit,
  edit base-field audit (password-not-echoed), no-op edit writes nothing, and
  both service-layer unknown-role/status refuse records a failed row + leaves
  the target untouched. AdminAuthorizationTest.adminRoutes() += admin.activity.
- Full suite before/after this story: 565 tests / 2178 → 581 tests / 2240
  assertions. Reconciliation (baseline = end-of-US-708 per the entry above, NOT
  the 548/2105 pre-US-708 number): 565 + 16 = 581, 2178 + 62 = 2240. Delta
  +16 tests / +62 assertions: AdminAuditTrailTest (+11 methods / +42
  assertions), AdminUserTest (+5 methods / +14 assertions), AdminAuthorizationTest
  route-loop expansion (78 → 84 assertions, +6 per new route: guest +2, four
  roles +1, admin +2). PHPStan 0/0 with the baseline untouched; pint fixed 4
  files (all mechanical). Fleet audit (US-704..US-708) was verified service by
  service, not by story claims — see fleet-audit-report.md §66: every service's
  record() callsites were read in code and cross-checked against test
  assertions, and only UserService had gaps (both fixed this story).

## US-710 — System Analytics: a read-only drill-down; the XP administration stats are the only genuinely new area

- Delta check first (what §29.0/§30.0 asked for vs what US-702 already had):
  US-702 AdminDashboardService::overview() supplies top-line counts (accounts/
  catalog/assessments/learning) — US-710's genuinely NEW area is fleet-level
  XP administration (§30.0: totals awarded / spent / deducted, fleet-wide) plus
  the per-course drill-down table. So the page reuses rather than duplicates:
  - System metrics: `AdminDashboardService::metrics()` — refactored out of
    overview(), which now returns ['metrics' => metrics(), 'recent_system_activity'].
    metrics() accepts an optional pre-fetched course overview so the analytics
    page never runs CourseAnalyticsService::overview() twice; course_completions
    stays the single sum over that collection ('completed' bucket).
  - Per-course table: the SAME CourseAnalyticsService::overview() collection,
    which is what course_completions sums — one source, never two.
  - XP administration: NEW `XpService::fleetSummary()` (one grouped SQL over
    the404_xp_transactions, plus a distinct-user count; no per-student fan-out).
    Type classification (awarded = mission_completed + assessment_completed;
    spent = hint_used + solution_revealed; deducted = wrong_submission) lives in
    XpService as public AWARD_TYPES/SPEND_TYPES/DEDUCTION_TYPES — the debit set
    is exactly SPEND ∪ DEDUCT, and a wrong-submission penalty clamped to a
    0-amount row still counts as deducted on intent, not sign.
- New: `AdminAnalyticsService::overview()` composes {system, roles, xp, courses}.
  roleBreakdown() is a single GROUP BY `role` normalized to the full ROLES set.
  `AdminAnalyticsController` is invokable, GET-only, aborts 403 on
  user_id/userId/user/student/owner (mirrors CourseAnalyticsController), and
  passes only the service shape to view admin/analytics.blade.php (XP
  Administration panel with 5 tiles + per-type table, Fleet by Role, System
  Snapshot, Per-Course Analysis). Nav item "System Analytics" in $adminItems.
- §30.0 read-only guarantee: the route is GET-only (asserted via the route
  collection), no controller/service path writes an XpTransaction or balance,
  and a test snapshots the ledger count + sum across the request. No CRUD here.
- Tests: new AdminAnalyticsTest (7 methods / 66 assertions: page renders all
  panels + data-metric values, XP classification by type-intent incl. the
  clamped-0 deduction and by-type ordering, per-course reuse equivalence check
  against CourseAnalyticsService, read-only + GET-only, the five 403 probes,
  empty states, roles normalized to full set). AdminAuthorizationTest route
  matrix += admin.analytics (+6 assertions, no new test methods).
- Full suite before/after: 581 tests / 2240 → 588 tests / 2312 assertions.
  Delta +7 tests / +72 assertions: AdminAnalyticsTest (+7 / +66),
  AdminAuthorizationTest (84 → 90, +6 for the new route row). PHPStan 0/0
  (baseline untouched); pint fixed 4 files (all mechanical).

## US-710 §29.0 completeness follow-up — the verbatim spec arrived after delivery

Built from a paraphrase the first time; when the verbatim §29.0 arrived the
page was checked line by line. Three lines were missing or not surfaced, all
added inside US-710 (no new story):

- `completed_challenges` (fleet-wide): raw the404_progress row count in
  AdminAnalyticsService::fleetLearningStats(), distinct from course
  completions. Test proves two progress rows = 2 completed while course
  completions stays 0.
- `pass_rate` (fleet-wide): distinct students with a passed attempt / distinct
  students with a terminal (passed|failed) attempt, one grouped SQL over
  the404_assessment_attempts (status predicates match CourseAnalyticsService),
  null when no terminal attempt exists. Tests cover 50% from one pass + one
  fail, submitted-only attempts excluded, null when nothing terminal.
- Surfacing + spec vocabulary: users active/inactive tiles and the raw `attempts`
  count moved onto the analytics page; Curriculum panel relabels Missions/
  Assessments. The view's System Snapshot became three panels that mirror the
  §29.0 Users / Curriculum / Learning headings.
- Tests: AdminAnalyticsTest 7/66 → 10/86 (+3 methods). Baseline re-checked:
  588 → 596 tests / 2312 → 2372 assertions once US-711 landed (- see below).

## US-711 — System Status (/admin/system): operational checks with a structural secrets boundary

- New-kind-of-check note (the user's explicit prompt): System Status is
  infrastructure visibility, NOT data aggregation like the rest of Phase 7.
  Three decisions made deliberately:
  - "Database connectivity" is a REAL attempt: getPdo() + SELECT 1 inside
    try/catch, status-only output (the PDO exception text is never echoed —
    DSN fragments can leak through it), driver badge + server version when up.
  - Migration status is deliberately bounded safe reads: applied count and
    latest batch from the migrations table, plus a readable flag. No pending
    computation: prod the404 tables predate the the404 migration files, so
    "pending vs applied" is meaningless there.
  - Storage/log health is filesystem state: laravel.log present, writable,
    last-modified, size; default log channel.
- Secret boundary STRUCTURAL, like sensitive model fields: SystemStatusService
  reads config only through safeConfig(), which throws on any key outside
  SAFE_CONFIG_KEYS (app.name/app.env/app.debug/logging.default). A future
  secret read must add the key to that constant first — an explicit review
  point. The controller also carries the same verbatim user-scoping probe
  rejections as the other admin read pages.
- Files: app/Services/SystemStatusService.php, AdminSystemController
  (invokable, GET-only, IDOR guard), route admin.system inside auth+admin
  group (use-statement added, same "Invalid route action" trap avoided),
  nav item "System Status" (⚙), view admin/system.blade.php (Application /
  Database / Migrations / Storage & Logs panels + NO CREDENTIALS SURFACED
  footer status).
- Tests: AdminSystemTest (5 methods / 34 assertions) — happy path with all
  badges + versions + migrations counts, the real-failure DB test (default
  connection forced onto a nonexistent sqlite file, asserted UNREACHABLE with
  no DSN in the body, default restored in finally because RefreshDatabase rolls
  back through the default connection), sentinels for DB/mail/API/session
  secrets provably absent plus real APP_KEY absent, the allowlist refused
  app.key by reflection, and the five 403 probes. AdminAuthorizationTest route
  matrix += admin.system (+6 assertions).
- Full suite: 588 → 596 tests / 2312 → 2372 assertions. Delta +8 / +60:
  AdminAnalyticsTest +3/+20, AdminSystemTest +5/+34, AdminAuthorizationTest
  +6 (90 → 96, one route row). 20 + 34 + 6 = 60. PHPStan 0/0 (baseline
  untouched); pint fixed 4 files (mechanical).

## US-712 — Admin security review: everything held; the route matrix and write-route probes were the only gaps (both suite-level)

Fleet-wide audit across US-701..US-711, the same set-recheck discipline as
US-511 and US-611. Re-verified as a set, not per story:

- (a) Gate coverage: 22 admin.* routes live (16 GET/HEAD + 6 write). All sit
  behind the auth+admin group in routes/web.php; the AdminAuthorizationTest
  matrix enumerated exactly the 16 GET routes and the 6 write routes were each
  exercised by their own management tests. Fixable gap: nothing bound the
  matrix to the live route table, so a route registered outside the group, or
  a GET route added without a matrix row, would pass silently — the exact
  US-611 enumeration shape. Two new suite-level guards close it: the drift
  test asserts every admin.* route carries the 'admin' middleware against
  Route::getRoutes(), asserts GET/HEAD routes equal the matrix, and asserts
  every non-matrix admin route is a POST/PUT write route; a second test probes
  all six write routes (users.store, users.update, courses.update,
  courses.sections.update, courses.missions.update, courses.assessment.update)
  with student/teacher/operator, each asserting 403 and no mutation (the
  per-resource management tests had only cast the teacher role at updates).
- (b) Audit rows re-run (US-709 discipline): 17 AdminAuditService::record()
  callsites, identical set to US-709 — UserService 7, CourseService 3,
  SectionService 2, AdminMissionService 2, AdminAssessmentService 3. Each
  records 'success' on an applied change, 'failed' before throwing on a
  refusal, nothing on a no-op. US-710/711 added AdminAnalyticsService and
  SystemStatusService: read-only, zero record() callsites, zero writes. No new
  mutation service exists since US-709.
- (c) Mass assignment: no Service or Controller calls ->all() or ->validated()
  into a write; every controller builds an explicit safe([...]) allowlist and
  every service maps fields deliberately (UserService builds $changes itself).
  Sensitive fields solution_code/validate_rule/grading_rule are absent from
  the request rules AND from the controllers' safe() lists, and smuggling
  tests already prove they never reach the DB at both the request and service
  layers (AdminMissionManagementTest, AdminAssessmentManagementTest). User
  create has no status seam: new adversarial test forges 'status' =>
  'inactive' in the store payload and asserts the row stays 'active'. role and
  password are admin-edit fields with request-level rule guards (pre-hashed
  passwords rejected, role in ROLES / CREATABLE_ROLES).
- (d) §49.0-style matrix (built from the user's enumerated components, §49.0
  verbatim text exists only with the user): role-escalation (non-admin 403 on
  every write route, role=admin forged in update payload, operator-upgrade),
  IDOR per resource type (user route-bound; course via bound model; nested
  section/mission/assessment cross-course attempts abort 404 at the controller
  AND throw at the service layer with a refused audit row — asserted per
  resource), self-protection/last-admin under adversarial conditions
  (self-demote, self-deactivate, demote/deactivate of the last other active
  admin at fleet=2, successful when two remain, exemption for non-active-admin
  targets — all at the HTTP boundary in AdminRoleManagementTest).

§33.0 re-verification same pass: storage/log health IS built (Storage & Logs
panel: laravel.log present/writable/last-modified/size) and it is appropriate
here, because config('logging.default') = 'stack' writes storage/logs/
laravel.log — a writability + file-stats check is real state for this app, not
decoration. The gate test now enumerates one representative key per §33.0
secret category (DB_PASSWORD, APP_KEY, session secrets, API keys, credentials)
and asserts each is refused by safeConfig(); caught the unqualified-catch
namespace trap that silently never matched the SPL exception.

- Full suite: 596 → 599 tests / 2372 → 2444 assertions. Delta +3 tests / +72
  assertions: AdminAuthorizationTest +2/+65 (drift guard ~45, write-route 403s
  20), AdminSystemTest +4 (gate category coverage), AdminUserTest +1/+3
  (forged status on create). 65 + 4 + 3 = 72. Confirmed by per-file runs
  (AdminAuthorizationTest 7/161, AdminSystemTest 5/38, AdminUserTest 24/120).
  PHPStan 0/0 (baseline untouched); pint fixed 2 files (mechanical).

## US-713 — End-to-end administrative integration: the fifth anchor, and it held

AdministrativeIntegrationTest, the §48.0 anchor required to run against REAL
services and REAL database state (no mocks), completing the harness alongside
Phase 4/5/6's four. Reuses the courseWithAssessment/passMission/passChallenge
harness verbatim. Two tests, 84 assertions:

- test_admin_curriculum_edits_flow_through_to_student_completion_and_teacher_
  monitoring: a student completes mission one BEFORE the admin edits (a
  recorded baseline). Then four edits through the real whitelisted PUT routes —
  course rename (slug stable), section reorder (the missions section and a
  fresh second section swap order_num), mission points 40 → 60 (unattempted
  mission), passing_score 70 → 100 — each asserted to write its success audit
  row. Student side then reads the changed catalog: renamed course on
  dashboard and learning path, sections in the new order (SectionService),
  mission page shows the edited +60 XP, the edited mission completes at the new
  award, the threshold edit judged the future submission (score 100 clears the
  new 100), course completes, next course unlocks, teacher side shows the
  renamed course with COMPLETED 1 / IN PROGRESS 0/1 · 0% / DEMONSTRATED 1 and
  PASSED on the per-student page. Pre-edit progress stayed at pts_earned 30.
- test_locking_a_course_mid_journey_seals_the_student_and_unlocking_restores_
  access: the US-705 fix revisited end to end. Lock mid-journey → mission.show
  and mission.submit 403 before any write, assessment reads sealed, no new
  progress/XP lands, pre-seal progress untouched, and the locked course leaves
  DashboardService::currentCourse (beta becomes current). Unlock → mission
  access restored without repair, remaining mission completes at 40, current
  course returns to alpha, Boss pass completes the chain and unlocks beta.
- Full suite: 599 → 601 tests / 2444 → 2528 assertions. Delta +2 / +84, both
  in the new anchor. No regressions across the four prior anchors or the rest
  of the suite. PHPStan 0/0 (baseline untouched); pint fixed 1 file (blank
  line at EOF).

## Tracked risks

- US-703: the directory's "Last activity" column calls TimelineService::events()
  per user row (compose() runs 4 source queries + a LearningPathService::build
  per user). At the current fleet scale this is bounded by the directory's
  10-per-page SQL filter, mirroring the accepted US-602 roster and US-606 feed
  trade-offs — a named N+1 only if the user table grows well past the fleet
  scale. Revisit together with the US-602 roster note if it does.