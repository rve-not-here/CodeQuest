---
paths:
  - .env
  - '**'
  - phpstan.neon
  - '.scratch/phase*/**'
---

# General

## Production DB lacks framework session/cache tables
The production MariaDB (system404) has NO sessions/cache/jobs tables, but .env sets SESSION_DRIVER=database and CACHE_STORE=database. Real HTTP requests therefore 500 with 'Table system404.sessions doesn't exist'. This is pre-existing and out of scope — do NOT run framework migrations against the production DB (it is already in production; the app adapts to the existing the404_* schema). Feature tests pass because phpunit.xml overrides these to array drivers.

## Production DB has only the five the404_* domain tables — no framework tables exist
CORRECTION (2026-09-12): the prior claim that framework tables (sessions, cache, cache_locks, jobs, job_batches, failed_jobs, migrations, users, password_reset_tokens) "were created in the production DB" was found to be FALSE during status-migration verification and has been replaced by this accurate inventory. Live inventory of the production system404 DB shows exactly 5 tables: the404_users, the404_courses, the404_missions, the404_progress, the404_activity. There is no migrations table, no jobs table, no sessions/cache tables, and no other framework table. Consequence: `php artisan migrate` against prod would CREATE the migrations table as a side effect — apply changes via the exact DDL instead, with explicit approval. The 500s observed against prod (e.g. missing system404.jobs, the404_sections, the404_assessments) are this schema drift, not code bugs. the404_* domain tables are untouched; do not run migrations or DDL for domain changes without explicit approval.

## Report test-count baselines against the prior story's stated number; surface unexplained deltas
When reporting a test-suite delta, the "up from N" baseline MUST equal the N stated in the immediately preceding story's own §58 report. Any test added outside a story's discrete change (e.g. a follow-up fix or edge-case test written in a later turn) must be reported as its own explicit increment, and carried-over items must remain in the carried-forward list until explicitly resolved. Never present a number that differs from the prior report without explaining the delta.

## Phase 5 story numbering map (US-501..US-512)
Pin this Phase 5 mapping: US-501 = PHPStan/Larastan baseline contract (82 findings frozen, bucket retirement explicitly deferred); US-502 = course progress page (CourseProgressService); US-503 = section progress page (SectionProgressService); US-504 = ResumeService/"Continue Learning"; US-505 = timeline; US-506 = XP ledger; US-507 = competency; US-508 = achievements (the 2026_09_05_000001/2 migrations + AchievementSeeder belong here, not earlier); US-509 = recommendations + review queue; US-510 = one-pass chain propagation proof (ProgressIntegrationTest, 2 tests/59 assertions); US-511 = cross-cutting security review (found /progress and /section-progress missing the IDOR guard); US-512 = end-to-end click-through journey (JourneyIntegrationTest, 1 test/41 assertions). Do not cite story numbers in docblocks from memory; check this map first.

## Phase report process (§62/§64/§65) lives outside the repo
§62 story reports, §64 phase definition of done, and §65 required final-report structure (23 numbered sections; §14 reconciles test/assertion counts to the approved final numbers, §19 carries findings like the US-511 IDOR drift as stated finding-and-fix, §21 traces §64 line by line as Passed/Failed/Deferred) exist only with the user, never in the repo. When asked for a phase report, take the structure and checklist from the user's message verbatim, ask if it was not provided, and never invent the process numbers. Do not start the next phase before the reviewer approves the report.

## phpstan-baseline.neon must never be deleted or regenerated manually
phpstan-baseline.neon is the frozen US-501 contract and is NOT under version control (non-git repo). Never delete or regenerate it manually: regeneration recomputes ignores from current code and can silently absorb NEW findings into the baseline, defeating the 'new findings fail the run' posture (reportUnmatchedIgnoredErrors is on). If it is ever lost, regeneration is the only recovery; afterwards audit the generated file to confirm no story-added file was absorbed and disclose the entry/error-count delta honestly in the findings log. Regenerating to clear a real new PHPDoc-type error is the wrong fix — fix the code.

## No ad-hoc credential edits on the real production system404 DB
Never modify credentials or any user row on the real production system404 DB via tinker or ad-hoc methods (UPDATE/INSERT/DELETE). Same approval standard as schema migrations: explicit user approval first, per account and per change. This rule was added after a 2026-09-12 incident where temp password edits on the 'operator' row lost its original hash (recovered role-wise to secret123; original unrecoverable).

## Final phase reports stored in the phase scratch dir
On 2026-09-12 the user directed that final phase reports be saved to a file in-repo as well as delivered in chat. The Phase 7 final report lives at .scratch/phase7/final-phase7-report.md. This supersedes the older 'phase reports exist only with the user, never in the repo' line in general.md for FINAL reports only; story-level §66 reports still live with the user. When writing a final report for a later phase, save it as .scratch/phase<N>/final-phase<N>-report.md and keep the §56 structure the user supplies verbatim.
