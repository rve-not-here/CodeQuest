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
Laravel authenticates, checks gates, loads the mission
       |
Structural validation (existing ValidationService)
       |
Hidden tests loaded server-side from the404_mission_behavior_tests
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
JSON, order, active flag, timestamps. The model hides `configuration`
from every array and JSON dump, no student route reads the table, and
the mission relation is never eager-loaded for views. Students see only
sanitized counts such as "3 of 4 hidden tests passed". Feedback never
carries inputs, expected values, or test source.

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

`MissionGradingService` orchestrates. Legacy missions without active
tests keep pure structural validation and never call the grader.
Missions with tests require structural PASS and behavioral PASS
together. Either failure deducts the normal wrong-submission penalty.
Grader outage (unconfigured, timeout, malformed response, error result)
fails closed: no completion, no XP, and no deduction, because an
infrastructure problem is not a student failure. Client fields like
`passed`, `score`, or `xp` never influence the outcome.

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

## Production notes

Run the service on private infrastructure with a strong token, keep it
off the public internet, and set timeouts below the web request
budget. There is deliberately no insecure local-execution fallback and
no setting to enable one. If behavioral grading is required but no
grader is configured, submissions fail closed.

## Intentionally unsupported

DOM test execution, public (student-visible) tests, rich diffs quoting
hidden values, and per-case breakdowns in feedback. Async function
results are supported: thenables resolve under the same hard timeout.
