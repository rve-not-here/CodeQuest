---
paths:
  - 'tests/**'
  - 'grader/test/integration-container.sh'
---

# Tests

## SQLite vs MariaDB test traps: no hardcoded id 1, cast aggregates, no EXPLAIN QUERY PLAN
Three traps bite the same suite under MariaDB even though SQLite is green. (1) SQLite (in-memory, no persisted AUTOINCREMENT) reuses rowids freed by a rollback, so a fresh fixture can get id 1 in every test; MariaDB/InnoDB never reuses rolled-back auto-increment ids, so hardcoded `id 1`/`resourceId()` returns 404 (implicit route-model binding resolves before the role gate). Build URIs and assertions from the created models' real ids — see AdminAuthorizationTest::boundResources()/uriFor(). (2) SUM/aggregate over an int returns a string via PDO on MariaDB, an int on SQLite: cast `(int) ...->sum('amount')` before assertSame. (3) `EXPLAIN QUERY PLAN` and its `detail` column are SQLite-only; branch on DB::connection()->getDriverName() and use `EXPLAIN ... key` on MariaDB (NotificationCenterTest).

## Reject unresolved database URLs before disposable tests or seeding
Use DisposableDatabase::allows before opening a test/demo connection. Reject nonempty connection URLs, persistent SQLite, mismatched drivers, and server databases without explicit approval, *_test names and empty Unix sockets. DB_URL can override an apparently safe database name; never relax this guard to make a test run.

## Grader container acceptance is the sandbox gate, strict mode needs rootless
grader/test/integration-container.sh builds the runner image and exercises real ephemeral sandboxes. Developer mode prints SKIP and exits 0 when no runtime is reachable; CODEQUEST_REQUIRE_SANDBOX_TESTS=1 turns that into a failure and requires a rootless runtime, so a missing or rootful sandbox never reads as acceptance. GRADER_RUNTIME pins podman or docker explicitly. CI asserts sandbox behaviour with Docker; rootlessness is a deployment posture gate verified on the private grader host.


## Current theme and competency test expectations (2026-10-22)
The theme control is a **single icon toggle** (`data-theme-toggle`, `aria-label="Switch to <next> mode"`, two `data-theme-icon` spans, no `aria-pressed`, no `data-theme-set`). ThemeToggleTest/ThemePreferenceTest assert that shape. The competency page is a `.atlas-register-table`; JourneyIntegrationTest asserts the current `data-label="Boss Challenge">Passed</td>` cell, not the retired `<dt>/<dd>` markup. Do not restore the three-option theme setter or a definition-list competency sheet just to satisfy old tests.
