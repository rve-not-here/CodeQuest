---
paths:
  - 'app/Exceptions/**'
---

# Exceptions

## One state-mismatch exception for all attempt lifecycle verbs
All assessment-attempt lifecycle-state mismatches (begin/submit/evaluate/retry) throw ONE exception: AssessmentAttemptStateException::mismatch($attemptId, array $expected, string $actual), which reports expected vs actual state. Do not add a new exception class per verb. Ownership failures use AssessmentAttemptAccessDeniedException; unlock failures use AssessmentNotUnlockedException.
