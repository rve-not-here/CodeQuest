---
paths:
  - 'app/**'
---

# App

## PHPStan baseline contract (US-501)
PHPStan/Larastan runs at level 8 via `composer run analyse` (phpstan.neon). The phpstan-baseline.neon file freezes the 82 findings that existed at US-501; it is not permission to ignore new problems. A finding outside the baseline fails the run, and reportUnmatchedIgnoredErrors is on so a baseline entry that no longer matches also fails. New or touched code must not add per-line ignore annotations.

## Static-analysis cleanup is explicitly deferred, not pending scope
The PHPStan baseline frozen in US-501 captures ~82 pre-existing findings. The suggestion to run a dedicated follow-up story retiring those buckets is OUT of the §52 Phase 5 backlog (US-501..US-512). It is a known future cleanup, explicitly deferred — do not treat it as dangling scope or a presumed task. If a later story (e.g., US-511 regression review) legitimately touches such a file, fixing the specific in-scope finding is fine, but never hunt for extra findings beyond a story's requirement (user decision, approved US-501).
