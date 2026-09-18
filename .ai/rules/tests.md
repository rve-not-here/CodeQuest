---
paths:
  - 'tests/**'
---

# Tests

## SQLite vs MariaDB test traps: no hardcoded id 1, cast aggregates, no EXPLAIN QUERY PLAN
Three traps bite the same suite under MariaDB even though SQLite is green. (1) SQLite (in-memory, no persisted AUTOINCREMENT) reuses rowids freed by a rollback, so a fresh fixture can get id 1 in every test; MariaDB/InnoDB never reuses rolled-back auto-increment ids, so hardcoded `id 1`/`resourceId()` returns 404 (implicit route-model binding resolves before the role gate). Build URIs and assertions from the created models' real ids — see AdminAuthorizationTest::boundResources()/uriFor(). (2) SUM/aggregate over an int returns a string via PDO on MariaDB, an int on SQLite: cast `(int) ...->sum('amount')` before assertSame. (3) `EXPLAIN QUERY PLAN` and its `detail` column are SQLite-only; branch on DB::connection()->getDriverName() and use `EXPLAIN ... key` on MariaDB (NotificationCenterTest).
