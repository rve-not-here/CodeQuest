# Behavioral JavaScript grading

Structural validation proves what a submission contains. Behavioral
grading proves what it does. A hardcoded `return 8` passes weak source
checks but fails hidden cases like `add(10, 20) => 30`. This document
describes the grading foundation: Laravel orchestration, the isolated
grader service, and the sandbox that makes hostile input safe to run.

## Architecture

```text
Student POSTs source
       |
Laravel authenticates, checks gates, loads the mission or Boss Challenge
       |
Structural validation (existing ValidationService)
       |
Hidden tests loaded server-side from the404_mission_behavior_tests
or the404_assessment_behavior_tests
       |
Private authenticated request to the grader service
       |
One ephemeral rootless container per submission
       |
Isolated Node runner evaluates and compares
       |
Strict result contract back to Laravel
       |
Laravel decides PASS or FAIL, then progress and XP
```

Laravel never executes student JavaScript. Laravel never controls the
container runtime and never sees a container socket. The grader service
is a separate component under `grader/`.

## Threat model

Student JavaScript is hostile input. Expected attacks include infinite
loops, memory and output bombs, filesystem reads and writes, child
process spawning, network exfiltration attempts, syntax bombs, and
hidden-test probing through error messages.

The defense has two layers with distinct jobs. The container is the
security boundary: no network, non-root user, read-only root
filesystem, no host mounts, no container socket, no privileged mode,
dropped capabilities, no-new-privileges, PID/CPU/memory limits, a hard
wall-clock timeout, automatic removal, and bounded output. The Node VM
context inside exists for evaluation ergonomics (function discovery,
console capture), not for isolation. Never claim VM alone is safe.

## Hidden-test storage

Table `the404_mission_behavior_tests`, one row per check: mission
foreign key, name, test type (`function` or `console`), configuration
JSON, order, active flag, timestamps. Table
`the404_assessment_behavior_tests` mirrors the same shape for a Boss
Challenge (assessment foreign key instead of mission). Both models hide
`configuration` from every array and JSON dump, no student route reads
either table, and the parent relation is never eager-loaded for views.
Students see only sanitized counts such as "3 of 4 hidden tests passed".
Feedback never carries inputs, expected values, or test source.

`BehaviorTestDefinition` is the single shaping/validation path for both
parents, so a test row cannot mean one thing for a mission and another
for an assessment. An assessment with no active hidden tests keeps the
legacy pattern-only evaluation below; this is a deliberate compatibility
path, not an implicit pass.

## Test types

Function tests name a function and list hidden argument and expected
pairs. Declaration, arrow, and function-expression styles all resolve
through an expression lookup, so formatting never matters. Comparison
is strict: `5` and `"5"` differ, key order in objects does not matter.

Console tests list the expected `console.log` and `console.info` calls
as ordered argument arrays. Source text is never searched, so a comment
containing `console.log(8)` counts for nothing.

A future `dom` type can be added with a preinstalled DOM
implementation. DOM missions keep structural validation until then.

## Structural plus behavioral grading

`MissionGradingService` orchestrates missions. Legacy missions without
active tests keep pure structural validation and never call the grader.
Missions with tests require structural PASS and behavioral PASS
together. Either failure deducts the normal wrong-submission penalty.
Grader outage (unconfigured, timeout, malformed response, error result)
fails closed: no completion, no XP, and no deduction, because an
infrastructure problem is not a student failure. Client fields like
`passed`, `score`, or `xp` never influence the outcome.

## Boss Challenge grading

`AssessmentGradingService` applies the same hidden-test contract to a
Boss Challenge through the shared `GraderClient` and
`BehaviorTestDefinition`. `AssessmentService::evaluateAttempt` scores the
combined set of checks:

- **Score** — percentage of all configured checks passed, pattern rules
  plus hidden tests, rounded to an integer. A configuration with zero
  checks scores 0 rather than passing vacuously.
- **Pass** — requires a structurally valid `grading_rule`, a
  `passing_score` of 1–100, at least one check, the combined score at or
  above the threshold, every pattern rule passing, and every configured
  hidden test passing. A high pattern-rule share cannot outvote a failed
  hidden test.
- **Evidence** — the attempt stores `tests_total`, `tests_passed`, and
  the authored rule/test failure messages in `failure_feedback`, so a
  failed verdict explains itself. Hidden inputs and expected values are
  still never stored or shown.
- **Legacy assessments** — no active hidden tests keeps the historical
  pattern-only evaluation. `tests_total` and `tests_passed` stay NULL so
  the row never claims a behavioural run that did not happen. The same
  holds while the hidden-test table's migration is still pending: no
  table means no hidden test can exist, so the pattern-only path is the
  honest evaluation for that schema state.
- **Grader unavailable** — transport failure, malformed response, or an
  oversized payload leaves the attempt `submitted` with neutral feedback
  and no score, XP, or activity row. It is not a student failure.
  Resubmitting the latest attempt re-runs evaluation because the attempt
  was never given a terminal verdict.

## Failure behavior and error types

Runner error types are `syntax`, `assertion`, `timeout`,
`resource_limit`, and `runtime`. Transport and contract problems map to
`grader_unavailable` and `invalid_response` on the Laravel side. Raw
stack traces stay server-side in logs carrying only mission id, user
id, result category, duration, and error category. Student source,
tokens, and hidden tests are never logged.

## Bounds

Source length, tests per mission, output entries, string lengths, and
execution time all have configured caps. Oversized payloads fail closed
before any container starts.

## Local development

The grader needs a rootless-capable runtime. Podman rootless is
preferred; rootless Docker works as an alternative:

```sh
podman build -t codequest-js-runner:1.0 grader/
GRADER_TOKEN="$(openssl rand -hex 32)" \
GRADER_RUNTIME=podman \
GRADER_RUNNER_IMAGE=codequest-js-runner:1.0 \
npm --prefix grader start
```

Point Laravel at it with `CODEQUEST_GRADER_URL`,
`CODEQUEST_GRADER_TOKEN`, and `CODEQUEST_GRADER_TIMEOUT_MS`.
The service binds loopback by default. Never expose it publicly and
never route it through CodeQuest's public routes.

## Container acceptance

`grader/test/integration-container.sh` builds the runner image and
exercises real ephemeral sandboxes: valid and hostile submissions,
timeout cleanup, output bounds, module and network isolation, forged
verdicts, live egress attempts, bind-mount and host-visibility probes,
read-only root, and the enforced cgroup/seccomp limits.

```sh
sh grader/test/integration-container.sh                     # developer mode
CODEQUEST_REQUIRE_SANDBOX_TESTS=1 sh grader/test/integration-container.sh
GRADER_RUNTIME=podman CODEQUEST_REQUIRE_SANDBOX_TESTS=1 sh grader/test/integration-container.sh
```

Developer mode prints `SKIP` and exits 0 when no runtime is reachable.
Strict mode turns that skip into a failure and additionally requires a
rootless runtime, so a missing or rootful sandbox can never read as
acceptance. `GRADER_RUNTIME` pins the runtime explicitly (`podman` or
`docker`); without it the script prefers podman and falls back to
docker.

CI runs the same suite with Docker to assert sandbox behaviour. The
rootless requirement stays a deployment posture gate, verified on the
private infrastructure that actually hosts the grader, because a
rootful CI runner would otherwise report a posture it does not have.

## Production notes

Run the service on private infrastructure with a strong token, keep it
off the public internet, and set timeouts below the web request
budget. There is deliberately no insecure local-execution fallback and
no setting to enable one. If behavioral grading is required but no
grader is configured, submissions fail closed.

## Intentionally unsupported

DOM test execution, public (student-visible) tests, rich diffs quoting
hidden values, and per-case inputs or expected values in feedback.
Authored rule and test failure messages are surfaced; hidden data never
is. Async function results are supported: thenables resolve under the
same hard timeout.
