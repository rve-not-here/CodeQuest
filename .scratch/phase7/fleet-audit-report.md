# §66: Fleet Audit: US-704 through US-708 (audit-write coverage)

## US-710 follow-up: System Analytics reuses the fleet aggregates, writes nothing

US-710 (System Analytics, §29.0/§30.0) added one new fleet page and audited
that it does NOT re-derive numbers the fleet services already produce.

### Reuse map (read in code, line-referenced)

| Figure on /admin/analytics | Single source | Where |
|---|---|---|
| Accounts/courses/sections/challenges/attempts, active students, assessments passed, course completions | AdminDashboardService::metrics() | AdminDashboardService.php |
| Per-course buckets, avg completion, pass rate | CourseAnalyticsService::overview() | CourseAnalyticsService.php |
| XP awarded / spent / deducted / outstanding / accounts | XpService::fleetSummary() | XpService.php |

AdminAnalyticsService composes all three and adds exactly one new bounded
query: the fleet role breakdown (single GROUP BY role, normalized to ROLES).
course_completions is computed from the SAME overview() collection the page
renders, so the per-course table and the total can never disagree, and the
dashboard path passes an already-fetched collection to metrics() so the page
does not run the course aggregates twice.

### No-write audit (the §30.0 guarantee)

Every route in the admin group is listed by the AdminAuthorizationTest matrix;
admin.analytics is registered in it and is GET-only (asserted from the route
collection). There is no controller or service path that creates, updates, or
deletes an XpTransaction or any balance, and a test snapshots the ledger count
plus sum across a page load to prove a visit writes nothing.

### Verification

- Full suite: 588 tests, 2312 assertions: all green. Delta over end-of-US-709
  (581 / 2240): +7 tests / +72 assertions. AdminAnalyticsTest +7 / +66;
  AdminAuthorizationTest route matrix 84 → 90 (+6 for the admin.analytics row,
  +2 guest + 4 roles + 2 admin per the established per-route accounting).
  66 + 6 = 72. Confirmed by per-file runs (AdminAnalyticsTest 7/66,
  AdminAuthorizationTest 5/90).
- PHPStan: 0 errors, baseline unchanged
- Pint: clean after a mechanical pass (4 files)
- Reuse regression proof is a test: test_analytics_page_reuses_the_course_analytics_aggregates
  and test_xp_summary_classifies_by_type_intent_not_amount_sign.

## §29.0 completeness check: every line traced to a page (post-approval gap fix)

The verbatim §29.0 spec was provided after the initial delivery, and the
analytics page plus the admin dashboard were checked line by line. Lines that
only existed as course-level or dashboard data were added to the analytics
page in a small follow-up within US-710. Trace table (gaps marked +, all on
/admin/analytics unless noted):

| §29.0 line | Where | Value |
|---|---|---|
| Users: total / students / teachers / administrators | Fleet by Role panel (+ Users panel keeps the dashboard's total) | roles GROUP BY, normalized to ROLES |
| Active / inactive users | Users panel (Users total/active/inactive) + | AdminDashboardService::metrics() |
| Curriculum: courses / sections | Curriculum panel | metrics() |
| Missions (spec wording) | Curriculum panel, tile "Missions" + | metrics() challenges key (the404_missions) |
| Assessments (spec wording) | Curriculum panel, tile "Assessments" + | metrics() boss_challenges key |
| Learning: active students | Learning panel | metrics() (14-day bound) |
| Completed challenges | Learning panel + | NEW: raw the404_progress count, fleet-wide, distinct from course completions |
| Course completions | Learning panel | metrics(), single sum over the shared CourseAnalyticsService overview |
| Assessment attempts | Learning panel + | metrics() attempts key (raw count, distinct from pass rate) |
| Assessment pass rate | Learning panel + | NEW: distinct passed / distinct terminal (passed\|failed) students, single grouped SQL |
| XP: awarded / spent / deducted | XP Administration panel | XpService::fleetSummary() (deducted, not "outstanding") |

The two new figures live in AdminAnalyticsService::fleetLearningStats() and
merge into the system key; the page's three-way panel split mirrors the
Users / Curriculum / Learning headings. AdminAnalyticsTest went 7/66 → 10/86
(+3 tests: completed-challenges counts progress rows not course passes,
distinct-student pass rate, null pass rate with no terminal attempt).

### US-711 verification: System Status boundary and no-secrets proof

- Full suite this batch: 588 / 2312 → 596 tests / 2372 assertions. Delta +8
  tests / +60 assertions: AdminAnalyticsTest +3 / +20, AdminSystemTest +5 /
  +34, AdminAuthorizationTest 90 → 96 (+6, admin.system row). 20 + 34 + 6 = 60.
  Confirmed by per-file runs (AdminAnalyticsTest 10/86, AdminSystemTest 5/34,
  AdminAuthorizationTest 5/96).
- Real connectivity: one test forces database.default onto a nonexistent
  sqlite file and asserts the page reports UNREACHABLE with no DSN leakage
  (and restores the default in finally — RefreshDatabase rolls back through
  the default connection, a poisoned default cascades "cannot start a
  transaction within a transaction" into every later test).
- Secret boundary: SystemStatusService reads config only via safeConfig(),
  which throws on any key outside SAFE_CONFIG_KEYS (app.name/env/debug,
  logging.default). Tests plant sentinels for DB/mail/API secrets and reject
  app.key by reflection; the page is asserted free of the real APP_KEY and all
  sentinels.
- PHPStan: 0 errors, baseline unchanged. Pint: clean (4 files mechanical).

## US-712 verification: fleet-wide admin security review (US-511/US-611 discipline)

Re-verified the whole Phase 7 admin surface as a set, not story by story:

- Gate: 22 admin.* routes, all carrying the 'admin' middleware in the live
  route table (asserted against Route::getRoutes() by the new drift test);
  matrix = the 16 GET routes, 6 write routes are POST/PUT. Two suite-level gaps
  closed (the exact US-611 enumeration shape): the matrix is now bound to the
  router so a later route outside the group or a forgotten GET row fails; and
  every write route is probed with student/teacher/operator (403, no mutation),
  where the per-resource tests had only cast teacher.
- Audit rows re-run: 17 record() callsites, same five mutation services as
  US-709; the two new services (AdminAnalyticsService, SystemStatusService)
  are read-only with zero audit and zero writes.
- Mass assignment: no ->all()/->validated() into any write; every controller
  whitelists via safe([...]); solution_code/validate_rule/grading_rule are
  absent from request rules and controllers, and smuggling tests cover both
  the request and service layers. Forged status on user create added and
  asserted dropped.
- §49.0-matrix components (role escalation, IDOR per resource type, self and
  last-admin guards) all hold under adversarial payloads at the HTTP boundary;
  nested resource cross-course attempts 404 and throw with a refused audit row.
- §33.0 re-confirmed: Storage & Logs panel is built and appropriate
  (log channel writes storage/logs/laravel.log); the allowlist gate test now
  names a representative key per secret category, not just planted sentinels.
- Full suite: 596 → 599 tests / 2372 → 2444 assertions. Delta +3 / +72:
  AdminAuthorizationTest +2/+65, AdminSystemTest +0/+4, AdminUserTest +1/+3
  (65+4+3=72). PHPStan 0/0 baseline; pint clean (2 files mechanical).

## US-713 verification: end-to-end administrative integration (fifth anchor)

AdministrativeIntegrationTest is the §48.0 anchor: real services and real
database state, no mocks, sharing the courseWithAssessment/passMission/
passChallenge harness with the other four anchors. Deltas:
599 → 601 tests / 2444 → 2528 assertions (+2 / +84, both new anchor tests);
PHPStan 0/0 baseline; pint clean (1 file mechanical). Proves the two chains
Phase 7 owns:

- Curriculum edits survive the student journey: rename, section reorder,
  mission points, and passing_score edits (each through the real PUT route,
  each with its success audit row) leave mission access, learning path, and
  Boss eligibility valid; the points edit awards at the new rate going forward
  without rewriting the earlier completion; the threshold edit governs the next
  submission; completion and next-course unlock proceed; the teacher side
  (Phase 6) reflects the renamed catalog immediately.
- The US-705 lock fix revisited end to end: locking mid-journey seals missions
  (403 before any write) and the challenge, records nothing, never rewrites
  pre-seal progress, and drops the course from the active universe
  (DashboardService::currentCourse); unlocking restores access cleanly and the
  whole remaining chain (mission, Boss pass, next-course unlock) runs normally.

## How the audit was done

Not per-story claims. Every `AdminAuditService::record()` callsite in the five
admin-mutation services was read in code, then cross-checked against the
`the404_admin_audit` assertions in the matching test file, confirming each
service writes a 'success' row on an actual change, a 'failed' row before
throwing on a refusal, and NOTHING on a no-op round-trip. Services carrying a
to-do or "not yet" statement are named as such; none were.

## Result: service by service

| Service | record() callsites | Success rows | Failed rows (refuse-before-throw) | No-op | Verdict |
|---|---|---|---|---|---|
| UserService (US-704) | 7 (UserService.php:156,238,251,264,287,335,359) | create, edit, role, status changes | role/status edits + service-layer unknown shapes | writes nothing | Had 2 gaps, fixed here |
| CourseService (US-705) | 3 (CourseService.php:124,138,161) | course.update, course.status.change | invalid values, unknown status | writes nothing | Already correct |
| SectionService (US-706) | 2 (SectionService.php:97,120) | section.update | cross-course, invalid title/order | writes nothing | Already correct |
| AdminMissionService (US-707) | 2 (AdminMissionService.php:179,202) | mission.update | cross-course, invalid difficulty/points/order/section | writes nothing | Already correct |
| AdminAssessmentService (US-708) | 3 (AdminAssessmentService.php:126,140,163) | assessment.update, assessment.status.change | cross-course, invalid title/passing_score/status | writes nothing | Already correct |

Test assertions per service (`the404_admin_audit` hits): AdminRoleManagementTest
11, AdminUserTest 7, AdminCourseManagementTest 4, AdminSectionManagementTest 4,
AdminMissionManagementTest 5, AdminAssessmentManagementTest 4. Each includes at
least one 'success' and at least one 'failed' assertion.

## Gaps found and fixed (US-709)

Both in UserService, both fixed before the admin trail page was built:

### Gap 1: User creation wrote nothing
UserService::create() had no audit side-effect at all. Fixed: the method now
takes `User $actor` as its first argument (sole caller AdminUserController::store
passes auth()->user()), and records a 'user.create' success row targeting the
new account. Creation is a mutation; it now shows on the trail.

### Gap 2: Base-field edits (username/name/password) wrote nothing
UserService::update() audited only role/status changes. Fixed: base fields now
record a 'user.update' success row only when they ACTUALLY change (password
counts as changed only when non-empty AND Hash::check fails against the stored
hash), summary "field → 'value', password → (changed)": the password value is
never echoed. No-op round-trips write no row, matching CourseService.

## Bonus hardening

The defense-in-depth throws for unknown role/status shapes in
UserService::update() (unreachable via HTTP because the FormRequest validates
first) now record 'failed' rows before throwing: matching every other service's
refuse-then-throw discipline so no service-layer refusal is silent.

## Verification

- Full suite: 581 tests, 2240 assertions: all green
- Reconciliation: baseline = end-of-US-708 (565 tests / 2178 assertions, per
  findings-fixes.md US-708 entry). 565 + 16 = 581 tests; 2178 + 62 = 2240
  assertions. Delta: AdminAuditTrailTest +11/+42, AdminUserTest +5/+14,
  AdminAuthorizationTest +0/+6 (admin.activity row in the route matrix).
  The 548 tests / 2105 assertions number is the pre-US-708 baseline, not the
  US-709 one.
- PHPStan: 0 errors, baseline unchanged
- Pint: clean (4 files, all mechanical formatting)
- AdminAuthorizationTest: admin.activity added to the adminRoutes() matrix
- findings-fixes.md: US-709 entry records the per-service audit above