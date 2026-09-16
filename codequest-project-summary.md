# CodeQuest project summary

## What this is
CodeQuest is a Laravel 13 learning and assessment platform running on PHP 8.5 and MariaDB. Students learn HTML, CSS, and JavaScript by writing code from scratch in a retro CRT interface called "System 404." The project was built one reviewed user story at a time across eight phases.

The stack is Laravel 13, PHP 8.5, MariaDB, Blade, Vite, CodeMirror 6, PHPUnit, and PHPStan/Larastan at level 8.

The repository is `~/codequest`. It is not under version control. The `.ai/rules/*.md` files and `.scratch/phase*/findings-fixes.md` logs are the only durable history. The old vanilla JS/PHP application at `~/system404` is a read-only reference.

## Process used throughout
1. Each phase's spec is pasted in full (numbered §-sections, an Agile backlog of user stories, a Definition of Done, a final-report structure).
2. The agent proposes decisions about schema, security, and thresholds. The reviewer confirms them before code is written.
3. Stories implemented one at a time; each ends with a structured report (objective, what shipped, tests, security, acceptance criteria, regression, issues, status).
4. Full test suite must stay green after every story; test/assertion counts are reconciled exactly against the prior story's stated baseline.
5. Migrations are always tested against an isolated SQLite connection first; **production MariaDB (`system404`) is never touched without explicit, separate approval.**
6. A final phase report (fixed ~25–29 section structure, unique per phase) is written only once all stories are approved.
7. PHPStan baseline is a live contract: `reportUnmatchedIgnoredErrors: true`, no new per-line suppressions ever added, baseline only shrinks via genuine fixes.

## Domain model / terminology (locked early, in CONTEXT.md + ADRs)
- **Mission** = internal/DB term for a coding exercise. **Challenge** = the student-facing name for the same thing.
- **Course** → **Section** → **Mission** hierarchy (`the404_courses`, `the404_sections`, `the404_missions`).
- **Assessment** = internal term; **Boss Challenge** = student-facing name. One assessment per course (enforced by unique `course_id`).
- **XP** = spendable resource, ledger-based (`the404_xp_transactions`), never a cached balance column. Floors at 0. Awarded on mission completion, deducted on wrong submission, spent on hints/solution reveals, awarded once on Boss Challenge pass.
- **Competency** = derived, monotone 4-state ladder (NOT STARTED/DEVELOPING/PRACTICING/DEMONSTRATED) per course, never derived from XP alone, never hard-coded.
- **Achievements** = seeded catalog (`the404_achievements`/`the404_user_achievements`), idempotent server-side awarding only.
- Server is always authoritative: client never decides XP, completion, pass/fail, progression, or unlock state.
- Live code preview uses a sandboxed `srcdoc` iframe with `allow-scripts` only. Preview output does not prove correctness. The server validates with an allow-listed rule engine such as `contains`, `contains_all`, `count_tag`, and `regex`. Student code never reaches `eval()`, `exec()`, or the shell.

## Phase-by-phase summary

### Phase 1: Design system and application shell
CRT visual identity, Tailwind v4 tokens, the application shell, navigation, and reusable Blade components. Real requests exposed missing session, cache, and job tables in production. Those tables are still absent and production requests that need them still fail.

### Phase 2: Authentication and role access
Login, logout, and the `student`, `teacher`, `admin`, and `operator` roles. The roles remain plain database enum values. No framework migrations were applied to production.

### Phase 3: Student learning and missions
Learning path, lesson→challenge→code editor (CodeMirror 6)→Run(preview)/Submit(server validation) flow. `ValidationService` built (allow-listed rule engine, no code execution). Draft autosave. Progress states (NOT STARTED/IN PROGRESS/COMPLETED). New tables: `the404_sections`, `the404_mission_drafts`.

### Phase 4: Assessments and Boss Challenges
`Assessment` and `AssessmentAttempt` are separate from missions. A student becomes eligible after completing every mission in every section. Attempts move through available, started, submitted, passed, and failed states. Scoring uses the proportion of grading rules passed against `passing_score`. Students may retry after passing, but a later failure does not undo course completion. `the404_xp_transactions` gained nullable `assessment_id`. The non-null mission or assessment foreign key identifies the XP source.

### Phase 5: Student progress and rewards
The phase established the PHPStan/Larastan baseline with about 82 existing findings. It added course and section progress, `ResumeService`, the unified timeline, XP ledger, competency, achievements, and deterministic recommendations. "Continue Learning" never links directly to an assessment.

### Phase 6: Teacher dashboard
Read-only monitoring: `/students` (roster + composed dashboard), `/student-progress/{student}`, `/activity` (fleet-wide `TimelineService::feed()`), `/course-analytics`, `/needs-attention` (6 deterministic OR-union signals with named thresholds: boss_fail, repeat_fail ≥2, low_performance <60% of passing_score, stalled ≥14d, inactive ≥21d, never_started ≥14d account age). System-wide teacher visibility (no enrollment/ownership model). Found & fixed: guests could open instructor shells; a test-suite coverage gap mirroring the exact same "under-enumerated" failure shape twice (found again in Phase 7).

### Phase 7: Admin and system management
This was the first phase with real write access. It added `EnsureUserIsAdmin`, active and inactive account status, immediate lockout through `EnsureUserIsActive`, and the append-only `the404_admin_audit` table. Admins can manage users, courses, sections, missions, and assessments. `solution_code`, `validate_rule`, and `grading_rule` remain view-only. Admins cannot demote, deactivate, or delete themselves, and the active-admin count cannot fall below two. A mid-phase review found that locked and draft courses were hidden but still accessible by direct URL. `MissionController` and `AssessmentService::isUnlocked` now enforce a hard access gate. The approved production status change was applied, so admin login works. Production still has only 5 of roughly 24 expected tables.

### Phase 8: Notifications and learning engagement
The phase added `the404_notifications` and `the404_announcements`. Notification rows restrict deletion through `user_id` and use a nullable unique `(user_id, dedupe_key)` pair. Payloads store a route name and parameters, never a raw URL. `NotificationService::create()` and `linkFor()` both check the notification type against the allowed route. Announcements move through draft, published, and archived states. First publication sends the notification once and records the write through `AdminAuditService`.

Completed stories:
- US-801 added ownership-gated notification reads. `findForUser` returns null for both a foreign notification and a missing one.
- US-802 and US-803 added the paginated notification center and read state. `EXPLAIN QUERY PLAN` verifies the unread-count index. Marking a foreign, missing, or already-read row is the same idempotent no-op.
- US-804 and US-805 connected real producers to `MissionService::submit`, `AssessmentService::evaluateAttempt`, and `AchievementService::award`. Each notification shares the transaction that changes the learning state. Structural constraints and retried requests prove deduplication.
- US-806 added teacher attention notifications. It reuses `AttentionService::list()`, emits only when the signal set changes, and enforces a 24-hour cooldown. The sync runs on the teacher dashboard request.
- US-807 added announcement management, first-publication fan-out, and an audit row for every write.
- US-808 was verification-only. US-804 through US-807 already covered the event integration criteria, so no duplicate implementation was added.
- US-809 added deterministic student reminders. Draft reminders fire after 3 and 7 untouched days. Learning reminders cover stalled courses and unlocked, unattempted Boss Challenges. `StudentReminderService` reuses `AttentionService::STALL_DAYS`, runs lazily on the student's `/dashboard` request, and emits only when the reminder state changes.
- US-810 added a two-item priority notification panel to the dashboard. It reads from `NotificationService`, uses the same safe link resolver as the notification center, and shows reminders created during the same request.
- US-811 completed the notification privacy and security review. It added the dashboard scoping guard and fleet-level tests for recipient injection, sensitive data exposure, safe rendering, and CSRF middleware coverage.
- US-812 added `NotificationIntegrationTest`. One real HTTP journey now proves mission completion, Boss Challenge unlock, notification rendering and read state, Boss Challenge pass, course completion, next-course unlock, teacher monitoring, and admin statistics stay in sync.

All 12 Phase 8 stories are implemented. The Phase 8 notification suite passes at 94 tests and 641 assertions. The full suite passes at 695 tests and 3,214 assertions, and PHPStan reports no errors. The required final Phase 8 report has not been written because its exact 29-section structure must come from the reviewer.

## Recurring engineering patterns established across phases
- **Shared rules.** New consumers reuse the existing service or predicate. When a dependency cycle makes that impossible, an equivalence test pins the duplicate calculation to its source.
- **"Verification-only" stories:** several stories across phases turned out to be already-satisfied by prior work; the agent is expected to say so plainly rather than manufacture busywork (a discipline explicitly rewarded throughout).
- **Oracle-proofing:** consistently collapsing "doesn't exist" and "not yours" into identical responses (both read and write paths) to prevent ID-enumeration side channels.
- **Boundary tests.** These caught `range(1,0)` returning `[1,0]`, `#[Fillable]` dropping backdated `created_at`, Carbon 3's signed `diffInDays`, and an unqualified `catch()` resolving to the wrong class. Equivalence tests check consistency. Boundary tests check the result against a fixed expectation.
- **Fleet security reviews.** Phase 5 US-511, Phase 6 US-611/612, Phase 7 US-712, and Phase 8 US-811 each found or prevented drift across otherwise correct stories.
- **Full audit trail discipline:** every mutation writes success/failed rows to an append-only audit table; refused actions are recorded, not silent.
- **Production database changes.** Every migration is proposed, approved, and tested on isolated SQLite. Production changes require separate approval. The Phase 7 `users.status` change is the only approved production schema change so far.

## Known open items / carried risks (as of last report)
- Production only has 5 of ~24 expected tables; most Phase 3–8 features have no backing table in prod. Sequencing/approving the remaining migrations is a pending human decision.
- There is no public registration flow. Seeders or the admin user form provision accounts. This is intentional.
- The roster and activity feed compute in memory before pagination. Current fleet size keeps the cost bounded. Revisit this if the user base grows.
- The off-canvas sidebar has layout coverage but no desktop click-interaction test. This remains low priority.
- PHPStan baseline still carries a number of pre-Phase-5 legacy findings, deliberately deferred as a future cleanup story.

## Current state and next action

Phase 8 implementation is complete through US-812. The full suite and PHPStan are green. Write `.scratch/phase8/final-phase8-report.md` using the reviewer's exact 29-section structure. Do not copy or invent that structure from earlier phase reports. Do not start Phase 9 until the Phase 8 report is approved.
