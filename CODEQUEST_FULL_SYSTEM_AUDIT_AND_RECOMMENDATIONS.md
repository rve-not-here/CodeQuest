# CodeQuest Full System Audit and Recommendations

Current results: [2026-10-07 independent continuation, sections A–V](#2026-10-07-final-independent-continuation). Earlier sections are historical evidence, not current release status. The reviewed changes are verified at the documented boundaries; deployment and complete browser acceptance remain blocked.

Audit date: 2026-10-02. Repository: `/home/vmc/Projects/CodeQuest`. This is a fresh inspection of the current working tree. The implementation was not edited. Only this requested report was added; verification artifacts were disposable or ignored.

## 1. Executive Summary

The system is **not ready for full-system E2E/UAT acceptance**. Student JavaScript can forge a passing behavioral-grader result. Generic Composer setup includes migrations expressly forbidden against the existing production database. Targeted probes also reproduced editor restoration failures, rejected browser-form values, impossible assessment thresholds, misleading availability, hidden required missions, broken teacher navigation and non-atomic administrative changes.

The substantial passing suite supports important strengths: server-side role enforcement, exact classroom student-course authorization, historical Boss-pass completion, protected Knowledge Check scoring, transactional mission completion, an XP ledger, notification ownership and hardened exports. Preserve these decisions while repairing the boundaries that the tests miss.

There are 38 top-level findings. Confirmed defects, runtime-verification risks, architectural recommendations and optional work are distinguished below. Unavailable production/browser/container checks are not represented as passed checks or confirmed outages.

## 2. Audit Scope

Reviewed student, teacher and admin entry points; authentication and middleware; courses, sections and missions; lessons, coding workspace and unlocking; Knowledge Checks and Boss attempts; XP and achievements; skills and competencies; learning paths, recommendations and resume; classrooms and teacher monitoring; reports and exports; administrative account/content operations; database fixtures and versioning; grader execution; tests, build and deployment configuration. Shared navigation also exposes the reserved operator role, which was considered where relevant.

Traces followed UI → request → route/middleware → authorization → controller → service → persistence → response → rendered state. Security, academic state, classroom scope and newly integrated UI boundaries received deeper inspection. This is a broad repository audit, not a line-by-line certification, live penetration test or browser accessibility certification. No production schema operations or academic writes ran.

| Authority | Treatment |
| --- | --- |
| Current user instructions and AGENTS.md | Binding audit scope and production database safety. |
| `CODEQUEST_MASTER_SPEC_FINAL_UPDATED.md`, revised 2026-09-24 | Primary product authority, including revised learning/workspace/report contracts. |
| `CONTEXT.md`, `docs/adr/`, area rules mapped by `.ai/rules/index.md` | Accepted decisions and constraints, subject to current instructions and master specification. |
| Accepted Phase 4 contracts | Preserve percentage scoring, retry/history and historical pass semantics. No scoring redesign is recommended. |
| `docs/design/` and prototype assets | Visual inputs; prototype state is not academic authority. |
| Historical phase reports and prior audit material | Context only; current findings and verification were independently gathered. |

Specification discrepancies are explicitly recorded in F20/F21/F24. Read-model disagreement is recorded in F07/F15. Database documentation conflicts are recorded in F31/F37; current instructions take precedence, but only a live read-only inventory can establish actual schema state.

## 3. Methodology and Tools Used

Read repository rules, specifications, relevant architecture/domain documentation, controllers, requests, services, models, migrations, seeders, templates, frontend/grader code and test contracts. Applied Laravel best practices, testing best practices, codebase design, Tailwind development and unslop skills. The code-review skill was inspected; its fixed-base diff workflow does not fit this whole-system request. No implementation refactor or committed regression tests were made.

Used code searches, package-version inspection, Artisan route enumeration, PHPUnit, PHPStan/Larastan, Vite, Node tests, Composer validation, advisory checks and disposable adversarial probes. Laravel Boost/live schema tools, a browser runtime and Docker/Podman were unavailable in this session. No replacement installation was attempted.

| Check actually run | Result and limitation |
| --- | --- |
| `php artisan test --compact` | 1,180 passed, 10 skipped, 6,685 assertions; 1,190 discovered tests, about 29.7 seconds. |
| Skip-source inspection | Nine academic concurrency cases require disposable MariaDB; one MariaDB report-index audit also skips on SQLite. |
| `composer run analyse` | Passed, zero reported new errors against the frozen baseline. Initial sandbox worker-socket failure was resolved by approved unrestricted execution. |
| `npm run build` | Passed with Vite 8.2.2; editor chunk-size advisory. Local Node 26.10.0 differs from CI/grader Node 22. |
| `node --test grader/test/runner.test.js` | 22 passed after approved unrestricted execution. Initial sandbox subprocess failures were environmental. No container/service integration run. |
| `composer validate --strict` | Passed. |
| Installed-version inspection | PHP 8.5.10, Laravel 13.30.1, Larastan 3.11.0, PHPUnit 12.5.34, Dompdf wrapper 3.1.2; JS manifests inspected. |
| `php artisan route:list --except-vendor --json` | Successful route/middleware inventory. |
| `npm audit --json` | Completed after approved network retry; zero reported vulnerabilities across 165 dependencies. |
| `composer audit --format=json` | Inconclusive. Approved retry timed out retrieving Packagist security advisories. |
| Temporary SQLite feature probes | Eight tests, 18 assertions confirmed observed bad behavior. They assert observations, not intended correctness. |
| Direct validation probes | Empty/bad JSON/empty-list rules passed with zero checks; scalar rule member raised TypeError. |
| Exact emitted editor expression | Multiline source reproduced JSON.parse failure with the templates' PHP encoding flags. |
| Adversarial runner subprocesses | Incorrect control failed; forged passing JSON passed; different suffixes after 1,000 characters compared equal. |
| Git status | Clean before audit and after build; final change is this report only. |

The eight SQLite probes established HTML-string course/mission edit rejection, accepted threshold 101, locked Boss shown ready, unsectioned mission omitted, teacher menu destinations returning 403, array search returning 500, and a persisted course edit after injected audit failure. They used the PHPUnit in-memory connection, not production.

Session evidence artifacts include `/tmp/codequest-audit-tests.log`, `/tmp/codequest-audit-phpstan.log`, `/tmp/codequest-audit-grader-escalated.log`, `/tmp/codequest-audit-probes.log`, `/tmp/codequest-audit-routes.json`, `/tmp/codequest-audit-npm-advisories.json`, `/tmp/codequest-audit-composer-advisories.json` and `/tmp/codequest-audit-grader-probes.json`. All material results and recommendations are included here; these temporary logs are not required reading. No credentials are reproduced.


### Main finding register

| ID | Finding | Severity | Priority | Classification |
| --- | --- | --- | --- | --- |
| F01 | Student code can forge the behavioral-grader verdict | Critical | P0 | Confirmed defect |
| F02 | Generic setup can invoke forbidden production migrations | High | P0 | Confirmed defect |
| F03 | Multiline source crashes editor initialization | High | P1 | Confirmed defect |
| F04 | Browser numeric fields fail strict admin validation | High | P1 | Confirmed defect |
| F05 | Missing/malformed mission rules fail open | High | P1 | Confirmed defect |
| F06 | Admin can set an impossible Boss threshold | High | P1 | Confirmed defect |
| F07 | Read models advertise actions the server locks | Medium | P1 | Confirmed defect |
| F08 | Unsectioned required missions disappear from the journey | High | P1 | Confirmed defect |
| F09 | Array-valued mission search returns 500 | Medium | P1 | Confirmed defect |
| F10 | Teacher navigation advertises student-only destinations | Medium | P1 | Confirmed defect |
| F11 | Admin mutation and audit are not atomic | High | P1 | Confirmed defect |
| F12 | Submitted Boss attempts lack demonstrated recovery | High | P1 | Runtime verification required |
| F13 | Replayed failures can deduct XP repeatedly | Medium | P1 | Confirmed defect |
| F14 | Assistance purchases and controls disagree with available content | Medium | P2 | Confirmed defect |
| F15 | Resume and learning path use different draft priorities | Medium | P2 | Confirmed defect |
| F16 | Drafts lack version identity and can race completion | Medium | P2 | Runtime verification required |
| F17 | Version hooks miss membership changes and can collide | Medium | P2 | Runtime verification required |
| F18 | General seeding bypasses curriculum version hooks | High | P2 | Confirmed defect |
| F19 | Tied course order bypasses the predecessor gate | High | P1 | Confirmed defect |
| F20 | The specified ungraded Experiment step is missing | Medium | P1 | Confirmed defect |
| F21 | Workspace omits required accessible resizing/pane controls | Medium | P1 | Confirmed defect |
| F22 | External student fonts violate the declared CSP | Low | P2 | Confirmed defect |
| F23 | Teacher scope hides key learning activity and KC detail | Medium | P1 | Confirmed defect |
| F24 | Report endpoints lack a complete discoverable report UI | Medium | P1 | Confirmed defect |
| F25 | Mission submissions lack an immutable academic journal | Medium | P2 | Architectural recommendation |
| F26 | Expensive flows lack application admission controls | High | P1 | Architectural recommendation |
| F27 | Grading holds a database lock across external work | Medium | P2 | Architectural recommendation |
| F28 | Minimum active-admin guard can race | High | P1 | Runtime verification required |
| F29 | Grader pipe/timeout cleanup requires runtime proof | High | P2 | Runtime verification required |
| F30 | CI omits critical integration boundaries | High | P1 | Architectural recommendation |
| F31 | Live production schema parity remains unverified | High | P1 | Runtime verification required |
| F32 | PHP advisory check is incomplete | Medium | P2 | Runtime verification required |
| F33 | Modal background and editor accessibility need verification | Medium | P2 | Runtime verification required |
| F34 | Login/logout can hide recent learning activity | Low | P2 | Confirmed defect |
| F35 | Reports/activity materialize unbounded evidence | Medium | P2 | Architectural recommendation |
| F36 | Frozen static-analysis baseline still defers existing issues | Low | P3 | Architectural recommendation |
| F37 | Shared guidance conflicts and a specification link is stale | Low | P2 | Confirmed defect |
| F38 | Editor chunk exceeds the default size advisory | Low | P3 | Optional enhancement |

## 4. System Architecture Assessment

Laravel services own academic mutations over the existing `the404_*` schema. Blade renders most interfaces; the CodeMirror workspace loads dynamically. An authenticated internal grader service starts isolated executions. Reporting separates authorized data collection from output rendering.

Useful service boundaries already exist. The weak boundaries are request-to-domain typing, execution-to-verdict trust, mutation-to-audit atomicity, independently calculated availability/resume state and mission analytics inferred from side effects. Repair these seams without replacing the overall architecture. Typed update inputs, shared availability with reasons, immutable operation identity and governed content versions would make existing modules easier to use correctly.

The main findings below include severity, priority, classification, category, affected code, observation/evidence, impact, expected behavior, concrete fix/approach, tests and dependencies. IDs are stable across the roadmap. Some related mechanisms are grouped into one finding to avoid inflating totals.


### Cross-system trace assessment

| Workflow | Inspected route-to-state chain | Assessment |
| --- | --- | --- |
| Login and role home | Login form → guest/login limiter → AuthController → active account/password check → session regeneration → role home/navigation | Server controls sound; shared destination links need F10. |
| Student learning entry | Catalog/path → student middleware → MissionIndex/LearningPath controllers → course reach/Progress/draft read models → Blade actions | No client unlock authority; F07/F08/F09/F15/F19 affect presentation/navigation. |
| Coding submission | Workspace source → CSRF/student gate → active/reached course and required KC guard → MissionController → MissionService/user lock → validation/grader → Progress/XP/achievement/notification → restored source/completion UI | Successful commit is atomic/idempotent; F01/F03/F05/F13/F26/F27 remain. |
| Hint/reveal/draft | Workspace actions → CSRF/student/course/KC guards → controller and XP/Draft services → purchase ledger/draft → redirect/editor | Purchase idempotency has dedicated concurrency tests; F14/F16 concern content availability and draft lifecycle. |
| Knowledge Check | Lesson action → nested mission/check/attempt routes → student/access/membership validation → KnowledgeCheckService → protected options and immutable answers/snapshots → feedback/Challenge eligibility | Server scoring and required-check gate preserved; F17/F23 concern definition identity and teacher evidence. |
| Boss Challenge | Hub/start/submit/retry → student gate → AssessmentService reach/eligibility/ownership → attempt transition/structural percentage → historical verdict/XP → course advancement | History is authoritative; F03/F06/F07/F12 cover UI/configuration/recovery. |
| XP/achievement/competency | Academic transaction → XP ledger and achievement grants → Progress/KC skill snapshots → competency service → dashboard/recommendation/report reads | No independent frontend award path found; preserve shared formulas. F01 can corrupt their input evidence. |
| Teacher monitoring | Teacher home/classroom/student selection → teacher middleware and policies → ClassroomAccessService exact pairs → scoped monitoring/timeline/competency → tables/detail | Pair authorization sound; event evidence/navigation gaps F10/F23/F24. |
| Admin write | Admin form → admin middleware/Form Request → administrative service guards → domain save/version → audit → flash/list | F04/F06/F11/F17/F28 expose input/invariant/atomicity gaps. |
| Reports and exports | Role export route → filter validation/ReportAuthorization → scoped report/analytics services → CSV/PDF renderer | Rendering/authorization protections exist; F24/F35 address discoverability/scale. |
| Notifications | Academic event or announcement → notification persistence → role/ownership-scoped center → read/mark response | Existing ownership/read-state/security tests passed; keep destination availability consistent with F07. |

This table reflects inspected code and executed feature tests, not live browser/container proof. Database and execution-specific limitations remain F29/F31.

## 5. Functional Findings

### F04: Browser numeric fields fail strict admin validation

- Severity / priority / classification: High / P1 / Confirmed defect.
- Category and affected code: Admin functionality; `CourseService.php:104`, `AdminMissionService.php:124`, `AdminAssessmentService.php:104`, corresponding update requests.
- Observation and evidence: Requests accept integer-shaped strings, but services require native integers. SQLite probes with course `order_num="7"` and mission `points="50"`, `order_num="1"` were rejected. Assessment uses the same strict boundary.
- Impact and expected behavior: Normal HTML edits fail despite request validation. Valid browser numeric fields must reach services as typed values.
- Fix and approach: Explicitly normalize successfully validated numeric fields at the HTTP boundary, retaining domain guards. Audit sibling admin forms for the same mismatch.
- Tests: Send actual form-string numerics for every admin update; cover fractional, negative, array and overflow values and successful persistence.
- Dependencies/risks: Do not cast unvalidated input into zero. Integer-valued PHP test payloads remain useful but do not reproduce browsers.

### F08: Unsectioned required missions disappear from the journey

- Severity / priority / classification: High / P1 / Confirmed defect.
- Category and affected code: Curriculum hierarchy; `AdminMissionService`, `LearningPathService`, `MissionIndexController`, `the404_missions.section_id`.
- Observation and evidence: Admin permits null section. Visible trees traverse sections, while completion and next-mission queries count course missions. A SQLite probe omitted an unsectioned mission from `/missions` rows while identifying it as currentMissionId.
- Impact and expected behavior: Invisible required work can prevent Boss eligibility. Every required mission must be discoverable.
- Fix and approach: Require a valid same-course section for publishable missions, or deliberately render an unsectioned group everywhere. Inventory existing assignments and correct content through audited changes.
- Tests: Null section, foreign-course section, reassignment and Boss eligibility; assert catalog/path/resume agreement.
- Dependencies/risks: Do not exclude invisible missions from eligibility as a workaround. Existing content may need approved reassignment.

### F09: Array-valued mission search returns 500

- Severity / priority / classification: Medium / P1 / Confirmed defect.
- Category and affected code: Input/error handling; `MissionIndexController.php:67`, `GET /missions`.
- Observation and evidence: Authenticated `/missions?q[]=x` returned 500 in a feature probe; scalar string operations receive an unvalidated query shape.
- Impact and expected behavior: Malformed input should produce a controlled validation response, not a server exception.
- Fix and approach: Validate query text and bounded length before normalization; preserve validated filter values in links. Inspect other manual filter extraction.
- Tests: Array/nested array, empty, Unicode and overlong search; assert no 500.
- Dependencies/risks: No SQL injection was established; preserve parameterized query behavior.

### F20: The specified ungraded Experiment step is missing

- Severity / priority / classification: Medium / P1 / Confirmed defect.
- Category and affected code: Missing learning feature; `lesson.blade.php`, routes, master spec Screen D/Phase 3.
- Observation and evidence: Lessons expose read-only examples and Knowledge Check/Challenge transitions. No separate interactive ungraded Experiment workflow was found in routes/templates. The updated spec explicitly requires it.
- Impact and expected behavior: Learners lack low-pressure manipulation before authoritative grading; the graded challenge cannot safely substitute for practice.
- Fix and approach: Reuse editor/preview components for an explicitly ungraded mode with clear entry/return actions. Keep its state separate from submission authority.
- Tests: Lesson → Experiment → Knowledge Check → Challenge browser journey; Experiment must write no Progress, attempts, XP or achievements.
- Dependencies/risks: Reuse preview isolation and accessible controls. Any scope removal requires an explicit authoritative specification revision.

### F24: Report endpoints lack a complete discoverable report UI

- Severity / priority / classification: Medium / P1 / Confirmed defect.
- Category and affected code: Reporting completeness; export routes, `*ReportService`, role navigation and report-related views.
- Observation and evidence: CSV/PDF endpoints exist, but a resources/views route-usage search found no links/forms to export routes. Inspected role pages lack the master-spec composition of scope, validated filters, evidence, drill-down and export.
- Impact and expected behavior: Backend reporting is not a coherent user workflow. Authorized roles must be able to reach and use supported reports.
- Fix and approach: Add role-specific report entry points/pages backed by existing services and ReportFilters. Render/export the same scoped response with identical filters and useful empty states.
- Tests: Student own report, teacher permitted student/course reports and admin fleet report; compare displayed and exported rows/metrics under the same filters.
- Dependencies/risks: Preserve export authorization and cumulative/window labels. Missing UI is not evidence of insecure export access.

## 6. Backend and Domain Logic

The accepted course-completion authority is a historical passed Boss attempt. Mission completion makes the Boss eligible; a later failed retry must not undo completion. Required published Knowledge Checks gate Challenge access on the server. Preserve these decisions.

### F05: Missing/malformed mission rules fail open

- Severity / priority / classification: High / P1 / Confirmed defect.
- Category and affected code: Validation/publication integrity; `ValidationService.php:43`, `MissionGradingService`, validate_rule/grading_rule.
- Observation and evidence: Direct probes with empty text, bad JSON and `[]` returned passed=true,total=0; `[1]` threw TypeError. A mission without behavioral tests can therefore pass arbitrary source under invalid configuration. Publication validation is required by the master spec.
- Impact and expected behavior: Content errors must not award progress or penalize learners. Published grading definitions must be valid and explicit.
- Fix and approach: Add a shared rule-definition validator for release/publication and runtime defense. Require valid member types/fields, safe regex and an explicit ungraded designation if supported. Return unavailable for malformed configuration, without XP mutation.
- Tests: Null, whitespace, bad JSON, scalar/unknown members, invalid regex, missing fields and invalid behavioral fixtures; assert no academic side effects.
- Dependencies/risks: Inventory existing curriculum. Preserve accepted Boss zero-check/percentage behavior rather than silently redesigning assessment scoring.

### F06: Admin can set an impossible Boss threshold

- Severity / priority / classification: High / P1 / Confirmed defect.
- Category and affected code: Assessment configuration; `AdminAssessmentUpdateRequest`, `AdminAssessmentService.php:104`.
- Observation and evidence: Only a nonnegative integer is required; a probe persisted passing_score=101 although evaluated scores are percentages.
- Impact and expected behavior: This can strand a course and its successors. Thresholds must be within 0–100.
- Fix and approach: Enforce max:100 at request and domain boundaries; inventory and repair bad current content via audited changes.
- Tests: String/integer 0,100,101,-1; verify refusal leaves current configuration consistent.
- Dependencies/risks: Keep historical threshold and verdict snapshots unchanged.

### F13: Replayed failures can deduct XP repeatedly

- Severity / priority / classification: Medium / P1 / Confirmed defect.
- Category and affected code: Idempotency; `MissionService.php:44`, `XpService`, submission route.
- Observation and evidence: Success replay is idempotent, but every failed request writes a fresh wrong-submission penalty. There is no stable submission operation ID. User locks serialize duplicates without deduplicating them.
- Impact and expected behavior: Lost-response retries or double-clicks can charge twice. One deliberate attempt should create one penalty.
- Fix and approach: Persist a unique server-validated submission operation ID and its result/XP reference; replay returns the stored outcome. A deliberate later attempt receives a new ID. Disable in-flight UI actions additionally.
- Tests: Same ID repeated/concurrent deducts once; distinct IDs with identical source count as legitimate separate attempts.
- Dependencies/risks: Do not deduplicate solely by source hash, which changes retry semantics. Coordinate with F25.

### F19: Tied course order bypasses the predecessor gate

- Severity / priority / classification: High / P1 / Confirmed defect.
- Category and affected code: Progression integrity; `AssessmentService.php:180`, course update validation, order_num.
- Observation and evidence: isCourseReached checks only active courses with strictly lower order_num. Ties are allowed; several lists also lack a tie-break.
- Impact and expected behavior: Equal-order active courses can both be reached without passing the displayed predecessor. Sequence and gates must share a deterministic total order.
- Fix and approach: Enforce unique active ordinals or consistently use order_num,id as the canonical sequence. Inventory ties and implement audited transactional reordering.
- Tests: Ties, reordering, inactive courses, missing assessments and historical passes; verify intended prerequisites.
- Dependencies/risks: Reordering affects access. Preserve earned completion and govern published-content changes.

### F25: Mission submissions lack an immutable academic journal

- Severity / priority / classification: Medium / P2 / Architectural recommendation.
- Category and affected code: Historical evidence; `MissionService`, `ChallengeAnalyticsService`, Progress/XP/Activity.
- Observation and evidence: Success is Progress; failures are inferred from wrong_submission XP rows. ChallengeAnalytics explicitly lacks per-attempt scores/durations. Completion version/skills do not reconstruct failed source/verdict history.
- Impact and expected behavior: Investigation and richer analytics cannot reconstruct attempts. Academic attempts need identity without competing with completion/XP authority.
- Fix and approach: Add a minimal immutable journal with operation ID, mission/version, source retention policy, verdict category, evaluated evidence and timestamps. Write it atomically with academic effects; migrate analytics carefully.
- Tests: Failed, passed, unavailable and replayed submissions; content edits; authorized source access; metric parity.
- Dependencies/risks: Approved schema, privacy/retention and size limits are required. Label pre-journal history limitations.

## 7. Frontend and UI/UX

Integrated student pages generally render Laravel state rather than accepting client scoring. The remaining problems are serialization, assistance availability, workspace behavior and inconsistent read-side labels.

### F03: Multiline source crashes editor initialization

- Severity / priority / classification: High / P1 / Confirmed defect.
- Category and affected code: Draft/editor integration; `challenge.blade.php:239`, `assessments/show.blade.php:146`.
- Observation and evidence: Both emit JSON inside a JS string and call JSON.parse. A probe using the exact PHP flags with multiline source produced a bad-control-character SyntaxError because JavaScript consumes JSON escapes first.
- Impact and expected behavior: Starter code, restored drafts and flashed submission source can prevent editor boot. Valid source must round-trip unchanged.
- Fix and approach: Emit a JavaScript value with installed Laravel Js::from; share initialization and preserve old-input/draft precedence. [Official Blade serialization guidance](https://laravel.com/framework/docs/blade).
- Tests: Real browser multiline/quotes/backslashes/Unicode/closing-script strings, failed POST restoration, reveal/hint redirects and Boss review.
- Dependencies/risks: Fix both templates together. This is a reproduced serialization failure, not a confirmed XSS exploit.

### F14: Assistance purchases and controls disagree with available content

- Severity / priority / classification: Medium / P2 / Confirmed defect.
- Category and affected code: UX/XP purchases; `MissionController` hint/reveal/parseHints, `challenge.blade.php:132`.
- Observation and evidence: Endpoint hint cap is fixed at three rather than actual hint count; usable content is not fully established before purchase. The solution form is nested under remaining-hints eligibility and disappears after all hints are revealed.
- Impact and expected behavior: Direct requests can charge unavailable assistance; solution access should be independent of remaining hints.
- Fix and approach: Centralize actual-content availability/costs, reject missing hint/blank solution without charging, and render independent action verdicts.
- Tests: Zero/one/two/three/more hints, exhausted hints, absent solution, replay and insufficient XP; verify ledger and controls.
- Dependencies/risks: Clarify support beyond three hints and preserve historical purchases/cost policy.

### F21: Workspace omits required accessible resizing/pane controls

- Severity / priority / classification: Medium / P1 / Confirmed defect.
- Category and affected code: Responsive/spec compliance; challenge template, student CSS, updated workspace contract.
- Observation and evidence: Fixed desktop grid and stacked narrow panes have no accessible Editor/Preview separator or practical collapse/restore state. The master spec and Phase 3 explicitly require them.
- Impact and expected behavior: Learners cannot adjust practical coding/feedback space. Narrow-screen usability also requires runtime evaluation.
- Fix and approach: Implement shared-source pane state, minimum dimensions, pointer/keyboard resizing, separator semantics/value attributes, visible focus and reset. Provide narrow-screen switching without remounting/loss of code.
- Tests: Phone/tablet/laptop widths, keyboard resize, collapse/restore, preview switching, navigation and draft preservation.
- Dependencies/risks: Coordinate with F03/F33. Do not create duplicate editor state when changing layouts.

### F22: External student fonts violate the declared CSP

- Severity / priority / classification: Low / P2 / Confirmed defect.
- Category and affected code: Asset/design integration; layout lines 74–75, `SecurityHeaders`.
- Observation and evidence: The layout requests fonts.bunny.net CSS; production style/font policy allows only self. The selected typography cannot load under that policy.
- Impact and expected behavior: Intended typography must be available under the production security policy.
- Fix and approach: Bundle/self-host fonts through the established asset pipeline or explicitly approve narrow style/font origins. Prefer local assets; remove blocked requests.
- Tests: Production browser cold-load, CSP console and computed font family/layout.
- Dependencies/risks: Appearance was not browser-inspected. Do not broaden default-src or grant unsafe-eval.

### F34: Login/logout can hide recent learning activity

- Severity / priority / classification: Low / P2 / Confirmed defect.
- Category and affected code: Dashboard selection; `DashboardService.php:152`, `DashboardController.php:62`.
- Observation and evidence: Query limits to six newest rows before the controller rejects authentication events. Six recent login/logout rows can hide older genuine learning.
- Impact and expected behavior: The panel should select learning events before its display limit.
- Fix and approach: Filter types in SQL before limiting, using shared learning-event semantics and deterministic timestamp/id order.
- Tests: Older mission/KC activity followed by six authentication rows must still appear.
- Dependencies/risks: Coordinate with scoped event provenance; do not invent another definition of learning activity.

## 8. Authentication and Authorization

Login regenerates the session; logout invalidates it and regenerates the token. Inactive accounts are refused at login and on ongoing requests. Password attributes are hidden/hashed. Student/teacher/admin routes have server role gates, ordered before route binding.

ClassroomAccessService uses current roles and exact student-course pair scopes. Empty scope refuses access; null has an intentional admin-wide meaning. Policies/report guards reuse this boundary. Existing authorization, pair-scope, ownership and role-change tests passed. No confirmed ordinary route IDOR was identified in the inspected/tested cases; this is not a universal security certification.

### F10: Teacher navigation advertises student-only destinations

- Severity / priority / classification: Medium / P1 / Confirmed defect.
- Category and affected code: RBAC/navigation; `layouts/app.blade.php:26`, routes.
- Observation and evidence: Instructor menu contains Dashboard, Assessments and Competency protected by student middleware. A teacher probe saw assessment/competency links on `/students`; both destinations returned 403.
- Impact and expected behavior: Authorized teachers encounter denied menu actions. Each role needs valid navigation and home destinations.
- Fix and approach: Separate role navigation models and reuse one role-home resolver for login/brand/menu links. Teachers should link to monitoring, not self-learning routes; clarify reserved operator behavior.
- Tests: Render each role home and visit every offered destination, while retaining direct cross-role denial tests.
- Dependencies/risks: This is a navigation defect, not role escalation. Do not loosen middleware to repair menus.

### F28: Minimum active-admin guard can race

- Severity / priority / classification: High / P1 / Runtime verification required.
- Category and affected code: Administrative authorization/concurrency; `UserService` active-admin count and role/status mutation.
- Observation and evidence: Fleet count is checked before mutation without a fleet-wide transaction lock. With three active admins, two concurrent removals can each see three and leave one. This MariaDB interleaving was not executed.
- Impact and expected behavior: Every committed change must preserve the documented minimum of two active admins.
- Fix and approach: Serialize admin-fleet mutations using an invariant lock row or another reliable fleet-wide lock; recount, mutate and audit in one transaction. Keep self-removal refusals.
- Tests: Disposable MariaDB concurrent demotion/deactivation from a fleet of three; only one reduction succeeds.
- Dependencies/risks: Coordinate with F11. Locking only each different target does not protect the fleet invariant.

## 9. Security Assessment

### F01: Student code can forge the behavioral-grader verdict

- Severity / priority / classification: Critical / P0 / Confirmed defect.
- Category and affected code: Academic integrity/execution trust; `grader/src/runner.js:76`, `server.js:92`, `GraderClient`, `MissionGradingService`.
- Observation and evidence: VM console receives a host callback. A subprocess probe used its constructor to obtain process, write passing JSON to stdout and exit before hidden evaluation. Incorrect control code failed; forged code passed. Laravel checks internal count consistency but does not bind returned total to the submitted test count. This was reproduced at runner level, not through live containers/Laravel.
- Impact and expected behavior: Adversarial source shares the trusted verdict channel. A false pass can propagate to Progress, XP, achievements, competency and Boss eligibility. Only a trusted evaluator may construct academic results.
- Fix and approach: Replace shared-process verdict trust with a supervisor/evaluator outside student execution. Keep expectations and final verdict construction outside the adversarial process; transport only bounded supported outputs across controlled IPC. Validate exact test identities/counts. Until demonstrated, affected published behavioral content should fail unavailable without academic mutation.
- Tests: Real container/service malicious constructor/process access, stdout forgery, early exit, malformed verdicts and unexpected totals; assert no academic changes. Trace a legitimate result through Laravel to rendered UI.
- Dependencies/risks: Removing one constructor access is insufficient. Preserve external isolation. Structural checks still precede behavior grading, so attacker source must also satisfy those checks. No host escape, secret access or production exploitation was tested.

A second reproduced defect belongs to the same evaluator repair: deepEqual sanitizes before comparison. Strings truncate at 1,000 characters, arrays/objects at 100 items and deep values at a fixed depth. Actual and expected strings differing only in WRONG/RIGHT suffixes after an identical 1,000-character prefix passed. Compare complete supported values in the trusted evaluator; reject oversized/unsupported outputs instead of silently changing equality. Test long strings, large arrays, deep objects and sentinel collisions. This is included in F01, not counted separately.

Node states that VM is not a security mechanism in its [official documentation](https://nodejs.org/api/vm.html). The current runner itself recognizes the container as its host-security boundary, but that does not protect a verdict emitted by the adversarial process.

The existing grader fixed argv, stdin payload, loopback/token service, no-network container, non-root UID, read-only filesystem, dropped capabilities and PID/CPU/memory limits are useful protections to preserve. The application also has CSRF, escaped report content, formula-safe CSV cells, disabled remote/PHP/JavaScript PDF features and sensitive exception-input exclusion. No XSS, SQL injection, host escape or credential leak was established. CSP denies remote scripts/unsafe-eval, but allows inline scripts/styles; moving static boot scripts into bundled modules with nonces is targeted future hardening after F03.

### F26: Expensive flows lack application admission controls

- Severity / priority / classification: High / P1 / Architectural recommendation.
- Category and affected code: Availability/resource abuse; routes, mission code validation, grader server, PDF exports.
- Observation and evidence: Login is throttled; grading/draft/assistance/export routes lack corresponding limiters. Mission code validates required,string without maximum. The grader starts executions without a global concurrency cap. Behavioral-client limits do not protect all structural/draft/Boss flows.
- Impact and expected behavior: An authenticated account can amplify worker, container, database-lock and PDF costs. Work/input must remain bounded.
- Fix and approach: Enforce source byte limits and named per-user operation limiters, bounded grader concurrency/queue and practical export limits. Use controlled Retry-After/unavailable responses without academic penalties.
- Tests: Oversized UTF-8 code, rapid/concurrent submissions, capacity exhaustion, repeated exports and recovery.
- Dependencies/risks: Tune to measured legitimate use. Supplement container/reverse-proxy limits; queue only exports large enough to justify it.

## 10. Database and Data Integrity

Progress uniqueness, attempt identities and snapshot fields support historical authority. Preserve legacy table timestamp conventions. SQLite migrations are test fixtures, not authorization to migrate the existing production database. The live schema was unavailable for read-only comparison.

### F11: Admin mutation and audit are not atomic

- Severity / priority / classification: High / P1 / Confirmed defect.
- Category and affected code: Data integrity/auditability; Course/User/AdminMission/AdminAssessment and classroom write services.
- Observation and evidence: Domain saves precede AdminAudit without a shared transaction; compound operations also contain multiple saves/syncs. A throwing audit mock left a CourseService name change persisted after the failed request.
- Impact and expected behavior: Accepted changes need durable audit evidence and compound mutations must commit together.
- Fix and approach: Transaction-wrap each logical accepted mutation/audit. Lock rows before invariant checks. Keep refusal evidence intentional and durable after a rolled-back refusal path where required.
- Tests: Inject audit insertion, second save and pivot sync failures; assert rollback and one accepted audit row per success.
- Dependencies/risks: Coordinate lock order, version hooks and F28. Refusal audits must not disappear accidentally inside rolled-back transactions.

### F16: Drafts lack version identity and can race completion

- Severity / priority / classification: Medium / P2 / Runtime verification required.
- Category and affected code: Draft integrity/concurrency; `DraftService`, MissionDraft, completion cleanup/draft route.
- Observation and evidence: Draft source has no curriculum version. Save does not share completion locking or reject completed work. A save after completion deletion can recreate an active draft. The production interleaving was not run.
- Impact and expected behavior: Content edits can restore incompatible source unnoticed; completion should not leave an accidental active draft.
- Fix and approach: Add originating mission version, stale-draft notice/copy recovery, and an explicit completed-practice policy. Serialize save/complete decisions using consistent locks or optimistic versions.
- Tests: MariaDB save/completion race, material/no-op content changes and completed-work reopening.
- Dependencies/risks: Approved schema change and product decision about practice drafts are needed.

### F17: Version hooks miss membership changes and can collide

- Severity / priority / classification: Medium / P2 / Runtime verification required.
- Category and affected code: Historical integrity; `HasCurriculumVersion`, Knowledge Check question/option models, content writes.
- Observation and evidence: The hook registers updating only. Added/deleted questions/options do not advance the parent through that hook. Self-versioning uses loaded version+1; concurrent edits can share the same next version. Child updates already use atomic parent increment. Lost-update behavior needs MariaDB verification.
- Impact and expected behavior: Distinct published definitions must not share a version identifier; material membership edits count too.
- Fix and approach: Govern all definition mutations in a version-aware transaction, with parent lock or compare-and-swap, covering membership create/delete. Bump once per logical edit; preserve no-op behavior.
- Tests: Child add/delete, concurrent parent changes, same-value changes and immutable historical snapshots.
- Dependencies/risks: Raw query writes/event-suppressed seeders need separate release governance. Avoid multiple increments per batch.

### F31: Live production schema parity remains unverified

- Severity / priority / classification: High / P1 / Runtime verification required.
- Category and affected code: Database/deployment; migrations, phpunit.xml, general rules, SystemStatusService.
- Observation and evidence: SQLite tests cannot establish MariaDB columns/constraints/locks. General rules claim a September 12 five-table schema without framework tables, contradicting current user instructions that these were added. SELECT 1 proves connectivity, not schema compatibility.
- Impact and expected behavior: Expanded classroom/KC/assessment/version/audit/notification/report functionality needs a verified deployed schema.
- Fix and approach: Obtain a dated read-only table/column/index/FK/collation inventory, compare required schema, and prepare exact reviewed DDL only for actual differences under explicit approval. Check framework session/cache/queue prerequisites and no-updated_at models.
- Tests: Disposable MariaDB race/index suites and production-driver staging smoke; required-table health checks.
- Dependencies/risks: No particular missing production table is asserted as a current defect. Never run create migrations against production to resolve uncertainty.

## 11. Backend ↔ Frontend Integration

Server route authority generally remains intact. Incorrect read-side availability creates broken navigation rather than granting access. Preview frames have titles and sandbox without allow-same-origin; preserve this isolation. Verify actual inherited CSP/preview behavior per language. [MDN srcdoc/sandbox guidance](https://developer.mozilla.org/en-US/docs/Web/API/HTMLIFrameElement/srcdoc).

### F07: Read models advertise actions the server locks

- Severity / priority / classification: Medium / P1 / Confirmed defect.
- Category and affected code: Authoritative presentation; `AssessmentService.php:110`, AssessmentController indexRows, LearningPathController, RecommendationService.
- Observation and evidence: Batched assessment state includes completion eligibility but not the full access verdict. A probe completed course missions under a locked assessment: hub state was ready while isUnlocked was false. Other annotations rely on status without full reach/status checks.
- Impact and expected behavior: Cards should advertise actions only when the destination permits them, with truthful lock reasons.
- Fix and approach: Expose batched reached/eligible/assessment-active/unlocked values with reasons. Reuse them in hubs, paths, recommendations, resume and notification destinations; separate historic pass from current access.
- Tests: Status/prerequisite/completion/history matrix; compare affordances to route verdicts.
- Dependencies/risks: Do not weaken route guards. Batch prerequisite history instead of adding per-row queries.

### F15: Resume and learning path use different draft priorities

- Severity / priority / classification: Medium / P2 / Confirmed defect.
- Category and affected code: Duplicated position logic; ResumeService, DashboardService nextMission, LearningPathService.
- Observation and evidence: Dashboard/Resume select the first unfinished mission by order only. Learning path has draft-aware selection. Resume docstring claims draft preference that its delegated query does not implement.
- Impact and expected behavior: Continue Learning and current-node highlights should agree on one authoritative position.
- Fix and approach: Reuse a shared position resolver with explicit draft priority and deterministic tie handling across dashboard/path/catalog. Retain historical Boss-pass completion.
- Tests: Earlier untouched mission/later draft, multiple drafts, all missions done and all courses passed.
- Dependencies/risks: Confirm priority from the accepted master/journey contract before updating selection expectations.

### F23: Teacher scope hides key learning activity and KC detail

- Severity / priority / classification: Medium / P1 / Confirmed defect.
- Category and affected code: Monitoring/evidence; `TimelineService.php:322`, teacher monitoring, student-progress view, KC attempts.
- Observation and evidence: Timeline intentionally drops Activity under classroom/course scope because rows lack reliable course references. mission_completed, wrong_submission and KC-completed filters select that source. Dedicated teacher question/check attempts and misconception analytics were not found.
- Impact and expected behavior: Scoped teachers can see empty important-event feeds and lack the specified KC drill-down. Competency totals do not replace evidence.
- Fix and approach: Derive events from authoritative course-linked records or add immutable provenance. Add privacy-scoped check/question summaries and drill-down with shared descriptive metric definitions.
- Tests: Teacher/student with courses A/B sees A mission/KC events only; event filters, empty scope, membership/role revocation and authorized question evidence.
- Dependencies/risks: Current exclusion correctly prevents leakage. Do not restore unscoped activities until provenance is safe. Reuse exact ClassroomAccessService pairs.

## 12. Performance and Scalability

Assessment, competency and scope batching already address several multiplicative-query paths, with passing query/performance tests for their cases. This audit did not measure a particular production N+1 or latency regression. Flat query counts do not bound rows, memory, lock waits or CPU.

### F27: Grading holds a database lock across external work

- Severity / priority / classification: Medium / P2 / Architectural recommendation.
- Category and affected code: Throughput/concurrency; `MissionService.php:44`, GraderClient, config/grader.php.
- Observation and evidence: User lock and transaction start before grading; HTTP timeout defaults to 15 seconds. External delays hold the lock and connection.
- Impact and expected behavior: Same-user academic operations can stall. Trusted computation should precede a short authoritative commit.
- Fix and approach: Grade an immutable definition snapshot outside the write lock; then lock/recheck access, version, operation identity and completion before applying results.
- Tests: Slow grader plus concurrent academic action; stale content/revoked access; duplicate success and lock-wait measurement.
- Dependencies/risks: Moving code casually creates time-of-check/time-of-use bugs. Preserve atomic effects/user-first lock order.

### F35: Reports/activity materialize unbounded evidence

- Severity / priority / classification: Medium / P2 / Architectural recommendation.
- Category and affected code: Scalability; TimelineService, ChallengeAnalyticsService, CSV/PDF exporters.
- Observation and evidence: Timeline composes/sorts collections before slicing; analytics loads evidence; CSV uses php://memory and returns a full string; PDF renders full HTML synchronously.
- Impact and expected behavior: Large histories can exceed worker memory/time despite flat query counts. Rows and memory need explicit budgets.
- Fix and approach: Profile realistic datasets, push appropriate aggregation/pagination into SQL, stream CSV and bound or queue large PDFs. Use exact existing metric grains and scope predicates.
- Tests: Realistic-size latency/memory budgets, metric parity, MariaDB EXPLAIN and scoped query plans.
- Dependencies/risks: No measured production overload is claimed. Preserve teacher pair scope and date semantics; avoid faster approximate formulas.

## 13. Accessibility and Responsive Design

Static inspection found titled previews, real controls and some dialog focus handling. Full keyboard/screen-reader/contrast/zoom/reflow compliance was not demonstrated. F21 records confirmed missing pane behavior; F33 records accessibility risks requiring interaction checks.

### F33: Modal background and editor accessibility need verification

- Severity / priority / classification: Medium / P2 / Runtime verification required.
- Category and affected code: Interactive accessibility; challenge completion overlay, editor initialization, shared layouts.
- Observation and evidence: Dialog has aria-modal and Tab handling but no inert background management. Boot code does not visibly assign an explicit editor accessible name. No assistive-technology run was available.
- Impact and expected behavior: Focus trapping alone does not establish screen-reader isolation; editable regions need meaningful names and keyboard exits.
- Fix and approach: Make background inert or use an equivalent accessible dialog, label the editing region, verify focus restoration/live feedback, and integrate visible keyboard splitter controls.
- Tests: Keyboard-only and screen-reader editor/dialog/menu/KC-radio journeys; automated scans plus contrast, 200%/400% zoom, reduced motion and phone/tablet reflow.
- Dependencies/risks: No blanket WCAG result is asserted. CodeMirror is not claimed to trap Tab. Preserve existing preview titles and valid skip links.

## 14. Testing and Quality Assurance

The passing suite covers meaningful domain, scope, notification and report behavior. Targeted fresh probes nevertheless found missed integration representations and failure modes. Integer PHP payloads do not simulate form strings; markup checks do not execute JavaScript; mocked grading does not establish hostile-protocol safety; SQLite does not prove independent row-lock behavior.

### F30: CI omits critical integration boundaries

- Severity / priority / classification: High / P1 / Architectural recommendation.
- Category and affected code: Test gaps/CI; `.github/workflows/laravel.yml`, MariaDB tests, grader tests, frontend test tooling.
- Observation and evidence: CI builds assets and runs SQLite PHPUnit, without PHPStan, grader unit/container tests, browser checks or disposable MariaDB. Ten MariaDB cases skipped here; no browser runtime was available.
- Impact and expected behavior: Green CI cannot establish the boundaries where this audit reproduced defects.
- Fix and approach: Add required static-analysis, grader, MariaDB and browser jobs. Heavy jobs may run separately, but their evidence must be mandatory before acceptance. Explicitly fail required jobs when runtimes are absent.
- Tests: F01/F03/F04/F11/F13/F19/F28 regressions and real-container trust checks; record skip counts and prohibit silent required-test skips.
- Dependencies/risks: New dependencies need approval. Keep production databases/credentials outside CI; HTTP feature tests are not interactive E2E.

## 15. Error Handling, Logging, and Observability

Sensitive exception inputs are excluded; route/method context and academic/admin records exist. Grader unavailability generally differs from student failure and avoids academic awards. Add operation IDs, definition version, duration/error category and recovery state to structured logs, excluding passwords, bearer tokens, hidden expectations and raw source by default. F11 covers non-atomic audit evidence.

### F12: Submitted Boss attempts lack demonstrated recovery

- Severity / priority / classification: High / P1 / Runtime verification required.
- Category and affected code: Assessment reliability; AssessmentController submit, `AssessmentService.php:508,557,693`, assessment view.
- Observation and evidence: Submit commits submitted status before evaluate runs separately. UI promises verification in progress, but no worker/reconciliation path resuming submitted attempts was found. Begin/retry refuse active submitted state. Crash injection was not performed.
- Impact and expected behavior: Failure between phases can strand attempts. Every submitted attempt needs a verdict or recoverable infrastructure state.
- Fix and approach: Add idempotent evaluation/reconciliation, or an appropriate atomic synchronous transition. Preserve source/snapshots; use an outbox/worker if asynchronous, with bounded retries and operational visibility.
- Tests: Crash after submission commit/during evaluation, resume processing, assert one verdict/XP effect and no stranded state.
- Dependencies/risks: Preserve Phase 4 scoring/retry contracts. Monitor queue/recovery if introduced.

### F29: Grader pipe/timeout cleanup requires runtime proof

- Severity / priority / classification: High / P2 / Runtime verification required.
- Category and affected code: Resource lifecycle; `grader/src/server.js:63,72,111`, integration-container script.
- Observation and evidence: Timeout kills the runtime CLI child, not an explicitly tracked container. stdin has no async error handler; try/catch cannot catch all stream EPIPE errors. No runtime was available to establish actual cleanup behavior.
- Impact and expected behavior: Orphan execution or service termination may result. Every request must settle once, clean execution and preserve service availability.
- Fix and approach: Handle stdin/output errors, clear timers on all settlements and track/cancel containers explicitly where needed; add bounded cleanup and lifecycle logging.
- Tests: Missing runtime, early exit/EPIPE, timeout, output flood, disconnect and restart; no leftover containers, healthy next request.
- Dependencies/risks: Runtime-specific verification is mandatory. Do not grant privileged sockets or weaken rootless isolation.

## 16. Build, Dependencies, and Configuration

Composer metadata, production assets and runner unit tests passed. npm advisories were retrieved; PHP advisories were not. Local Node 26 results are not Node 22 container verification. Production debug/proxy/HTTPS/cookie/body/time-limit settings, grader connectivity/token, session/cache/queue prerequisites and backup/restore require staging evidence; no hypothetical private-.env misconfiguration is counted as a defect.

### F02: Generic setup can invoke forbidden production migrations

- Severity / priority / classification: High / P0 / Confirmed defect.
- Category and affected code: Deployment/database safety; composer setup/post-create-project hooks, example environment, fixture migrations.
- Observation and evidence: setup runs migrate --force; create-project also runs migrate. The project explicitly prohibits these create migrations against existing system404. No setup/migration command was executed.
- Impact and expected behavior: Generic bootstrap must not attempt schema mutation on the existing production database or create migration metadata as a side effect.
- Fix and approach: Remove automatic migrations from generic setup/install; separate disposable schema bootstrap from explicitly approved production DDL. Check effective connection before any schema command.
- Tests: Default setup must not invoke production migrations; clean disposable SQLite/MariaDB bootstrap works.
- Dependencies/risks: Establish release ownership/live inventory. Do not fix this by running migrations now.

### F18: General seeding bypasses curriculum version hooks

- Severity / priority / classification: High / P2 / Confirmed defect.
- Category and affected code: Content release/history; DatabaseSeeder, curriculum/assessment seeders, version trait.
- Observation and evidence: DatabaseSeeder and CurriculumContentSeeder suppress model events while updating existing content, bypassing version hooks. It also unconditionally creates a test user, making reruns fragile. No production seed ran.
- Impact and expected behavior: Material releases must retain historical identity; sample accounts belong in disposable setup.
- Fix and approach: Separate demo/test bootstrap from versioned curriculum releases; validate a content diff, bump material versions once and record release identity. Make dev setup repeatable.
- Tests: Seed twice safely; no-op content keeps version, material edit advances version and preserves attempts.
- Dependencies/risks: Follow approved content-release ownership. Never use generic db:seed as an unreviewed production repair.

### F32: PHP advisory check is incomplete

- Severity / priority / classification: Medium / P2 / Runtime verification required.
- Category and affected code: Dependency verification; composer.lock/Packagist advisory retrieval.
- Observation and evidence: Composer validate passed, but audit timed out after approved retry. npm audit completed cleanly. No PHP vulnerability status was established.
- Impact and expected behavior: Release candidates need a completed dated advisory result, not merely a valid lockfile.
- Fix and approach: Rerun Composer audit with working network access; retain machine-readable results and exact accepted advisory exceptions with expiry.
- Tests: CI distinguishes retrieval failure from clean/vulnerable results; test/build approved package updates.
- Dependencies/risks: No specific PHP advisory is claimed. Dependency changes require approval.

## 17. Confirmed Strengths

| Implementation/decision | Evidence and behavior to preserve |
| --- | --- |
| Academic state is server-owned | Controllers accept source/answers, not client scores/XP/verdicts. Services own mutations. F01 is the external trusted-input exception to fix. |
| Historical course completion | Passed attempt history remains authoritative after later failed retries; finishing missions alone does not complete the course. |
| Required Knowledge Checks | Server Challenge/mutation guards enforce required published check completion; direct-submit bypass tests passed. |
| Protected question scoring | Option correctness is not client authority; membership/ownership checks and immutable submitted attempts protect evidence. |
| Successful mission idempotency | User-first lock, unique Progress and transaction protect completion/XP/achievement/notification/draft effects from duplicate success. |
| XP ledger | Persisted reasons and clamped balances give auditable outcomes; add failure-operation identity without replacing the ledger. |
| Exact classroom pair scope | Current role/membership and student-course pairing constrain teacher data; empty scopes refuse access. |
| Notification ownership | Own-user read/mark operations and role-aware scope have passing security tests. |
| Report layering | Authorization/data collection stays separate from serialization; filters narrow authorized scope. |
| Hardened exports | fputcsv and dangerous-prefix protection; escaped PDF cells with remote/PHP/JavaScript disabled. |
| Execution resource controls | Fixed arguments/stdin, loopback/token, non-root/no-network/read-only execution, dropped capabilities and resource limits. Preserve while fixing verdict trust. |
| Query batching | Assessment history/eligibility, competency and scopes use batching with dedicated performance tests. |
| Verification foundation | 1,180 passing PHP tests, baseline-relative analysis, production build and 22 runner tests are useful regression foundations. |

These strengths are supported by code inspection and executed tests, with the live/runtime limitations stated earlier. Passing tests do not negate the targeted counterexamples.

## 18. Technical Debt

Debt with near-term consequences includes typed request/domain boundaries, shared availability/resume projections, attempt evidence and governed content releases. F25/F27/F35 cover deeper design changes. The two entries below concern analysis/documentation debt.

### F36: Frozen static-analysis baseline still defers existing issues

- Severity / priority / classification: Low / P3 / Architectural recommendation.
- Category and affected code: Maintainability/type safety; phpstan configuration/baseline, US-501 rules.
- Observation and evidence: Analysis passes relative to the documented frozen 82-finding baseline. Zero reported errors does not mean those deferred findings were repaired.
- Impact and expected behavior: Preserve no-new-errors while retiring agreed baseline groups alongside relevant work.
- Fix and approach: Prioritize request/domain types and report shapes; remove verified obsolete entries incrementally. Require CI to prevent baseline growth.
- Tests: Analysis and affected boundary regressions after each retirement.
- Dependencies/risks: Do not regenerate the baseline to hide errors or start a wholesale type rewrite during this audit.

### F37: Shared guidance conflicts and a specification link is stale

- Severity / priority / classification: Low / P2 / Confirmed defect.
- Category and affected code: Documentation; general rules, AGENTS/CLAUDE, `docs/design/README.md`, master spec.
- Observation and evidence: General production inventory contradicts current instructions. Design README refers to absent `docs/CODEQUEST_MASTER_SPEC.md`; current updated master spec is at root.
- Impact and expected behavior: Agents/deployers need resolving links and dated verified operational facts; historical reports must not masquerade as current acceptance evidence.
- Fix and approach: Correct spec link; reconcile durable rules through record-rule after live inventory, keeping AGENTS/CLAUDE synchronized. Explain superseded decisions.
- Tests: Link check and read-only inventory verification of operational facts.
- Dependencies/risks: Do not invent schema facts to reconcile documents. This audit adds only the requested report.

## 19. Recommended Improvements

Make changes at the owning boundary: normalize requests before domain calls; validate definitions before publication; give attempts operation identity; use shared server read models for availability; add safe provenance for teacher evidence. Keep one authoritative completion, XP and competency formula.

### F38: Editor chunk exceeds the default size advisory

- Severity / priority / classification: Low / P3 / Optional enhancement.
- Category and affected code: Frontend payload; app/editor JS and production Vite output.
- Observation and evidence: Build passed, with editor 561.96 kB, 192.83 kB gzip and a chunk-size warning. app.js already loads it dynamically only when editor-host exists.
- Impact and expected behavior: First workspace entry may cost time on constrained devices; ordinary pages do not necessarily load it.
- Fix and approach: Measure cold load/initialization; trim unused extensions or lazy-load language support only if worthwhile. Provide accessible loading/failure states.
- Tests: Constrained-network/device workspace timing and unchanged editor functionality.
- Dependencies/risks: Do not suppress the advisory as the sole fix or claim this is a failed build.

Recommended sequencing is academic integrity/deployment safety, then browser input/configuration, then atomicity/idempotency/ordering, then availability/hierarchy/navigation, then complete monitoring/report/workspace flows. Content-history and infrastructure work require coordinated decisions rather than disconnected patches.

## 20. Prioritized Remediation Plan

Priority is sequencing, not only severity. Runtime-verification findings may close with convincing contrary evidence; architectural recommendations should close against an explicit requirement/measurement. Every item has a concrete verification requirement in its finding.

### P0 — Blocking

| ID | Work | Required exit evidence |
| --- | --- | --- |
| F01 | Replace shared-process verdict trust with a supervisor/evaluator outside student execution. Keep expectations and final verdict construction outside the adversarial process; transport only bounded supported outputs across controlled IPC. Validate exact test identities/counts. Until demonstrated, affected published behavioral content should fail unavailable without academic mutation. | Real container/service malicious constructor/process access, stdout forgery, early exit, malformed verdicts and unexpected totals; assert no academic changes. Trace a legitimate result through Laravel to rendered UI. |
| F02 | Remove automatic migrations from generic setup/install; separate disposable schema bootstrap from explicitly approved production DDL. Check effective connection before any schema command. | Default setup must not invoke production migrations; clean disposable SQLite/MariaDB bootstrap works. |

### P1 — Before Full-System E2E/UAT

| ID | Work | Required exit evidence |
| --- | --- | --- |
| F03 | Emit a JavaScript value with installed Laravel Js::from; share initialization and preserve old-input/draft precedence. [Official Blade serialization guidance](https://laravel.com/framework/docs/blade). | Real browser multiline/quotes/backslashes/Unicode/closing-script strings, failed POST restoration, reveal/hint redirects and Boss review. |
| F04 | Explicitly normalize successfully validated numeric fields at the HTTP boundary, retaining domain guards. Audit sibling admin forms for the same mismatch. | Send actual form-string numerics for every admin update; cover fractional, negative, array and overflow values and successful persistence. |
| F05 | Add a shared rule-definition validator for release/publication and runtime defense. Require valid member types/fields, safe regex and an explicit ungraded designation if supported. Return unavailable for malformed configuration, without XP mutation. | Null, whitespace, bad JSON, scalar/unknown members, invalid regex, missing fields and invalid behavioral fixtures; assert no academic side effects. |
| F06 | Enforce max:100 at request and domain boundaries; inventory and repair bad current content via audited changes. | String/integer 0,100,101,-1; verify refusal leaves current configuration consistent. |
| F07 | Expose batched reached/eligible/assessment-active/unlocked values with reasons. Reuse them in hubs, paths, recommendations, resume and notification destinations; separate historic pass from current access. | Status/prerequisite/completion/history matrix; compare affordances to route verdicts. |
| F08 | Require a valid same-course section for publishable missions, or deliberately render an unsectioned group everywhere. Inventory existing assignments and correct content through audited changes. | Null section, foreign-course section, reassignment and Boss eligibility; assert catalog/path/resume agreement. |
| F09 | Validate query text and bounded length before normalization; preserve validated filter values in links. Inspect other manual filter extraction. | Array/nested array, empty, Unicode and overlong search; assert no 500. |
| F10 | Separate role navigation models and reuse one role-home resolver for login/brand/menu links. Teachers should link to monitoring, not self-learning routes; clarify reserved operator behavior. | Render each role home and visit every offered destination, while retaining direct cross-role denial tests. |
| F11 | Transaction-wrap each logical accepted mutation/audit. Lock rows before invariant checks. Keep refusal evidence intentional and durable after a rolled-back refusal path where required. | Inject audit insertion, second save and pivot sync failures; assert rollback and one accepted audit row per success. |
| F12 | Add idempotent evaluation/reconciliation, or an appropriate atomic synchronous transition. Preserve source/snapshots; use an outbox/worker if asynchronous, with bounded retries and operational visibility. | Crash after submission commit/during evaluation, resume processing, assert one verdict/XP effect and no stranded state. |
| F13 | Persist a unique server-validated submission operation ID and its result/XP reference; replay returns the stored outcome. A deliberate later attempt receives a new ID. Disable in-flight UI actions additionally. | Same ID repeated/concurrent deducts once; distinct IDs with identical source count as legitimate separate attempts. |
| F19 | Enforce unique active ordinals or consistently use order_num,id as the canonical sequence. Inventory ties and implement audited transactional reordering. | Ties, reordering, inactive courses, missing assessments and historical passes; verify intended prerequisites. |
| F20 | Reuse editor/preview components for an explicitly ungraded mode with clear entry/return actions. Keep its state separate from submission authority. | Lesson → Experiment → Knowledge Check → Challenge browser journey; Experiment must write no Progress, attempts, XP or achievements. |
| F21 | Implement shared-source pane state, minimum dimensions, pointer/keyboard resizing, separator semantics/value attributes, visible focus and reset. Provide narrow-screen switching without remounting/loss of code. | Phone/tablet/laptop widths, keyboard resize, collapse/restore, preview switching, navigation and draft preservation. |
| F23 | Derive events from authoritative course-linked records or add immutable provenance. Add privacy-scoped check/question summaries and drill-down with shared descriptive metric definitions. | Teacher/student with courses A/B sees A mission/KC events only; event filters, empty scope, membership/role revocation and authorized question evidence. |
| F24 | Add role-specific report entry points/pages backed by existing services and ReportFilters. Render/export the same scoped response with identical filters and useful empty states. | Student own report, teacher permitted student/course reports and admin fleet report; compare displayed and exported rows/metrics under the same filters. |
| F26 | Enforce source byte limits and named per-user operation limiters, bounded grader concurrency/queue and practical export limits. Use controlled Retry-After/unavailable responses without academic penalties. | Oversized UTF-8 code, rapid/concurrent submissions, capacity exhaustion, repeated exports and recovery. |
| F28 | Serialize admin-fleet mutations using an invariant lock row or another reliable fleet-wide lock; recount, mutate and audit in one transaction. Keep self-removal refusals. | Disposable MariaDB concurrent demotion/deactivation from a fleet of three; only one reduction succeeds. |
| F30 | Add required static-analysis, grader, MariaDB and browser jobs. Heavy jobs may run separately, but their evidence must be mandatory before acceptance. Explicitly fail required jobs when runtimes are absent. | F01/F03/F04/F11/F13/F19/F28 regressions and real-container trust checks; record skip counts and prohibit silent required-test skips. |
| F31 | Obtain a dated read-only table/column/index/FK/collation inventory, compare required schema, and prepare exact reviewed DDL only for actual differences under explicit approval. Check framework session/cache/queue prerequisites and no-updated_at models. | Disposable MariaDB race/index suites and production-driver staging smoke; required-table health checks. |

### P2 — Before Deployment

| ID | Work | Required exit evidence |
| --- | --- | --- |
| F14 | Centralize actual-content availability/costs, reject missing hint/blank solution without charging, and render independent action verdicts. | Zero/one/two/three/more hints, exhausted hints, absent solution, replay and insufficient XP; verify ledger and controls. |
| F15 | Reuse a shared position resolver with explicit draft priority and deterministic tie handling across dashboard/path/catalog. Retain historical Boss-pass completion. | Earlier untouched mission/later draft, multiple drafts, all missions done and all courses passed. |
| F16 | Add originating mission version, stale-draft notice/copy recovery, and an explicit completed-practice policy. Serialize save/complete decisions using consistent locks or optimistic versions. | MariaDB save/completion race, material/no-op content changes and completed-work reopening. |
| F17 | Govern all definition mutations in a version-aware transaction, with parent lock or compare-and-swap, covering membership create/delete. Bump once per logical edit; preserve no-op behavior. | Child add/delete, concurrent parent changes, same-value changes and immutable historical snapshots. |
| F18 | Separate demo/test bootstrap from versioned curriculum releases; validate a content diff, bump material versions once and record release identity. Make dev setup repeatable. | Seed twice safely; no-op content keeps version, material edit advances version and preserves attempts. |
| F22 | Bundle/self-host fonts through the established asset pipeline or explicitly approve narrow style/font origins. Prefer local assets; remove blocked requests. | Production browser cold-load, CSP console and computed font family/layout. |
| F25 | Add a minimal immutable journal with operation ID, mission/version, source retention policy, verdict category, evaluated evidence and timestamps. Write it atomically with academic effects; migrate analytics carefully. | Failed, passed, unavailable and replayed submissions; content edits; authorized source access; metric parity. |
| F27 | Grade an immutable definition snapshot outside the write lock; then lock/recheck access, version, operation identity and completion before applying results. | Slow grader plus concurrent academic action; stale content/revoked access; duplicate success and lock-wait measurement. |
| F29 | Handle stdin/output errors, clear timers on all settlements and track/cancel containers explicitly where needed; add bounded cleanup and lifecycle logging. | Missing runtime, early exit/EPIPE, timeout, output flood, disconnect and restart; no leftover containers, healthy next request. |
| F32 | Rerun Composer audit with working network access; retain machine-readable results and exact accepted advisory exceptions with expiry. | CI distinguishes retrieval failure from clean/vulnerable results; test/build approved package updates. |
| F33 | Make background inert or use an equivalent accessible dialog, label the editing region, verify focus restoration/live feedback, and integrate visible keyboard splitter controls. | Keyboard-only and screen-reader editor/dialog/menu/KC-radio journeys; automated scans plus contrast, 200%/400% zoom, reduced motion and phone/tablet reflow. |
| F34 | Filter types in SQL before limiting, using shared learning-event semantics and deterministic timestamp/id order. | Older mission/KC activity followed by six authentication rows must still appear. |
| F35 | Profile realistic datasets, push appropriate aggregation/pagination into SQL, stream CSV and bound or queue large PDFs. Use exact existing metric grains and scope predicates. | Realistic-size latency/memory budgets, metric parity, MariaDB EXPLAIN and scoped query plans. |
| F37 | Correct spec link; reconcile durable rules through record-rule after live inventory, keeping AGENTS/CLAUDE synchronized. Explain superseded decisions. | Link check and read-only inventory verification of operational facts. |

### P3 — Future Improvements

| ID | Work | Required exit evidence |
| --- | --- | --- |
| F36 | Prioritize request/domain types and report shapes; remove verified obsolete entries incrementally. Require CI to prevent baseline growth. | Analysis and affected boundary regressions after each retirement. |
| F38 | Measure cold load/initialization; trim unused extensions or lazy-load language support only if worthwhile. Provide accessible loading/failure states. | Constrained-network/device workspace timing and unchanged editor functionality. |

## 21. Recommended Test Additions

| Cross-system boundary | Specific coverage | Findings |
| --- | --- | --- |
| UI/editor → submit → restored source | Execute multiline/escaped/Unicode source, reload drafts, failed POST, hint/reveal and Boss review; assert submitted source matches editor. | F03, F16, F21 |
| Admin form → request → service → audit | Real string numerics, invalid percentages/content, injected audit/pivot failures and atomic rollback. | F04, F06, F11 |
| Student code → isolated execution → trusted verdict → academic state | Forged stdout, constructor/process access, early exit, incorrect totals, long/deep equality collisions and legitimate successful flow through the container/service/Laravel. | F01, F29 |
| Publication → validator → grading | Bad JSON/scalars/unknown types/regex/empty configuration return unavailable without penalty; accepted Boss scoring stays unchanged. | F05, F06 |
| Course/section → path/catalog/resume → access | Null/foreign section, tied order, inactive Boss, predecessor gates, drafts and historical passes; compare UI actions to route outcomes. | F07, F08, F15, F19 |
| KC → Challenge → teacher evidence | Required gate remains server-enforced; foreign attempts/options, stale definitions, version changes and permitted check/question drill-down. | F17, F23 |
| Failed operation replay → XP | Same ID repeated/concurrent charges once; distinct deliberate IDs remain separate attempts. | F13, F25 |
| Concurrent writes on MariaDB | Completion/draft race, version collisions, first KC creation, admin-fleet reductions, audit rollback and recovery after submitted state. | F11, F12, F16, F17, F28 |
| Membership → teacher monitoring → report/export | Exact student-course pair, empty scope, role/membership revocation; permitted mission/KC events and no other-course leakage. | F10, F23, F24 |
| Report navigation → filters → CSV/PDF | Discoverability, shared metrics/filter values, spreadsheet injection, PDF escaping, time-semantics labels and large-data limits. | F24, F35 |
| Keyboard/screen reader → workspace/dialog/menu | Names, visible focus, skip-link destination, escape/return, inert background, radio grouping, live feedback, separators and zoom/reflow. | F21, F33 |
| Resource failure → recovery | Rate/capacity limits, timeout, EPIPE, disconnect and restart; no penalty, stranded attempts or leftover executions; next request remains healthy. | F12, F26, F29 |
| Deployed schema/drivers → staging | Read-only parity, actual Node 22 runtime, MariaDB EXPLAIN/locks, database session/cache and completed dependency checks. | F30, F31, F32 |

A browser suite/disposable MariaDB job may require approved tooling changes. Keep tests isolated from production. Markup assertions are not equivalent to executing JavaScript; SQLite is not proof of independent MariaDB row locking. The current suite remains useful and should gain targeted boundary regressions rather than broad tests that mirror implementation.

## 22. Production Readiness Assessment

| Gate | Current state | Exit requirement |
| --- | --- | --- |
| Academic integrity | Blocking | F01 hostile source cannot forge/distort a verdict; full result-to-Progress trace verified. |
| Safe bootstrap | Blocking | F02 generic setup cannot invoke forbidden production migrations. |
| Core browser integration | Not acceptance-ready | F03/F04/F07/F08/F10 fixed and exercised interactively. |
| Published configuration/progression | Not acceptance-ready | F05/F06/F19 valid definitions/thresholds and deterministic prerequisites. |
| Atomicity/concurrency/recovery | Partial | F11/F12/F13/F28 regressions and MariaDB evidence; skipped cases executed. |
| Complete specified experiences | Partial | F20/F21/F23/F24 completed or explicitly revised in authoritative scope. |
| Production schema/environment | Unverified | Read-only parity, staging production-driver smoke and approved rollout/rollback. |
| Accessibility/responsiveness | Unverified interactively | Keyboard, screen-reader, viewport, contrast/zoom and state-preservation evidence. |
| Dependencies/build | Build verified; PHP advisories unknown | Completed Composer advisory check and actual deployed-runtime verification. |
| Observability/resource lifecycle | Partial | Operation IDs, recovery and real runtime/capacity cleanup evidence. |

Focused diagnostic E2E testing should support fixes now. Full-system E2E/UAT should not accept these known blockers. After P0/P1 close, proceed to controlled staging acceptance with an explicit issue register. Deployment additionally needs P2 closure or documented acceptance and actual environment verification. No production readiness claim follows solely from passing SQLite tests.

## 23. Final Audit Summary

| Severity | Findings |
| --- | ---: |
| Critical | 1 |
| High | 15 |
| Medium | 17 |
| Low | 5 |
| Informational | 0 |
| Total | 38 |

| Priority | Findings |
| --- | ---: |
| P0, blocking | 2 |
| P1, before full-system E2E/UAT | 20 |
| P2, before deployment | 14 |
| P3, future improvements | 2 |
| Total | 38 |

| Classification | Findings |
| --- | ---: |
| Confirmed defects | 23 |
| Runtime verification required | 8 |
| Architectural recommendations | 6 |
| Optional enhancements | 1 |
| Total | 38 |

F01 groups verdict forgery and lossy equality because they share the trusted-evaluator repair. Test gaps overlap findings: F30 is the dedicated coverage finding; all 38 items specify verification, and section 21 maps the major boundaries. The ten skipped MariaDB tests are an unverified test set, not ten additional defects.

The strongest parts are exact classroom pair authorization, server-owned academic operations, historical Boss-pass completion, protected Knowledge Check scoring, successful-completion idempotency, XP ledger authority, hardened reports and the substantial automated foundation.

The highest-risk remaining areas are hostile-grader verdict trust, unsafe bootstrap, browser source/form integration, published rule/threshold/order integrity, admin transaction/concurrency invariants and unavailable live schema/runtime evidence. Teacher/reporting and revised workspace flows also need completion before full-scope acceptance.

Recommended order: F01/F02; then serialization/input/configuration F03/F04/F05/F06; then atomicity/idempotency/ordering F11/F13/F19/F28; then consistent availability/hierarchy/navigation/resume F07/F08/F10/F15; then recovery, monitoring, reports, Experiment and workspace F12/F20/F21/F23/F24/F33. Run required CI/MariaDB/container/browser checks F30/F31 throughout. Finish versioning/retention, capacity/lifecycle, advisory and documentation work before deployment; optional baseline/bundle work follows.

All discovered material observations, findings, recommendations, verification results and limits are in this file, rather than only in temporary output. No implementation changes were made. The system is **not ready for full-system E2E/UAT acceptance** until P0/P1 gates close with evidence.


# Remediation Results

Remediation date: 2026-10-02. This section is an ongoing record, not final acceptance. No deployment, push, production migration, seeding, credential change, or academic write has been performed. The configured MariaDB database on port 3307 is explicitly read-only for this work. Destructive verification must use the separate disposable container/database.

## Baseline and authority

- Branch: `main`.
- Starting HEAD: `95df844ae165997ee59eb739276e38a4da8d3a38`.
- Starting worktree: only the requested, untracked audit report; implementation clean.
- Fresh baseline: `php artisan test --compact` discovered 1,190 tests, with 1,180 passed, 10 skipped, and 6,685 assertions. The ten skips require MariaDB, not SQLite.
- Specifications consulted: the entire original audit, updated master specification, CONTEXT, ADRs 0001/0002/0003, relevant repository rules, and Laravel/testing skills. Installed Laravel 13.30.1 and PHPUnit 12.5.34 APIs were inspected where needed; Boost tools were unavailable.
- The original report was preserved in commit `7d2d2f9`. Package/API assumptions and findings were rechecked against actual implementation.

## P0 Results

### F01: FIXED, trusted grader authority restored

**Root cause.** Student execution shared the process and stdout channel that constructed a verdict. The service accepted that verdict, and Laravel did not bind its total to the protected test definitions. Truncated equality also accepted different long values.

**Attack path and state map.** `POST mission.submit` accepts source through CSRF/auth/student middleware, course reach/status and required-KC guards. MissionController whitelists source; MissionService locks the user and owns its transaction. MissionGradingService loads protected structural and behavioral definitions, calls GraderClient with an internal token, and applies its decision. A passing decision writes `the404_progress`, XP ledger, achievements and notifications, clears the draft, and affects competency, Boss eligibility and course reach. The frontend renders this server result. No new frontend score, completion, reward or unlock input was added. Assessment outcomes remain owned by AssessmentService and passed-attempt history.

**Implementation.** The HTTP service now retains expectations and computes the final version-2 verdict outside the adversarial container. Execution receives source and invocation arguments only. The worker returns bounded, tagged observations; the trusted evaluator compares complete supported values and rejects malformed/old verdict envelopes. Oversized or unsupported values are rejected instead of truncated. Laravel requires protocol version 2 and exact server-owned test totals for academic outcomes. Fixed argv, loopback/token authentication, rootless non-root execution, no network, read-only root, dropped capabilities and PID/CPU/memory limits remain.

**Compatibility reassessment.** The first correction restricted function definitions to identifiers. Reinspection found trusted curriculum arrow-function probes. That regression was corrected in `21ff4d2` and covered by real-container and subprocess cases. Only protected server definitions supply these expressions; they execute inside the container and receive no expectations.

**Files changed.** `app/Services/GraderClient.php`; `grader/src/{server,runner,evaluator,values}.js`; `grader/test/{runner.test,server.test,grade-container}.js`; `grader/test/integration-container.sh`; `grader/{Dockerfile,package.json,README.md}`; `tests/Feature/MissionBehavioralGradingTest.php`.

**Regression evidence.** The original 22 grader tests plus new cases cover stdout forgery/early exit, request verdict fields, old protocols, wrong totals, service credentials, malformed definitions, full long-string/array/deep-object equality, sentinel collisions, UTF-8 streams and trusted curriculum probes. PHP adversarial requests cannot create progress, mission XP, achievements, assessment results, demonstrated competency, Boss eligibility or later-course access. Valid implementations still complete and earn the protected reward.

**Executed verification.** `node --test grader/test/*.test.js`: 37 passed, zero failed/skipped. Strict `CODEQUEST_REQUIRE_SANDBOX_TESTS=1 sh grader/test/integration-container.sh`: 20 checks passed, zero failed, real rootless Podman and Node 22.23.3. The actual evaluator-to-container path passed valid, invalid and forged submissions plus network/filesystem/resource/cleanup checks. PHP target and broad-suite results appear below. Syntax/shell checks and `git diff --check` passed.

**Environment evidence.** System sudo installation required a password. With approved tooling setup, signed distribution packages were verified and extracted under `/tmp`; no application dependency changed. The first image download stalled, and a public mirror supplied the same Node image. An initial strict run failed due to the temporary crun library path; this was corrected and the entire suite rerun successfully. Failed attempts are not passing verification.

**Remaining risk.** F29's service restart/disconnect and explicit container cancellation scenarios still need broader lifecycle coverage; the timeout test alone does not close F29. Live Laravel-to-container and interactive UI acceptance remain part of F30. Protocol mismatch deliberately fails unavailable; any future infrastructure change must preserve that behavior. No host escape or deployment certification is claimed.

**Commits.** `9ead394`, `21ff4d2`.

### F02: FIXED, setup no longer mutates the configured schema

**Root cause.** Composer setup/create-project hooks invoked migrations against whichever database was configured, contrary to the existing-schema safety contract.

**Files changed.** `composer.json`, `tests/Feature/SetupSafetyTest.php`.

**Implementation.** Removed automatic migration calls and the create-project SQLite-file hook. Key generation and ordinary asset/dependency setup remain. Fixture migrations continue to run only through explicit test database setup. No migration was run against the configured database.

**Tests and commands.** SetupSafetyTest reproduced the unsafe hook before the change, then passed one test with ten assertions. It examines installation/setup hooks for schema migration, seed and wipe commands. `composer validate --strict` passed. The complete SQLite suite demonstrated fixture schema bootstrap and existing functionality. Diff checks passed.

**Remaining risk/follow-up.** Production DDL still requires explicit reviewed approval; F31 must establish schema parity. Do not restore automatic migration hooks as a workaround.

**Commit.** `9d175fc`.

## P1 Results

| Finding | Current status | Evidence / remaining action |
| --- | --- | --- |
| F03 | FIXED | Both workspaces use context-safe Js::from output; exact restoration and malformed-input regressions execute rendered JavaScript successfully. |
| F04 | FIXED | Browser-string course/section/mission updates now normalize valid bounded digit strings; strict domain guards remain. |
| F05 | FIXED | Protected mission definitions now preflight before evaluation; malformed content returns unavailable without progress, reward or penalty. |
| F06 | FIXED | HTTP and domain boundaries reject thresholds outside 0–100. |
| F07 | NOT FIXED | Shared access/read-model verdicts still required. |
| F08 | NOT FIXED | Required unsectioned missions still need consistent discovery. |
| F09 | FIXED | Form Request validates scalar/bounded filters before read-model work; JSON errors are 422 and browser errors redirect to a safe index with feedback. |
| F10 | NOT FIXED | Role-specific valid navigation still required. |
| F11 | FIXED | Accepted mutations, version/status saves, assignments and audit now commit together; failure injection passes on SQLite and MariaDB. |
| F12 | FIXED | HTTP submission/verdict now share one transaction; legacy submitted attempts can resume using stored source. Failure and concurrency regressions pass. |
| F13 | NOT FIXED | Failed submission operation identity/replay protection remains missing. |
| F19 | FIXED | Course lists/current-course selection and predecessor gates consistently use order_num then id; tied-course bypass regression passes. |
| F20 | NOT FIXED | Ungraded Experiment workflow remains missing. |
| F21 | NOT FIXED | Accessible resizing/pane controls remain missing. |
| F23 | NOT FIXED | Privacy-scoped authoritative event/KC evidence remains required. |
| F24 | NOT FIXED | Discoverable role report UI remains required. |
| F26 | NOT FIXED | Source/admission/export/concurrency limits remain required. |
| F28 | FIXED | Ordered admin-fleet locks protect the minimum; all three independent-connection MariaDB race cases now refuse one competing removal. |
| F30 | NOT FIXED | Mandatory integration CI and browser evidence remain required. |
| F31 | REQUIRES RUNTIME VERIFICATION | Read-only configured MariaDB inventory succeeded, 38 tables including framework and expanded domain tables; full column/index/FK parity and staging behavior still need comparison. |

### F04: numeric request-to-domain boundary

- Root cause: validated HTML numeric strings reached services that require native integers; course, section and mission fields were affected. Assessment passing-score coercion already existed and was preserved.
- Files: `AdminCourseUpdateRequest`, `AdminSectionUpdateRequest`, `AdminMissionUpdateRequest`; `tests/Feature/AdminNumericInputTest.php`.
- Approach: convert only digit strings accepted by FILTER_VALIDATE_INT, retaining malformed, fractional, negative, array and overflowing values for validation rejection. Never coerce an invalid value into zero.
- Tests: browser-string persistence for course/section/mission/assessment; malformed numeric matrix and unchanged stored values; existing role, ownership and history suites retained.
- Verification: two defects reproduced in the initial numeric regression group; subsequent five admin suites passed 74 tests / 349 assertions. Formatting and baseline-relative static analysis passed.
- Remaining risk: interactive browser form smoke remains to be performed; server HTTP representation is verified.
- Commit: `88da55c`.

### F06: valid percentage thresholds

- Root cause: request and service only required a nonnegative integer.
- Files: `AdminAssessmentUpdateRequest`, `AdminAssessmentService`, `AdminNumericInputTest`, `CurriculumVersioningTest`.
- Approach: max:100 in the request and 0–100 enforcement in the domain. Existing historical verdict and threshold snapshots are unchanged.
- Tests: native/string 0 and 100 accepted; 101 and -1 rejected; direct-service 101 refused without configuration changes or attempts. Historical-pass test now uses threshold 100 and genuinely failing retry source, preserving its original history purpose without invalid content.
- Verification: numeric/admin group 74 / 349 passed; subsequent versioning regression group passed; no academic results were rewritten.
- Remaining risk: current configured content needs read-only threshold inventory; any correction must use an audited authorized write, not an ad hoc database update.
- Commits: `88da55c`, `7475d15`.

### F05: fail-closed mission definitions

- Root cause: blank/bad/empty definitions were interpreted as no checks, member shapes could throw TypeError, and content errors could be charged as student failures.
- Files: `ValidationService`, `MissionGradingService`, `CurriculumContentSeeder`, `MissionDefinitionIntegrityTest`, `MissionTest`, `MissionServiceTest`.
- Approach: validate list/member/type/required-value/count/operator/tag/negation/message shapes before academic evaluation. Validate regex syntax and bound matching/depth; exhausted evaluation returns unavailable. Validate behavioral fixtures before structural source failures can be charged. Valid behavioral-only definitions may omit structural checks because protected behavior tests remain mandatory for success. Boss zero-check/percentage scoring is preserved.
- Publication: the reviewed curriculum release preflights all structural definitions before any curriculum write using the same validator. The seed command was executed only inside PHPUnit's disposable SQLite connection, never against the configured database. Existing version-event suppression is F18 and is not claimed resolved.
- Tests: null, blank, bad JSON, empty list, object/scalar/unknown members, missing/empty needles, wrong member types, malformed regex/tag/operator/negation, invalid behavior configuration with failing source, valid completion, every seeded definition and publication refusal before writes. Existing blank-pass and expected-500 tests were updated to the intended unavailable behavior; legitimate completion fixtures now supply explicit valid rules.
- Verification: 141 relevant tests / 562 assertions passed. Full suite before the last additional refusal test passed 1,209, skipped 10, with 6,952 assertions. The final definition/versioning target including that extra test passed 39 / 274. Formatting, analysis and diff checks passed.
- Remaining risk: read-only live curriculum inventory still needed; malformed existing content will become unavailable until repaired through approved content changes. The full conceptual versioned DSL is not introduced by this narrow fix.
- Commit: `a1d6a11`.

### F11: atomic accepted admin changes

- Root cause: domain saves and assignment syncs preceded audit insertion without a shared transaction, allowing partial accepted changes.
- Files changed: `CourseService`, `SectionService`, `AdminMissionService`, `AdminAssessmentService`, `ClassroomService`, `UserService`; new `AdminMutationAtomicityTest`.
- Implementation: each accepted logical mutation and its success audits share a retryable database transaction. Existing records are locked and refreshed before computing changes/version summaries. Refusal audit records remain durable outside rolled-back refusal transactions. Existing role, ownership, no-op, historical-score and XP contracts are preserved.
- Tests added: audit-storage failure across six update services, user/classroom creation rollback, assignment audit rollback, second course-save rollback including version/audit, and pivot failure after detach. Existing management tests verify legitimate changes and refusal boundaries.
- Commands/results: `php artisan test --compact` passed 1,220 tests, skipped 13 MariaDB-only cases, 6,993 assertions. Disposable MariaDB run of `AcademicConcurrencyTest`, `ReportIndexAuditTest`, `AdminMutationAtomicityTest` passed all 23 tests / 95 assertions without skips. `composer run analyse`, Pint and both unstaged/staged `git diff --check` passed.
- Remaining risk: published curriculum version conflicts, F17/F18, remain separate findings. No configured-database writes occurred.
- Commit: `daa179f`.

### F28: serialize active-admin removals

- Root cause confirmed at runtime: two independent MariaDB transactions each counted three active admins before removing different targets. Demotion, deactivation and mixed cases all failed the new regression before the fix.
- Files changed: `UserService`, `AcademicConcurrencyTest`.
- Implementation: role/status mutations lock the admin fleet in ascending ID order, then lock and refresh the target, check current self/fleet guards, and commit updates plus audit together. A protection refusal rolls back and records the failed attempt afterward. Base-field-only updates lock the target alone.
- Tests added: three independent-connection concurrent-removal cases, each asserting exactly one success, two remaining active admins and both success/refusal audit evidence. Existing role-management/security tests retain legitimate promotion, no-op and self-protection behavior.
- Commands/results: disposable MariaDB `AcademicConcurrencyTest --filter=admin_removals` reproduced three failures before the fix and passed three tests / 12 assertions afterward. The subsequent MariaDB 23-test group and full SQLite suite above passed. Static analysis, Pint and diff checks passed.
- Remaining risk: lock contention scales with the admin fleet; this preserves the existing small-fleet model. No new lock table or production DDL is required.
- Commit: `daa179f`.

### Destructive-test database protection

`tests/TestCase.php` now refuses fixture initialization unless the target is in-memory SQLite or an explicitly opted-in disposable MariaDB/MySQL database ending in `_test` with its inherited Unix socket cleared. The missing-opt-in invocation failed before fixtures with zero assertions, an expected safety refusal. This guards against environment overrides accidentally directing resets to the configured database. Commit `b34097d`. The configured port 3307 database remains read-only; tests use the separate rootless container on port 33308 without host volumes. F30 remains open for mandatory CI integration jobs.

### F19: deterministic tied-course prerequisites

- Root cause: the gate ignored equal ordinals while displayed courses implicitly had a predecessor. The new regression reproduced the bypass before implementation.
- Files changed: `AssessmentService`, `DashboardService`, `LearningPathService`, `CourseService`, `CourseProgressService`, `StudentService`, `CompetencyService`, `CourseAnalyticsService`, `AttentionService`, `AssessmentController`, `MissionIndexController`; new `CourseSequenceSecurityTest` and affected preview/report/ownership fixture tests.
- Implementation: earlier courses are those with lower order_num, or equal order_num and lower ID. All course ordering consumers use order_num then ID. Existing active/nonempty predecessor and historical-pass semantics remain. Accepted reordering still uses the audited transactional course write service; no schema mutation or historical-state rewrite was introduced.
- Tests: tied successor denies lesson/workspace/submission/draft/hint/reveal/Boss initiation with no academic writes; historical pass followed by failed retry permits successor; inactive/empty courses do not block; audited reordering changes prerequisites. Existing preview tests now isolate each active course. Reporting fixtures provide trusted historical state instead of relying on the bypass, and student ownership is tested against the same eligible course.
- Verification: new regression failed before fix; final four-suite progression group passed 40 / 249. The eight fixture-dependent failures/errors discovered by the broad suite were corrected without weakening route gates or changing metric expectations. Full suite then passed 1,223 / 7,015 assertions, skipped 13 MariaDB-only cases. Disposable MariaDB progression/Boss group passed 37 / 149; ordering/analytics/report group passed 35 / 300. Analysis, Pint and diff checks passed.
- Remaining risk: published reordering intentionally affects future access; earned historical passes remain valid. Read-only inventory of existing ties remains part of F31.
- Commit: `8b32c95`.

### F12: atomic Boss submission and recoverable stored submissions

- Root cause confirmed by failure injection: a notification/evaluation exception left the separately committed attempt in submitted status. A second request could not resume it.
- Files changed: `AssessmentService`, `AssessmentController`, `resources/views/assessments/show.blade.php`, new `AssessmentRecoveryTest`, `AcademicConcurrencyTest`.
- Implementation: the only HTTP submission caller uses a new synchronous domain operation that locks the user then attempt, rechecks ownership/current eligibility, and wraps submit plus evaluate plus all reward/notification effects in one transaction. Existing lower-level submit/evaluate methods retain their accepted separate contracts. A pre-existing submitted attempt is evaluated using its persisted source, ignoring replacement source and authoritative fields from the request. The pending view offers a CSRF-protected RESUME VERIFICATION action. Final attempts remain immutable; repeat requests cannot re-award.
- Tests: injected evaluation/notification failure leaves started status, null submission/score and no reward/achievement/notification; repaired infrastructure permits a successful retry. Stored failing source cannot be replaced with passing source or forged score/status/XP. Stored passing source recovers once despite replacement failing source. Wrong user/attempt targeting is denied at HTTP and domain boundaries. Concurrent new submission and recovery cases each commit one verdict and one XP/notification effect.
- Verification: two regression defects reproduced before fix. Final assessment/recovery/attempt group passed 83 / 266. Full PHP suite passed 1,227 / 7,051 assertions with 15 MariaDB-only skips. Disposable MariaDB recovery/concurrency group passed 18 / 88 without skips, including both new races. Static analysis, Pint and diff checks passed. The first concurrency fixture omitted rules and correctly failed; explicit matching protected rules corrected the fixture, not the grading contract.
- Remaining risk: infrastructure failures still use the existing reported HTTP 500 handler; rollback makes them retryable. Recovery requires current eligibility and an active course/challenge, preserving access restrictions. Existing malformed legacy source/content still needs controlled inventory/repair under F31. Interactive browser smoke remains part of F30.
- Commit: `f379fd1`.

### F09: validate mission-index filters before normalization

- Root cause: manual casts/string operations accepted array-valued GET filters; new tests reproduced 500s for nested search/course inputs and missing validation for unsupported values.
- Files changed: new `MissionIndexRequest`, `MissionIndexController`, `resources/views/missions-index.blade.php`, new `MissionIndexInputTest`.
- Implementation: student authorization and ownership-probe rejection run before validation. Search is nullable scalar text bounded to 200 characters, status is an explicit scalar vocabulary, and course is an existing integer ID. Only safe validated fields reach filtering. Browser validation redirects to the unfiltered missions route and displays the error with the existing status component; the search input also communicates the length limit.
- Tests: nested/array search, course and status; unsupported status, fractional/missing course, oversized search; valid scoped filtering; combined ownership probe and malformed search; browser redirect and visible message. Existing role/security/index cases remain intact.
- Verification: initial regression group reproduced seven missing-validation defects. Final input/index/authorization/security group passed 33 tests / 196 assertions. Pint, static analysis and diff checks passed; the subsequent full suite after F03 includes this group and passed.
- Remaining risk: catalog processing scale remains separately tracked under performance findings; this fix does not expand academic visibility. Commit `49ff769`.

### F03: safe exact editor source restoration

- Root cause: JSON embedded inside a JavaScript string was decoded twice, turning source escapes into invalid JSON or changing characters. All 12 original rendered-expression regressions failed before correction.
- Files changed: `resources/views/challenge.blade.php`, `resources/views/assessments/show.blade.php`; new `EditorSourceRestorationTest`.
- Implementation: use installed Laravel's context-safe `Js::from` exactly once. The hidden textarea and editor initialization consume the same normalized source string. Valid flashed text takes precedence over stored code; null becomes empty text, and malformed non-string flashed input falls back to the stored draft/attempt. No draft, grading or completion authority moved to JavaScript.
- Tests: execute the actual rendered initialization expression in isolated Node/V8 for both workspaces, with stored and flashed multiline/quote/backslash/Unicode/line-separator/script-boundary/template/empty source; assert exact round trip, safe script boundaries and no XP writes. Both malformed flashed-array cases reproduced render-time 500s before their correction.
- Verification: final editor/mission/Boss group passed 89 tests / 364 assertions, including 18 restoration cases. Full PHP suite passed 1,255 / 7,201 assertions, skipped 15 MariaDB-only cases already exercised across isolated target groups. Static analysis, Pint, production build and diff checks passed. Existing editor chunk-size warning remains F38.
- Remaining risk: these checks execute rendered JavaScript, not interactive CodeMirror/browser navigation. Browser work remains required under F30 and is being provisioned in the approved disposable tooling environment. Commit `68771ad`.

## Remaining P2/P3 Work

Thirteen original P2 and two P3 findings remain open; F32 is now fixed as described below. F29 gained stdin-error handling and successful ordinary timeout/cleanup evidence during F01, but is only PARTIALLY FIXED and requires lifecycle verification. No broader lifecycle claim is made.

### F32: conclusive dependency advisories and patched package

- Status: FIXED. Retrieval was originally inconclusive, then found Medium PKSA-m2dq-1fhr-29b1 / GHSA-97jj-33gv-5xf9 and High PKSA-m4t9-vsgq-8khn / GHSA-3q6v-r5mr-hxv8 affecting Commonmark 2.10.0. See [raw HTML advisory](https://github.com/advisories/GHSA-97jj-33gv-5xf9) and [table parsing advisory](https://github.com/advisories/GHSA-3q6v-r5mr-hxv8).
- Explicit user approval obtained for a targeted dependency update. `composer update league/commonmark --no-interaction --no-scripts` changed only Commonmark to 2.10.3 in `composer.lock`, without changing manifest constraints or executing project hooks.
- Verification: independent `composer audit --format=json` returned empty advisories and abandoned lists; `composer validate --strict` passed. The production build passed with existing F38 chunk-size warning. Full PHP suite after update passed 1,227 / 7,051 assertions, with 15 separately tracked MariaDB-only skips. No direct application Markdown rendering usage was found by code search; broad rendered feature coverage passed. npm advisory verification remains zero vulnerabilities.
- Remaining risk: future advisories require periodic verification; F30 CI evidence remains separate. Commit `dd2f419`.


## Regression Verification

| Executed check | Latest completed evidence |
| --- | --- |
| Fresh baseline PHP | 1,180 passed / 10 skipped / 6,685 assertions. |
| PHP after P0 | 1,183 passed / 10 skipped / 6,711 assertions. |
| PHP after F04/F05/F06 | 1,209 passed / 10 skipped / 6,952 assertions; final extra publication-refusal test passed in its target group. Further full verification remains required after subsequent fixes. |
| PHP after F11/F28 | 1,220 passed / 13 skipped / 6,993 assertions; all skips require MariaDB and were separately executed successfully. |
| PHP after F12 and approved F32 update | 1,227 passed / 15 skipped / 7,051 assertions; all 15 MariaDB-only cases have executed successfully across isolated target groups. |
| PHP after F03/F09 | 1,255 passed / 15 skipped / 7,201 assertions. Editor restoration expressions also executed in isolated Node/V8; interactive browser acceptance remains outstanding. |
| Grader subprocess/HTTP suite | 37 passed, zero skipped/failed. |
| Strict real sandbox | 20 checks passed, zero failed, rootless Podman and Node 22.23.3. |
| Static analysis | Passed, zero new errors against frozen baseline, after approved execution outside worker-socket sandbox restriction. |
| Pint | Passed after modified PHP files. |
| Production frontend build | Passed after P0; existing editor-size advisory remains F38. No interactive frontend acceptance claimed. |
| Composer metadata | `composer validate --strict` passed. |
| npm advisories | Completed, zero vulnerabilities across 165 dependencies. |
| Composer advisories | Completed after approved update to Commonmark 2.10.3, zero advisories and zero abandoned packages. |
| Git diff whitespace | `git diff --check` and staged diff checks passed for committed groups. |
| MariaDB skips | All 15 currently skipped SQLite cases were executed successfully in the separate disposable MariaDB target; the concurrency/index/rollback group passed 23 / 95, and the expanded recovery/concurrency group passed 18 / 88, zero skips. Port 3307 stays read-only. |
| Browser/front-end tests | Not yet executed; no readiness claim. |

## Security Reassessment

Preserved CSRF/student/role gates, source-only submission authority, required Knowledge Check guards, server-owned XP, historical Boss-pass completion, exact classroom student-course scope and report protections. Grader observations are untrusted; only the trusted service compares them to protected expectations. Forged client authority fields do not affect academic outcomes. Legacy verdicts and wrong academic totals fail unavailable without XP changes.

Remaining security/integrity risks include malformed existing content requiring repair, F13 replay penalties and F26 admission limits. These have not been concealed by passing tests.

## E2E/UAT Readiness Reassessment

**NOT READY.** Both original P0 findings are fixed and verified at the documented boundaries. Eleven P1 findings remain open, including integrity, recovery, admission, authorization-invariant and integration-confidence blockers. Passing the SQLite suite and sandbox tests is insufficient for full-system acceptance.

Current unresolved original-register counts: Critical 0, High 6, Medium 15, Low 5; P0 0, P1 11, P2 13, P3 2, total 26. Counts retain original finding severity/priority rather than counting two advisories as new top-level entries. The two advisories retrieved under F32 were patched and independently reverified; they are not counted as new top-level findings.

Continue security/integrity P1 groups, then progression/read-model integration, then UI/accessibility and required integration CI. Re-run the broad suite and reassess every finding before issuing a final readiness decision. This phase is ongoing; the report does not authorize deployment or the next development phase.


---

# 2026-10-07 Continuation: Evidence and Implementation Plan

> This dated continuation supersedes current-state assertions in the historical report above. Investigation found no pre-existing uncommitted changes: `main`, HEAD `9a6cd6484d0767a8cb7f66d8a890afc2b977687f`, empty staged/unstaged diff and untracked list. Historical fixes were independently inspected, not automatically accepted.

**Goal:** Correct reproduced scope, progression, submission, preview and development-fixture defects while preserving Laravel domain services and role boundaries.
**Architecture:** Keep authoritative decisions in existing domain services. Reuse exact classroom student/course pairs and canonical assessment reachability. Make no production writes or dependency changes without required approval.
**Tech stack:** PHP 8.5.10, Laravel 13.30.1, PHPUnit 12.5.34, MariaDB 13.0.2, Tailwind 4.3.3, Vite 8.2.2, Node 26.10.0.
**Spec:** User's comprehensive A–V audit and continuation instructions in this session.
**Execution:** Caveman builders handle at most two owned files per task; root owns cross-cutting work. TDD before each behavioral fix. No commits or pushes.

## Confirmed findings and ordered plan (P)

No authentication bypass or arbitrary application execution was established. Priority follows observed impact, not advisory labels alone.

| ID | Priority | Evidence / root cause | Smallest fix / files | Regression risk and acceptance |
|---|---|---|---|---|
| C01 | P1 | AttentionService scopes candidates but reads all-course evidence. Private progress suppresses never_started; unattributed activity removes inactive and exposes its timestamp. | AttentionService + scoped behavioral tests: apply exact authorized pairs to every source, exclude unscoped activity for teachers. | Preserve Admin fleet behavior; private evidence must not change teacher output. |
| C02 | P1 | Same-order earlier unfinished course: AssessmentService denies eligibility while roster shows READY and analytics counts ready=1. | StudentService/CourseAnalyticsService reuse canonical batched reach verdict including id tie-break. | Avoid private course details or per-row query fan-out; predecessor history and zero-mission semantics preserved. |
| C03 | P1 | Identical failed POST twice: XP 150→140→130, two penalties. A user lock serializes without deduplicating operations. | Add explicit operation identity and persist replay outcome atomically at submission boundary; regression same ID vs distinct deliberate IDs. | Explicit UUIDs distinguish deliberate attempts. Legacy callers intentionally deduplicate identical mission/version/source until they send UUIDs. Unavailable grading remains free and retryable. Schema rollout requires separate approval; no production migration. |
| C04 | P1 | Course switches active→locked at user-lock boundary after controller checks; submission still writes Progress and 50 XP. | Fresh availability checks at domain mutation boundary with compatible locking; regression boundary interleaving and MariaDB. | Lock order/deadlocks, incomplete fixtures, current required Knowledge Check semantics. |
| C05 | P1 | Boss Run/initial preview assigns raw srcdoc; CSS renders text, JS does not execute as script. | assessments/show uses existing previewDocument helper; rendered browser checks HTML/CSS/JS. | Preserve opaque iframe sandbox and CSP. |
| C06 | P2 | Missing hints charged 5 XP; missing solution charged 30 XP. | XpService rejects unavailable content; challenge affordances reflect actual availability. | Existing ordinal prices and legitimate idempotent assistance purchases preserved. |
| C07 | P2 | Draft POST after completion recreates a stale draft. | DraftService serializes on user and refuses completed saves. | Exact unfinished source survives; no draft after completion. |
| C08 | P2 | KC-only submitted learner appears in timeline but active_students=0. | TeacherDashboardService includes submitted KC with exact pairs. | No started-only or private-course activity counted; unique student count. |
| C09 | P2 | Admin monitoring pages 200 link to teacher reports returning403. | Gate report CTA by actual role; preserve direct URL denial. | Teacher exports retain screen/filter parity. |
| C10 | P2 | Announcement audit failure leaves mutation; stale draft publishes archived row. | Atomic audit+mutation and row reload under lock. | Refusal audit retention and notification fan-out dedupe. |
| C11 | P2 | Mobile nonstudent logout exposes only aria-hidden SVG; no accessible name. | Explicit accessible label and real keyboard/AX validation. | Desktop spacing and role navigation unchanged. |
| C12 | P2 | Knowledge Check return link discards unsent selections. | Preserve safe attempt draft or clearly communicate unsaved state after actual browser reproduction. | Client selections must never become authoritative score/completion. |
| C13 | P2 | npm audit: shell-quote critical, source-map-js high; three affected package nodes including concurrently. | Prepare narrowly scoped dependency override for explicit approval; no blind major downgrade. | Verify installed APIs/build/tests; dev advisory is not proof of student-reachable RCE. |

- [x] Pin focused failing regressions, fix C01–C05 before cosmetic work.
- [x] Apply C06–C11 when narrow and verified; record remaining items explicitly.
- [x] Add repeatable development-only Admin/Teacher/Student fixtures through existing factories/curriculum/classroom services; require external demo password and disposable database.
- [x] Migrate/seed only new isolated `_test` database; verify repeat seeding, role landing pages, classroom ownership and fresh tests.
- [x] Exercise actual authenticated Student/Teacher/Admin browser paths at 390/768/1280/1920px, keyboard controls, previews, errors, login/logout and direct authorization denials. Complete course/Boss progression remains open.
- [x] Execute available full PHPUnit/MariaDB/analysis/format/advisory/build/Node/browser checks and diff review; record strict-container failure and unverified accessibility separately.
- [x] Fresh independent reviewer examines complete final diff and required behavior; final A–V results report exact passes/skips and one release verdict.

## Baseline evidence (A)

`php artisan test --compact`: 1311 total, 1294 passed, 17 skipped, 7700 assertions, 51517ms. `composer run analyse`: zero new errors. `npm run build`: succeeded; lazy editor chunk 563.90kB (gzip193.56kB), Vite advisory above500kB. Unrestricted Node tests: 51 passed, zero failures/skips. Composer audit: no advisories/abandoned packages. npm audit: three vulnerable nodes (critical2/high1); no dependency edits authorized yet. Browser/CDP and MariaDB acceptance skips are not passing evidence.

Named reviewing-security, investigating-performance, Impeccable and testing-webapps skill packages were searched but unavailable. Actual Laravel security/performance rules, UI/UX skills and provisioned Playwright are used as equivalents, without claiming unavailable skills were invoked.


Plan extension C14 (P1): read-only configured DB returned session timezone SYSTEM, offset +08:00; fresh DB-default audit timestamps appeared eight hours ahead under Laravel UTC. Verify a failing MariaDB clock regression, set both MySQL/MariaDB connection sessions to UTC using the installed Laravel 13 connector's supported timezone option, then prove default ledger timestamps align. Do not rewrite ambiguous historical timestamps.

C09 correction: independently reviewed `student-progress.blade.php` was already Teacher-only at HEAD; that allegation is withdrawn. Only Course Analytics had the reproduced Admin report CTA defect.

C18 (P1) plan: `.github/workflows/laravel.yml` runs only frontend build and SQLite PHPUnit, omitting existing PHPStan/advisory/Node security commands despite a tracked frozen baseline. Add those already verified commands as required steps in the same job, parse the workflow with the installed YAML parser, and obtain independent diff review. Do not introduce dependencies or claim a hosted run; required MariaDB/browser/strict-container gates remain a separate unresolved portion of F30.

---

# 2026-10-07 Final Independent Continuation

This is the current audit and implementation record. It supersedes historical counts, readiness statements and open/closed labels above. Repository changes passed the recorded checks; complete deployment acceptance has not been demonstrated. No configured-database migration, seed, password change, commit or push was performed.

## A. Baseline

| Item | Observed baseline |
| --- | --- |
| Repository | `/home/vmc/Projects/CodeQuest` |
| Branch / HEAD | `main` / `9a6cd6484d0767a8cb7f66d8a890afc2b977687f` |
| Working tree before implementation | Empty `git status --short`, staged/unstaged diff and untracked-file list. Thus there were no inherited uncommitted edits to preserve or correct. Committed prior work was independently reviewed. |
| Recent commits | `9a6cd64` design/prototypes; `a1a6f0c` learning/workspace; `4c6d93f` report scope/preview; `f691d45` reports UI; `81dbecf` monitoring; `a046929` unsectioned progress; `056ac17` pane controls; `330a354` Experiment; `71c5e98` required unsectioned missions; `fbede7d` server verdicts. |
| PHP / framework / Composer | PHP 8.5.10; Laravel 13.30.1; Composer 2.10.3. |
| Test/static tools | PHPUnit 12.5.34; Larastan 3.11.0; Pint 1.30.5; Boost 2.7.0. Frozen PHPStan baseline preserved. |
| Other PHP packages | CommonMark 2.10.3; Laravel Dompdf wrapper 3.1.2. Installed versions, not manifest minimums. |
| Frontend | Node 26.10.0, npm 12.2.0; Vite 8.2.2; Tailwind 4.3.3; laravel-vite-plugin 3.2.0; concurrently 10.0.5. CI uses Node 22. |
| Editor | CodeMirror 6.0.2; HTML 6.4.12, CSS 6.3.1, JavaScript 6.2.5 language packages; one-dark 6.1.3. Editor loaded separately from ordinary pages. |
| Configured database | Read-only inventory: connection `mysql`, database `codequest`, MariaDB 13.0.2, 38 tables. Configured Unix socket matters; clearing it and probing TCP 3307 does not test the same connection. Older `system404` documentation is not current inventory evidence. |
| Runtime configuration | Local/debug configuration with database sessions/cache. This was not used as evidence that production debug is enabled. No secret values were copied into this report. |
| Baseline PHP suite | 1,311 discovered; 1,294 passed; 17 skipped; 7,700 assertions; 51,517ms. |
| Baseline analysis/build | PHPStan zero errors; Vite success, 31 modules, approximately 1.33s; editor 563.90kB / gzip 193.56kB generated a chunk-size advisory. |
| Baseline security checks | Composer audit zero advisories/abandoned packages. npm audit three vulnerable package nodes: two critical, one high. Node tests 51 passed after permitted unrestricted retry. |

Investigation covered routes/middleware, requests/policies/gates, services/models/schema, seeders/factories, Blade/components, JavaScript/CSS/Vite, authentication, tests and CI. Implementation followed the dated plan. Newly discovered clock, database-URL and factory failures were reproduced before their narrow fixes. Requested reviewing-security, investigating-performance, Impeccable and testing-webapps packages were unavailable; Laravel security/performance guidance, UI/UX skills and actual Playwright supplied the corresponding review. Caveman investigator/builder/reviewer roles, planning, TDD, systematic debugging and verification/review skills were applied. Unavailable skill names are not claimed as executed tools.

## B. Architecture Map

Laravel remains the academic authority. Thin controllers validate/authorize and call existing services; Eloquent models represent `the404_*` tables. Services produce both academic mutations and scoped read models. Blade renders those verdicts; JavaScript manages presentation, editors and optional drafts. No framework replacement or new server-side student-code execution was introduced.

| Workflow and routes | Controller, authorization and domain authority | State, presentation, success/failure/recovery |
| --- | --- | --- |
| Login → `/dashboard`; `/logout` | Auth controller, guest/auth middleware, current active-user check, role landing rules; Laravel session regeneration/invalidation and login throttling. | Session plus user status/role; Student dashboard, Teacher roster, Admin dashboard, Operator permitted notification area. Invalid credentials remain a form error; inactive/incorrect roles are denied on the server. |
| Student `/learning-path`, `/missions`, `/missions/{mission}` | LearningPathController/MissionController; Student gate; existing course-reach, active-course and required Knowledge Check guards; LearningPath/Resume/Dashboard services. | Courses → sections plus required unsectioned missions → lessons. Progress is persisted completion, not a button state. Locked contexts explain prerequisites; named links lead to lesson/check/challenge. No preceding-mission completion gate is inferred. |
| Knowledge Check start/show/submit/retry under `/missions/{mission}/knowledge-checks/...` | KnowledgeCheckController and service; Student gate, attempt ownership and mission/check association, published-content and prerequisite guards, bounded validation/throttle. | Immutable question/answer snapshot and owned attempt. Backend calculates results; formative checks grant no XP or mission completion. Return/resume preserves attempt; tab draft restores only selected radios; submitted result clears that draft. Invalid answers stay server validation errors; retry creates a distinct numbered attempt. |
| Challenge/Experiment; mission submit/draft/hints/reveal | MissionController; Student gate, access guards and operation throttles; MissionService, MissionGradingService, DraftService, XpService. | Source byte limits; sandboxed browser preview; existing trusted JS evaluator where configured. Experiment remains ungraded. User-first transaction commits completion, XP, achievements and notifications. Failed operation replay is journaled; unavailable grading/schema creates no academic mutation. Completion has a next action; stale completed drafts/purchases are refused. |
| `/assessments`, show/start/submit/retry | AssessmentController/AssessmentService; owned attempts; active definition, reached course and completed required missions. | Backend Boss eligibility; immutable attempt snapshot, stored source and recovery; atomic verdict/first-pass rewards. A historical pass establishes course completion and permits subsequent ordered courses. Failure permits retry; unavailable evaluator preserves recovery rather than inventing a result. |
| Teacher `/students`, `/classrooms`, `/student-progress`, `/needs-attention`, analytics/activity/reports/exports | Teacher-area middleware plus ClassroomAccessService, ReportAuthorizationService and monitoring services. Some monitoring routes allow Admin fleet views; Teacher-specific report contract still requires Teacher. | Exact authorized `(student, course)` pairs in queries and summaries. Roster → classroom/student detail → intervention signals → scoped reports. Empty scope stays empty; foreign classroom/student/course requests return 403. Reports and exports share filters/evidence. |
| Admin `/admin`, users, classrooms, content, announcements, system report | Admin middleware, existing policies/validated requests and audited services. | Explicit administrative permissions; versioned content and preserved academic history; no general endpoint to author scores/XP/completion. Announcement lifecycle is locked and audited atomically. Validation/refusal produces feedback and an audit without partial successful mutation. |
| Operator / service roles | Explicit role gates only; no implied Teacher/Student/Admin authority. | Operator login, notifications and forbidden academic URLs exercised. Grader is an operational service returning observations to a trusted evaluator; it cannot accept client-authored academic verdicts. |

All course ordering uses `(order_num, id)` where prerequisite reach is decided. Historical Boss passes remain valid after content edits. Teacher readiness receives boolean prerequisite reach without exposing private prerequisite names or scores.

## C. Executive Summary

The highest-impact reproduced defects were private-course evidence affecting Teacher intervention signals, false assessment readiness, repeated penalties for replayed failed submissions, stale course/completion state at mutation boundaries, and broken CSS/JavaScript Boss previews. These received targeted fixes and behavioral regression coverage.

The full PHP suite now records 1,377 passed and 21 skipped; disposable MariaDB checks independently cover row-lock behavior. UI changes repaired Knowledge Check return/resume, unavailable assistance, mobile logout naming and Admin navigation overflow. The two explicitly approved dependency overrides reduced npm advisories to zero without changing direct dependency versions.

Release acceptance remains open: the configured database lacks the new replay journal; no DDL was applied there. Strict container-grader checks cannot run without Docker/Podman. The actual browser completed all 30 HTML missions, passed its Boss at 100%, confirmed course completion, reached the next CSS lesson and verified Teacher reporting. Complete JavaScript runtime acceptance remains dependent on the unavailable strict grader environment.

## D. UI/UX Findings

Each confirmed finding below records evidence, cause, effect and the smallest implemented correction. Boundary risks and regression tests are recorded in Q/S. C-identifiers refer to this continuation, not the older F-register.

| ID / severity | Page/component; evidence and root cause | User impact and fix |
| --- | --- | --- |
| C02 / P1 | Teacher roster/analytics advertised READY for a course blocked by an earlier course with the same order. Local checks omitted canonical reach and/or the ID tie-break. `AssessmentReadinessParityTest` reproduces a private prerequisite. | Misleading assessment/intervention decisions. Shared batch reach now governs row state, READY filter and analytics; only a boolean crosses the private-course boundary. |
| C05 / P1 | Boss workspace assigned raw CSS/JS source to `iframe.srcdoc`. Rendered-script regressions failed for three language cases. | Run/initial preview failed to represent the work. Both paths now use the existing language-aware `cq.previewDocument`; sandbox permissions remain restricted. |
| C06 / P2 | Hint/reveal POSTs could debit XP with absent/exhausted/blank content. Presentation and purchase validation differed; stale requests also crossed completion/course boundaries. | Student pays for no assistance. Shared parsing and fresh locked content/course/completion checks refuse unavailable purchases; CTA availability and recovery feedback match. |
| C09 / P2 | Admin Course Analytics displayed a Teacher-report link whose server contract correctly returned 403. | Dead-end CTA. Teacher-only rendering; backend permission unchanged. The separate Student Progress allegation was withdrawn because HEAD already gated its CTA. |
| C11 / P2 | Teacher/Admin/Operator mobile logout button had no accessible name; three role cases failed. | Unidentifiable keyboard/screen-reader action. Explicit `aria-label="Sign out"`; role navigation assertions retained. |
| C12 / P2 | Real browser Return → Resume retained the Knowledge Check attempt but lost an unsent radio selection. No draft presentation state was saved. | Lost work and uncertainty. Optional sessionStorage keyed by authenticated user/attempt restores matching choices; server old input wins; submitted result clears the key. Scores remain server-owned. |
| C15 / P2 | At 1280px Admin top navigation required approximately 1414px; logout fell outside the viewport. Nonwrapping primary links competed with account controls. | Hidden account action and page overflow. Desktop Admin links wrap within the same top-navigation system; no persistent sidebar introduced. |
| C19 / P2, deferred | `Safe_Embed` lesson/example, `CurriculumContentSeeder.php:2053`: starter and authored solution reference nonexistent `/map`. Full browser journey recorded a 404 and the correctly enforced application `frame-ancestors 'none'` block. | Preview cannot show the advertised map. A managed curriculum revision should supply a self-contained `srcdoc` example while retaining empty sandbox/title. No new route, CSP relaxation or historical curriculum overwrite was introduced during this pass. |

Page review covered Student dashboard/path/catalog/lesson/check/challenge/Boss, Teacher roster/classroom/detail/attention/analytics/reports, Admin dashboard/users/content/reports, and role navigation. Existing editor panes, output hierarchy, return links and lesson progression were preserved. Decorative terminal labels and component consistency remain P3 review items where their benefit is unclear; this pass did not undertake a speculative visual redesign.

## E. Student Flow Findings

| ID / severity | Confirmed evidence → cause → effect → fix |
| --- | --- |
| C03 / P1 | Identical failed requests caused XP 150→140→130. A row lock serialized two independent penalties but had no replay identity. Explicit UUID plus an atomic failed-result journal now charges once for that operation; a new UUID permits a deliberate retry with identical source. Legacy no-ID callers replay identical mission/version/source. Completed state takes precedence over an old failed replay; unavailable grading is not cached as academic failure. |
| C04 / P1 | Course became locked after the controller check but before the domain decision; stale mission/course data still allowed completion. Fresh course status under shared lock inside the user transaction now refuses it; a concurrent course revocation cannot commit during the authoritative submission. |
| C07 / P2 | A late draft save recreated work after mission completion; independent review also reproduced course sealing during draft save. Unconditional upsert lacked the completion/course boundary. User-first transaction, fresh shared locks and Progress check now refuse either state. |
| C05, C06, C12 | Preview fidelity, usable assistance and retained check selections repaired as described in D. |

Server tests exercise course reach, required checks, own attempts, first completion/repeat submission, XP/achievements, Boss locking/retry/pass, historical completion and next-course progression. Real browser covers login, path/lesson, check submission/retry/return, draft, all 30 HTML mission completions, Boss sealed before the last prerequisite, actual Boss pass/course completion and next CSS lesson access. It also verifies direct denials and Teacher PASSED monitoring. Authored solutions are read from pure seed definitions into a `/tmp` test fixture; every new academic result comes from normal UI source submission. No precompleted Progress/XP/attempt fixtures, client-authored scores or static progress were used. JavaScript-course container/browser acceptance remains open.

## F. Teacher Flow Findings

| ID / severity | Evidence / root cause / impact / correction |
| --- | --- |
| C01 / P1 | Attention used unscoped progress/attempt/XP timestamps and unattributed activity for an otherwise authorized student. Private-course work could suppress inactivity or alter intervention signals. Evidence sources now apply exact allowed student/course pairs; unattributed activity is excluded for Teacher, empty course scope yields no student, submitted authorized Knowledge Checks supply activity. Admin fleet behavior remains explicit. `ScopedAttentionTest`: 13 tests, 54 assertions in focused verification. |
| C08 / P2 | A student whose only recent learning event was a submitted authorized Knowledge Check was counted inactive. The active-student union omitted that source. Add scoped submitted checks within the existing 14-day window and count distinct users; started, old or private checks remain excluded. `KnowledgeCheckMonitoringTest`: 5 tests, 13 assertions. |
| C02 / P1 | Roster READY, assessment label, READY filter and course analytics now agree with authoritative reach. Private prerequisite content remains hidden. |

Actual Teacher browser navigation exercised authorized classroom/student/report routes and foreign student/classroom denials. Existing report services maintain exact filters, server-derived metrics and CSV/PDF scope. Broad raw metrics were not added; query-budget and report-parity tests remain in the passing full suite.

## G. Admin Flow Findings

| ID / severity | Evidence / root cause / impact / correction |
| --- | --- |
| C10 / P2 | Announcement mutation could succeed before its audit failed; stale draft models could update an archived row. State validation used stale objects and audit writes were outside the same atomic boundary. Create/mutate now commit with audit; mutation locks and refreshes the row before lifecycle checks. Refused lifecycle operations keep their failure audit without retaining partial mutation. Focused existing/new coverage: 27 tests, 143 assertions. |
| C09 / P2 | Course Analytics Teacher-report CTA removed for Admin; direct Teacher-report denial preserved. |
| C11, C15 / P2 | Mobile sign-out name and desktop navigation wrapping repaired. |

Admin browser verified dashboard/users/classrooms/content/system-report navigation and Student-specific denials. Existing user role/status constraints, active-admin minimum, validated content changes and audit mechanisms were retained. No new academic override authority was assigned to Admin.

## H. Security Findings

Authentication inspection retained session regeneration at login, invalidation/token regeneration at logout, active-user checks, server role gates and existing login limiting. Web mutation routes retain CSRF protection; academic inputs retain byte bounds and named throttles. No reachable PHP `eval`/shell-execution path from submitted student source was established. Escaped Blade/`Js::from` boundaries, preview sandbox and CSP were reviewed; tests cover hostile editor restoration and forged grader fields. This is not a production penetration-test certification.

| ID / severity | Evidence / cause / minimal fix / remaining risk |
| --- | --- |
| C13 / P2 | npm reported shell-quote 1.9.0 and source-map-js 1.2.1 advisories, including concurrently as an affected parent. User approved only the prepared overrides. Installed overrides 1.11.0 / 1.2.2; direct dependencies unchanged; audit now zero. Advisories alone did not establish student-triggered server execution. Primary advisories: [shell-quote](https://github.com/advisories/GHSA-pqg4-j6r4-53mv), [source-map-js](https://github.com/advisories/GHSA-68fv-2mgg-jv7q). |
| C16 / P1 | Independent probe showed a nonempty connection URL could bypass a nominally disposable database-name check in test/demo bootstrap. Laravel can resolve URL settings over that name. Shared `DisposableDatabase::allows` now rejects URLs before opening the connection, persistent SQLite, mismatched drivers and unapproved server databases. Ten unit matrix cases plus a seeder refusal regression verify the safety boundary. No destructive probe ran against the configured database. |

Student preview retains an opaque sandbox; CSS/HTML do not gain script permissions and JS does not gain same-origin authority. The existing trusted grader protocol is retained. Node security tests pass, but strict container isolation/service behavior remains unverified because the required runtime is absent. No production debug claim or secret exposure was inferred from local configuration.

## I. Authorization / IDOR Findings

C01 repaired an aggregate Teacher course-scope leak; C02 repaired eligibility presentation without exposing prerequisite details. Direct URL/server permission checks were retained, including Teacher foreign student/classroom/course, Student cross-user attempts, Admin Student-specific actions, and Operator academic actions. UI-hidden CTAs are supplementary to these checks.

No new direct authentication bypass, role escalation or arbitrary cross-user mutation was reproduced during this continuation. That statement is scoped to inspected paths and executed tests, not a universal security guarantee. Independent reviewer probes challenged the actual mutation/safety boundaries and found C16, draft-course sealing and late assistance purchases; each was corrected and rerun. Current-status revocation on a subsequent request remains the middleware contract; broader mid-request role-revocation semantics were not silently invented.

## J. Data Integrity / Concurrency Findings

C03 adds replay identity to failed submissions while preserving existing idempotent successful Progress/XP rewards. Its journal uses unsigned integer IDs compatible with domain tables, restrictive FKs, a short explicitly named unique `(user_id, submission_id)` index, mission version, source hash and serialized failure messages. It retains no submitted source or passwords. This is a failure replay ledger, not a replacement analytics/event model or complete attempt-history system.

C04/C06/C07 recheck fresh mutable state after the user lock. Mission completion/drafts use shared mission/course locks; assistance locks current mission content before charging. C10 announcement state and success audit now share one transaction. Existing Boss first-pass, achievement, retry-number and minimum-admin protections remain.

C14 / P1: configured database session clock was SYSTEM/+08:00 while Laravel used UTC; database-default audit timestamps could appear 28,800 seconds in the future. Both MySQL/MariaDB connection sessions now explicitly use UTC using the installed Laravel connector option. MariaDB clock regressions pass. No global server setting or historical data was rewritten; mixed historical timestamps need separate provenance review.

Disposable MariaDB tests cover independent-connection purchase/spend/start/retry races, duplicate valid/failed submissions, distinct deliberate operations, save/completion order, course revocation, Boss submit/recovery/evaluation and concurrent Admin removal. SQLite skips those lock tests intentionally. Production-driver evidence is the selected MariaDB suite, not a claim that the entire suite ran on MariaDB.

## K. Performance Findings

Confirmed batch-read improvement: C02 replaces divergent readiness calculations with two batch queries for ordered active courses and historical passes. Teacher Attention sources are now bounded by the selected students and permitted pairs. Existing query-budget tests and the MariaDB reporting-index audit pass. No speculative index, new cache or hydration rewrite was introduced.

Lazy editor remains 563.90kB minified / 193.56kB gzip; normal app JavaScript is approximately 5.29kB. The Vite >500kB warning persists. No cold-device/editor initialization benchmark demonstrated that further splitting would justify extra complexity. Final CSS is 119.85kB app / 76.90kB Student, with unchanged separate asset structure; no broad CSS rewrite occurred.

Mission grading still occurs inside the academic transaction. Lock correctness is tested; realistic grading latency/lock-wait throughput was not benchmarked. Long-lock optimization, production-scale report memory/latency and query plans outside the tested report patterns remain recommendations rather than measured regressions.

## L. Accessibility Findings

C11 gives mobile sign-out a name. C15 keeps desktop account actions visible. Real keyboard checks exercised Student account dropdown Escape/outside-click/focus handling, mobile drawer, workspace tab switching/keyboard resizing, completion-dialog dismissal and role logout. Responsive checks use reduced-motion preference. Return/resume check storage is optional and fails safely when unavailable; no academic authority is stored there.

This is functional browser evidence, not a WCAG certification. No complete axe scan, screen-reader matrix, computed contrast audit, 200%/400% zoom or 320px reflow acceptance was executed. Those remain unverified. Existing modal/background focus contracts should receive a dedicated assistive-technology pass; no blanket ARIA additions were made to conceal that gap.

## M. Responsive Design Findings

Student/Teacher/Admin actual pages were checked at 390px mobile, 768px tablet, 1280px laptop and 1920px desktop in the final expanded browser pass. C15 fixed reproduced Admin page/navigation overflow. Native horizontal scrolling for the Admin user table at tablet width remains deliberate; keyboard focus was verified to reveal its offscreen actions. It is recorded as recoverable table overflow, not falsely reported as a clipped navigation control.

Operator was exercised at 390px for authentication, restrictions, keyboard/logout; it was not included in the full three-role layout matrix. No persistent sidebar was added. Final exact viewport/assertion counts are in S.

## N. Architecture / Maintainability Findings

Changes stay within existing controllers/services, views, asset entries, schema and tests. Readiness and assistance parsing now have shared authoritative definitions; no generic repository layer, framework migration or client-authority abstraction was added. Durable replay/deployment and disposable-URL rules were recorded through Boost in `.ai/rules/services.md` and `.ai/rules/tests.md`.

C17 / P2: final full-suite run reproduced `Maximum retries of 10000 reached without finding a unique value`. Course/Section globally requested unique random order values from the same finite 1–10 Faker pool; Mission used 1–20. Schema requires no such uniqueness and ordering ties are valid. Three capacity tests errored before correction; removing only `unique()` from the three order fields yielded 13 targeted passing tests / 90 assertions. Name/slug uniqueness remains. Ordering-sensitive tests still specify positions explicitly. Independent review accepted the fix and verified 12 tests / 168 assertions including tied-course and progression behavior.

Historical docs contain contradictory database inventory and outdated closed/open labels. Current read-only inventory in A is authoritative for this audit. Existing frozen PHPStan debt, curriculum release/version governance and richer submission-history design were not rewritten without reproduced need.

## O. Testing Gaps

New regressions cover scoped signals, readiness parity, replay penalties, current course/completion checks, assistance availability/races, immutable announcement lifecycle/audit rollback, preview languages, role navigation naming, UTC clock, factory capacity and fresh guarded demo fixtures. Existing fixture changes supply actual hints/solutions required for legitimate purchase scenarios; assertions were not relaxed. An unscoped login-only Attention fixture now uses an authorized course-linked learning event because Teacher privacy excludes unattributed activity.

Initial build/browser failures were reported and investigated: the first browser pass found Admin clipping and a harness tab-selector issue; a concurrent Vite rebuild briefly removed the manifest and caused a login 500. Builds and browser execution were then serialized. The KC red pass proved selection loss; its green pass verified retention and storage clearing. A full-suite Faker error was reproduced rather than dismissed as flaky.

C18 / P1: CI previously ran only the SQLite suite/build with Node 22. Four required steps now run existing PHPStan, Composer/npm audits and the combined Node grader/frontend tests; no `continue-on-error` bypass or new dependency was added. Installed YAML parser verifies 14 steps; independent review accepts the diff. The same Node test command also passed locally on official Node 22.23.3. Hosted execution remains unverified. Mandatory disposable MariaDB, real-role browser and strict-container acceptance are still absent from CI. Playwright/Chromium/MariaDB/Node 22 tooling lives under `/tmp`. Dedicated frontend linting is not configured; syntax checks/build are not represented as a linter run.

## P. Prioritized Improvement Plan

| Priority | Confirmed work and current disposition |
| --- | --- |
| P0 | No newly reproduced P0 in this continuation. Existing grader-authority/setup protections preserved and their tests rerun; container isolation still requires acceptance evidence. |
| P1 | Implemented C01 scoped evidence, C02 canonical reach, C03 replay safety, C04 fresh course gate, C05 preview correctness, C14 UTC sessions, C16 database safety and C18 existing CI analysis/security gates. Actual HTML course/Boss/next-course browser flow now verified. C03 deployment and strict grader/JavaScript/remaining CI acceptance remain open. |
| P2 | Implemented C06/C07 assistance/drafts, C08 KC activity, C09 valid CTA, C10 announcement atomicity, C11 logout naming, C12 check draft, C13 approved advisories, C15 navigation and C17 fixture capacity. C19 authored map placeholder deferred to a managed curriculum revision. Broader accessibility, latency/load, historical-time and curriculum/version checks remain scoped follow-ups. |
| P3 | Visual-label polish and additional editor bundle reduction deferred without measured benefit; no large redesign. |

Completed: read-only baseline/system mapping; evidence-backed plan; red/green targeted changes; safe fresh database/seeding; actual role browser checks plus full HTML course/Boss/next-course journey; strongest available local verification; independent complete diff/authority review. Incomplete: production schema deployment, strict container isolation/JavaScript-course acceptance and mandatory remaining integration CI. These are not treated as optional passing checks.

## Q. Changes Implemented

| Files / IDs | Before → after; rationale and regression boundary |
| --- | --- |
| `app/Services/AttentionService.php`; `ScopedAttentionTest.php`; authorized fixture in `AttentionTest.php` — C01 | Unscoped evidence affected Teacher signals → exact pairs and attributable submitted checks. Preserve explicit Admin fleet behavior; verify private progress/attempt/XP/activity cannot change Teacher evidence and empty scope stays empty. |
| `AssessmentService.php`, `StudentService.php`, `CourseAnalyticsService.php`; `AssessmentReadinessParityTest.php` — C02 | Conflicting reach/READY definitions → batch canonical reach with `(order_num,id)`; test blocked and historically passed private prerequisites without names/scores leaking. Existing performance budgets retained. |
| `MissionController.php`, `MissionService.php`, `challenge.blade.php`, journal migration; `MissionSubmissionReplayTest.php`, `MissionMutationIntegrityTest.php`, `AcademicConcurrencyTest.php` — C03/C04 | Replay charged repeatedly; stale active-course check → operation ledger and locked fresh status. Verify same vs distinct UUIDs, changed-work rejection, unavailable/replay/completed behavior, missing schema and MariaDB races. Missing schema safely declines submissions; rollout is mandatory before deployment. |
| `assessments/show.blade.php`; `EditorSourceRestorationTest.php` — C05 | Raw CSS/JS srcdoc → existing language-aware document builder for initial/Run. Execute rendered scripts for HTML/CSS/JS/javascript; retain sandbox limits. |
| `XpService.php`, `MissionController.php`, `challenge.blade.php`; `MissionAssistanceIntegrityTest.php` and existing purchase fixtures — C06 | Purchases could charge for absent content or late completed work → shared usable-content parser, fresh locked course/content/Progress checks, accurate controls/feedback. Tests cover malformed/empty/exhausted content, legitimate purchase, idempotency and late mutation boundary. |
| `DraftService.php`; `MissionMutationIntegrityTest.php`, `AcademicConcurrencyTest.php` — C07 | Unconditional draft upsert → user-first course/completion check and atomic save. Verify completion wins, sealed course is refused, concurrent save/completion leaves no completed-mission draft. |
| `TeacherDashboardService.php`; `KnowledgeCheckMonitoringTest.php` — C08 | KC-only learner counted inactive → scoped submitted checks in distinct-user window. Started/old/private checks excluded. |
| `course-analytics.blade.php`; `CourseAnalyticsTest.php` — C09 | Admin sees a forbidden Teacher-report CTA → Teacher-only link, direct denial retained. |
| `AnnouncementService.php`; `AnnouncementMutationIntegrityTest.php` — C10 | Partial audit/mutation and stale lifecycle → locked refresh, atomic success audit, retained refusal audit. Failure injection and archived/no-op/publish behavior tested. |
| `layouts/app.blade.php`; `RoleNavigationTest.php` — C11 | Unnamed mobile logout → explicit name; five role/navigation cases pass. |
| `resources/js/knowledge-check.js`, `knowledge-check.blade.php`, browser harness — C12 | Unsent selection lost on return → optional user/attempt tab draft, matching radios only, old-input priority, result cleanup. Real browser verifies same-attempt retention and actual submission clearing. Harness also supports an opt-in authored HTML fixture, paced normal submissions, Boss/next-course and saved-preview assertions. |
| `package.json`, `package-lock.json` — C13 | Vulnerable transitive packages → user-approved overrides only; unrelated lockfile-name change removed. Audits/build/Node tests verify; no direct dependency changes. |
| `config/database.php`; `DatabaseClockTest.php` — C14 | SYSTEM/+08 database session → explicit UTC connection sessions. Verify database default timestamps and application clock on MariaDB; no historical rewrite. |
| `resources/css/app.css`; browser harness — C15 | Admin links force overflow → desktop wrap within single top navigation. Actual 390/768/1280/1920 layout checks; no sidebar. |
| `app/Support/DisposableDatabase.php`, `tests/TestCase.php`, `DevelopmentDemoSeeder.php`; `DisposableDatabaseTest.php`, `DevelopmentDemoSeederTest.php` — C16 | Nominal name guard could be overridden by URL → shared preconnection refusal. Ten configuration matrix cases and untouched-database refusal test; environment approval still required for server `_test` databases. |
| `CourseFactory.php`, `SectionFactory.php`, `MissionFactory.php`; `FactoryCapacityTest.php` — C17 | Finite globally unique ordering pool failed bulk fixtures → ordinary bounded order values; names/slugs stay unique. Three deterministic red/green cases, tied-order/progression review and full suite. |
| `.github/workflows/laravel.yml` — C18 | SQLite/build-only job → required existing analysis/advisory/combined Node security/frontend commands before PHP tests. YAML parses 14 steps; commands verified locally including 51 tests under Node 22.23.3; diff independently reviewed. Hosted execution and remaining integration gates are not claimed passed. |
| `DatabaseSeeder.php`, `DevelopmentDemoSeeder.php`; fresh-seeding tests | Unconditional known test account removed from generic seed → curriculum-only generic seed and explicit externally passworded, disposable-only demo seed. Normal factories/hashing and audited ClassroomService relationships; no precompleted academic state. |
| `.ai/rules/services.md`, `.ai/rules/tests.md`, this report | Durable safety/replay decisions recorded via Boost; dated current findings replace ambiguous historical status, without deleting historical evidence. |

All paths without prefixes in Q are under their corresponding existing `app/Services`, `resources/views`, `database/factories` or `tests/Feature` directory. U provides the exact changed paths.

## R. Development Accounts

The accounts below exist only in disposable `codequest_browser_test` on the isolated MariaDB instance at port 33309. They were created through factories and the normal hashed password cast. Production/configured users and passwords were untouched. The local password was supplied externally and is not embedded in application source: **`CQ-Local-20261007!`**.

| Role | Username | Verified scope |
| --- | --- | --- |
| Admin | `demo.admin` | Admin dashboard/users/classrooms/content/system reports; Student-specific routes denied. |
| Teacher | `demo.teacher` | `DEMO-01`, own Student and three assigned active courses; unrelated Student/classroom denied. |
| Student | `demo.student` | `DEMO-01`, curriculum/lessons/checks/challenge/path; seed starts empty, actual browser later completed HTML and its Boss. Current next course is CSS; recorded HTML history stays visible. |

Additional fixtures: `demo.unrelated.teacher` / `demo.unrelated.student` in `DEMO-02`, sharing the same course catalog to expose pair-scope mistakes; `demo.operator` for role-isolation testing. All six use the same disposable local password. Demo seeding twice preserves existing passwords and student work; role/status collisions refuse rather than silently promoting accounts.

Temporary browser login URL, resolved through Boost: `http://127.0.0.1:8766/login`. This local server is not the configured/production deployment. Tooling/data live under `/tmp` and are not persistent installation promises.

Regenerate on an **already provisioned disposable** MariaDB database only, with an empty URL/socket and explicit safety flag. Do not substitute the configured database. On a new empty `_test` database, run migrations before the demo seed:

```bash
CODEQUEST_ALLOW_DISPOSABLE_DATABASE=1 DB_CONNECTION=mariadb DB_HOST=127.0.0.1 DB_PORT=33309 DB_SOCKET='' DB_DATABASE=codequest_browser_test DB_USERNAME=root DB_PASSWORD='' DB_URL='' php artisan migrate --no-interaction
CODEQUEST_ALLOW_DISPOSABLE_DATABASE=1 CODEQUEST_DEMO_PASSWORD='choose-your-local-password' DB_CONNECTION=mariadb DB_HOST=127.0.0.1 DB_PORT=33309 DB_SOCKET='' DB_DATABASE=codequest_browser_test DB_USERNAME=root DB_PASSWORD='' DB_URL='' php artisan db:seed --class=DevelopmentDemoSeeder --no-interaction
```

The root/empty-password connection belongs only to this temporary isolated instance. Demo seed requires `APP_ENV=local` or `testing`, a supplied password of at least 12 characters, and the shared disposable guard. Generic `DatabaseSeeder` does not create demo users or require a demo password. Never use `migrate:fresh` against an existing developer database.

## S. Verification Results

All results below were actually executed; nonzero/skipped/unavailable results remain visible. Raw artifacts are under `/tmp`, not added to Git.

| Command/check | Exact current result and evidence |
| --- | --- |
| Required-browser Laravel suite: `CODEQUEST_REQUIRE_BROWSER_TESTS=1 CODEQUEST_CHROMIUM=/tmp/codequest-20261007-browsers/chromium-1243/chrome-linux64/chrome php artisan test --compact` | **1,398 total, 1,377 passed, 0 failed/errors, 21 skipped, 8,015 assertions, 42,061ms**. `/tmp/codequest-20261007-final-php-v3.log`. Baseline delta: +87 discovered / +83 passed / +4 skipped / +315 assertions. |
| SQLite skips explained | 18 independent-row-lock concurrency cases + 2 MariaDB clock cases + 1 MariaDB reporting-index case. Browser/CDP checks were required, not silently skipped. |
| Disposable MariaDB selected suite | **74 passed, 0 failed/skipped, 328 assertions, 4,694ms**. AcademicConcurrency, DatabaseClock, DevelopmentDemoSeeder, DatabaseSeeder, ScopedAttention, AssessmentReadinessParity, AnnouncementMutationIntegrity, MissionAssistanceIntegrity, ReportIndexAudit, MissionMutationIntegrity. `/tmp/codequest-20261007-final-mariadb-v2.log`. Explicit `_test` environment on port 33309; no configured DB writes. |
| Fresh database/seeding | Fresh disposable database migrated successfully; Demo seeded twice through existing curriculum/services. Fresh seeder tests verify three curriculum/assessment/skill/check mappings, six accounts, two classroom cohorts, permitted/denied role access, no precompleted work, repeatability and production/URL refusal. Included in the 74-test MariaDB result and full PHP suite. |
| `composer run analyse` | **Zero errors**, exit 0 after latest safety/factory fixes; frozen baseline unchanged. `/tmp/codequest-20261007-final-analysis-v3.log`. |
| `vendor/bin/pint --dirty --format agent` | **Passed**, exit 0. `/tmp/codequest-20261007-final-pint.log`. |
| `composer validate --strict` | **Valid**, exit 0. `/tmp/codequest-20261007-final-composer-validate.log`. |
| `composer audit --format=json` | **Zero advisories; zero abandoned packages**, exit 0. `/tmp/codequest-20261007-final-composer-audit.json`. |
| `npm audit --json` | **Zero vulnerabilities of every severity**, 165 package nodes, exit 0 after approved overrides. `/tmp/codequest-20261007-final-npm-audit-v2.json`. |
| `node --test grader/test/*.test.js tests/*.test.js` | **51 passed, 0 failed/skipped/cancelled** on Node 26.10.0 (3,936.18ms, `/tmp/codequest-20261007-final-node.log`) and official Node 22.23.3 (3,643.56ms, `/tmp/codequest-20261007-node22-full.log`). Includes 40 grader/security plus 11 frontend tests; the grader-only Node 22 run records 40, not 51. Temporary official archive matched its published SHA256. This is not the strict container test. |
| `npm run build` | **Passed**, exit 0, Vite 8.2.2, 31 modules, 815ms. App CSS 119.85kB / gzip 21.78; Student CSS 76.90 / 13.29; app JS 5.29 / 2.14; workspace JS 3.86 / 1.36; lazy editor 563.90 / 193.56. >500kB advisory remains. `/tmp/codequest-20261007-final-build.log`. |
| JavaScript syntax checks | `node --check tests/codequest-browser.mjs` and `node --check resources/js/knowledge-check.js`: exit 0. No configured frontend lint command exists. |
| CI configuration | Installed Symfony YAML parser accepts 14 steps; independent review accepts the four added fail-on-error checks. Exact commands executed locally with results above, including Node 22 test compatibility. Hosted GitHub Actions remains unverified. |
| Actual role Playwright, full HTML course | **267 assertions passed, 0 failed; 52 screenshots/layout checks at 390/768/1280/1920px; 0 page exceptions or HTTP 500s, 2 console diagnostics** from C19 `/map` iframe (404 + expected CSP block). `/tmp/codequest-20261007-html-course-browser/report.json`. Initial 1 mission already complete; 29 actual submissions reached 30/30, initial/penultimate Boss sealed, new Boss 100% pass/+100 XP, persisted course-cleared, next CSS lesson access and Teacher PASSED. Source-only authored fixture; 12/min throttle respected without bypass. |
| Read-only actual Boss ledger | Boost database-query against disposable `codequest_browser_test` returns one joined passed HTML attempt: `score=100`, XP `type=assessment_completed`, `amount=100`, `username=demo.student`. `/tmp/codequest-20261007-boss-ledger-evidence.json`. This verifies the actual +100 posting; the static reward label alone was not treated as ledger evidence. |
| Actual role Playwright, final persistence | **197 assertions passed, 0 failed; 48 screenshots/layout checks across all four widths; 0 page exceptions, console errors or HTTP 500s.** `/tmp/codequest-20261007-preview-diagnostic-browser/report.json`. Persisted 30/30 + Boss PASSED + CSS reachable + Teacher PASSED; actual saved frame heading visible, srcdoc equals server-saved source, empty sandbox, named preview, black text/white frame. KC `cq:check:3:9` retains/clears choices. It does not rerun the 29 first completions, which remain proved by the full journey artifact. |
| Earlier browser proof and count changes | Fresh first draft/first completion: 144 passed; KC red/green: selection-loss reproduced then 145 passed. Repeated initial state produced 144; adding 1920 gave 164, also passed against final `app-URT6xElt.css`. Full journey added real mission/Boss/CSS checks (267); subsequent already-passed-state checks omit those mutations (196), then exact saved-source instrumentation adds one (197). No failed assertions were removed to hide a defect. Latest harness: 454 lines, SHA256 `98d9c4b8237ef37be23bd533536166b963e1bc818b6835528ed6bdb57688caa6`. |
| Strict container grader: `CODEQUEST_REQUIRE_SANDBOX_TESTS=1 sh grader/test/integration-container.sh` | **Nonzero exit 1**, reports absent Docker/Podman. Blocked required check, not a pass or accepted skip. Independently reproduced by reviewer. |
| Accessibility | Actual named/keyboard/focus interactions and responsive layout checks passed as described in L/M. Formal automated accessibility/contrast/assistive-technology/zoom certification not run. |
| Independent code review | Reviewer began with complete final diff and affected authority paths, wrote no implementation. Original URL/draft/assistance probes: **3 passed, 8 assertions** after fixes; focused review suite **44 passed, 145 assertions**. Factory follow-up **12 passed, 168 assertions**. No remaining confirmed introduced P0/P1/P2 code defect identified; release gates still open. |
| `git diff --check` | Passed at final inspection; exact final status recorded in U. |

Build/browser writes were serialized after the demonstrated manifest race. Tests were not changed to hide production defects. Trusted historical fixtures remain supported; an overbroad early domain guard was removed after legitimate reporting fixture failures, retaining the reproduced active-course fix.

## T. Remaining Issues

| Priority / status | Remaining issue and practical limit |
| --- | --- |
| P1 / deployment blocked | Read-only configured inventory confirmed `the404_mission_submission_results` absent. This change returns controlled submission-unavailable there instead of risking duplicate penalties or a 500. Do not deploy the replay change as operationally complete until exact journal DDL is reviewed and separately authorized for the intended database. No production migration was executed. |
| P1 / verification blocked | Strict real grader-container/service trust checks require Docker/Podman; absent here. Host Node tests do not replace that boundary. |
| P1 / incomplete runtime acceptance | The actual HTML course → Boss pass → course completion → next CSS lesson is now verified. Complete JavaScript course/browser acceptance still requires the unavailable trusted container grader; the HTML flow does not certify that different execution boundary. |
| P1 / assurance gap | CI now requires analysis/advisory/Node security checks, but still lacks mandatory concurrency/real-role browser/strict-container jobs. No remote CI run was claimed. |
| P2 / unverified | Full configured-schema column/index/FK parity and production-equivalent staging behavior beyond the confirmed inventory/report patterns; entire PHP suite on MariaDB; realistic report and grader latency/memory. |
| P2 / unverified | Complete assistive-technology, contrast, modal focus isolation, zoom and 320px reflow acceptance. Operator larger-viewport matrix also not run. |
| P2 / intentionally deferred | Historical mixed timezone repair requires provenance; persistent draft version/copy recovery and versioned curriculum-release governance require explicit product/data decisions. No historic academic data rewritten. |
| P2 / confirmed C19 | `Safe_Embed` authored starter/solution uses nonexistent `/map`, generating two preview diagnostics during the full journey. Correct the teaching example in a versioned curriculum revision; do not relax application framing protection or add a speculative map feature. |
| P2/P3 / recommendation | Grading under write lock, richer attempt-history retention/analytics, CSS consolidation, prototype-label polish and editor cold-load splitting need measured benefit and a bounded plan. |

Historical-register reconciliation: F01–F06 protections were preserved/retested; C02 addresses the reproduced F07 readiness discrepancy without asserting every read model is now universally identical; unsectioned/Experiment/pane/report implementations already present at HEAD were inspected, not rebuilt. C03 closes the reproduced F13 replay behavior only after journal rollout; C06 closes the reproduced F14 content-charge defect; C07 addresses F16 late saves without adding draft versions. F30/F31 assurance/deployment gates remain open. Old F15/F17/F18/F25/F27/F29/F33–F38 broader recommendations are not silently marked fixed by this pass. Historical finding counts therefore cannot be used as current unresolved totals.

Independent review accepts the reviewed code corrections and rejects complete release acceptance until the gates above are evidenced. This report makes no universal system-completion claim.

## U. Final Git State

Branch: `main`. HEAD: `9a6cd6484d0767a8cb7f66d8a890afc2b977687f`. No commit or push. Final working tree: **39 modified tracked files, 15 untracked additions, 54 paths**, no staged changes. Only authorized source/tests/CI/rules/report and the approved lockfile override remain; no `.env`, database files, screenshots, browser packages, temporary tooling, production credentials or generated build artifacts were added.

```text
 M .ai/rules/services.md
 M .ai/rules/tests.md
 M .github/workflows/laravel.yml
 M CODEQUEST_FULL_SYSTEM_AUDIT_AND_RECOMMENDATIONS.md
 M app/Http/Controllers/MissionController.php
 M app/Services/AnnouncementService.php
 M app/Services/AssessmentService.php
 M app/Services/AttentionService.php
 M app/Services/CourseAnalyticsService.php
 M app/Services/DraftService.php
 M app/Services/MissionService.php
 M app/Services/StudentService.php
 M app/Services/TeacherDashboardService.php
 M app/Services/XpService.php
 M config/database.php
 M database/factories/CourseFactory.php
 M database/factories/MissionFactory.php
 M database/factories/SectionFactory.php
 M database/seeders/DatabaseSeeder.php
 M package-lock.json
 M package.json
 M resources/css/app.css
 M resources/js/knowledge-check.js
 M resources/views/assessments/show.blade.php
 M resources/views/challenge.blade.php
 M resources/views/course-analytics.blade.php
 M resources/views/knowledge-check.blade.php
 M resources/views/layouts/app.blade.php
 M tests/Feature/AcademicConcurrencyTest.php
 M tests/Feature/AttentionTest.php
 M tests/Feature/ChallengeAnalyticsTest.php
 M tests/Feature/CourseAnalyticsTest.php
 M tests/Feature/CurriculumVersioningTest.php
 M tests/Feature/EditorSourceRestorationTest.php
 M tests/Feature/MissionTest.php
 M tests/Feature/RoleNavigationTest.php
 M tests/Feature/XpLedgerTest.php
 M tests/TestCase.php
 M tests/Unit/XpServiceTest.php
?? app/Support/DisposableDatabase.php
?? database/migrations/2026_10_07_020341_create_the404_mission_submission_results_table.php
?? database/seeders/DevelopmentDemoSeeder.php
?? tests/Feature/AnnouncementMutationIntegrityTest.php
?? tests/Feature/AssessmentReadinessParityTest.php
?? tests/Feature/DatabaseClockTest.php
?? tests/Feature/DevelopmentDemoSeederTest.php
?? tests/Feature/FactoryCapacityTest.php
?? tests/Feature/KnowledgeCheckMonitoringTest.php
?? tests/Feature/MissionAssistanceIntegrityTest.php
?? tests/Feature/MissionMutationIntegrityTest.php
?? tests/Feature/MissionSubmissionReplayTest.php
?? tests/Feature/ScopedAttentionTest.php
?? tests/Unit/DisposableDatabaseTest.php
?? tests/codequest-browser.mjs
```

## V. Recommended Next Step

Run the strict grader-container acceptance suite in a disposable production-equivalent environment.


# STRICT RELEASE-BLOCKER ACCEPTANCE — 2026-10-07

## A. Session Baseline

Freshly inspected `main`, HEAD `9a6cd6484d0767a8cb7f66d8a890afc2b977687f`. Starting tree: 54 pending paths, 39 tracked modifications and 15 untracked files; no staged changes. `git diff --check` passed. The complete starting tracked diff and SHA256 hashes of all 54 paths were retained under `/tmp` for preservation checks. No configured-database writes, commits, pushes or PRs are authorized.

Installed versions verified: PHP 8.5.10, Laravel 13.30.1, Composer 2.10.3, PHPUnit 12.5.34, Larastan 3.11.0, Pint 1.30.5, Boost 2.7.0, Node 26.10.0 and npm 12.2.0. The previous audit's verification counts remain historical evidence until rerun in this session.

## B. Skills / Plugins Used

Caveman Full and Ultra, Cavecrew investigator and builder delegation, writing-plans, test-driven-development, systematic-debugging, Laravel security/migration/performance guidance and testing-best-practices are used for the corresponding work. Unslop, verification-before-completion and requesting-code-review govern the final checks. The requested exact `reviewing-security`, `investigating-performance`, `Impeccable` and `testing-webapps` names were not found; Laravel review guidance, UI/UX review guidance and the existing real Playwright/Chromium harness are the available equivalents. Boost is callable through its configured stdio MCP; documentation requests require an outside-sandbox retry after DNS failure. The runtime was initially absent. Rootless Podman was provisioned under `/tmp` without changing application dependencies or host configuration.

Two investigator/reviewer agents stopped on the service usage limit. Their unfinished reviews are not accepted evidence. Journal investigator findings were returned successfully; independent final review must still be completed or recorded as unavailable.

## C. Previous Blocker Confirmation

The prior verdict remains REJECT. Confirmed gaps: populated pre-journal MariaDB upgrade and rollback proofs; strict rootless grader isolation and real JavaScript curriculum execution; configured journal deployment approval. Missing journal safely refuses incomplete mission submissions before academic writes. This safeguard will remain intact. The journal is a failed-operation replay ledger, not a replacement for successful Progress, XP, Boss attempts or Knowledge Check history.

### Concrete acceptance plan

- [x] Add `tests/Feature/JournalSchemaAcceptanceTest.php` only for journal lifecycle evidence. Use guarded disposable databases and `DatabaseMigrations`, avoiding MariaDB DDL inside transaction fixtures. Prove populated historical row checksums survive upgrade and rollback, unique/FK constraints reject invalid writes, real submission retries charge once, and a database-triggered journal insert failure rolls back XP/activity.
- [x] Run the lifecycle tests on in-memory SQLite and a newly created disposable MariaDB `_test` database. Verify fresh migration and seeding separately from populated upgrade.
- [x] Provision rootless Podman and its helpers under `/tmp`. Run the existing strict evaluator/container acceptance without relaxing user, filesystem, network or resource limits. Any missing required runtime capability remains a release blocker.
- [ ] Exercise authored JavaScript behavior through the accepted container and actual application where that environment works. Inspect and test actual preview isolation; classify both `/map` diagnostics using their source and user impact.
- [ ] Fix only reproduced defects with regression coverage. Run Unslop on this session's changed files, then targeted and full regression, real-browser validation and an independent final diff/authority-path review.
- [x] Append the actual results and one release verdict below. Preserve the previous A–V report and all unrelated pending work. Never treat an unavailable required check as passing.


## D. Journal Architecture

`MissionController::submit` derives identity from authentication and checks Student role, course availability/reach and Knowledge Check requirements. It validates source size and an optional UUID. `MissionService::submit` locks the user, reloads the mission/course with shared locks, rechecks active status, then checks authoritative completion before replay history. Missing journal returns controlled unavailability before Progress, XP or journal writes.

The only new table is `the404_mission_submission_results`, introduced by `2026_10_07_020341_create_the404_mission_submission_results_table.php`. It stores failed, cacheable outcomes: user, mission/version, operation identity, source SHA256, failure messages and creation time. It stores neither source nor credentials. There is no public journal endpoint or separate model/repository. The unique `(user_id, submission_id)` constraint and restrictive user/mission FKs backstop the user lock. Successful completion remains Progress plus the XP ledger; unavailable grading creates no failure journal or penalty. Boss and Knowledge Check attempts remain separate authoritative histories.

## E. Journal Fresh-Install Acceptance

Created empty `codequest_release_fresh_test` on the isolated MariaDB instance at port 33309. `php artisan migrate --seed --force --no-interaction` succeeded. `DevelopmentDemoSeeder` succeeded through normal factories, role/classroom services and password hashing. No configured database was migrated.

Actual `SHOW CREATE TABLE` confirms InnoDB, unsigned integer IDs, required mission/version/source/operation fields, `CURRENT_TIMESTAMP` default, explicitly named unique operation index and restrictive parent FKs. Fresh real-browser submission subsequently wrote one failed journal fact and one correct completion reward. The new lifecycle test also exercises fresh wrong/replay/correct/repeated-correct transitions on MariaDB.

## F. Journal Upgrade Acceptance

`JournalSchemaAcceptanceTest::test_populated_pre_journal_upgrade_preserves_academic_history_and_accepts_new_work` establishes all previous migrations with only the journal migration rolled back. It creates representative users, courses, sections, missions, completed Progress, XP, a passed Boss attempt, submitted Knowledge Check attempts and immutable answer snapshots. It records ordered full-row SHA256 checksums and counts across 16 academic tables, then runs only the journal migration through Laravel's migrator.

MariaDB and SQLite both preserve every recorded checksum/count. The newly created journal is empty and subsequent authoritative submissions work. No backfill/conversion occurs because this is an additive future-operation replay table. This tests a realistic populated fixture schema, not a copied production dataset.

Rollback was exercised with a populated replay cache. Other academic rows remain byte-equivalent; incomplete submissions then fail closed. **Dropping the journal deletes replay records and is not a lossless reversal.** A production rollback requires stopping submissions, preserving/exporting replay records and deploying compatible application code. Recreating the table cannot restore lost replay identities. No production rollback or DDL was performed.

Read-only Boost metadata independently confirms configured parent IDs are unsigned INT, InnoDB and `utf8mb4_unicode_ci`. The configured journal table is absent. Deployment remains a separate unapplied gate; never run the full historical migration chain against that pre-existing database.

## G. Journal Concurrency / Idempotency

New lifecycle coverage: **5 passed, 53 assertions** on SQLite and MariaDB. It proves duplicate operation rejection, cross-user operation independence, orphan FK refusal, parent deletion refusal, one failure penalty per replay, one completion reward and one Progress row. A real database trigger aborts journal insertion after failure effects are staged; XP, activity and the 16-table history snapshot roll back together. DDL lifecycle tests use `DatabaseMigrations`, not a MariaDB transaction fixture.

Independent MariaDB worker coverage was rerun: identical failed operations charge once; deliberate distinct operations charge separately; concurrent successful completion awards once; Boss starts/retries/passes, assistance and draft/completion races preserve their invariants. The complete previous MariaDB target set plus the lifecycle test records **79 passed, 381 assertions, zero skipped/failed**.

## H. Grader Container Architecture

Submission authorization/validation and mission-owned rules run in Laravel. The private loopback grader requires a bearer token. Its trusted HTTP process retains expectations and validates protocol-v2 observations. Student source and invocation arguments travel over stdin into a fresh runner container; academic verdicts are constructed outside that untrusted process. Laravel receives only the strict result contract and rejects malformed/infrastructure outcomes.

Accepted disposable runtime: rootless Podman **6.1.3**, crun **1.30.1**, conmon **2.2.1**, cgroup v2 and actual runner Node **22.23.3**. Runtime packages, configuration, storage and helper wrappers are under `/tmp`. A temporary user scope delegates CPU/memory/PID controllers. Host settings and application dependencies were not changed. The image is built from the existing `grader/Dockerfile`, `node:22-alpine`; only runner package/source is copied.

Actual runtime flags retain no network, UID/GID 65534, read-only root, `/tmp` tmpfs 16MB with noexec, all capabilities dropped, no-new-privileges, 32 PIDs, 0.5 CPU and 128MB memory/swap ceiling. No application/source checkout, secret file, database socket or runtime socket is mounted or passed as environment. The trusted service invokes the CLI, not a student-controlled command string. Execution defaults to the configured 10-second service bound; failure probes also exercised a 3-second disposable configuration. Cleanup is bounded separately at 5 seconds.

## I. Grader Isolation Findings

**P1 fixed:** a valid async-loop request returned timeout while its container remained alive. Killing the Podman client did not kill its container. Reproduced against the actual service and observed through `podman ps` before removing that specific disposable probe.

The fix generates an untrusted-input-independent UUID container name and explicitly removes abnormal executions before resolving grading. Cleanup failure returns unavailable and latches HTTP grading/health unavailable until restart. Podman removal supports already-removed executions; Docker uses supported arguments and accepts only its exact missing-container result. No generic error swallowing grants success.

The strengthened strict suite records **24 passed, zero failed**. It verifies actual valid/invalid/forged grading, bounded loops/output, unavailable Node modules, live egress denial, no host bind mounts, absence of host paths, read-only root, writable/noexec tmpfs and actual async timeout removal. Failed visibility probes no longer count as proof; failed container-list commands propagate.

Kernel readings from a live restricted container: UID **65534**, CPU quota/period **50000/100000**, memory **134217728 bytes**, PIDs **32**, seccomp **2**, no-new-privileges **1**. An 80-process burst starts **25** processes and rejects **55**. Actual HTTP OOM returns infrastructure error in **526ms**; async timeout returns error in **3158ms** including removal. Health then remains available, a subsequent valid request passes in **155ms**, and no grading container remains. These are real Podman findings; live Docker isolation was not run.

## J. JavaScript Challenge Acceptance

All **21** seeded behavioral JavaScript missions were executed with their actual authored solutions and server-owned definitions through the real container boundary: **21 passed, 28 behavior checks**. Definitions were exported read-only from the disposable seeded database, not invented toy expectations. Execution durations were **154–465ms**. The remaining **14** of the course's **35** missions use existing structural grading and are not silently counted as runtime proofs.

**P1 unresolved:** actual browser experiments fail for four authored correct DOM solutions: `DOM_Node_Activation`, `Event_Listener_Rewire`, `Build_Nodes` and `Form_Guard`. They require `#status`, `#emergencyBtn`, `#bands` and `#signup/#callsign`. `previewDocument` creates only the console node; correct source therefore throws null-element exceptions. Console/variables execution works. Generic invented DOM markup or network mocks would conceal missing curriculum setup, so none was added. The necessary authored preview fixtures and full JavaScript Student/Boss journey remain release work.

Node regressions cover malformed input/output, transport/spawn failure, forged verdicts, wrong results, source bounds and supported asynchronous behavior. Container timeout/OOM fail closed. This evidence does not certify every structurally graded DOM/module/network exercise as executable.

## K. Preview Sandbox Acceptance

The actual CodeMirror editor writes only `iframe.srcdoc`; preview never submits or creates academic state. Actual JS frames have exactly `sandbox="allow-scripts"`, with no same-origin/forms/popups/downloads/top-navigation grants. HTML/CSS frames have an empty sandbox. Script serialization escapes closing-script content; CSS serialization protects the style boundary.

Real Chromium probes block parent/top DOM, parent cookies, parent and own local/session storage, CSRF access and top navigation with `SecurityError`. Popups are blocked; an application fetch is rejected. Parent location remains unchanged. Read-only database checks after preview probing confirm **zero Progress, XP and journal rows** before subsequent deliberate browser submissions. Keyboard Run works and layouts reflow at 390/768/1280/1920px. The four DOM execution defects remain despite accepted parent isolation.

## L. `/map` Diagnostic Classification

| Diagnostic | Reproduction and impact | Classification |
| --- | --- | --- |
| Missing `/map`, HTTP 404 | Authored `Safe_Embed` uses a resource for which no route exists. Actual HTML experiment displays no map. Local page remains usable; no academic mutation or exception occurs. | **NON-BLOCKING DEFECT**, P2 curriculum example |
| `frame-ancestors 'none'` refusal | The actual 404 response includes CSP plus `X-Frame-Options: DENY`; Chromium refuses nested framing as configured. | **EXPECTED DIAGNOSTIC** |

No route was invented and no sandbox/CSP protection was relaxed to suppress console output.

## M. Authoritative Submission Flow

Authenticated Student, authorized reached mission and required Knowledge Check, validated source/UUID, locked current records, trusted structural/behavioral grading, then one database transaction. Cacheable failure writes bounded penalty/activity and journal together. Correct first completion writes Progress, trusted XP and subsequent achievements/notifications together; the existing completed guard handles later retries. Preview results and client identity/verdict fields never create those facts.

Boss uses authenticated attempt ownership, authoritative eligibility, persisted assessment/source snapshots, trusted evaluation, verdict, first-pass effects and course progression inside its existing submission transaction. It does not use the mission failure journal or the JS mission grader. A passed course controls later course reach through the existing services.

## N. Authorization / IDOR Review

Independent review inspected Student middleware, authenticated identity, course reach/KC gates, Teacher classroom/student/course scope, Admin management boundaries and Operator denial paths. The complete feature suite includes the existing role, classroom-pair, report, admission, behavioral tampering and sequence security tests. Real role browser checks confirm landing pages, authorized reporting and denied Admin/Teacher/Student destinations. The private grader rejects missing/wrong credentials; no journal HTTP endpoint exists.

No new authorization route or academic mutation permission was introduced. Container identity/name/argv comes from trusted service state; source does not become a shell command or environment value. These findings do not claim an exhaustive security certification beyond the exercised boundaries.

## O. Transaction / Data Integrity Review

Journal-trigger failure preserves the before/after academic snapshot. Grader infrastructure errors award no completion/XP and are not cached as student failures. MariaDB races prove completion/first-pass/replay serialization. Published Boss and Knowledge Check history remains in existing attempt/snapshot tables.

Actual fresh-browser wrong submission, authenticated same-operation network retry, correct new operation, reload and successful retry were independently reconciled against SQL: Student3 has one mission2 penalty **−10**, one mission2 completion reward **+60**, one failed journal row and one mission2 Progress row. With the first mission's **50**, balance is **100**. UI progress moved **3% to 7%**; completion survives reload. The previous disposable history independently retains **30 HTML completions and a Boss pass at 100%**.

## P. Performance Findings

Measured 21 real curriculum executions: **154–465ms** per reported grader execution. Actual timeout including cleanup **3158ms** under a 3-second bound; OOM failure **526ms**; post-fault valid grading **155ms**. The confirmed resource leak was a correctness/isolation defect, not an optimization opportunity. No unrelated query or bundle optimization was made.

Production build completes in **771ms**. The lazy CodeMirror chunk remains **563.90kB**, gzip **193.56kB**, with the existing >500kB advisory. No new frontend dependency or eager editor import was introduced. These local timings are not production load-testing evidence.

## Q. Fixes Implemented

| File / symbol | Defect and root cause | Change / regression proof |
| --- | --- | --- |
| `grader/src/server.js::gradeInSandbox`, `removeContainer`, `createApp` | Client SIGKILL leaves a live adversarial container; cleanup failure could resume capacity. | Trusted UUID name, bounded explicit removal before settlement; nonzero exit/error remains unavailable; cleanup failure latches health/capacity 503. Real async-loop reproduction then strict/HTTP cleanup proof. |
| `grader/test/server.test.js` | Existing tests did not prove cleanup settlement, missing-container portability or cleanup-failure admission. | Five regressions. RED proved missing name and incorrect recovery; GREEN on Node 22/26. Portable Podman/Docker arguments and exact-not-found versus daemon-error behavior exercised through executable test transports. |
| `grader/test/integration-container.sh` | Old cleanup payload lacked required expectation and bypassed the HTTP service timeout path; an unexecuted visibility probe could pass. | Valid async-loop through trusted evaluator/service, container absence check with error propagation, visibility marker and live kernel limits. **24/0** strict real-rootless checks. |
| `tests/Feature/JournalSchemaAcceptanceTest.php` | Missing populated upgrade/rollback, FK/unique and journal insert-failure evidence. | Five behavioral lifecycle tests, 16-table history checksums, real trigger failure and real submission/replay transitions. SQLite/MariaDB **5/53** each. No production journal behavior rewritten. |

## R. Unslop Results

Reviewed this session's grader code, lifecycle tests and report. Removed one extra blank line from the Node tests. Kept the small runtime cleanup function and test seam because actual termination/portable cleanup requires them. No wrapper classes, speculative abstractions, UI badges, dependencies or configurable bypasses were added. Comments describe why an async loop exercises service cleanup and why kernel readings differ from configuration evidence.

All authentication, authorization, validation, journal uniqueness, locks, transactions, timeout/resource controls and accessibility behavior were preserved. All 53 non-report paths from the original pending work remain SHA256-identical. Targeted Node and strict container checks were rerun after this pass.

## S. Browser / E2E Results

| Actual application run | Exact result and scope |
| --- | --- |
| Fresh disposable four-role harness, port 8767 | **170 passed assertions, zero failed**, **40** screenshot/layout checks, zero page exceptions/HTTP500/console errors. Real login/navigation, Knowledge Check state, draft/preview/first completion, Teacher/Admin/Operator boundaries. Full course/Boss journey not enabled in this run. |
| Existing completed-history harness, port 8766 | First attempt failed with `ERR_CONNECTION_REFUSED` because server was stopped. After restarting only that disposable app: **164 passed assertions, zero failed**, **40** screenshot/layout checks, zero page exceptions/HTTP500/console errors. Existing completed history retained; this rerun does not claim another full course completion. |
| Real JS preview investigation | **45/45 condition assertions**, **4** responsive layouts. Four conditions deliberately confirm authored DOM defects; **5 expected browser exceptions** include starter plus correct DOM failures. Parent authority probes blocked. This is not a successful complete JS learning journey. |
| Focused fresh wrong/retry/correct/reload proof | **13/14 assertions passed**, zero page exceptions. Failed assertion expected unsaved submitted editor source after reload; existing contract restores explicitly saved drafts. No Save draft occurred. Retained as a failed probe expectation, not hidden or converted to a new academic defect. Completion/XP/journal SQL proof is recorded in O. |
| `/map` actual HTML experiment | Reproduced 404 and frame-ancestor refusal; zero page exceptions. Classification in L. |

JS-only preview investigation temporarily locked earlier courses through legitimate Admin forms on the disposable fresh database, then restored both statuses in `finally`. It did not fabricate Progress or XP. Keyboard/viewport checks are practical evidence; formal screen-reader/contrast/zoom certification was not performed.

## T. Fresh Database Results

Two newly created disposable MariaDB databases isolate acceptance from existing data: `codequest_release_journal_test` for tests and populated upgrade/rollback, `codequest_release_fresh_test` for complete migrations/seeding and real browser persistence. The existing `codequest_browser_test` retained earlier completed history. Configured `system404` received only metadata SELECTs through Boost.

DevelopmentDemoSeeder uses the existing six local users, normal role system, two independent classroom cohorts and assigned curriculum. Fresh users have no precompleted Progress/XP. Local password remains external to the seeder, not hardcoded into production. Migration, seeding, fresh submission and browser persistence succeeded; historical upgrade preservation succeeded on the actual MariaDB driver.

## U. Complete Verification Results

| Command / check | Fresh result |
| --- | --- |
| `CODEQUEST_REQUIRE_BROWSER_TESTS=1 CODEQUEST_CHROMIUM=... php artisan test --compact` | **1403 total, 1382 passed, 21 skipped, 0 failed, 8068 assertions, 54429ms**. Previous 1377/8015 increases by exactly five lifecycle tests/53 assertions. Existing driver-dependent skips remain; MariaDB target follows. `/tmp/codequest-release-full-laravel.log` |
| Previous MariaDB target set plus `JournalSchemaAcceptanceTest` | **79 passed, 0 skipped/failed, 381 assertions, 5808ms**. Previous 74/328 increases by five tests/53 assertions. `/tmp/codequest-release-mariadb-full-targeted.log` |
| Journal/replay/mutation SQLite target | **13 passed, 0 skipped/failed, 85 assertions, 1052ms**. Lifecycle alone **5/53**. |
| Journal lifecycle MariaDB target | **5 passed, 0 skipped/failed, 53 assertions, 1545ms**. `/tmp/codequest-release-journal-mariadb.log` |
| `node --test grader/test/*.test.js tests/*.test.js`, Node 26.10.0 | **56 passed, 0 failed/skipped/cancelled, 4037.34ms**. Five cleanup regressions added to 51 baseline. `/tmp/codequest-release-node26-final.log` |
| Same complete command, Node 22.23.3 | **56 passed, 0 failed/skipped/cancelled, 3698.44ms**. `/tmp/codequest-release-node22-final.log` |
| Strict actual rootless container suite | **24 passed, 0 failed**, exit0. Initial absent-runtime exit1 and incomplete-runtime6/14 failure were resolved, not counted as passes. `/tmp/codequest-release-strict-container-final-v2.log` |
| Authored JS definitions / actual container | **21 missions passed, 28 behavior checks**; no toy-only acceptance. `/tmp/codequest-release-js-behavior-results.json` |
| Actual HTTP timeout/OOM/recovery; PID burst | Error/timeout; error/unavailable; subsequent pass; **25 started/55 blocked**. No live grading containers. Temporary JSON evidence under `/tmp`. |
| `composer run analyse` | **0 errors**, exit0; frozen baseline preserved. `/tmp/codequest-release-phpstan.log` |
| `vendor/bin/pint --dirty --format agent` | Passed, exit0. `/tmp/codequest-release-pint.log` |
| `composer validate --strict` | Valid, exit0. |
| `composer audit --format=json` | **0 advisories**, exit0. |
| `npm audit --json` | **0 vulnerabilities** at every severity, exit0. Approved overrides unchanged. |
| `npm run build` | Passed,31 modules,771ms. Existing editor size advisory remains. No configured frontend lint command. |
| Browser / accessibility | Exact results and limitations in S; complete JS/Boss journey not accepted. |
| Syntax / diff | Node server syntax, shell syntax and `git diff --check` passed. No generated artifacts added to Git. |

## V. Remaining Issues

- **P1 release blocker:** four authored DOM exercises cannot execute correctly in the browser without curriculum-owned preview fixtures. Complete JavaScript Student/Boss/end-to-end progression acceptance remains incomplete. Console/function container success cannot waive this.
- **P1 deployment gate:** configured journal remains absent. Forward schema safety has disposable fresh/populated-upgrade evidence, but the intended deployment needs separately authorized exact DDL. Missing-schema submission refusal remains intact. No approval for configured writes was inferred.
- **P2 non-blocking:** authored `/map` resource missing. Correct frame-ancestor protection remains enabled.
- Live Docker deployment was not exercised; its cleanup argument behavior was regression-tested. Actual strict runtime acceptance here is rootless Podman.
- Prior hosted CI and formal accessibility/load-testing assurances are not manufactured by this local acceptance pass. Unsaved submission-source retention is not part of the current saved-draft contract.

## W. Final Git State

Branch `main`; HEAD `9a6cd6484d0767a8cb7f66d8a890afc2b977687f`. **58 pending paths:42 tracked modifications,16 untracked. No staged files, commits, pushes or PRs.** Session additions to the starting 54 paths are the three grader paths and the new lifecycle test. The existing report is appended. All other 53 starting paths are hash-identical. Runtime packages, credentials, logs, database data, probes and browser artifacts remain outside the repository under `/tmp`; no `.env`, dump, SQLite file, vendor/node_modules or build artifact entered the pending diff.

`git status --short`:

```text
 M .ai/rules/services.md
 M .ai/rules/tests.md
 M .github/workflows/laravel.yml
 M CODEQUEST_FULL_SYSTEM_AUDIT_AND_RECOMMENDATIONS.md
 M app/Http/Controllers/MissionController.php
 M app/Services/AnnouncementService.php
 M app/Services/AssessmentService.php
 M app/Services/AttentionService.php
 M app/Services/CourseAnalyticsService.php
 M app/Services/DraftService.php
 M app/Services/MissionService.php
 M app/Services/StudentService.php
 M app/Services/TeacherDashboardService.php
 M app/Services/XpService.php
 M config/database.php
 M database/factories/CourseFactory.php
 M database/factories/MissionFactory.php
 M database/factories/SectionFactory.php
 M database/seeders/DatabaseSeeder.php
 M grader/src/server.js
 M grader/test/integration-container.sh
 M grader/test/server.test.js
 M package-lock.json
 M package.json
 M resources/css/app.css
 M resources/js/knowledge-check.js
 M resources/views/assessments/show.blade.php
 M resources/views/challenge.blade.php
 M resources/views/course-analytics.blade.php
 M resources/views/knowledge-check.blade.php
 M resources/views/layouts/app.blade.php
 M tests/Feature/AcademicConcurrencyTest.php
 M tests/Feature/AttentionTest.php
 M tests/Feature/ChallengeAnalyticsTest.php
 M tests/Feature/CourseAnalyticsTest.php
 M tests/Feature/CurriculumVersioningTest.php
 M tests/Feature/EditorSourceRestorationTest.php
 M tests/Feature/MissionTest.php
 M tests/Feature/RoleNavigationTest.php
 M tests/Feature/XpLedgerTest.php
 M tests/TestCase.php
 M tests/Unit/XpServiceTest.php
?? app/Support/DisposableDatabase.php
?? database/migrations/2026_10_07_020341_create_the404_mission_submission_results_table.php
?? database/seeders/DevelopmentDemoSeeder.php
?? tests/Feature/AnnouncementMutationIntegrityTest.php
?? tests/Feature/AssessmentReadinessParityTest.php
?? tests/Feature/DatabaseClockTest.php
?? tests/Feature/DevelopmentDemoSeederTest.php
?? tests/Feature/FactoryCapacityTest.php
?? tests/Feature/JournalSchemaAcceptanceTest.php
?? tests/Feature/KnowledgeCheckMonitoringTest.php
?? tests/Feature/MissionAssistanceIntegrityTest.php
?? tests/Feature/MissionMutationIntegrityTest.php
?? tests/Feature/MissionSubmissionReplayTest.php
?? tests/Feature/ScopedAttentionTest.php
?? tests/Unit/DisposableDatabaseTest.php
?? tests/codequest-browser.mjs
```

## X. Independent Reviewer Findings

Cavecrew reviewer inspected the full tracked production diff, new migration/test, grader/runtime changes and actual evidence independently of builders. Initial unavailable reviewers were replaced; their failed turns were never treated as approval. No introduced authorization/journal/XP P0/P1 defect was demonstrated. The grader cleanup fix is accepted; release acceptance is withheld.

| # | Required review question | Independent finding |
| --- | --- | --- |
| 1 | Fresh journal migration safe? | Yes on disposable MariaDB migrations/seeding/lifecycle evidence. |
| 2 | Populated upgrade safe? | Yes for the representative pre-journal fixture; configured rollout remains unapplied. |
| 3 | Historical facts preserved? | Counts/full-row checksums unchanged across 16 tables. |
| 4 | Journal constraints correct? | Compatible unsigned IDs; scoped operation uniqueness; restrictive parent FKs, rejection tests passed. |
| 5 | Journal idempotent? | Locked user plus unique operation; identical failed UUID replays once. |
| 6 | Concurrent submissions safe? | Independent-worker MariaDB races passed. |
| 7 | Infrastructure failure closed? | Timeout/OOM/nonzero/malformed outcomes remain unavailable, never completion. |
| 8 | Application secrets reachable? | No application mounts/environment forwarded; executed host-visibility proof passed. |
| 9 | Database unexpectedly reachable? | No network, socket or credentials passed. |
| 10 | Filesystem boundaries enforced? | Live read-only root/noexec bounded tmpfs and absent host paths passed. |
| 11 | Network restricted? | Live egress denial passed. |
| 12 | Resource limits enforced? | Kernel CPU/memory/PID readings, real fork burst, OOM and async timeout/removal passed. |
| 13 | JavaScript functional? |21 behavioral solutions passed;4 authored DOM previews fail; complete journey unaccepted. |
| 14 | Preview gains parent authority? | Actual DOM/cookie/storage/CSRF/navigation/popup probes blocked. |
| 15 | Preview authoritative? | No; local srcdoc only, confirmed zero academic writes from probes. |
| 16 | Student identity spoofable? | Authenticated server identity controls mutation; existing isolation tests passed. |
| 17 | Mission progression bypass? | Controller reach/KC gates and fresh active-course mutation check preserved. |
| 18 | Premature Boss callable? | Authoritative ownership/eligibility inside transaction preserved. |
| 19 | Duplicate XP? | Serialized completion/first-pass/replay guards; actual retries and MariaDB races passed. |
| 20 | Duplicate Progress? | Unique/serialized completion; actual repeated submission creates one row. |
| 21 | Retries rewrite historical attempts? | New Boss attempts/snapshots preserved; failure replay returns stored fact. Rollback intentionally loses replay cache. |
| 22 | Boss transition atomic? | Existing evaluation/verdict/XP/achievement/notification transaction preserved; separate from mission journal. |
| 23 | `/map` release relevance? |404: NON-BLOCKING DEFECT; framing refusal: EXPECTED DIAGNOSTIC. |
| 24 | Regression elsewhere? | None demonstrated by fresh full regression; DOM/full JS acceptance remains outstanding. |
| 25 | Unslop removed safeguards? | No authorization/transaction/validation/isolation safeguard removed. |

## Y. Release Verdict

Journal forward-schema acceptance and strict rootless grading now have fresh evidence. The timeout container leak is fixed and verified. Release acceptance still requires working authored DOM previews, the complete JavaScript journey and approved journal deployment. Passing regression counts cannot replace these requirements.

The highest-value next action is to add curriculum-owned DOM preview fixtures and verify the complete JavaScript Student/Boss journey without granting parent or academic authority.

REJECT


# JavaScript and DOM release acceptance continuation — 2026-10-07

## Implementation plan

Baseline: main, 9a6cd6484d0767a8cb7f66d8a890afc2b977687f; 58 pending paths retained. Only remaining DOM/JavaScript/Boss evidence and configured journal deployment procedure are in scope.

1. Reproduce missing DOM at the authored curriculum/preview boundary. Add starter-owned HTML comments for eight DOM exercises and explicit text-preview marker for the two-file module lesson. Publish through existing curriculum seeding, version changed mission content once, preserve historical academic records. Test seeded ownership/idempotence and trusted starter propagation.
2. Extend previewDocument to read setup only from the separately supplied original starter. Maintain script encoding and sandbox grants. Verify corrected DOM and events in actual Chromium.
3. Correct Boss editor/preview document handling to match existing HTML/style/script rubric. Preserve evaluation and historical attempts; regression-test document execution under the existing course-specific sandbox.
4. Exercise legitimate CSS prerequisites followed by all 35 JavaScript missions, configured Knowledge Checks, invalid/correct submissions, replay, Boss eligibility/pass and Teacher reporting. Reconcile disposable MariaDB facts with browser evidence.
5. Rehearse exact journal deployment procedure on disposable MariaDB; do not migrate the configured database. Run targeted/full verification, Unslop and independent standards/spec/security review. Append A–O findings and one evidence-supported verdict.


## A. Continuation baseline and interruption

Started 2026-10-07 on `main`, HEAD `9a6cd6484d0767a8cb7f66d8a890afc2b977687f`, with 58 pending paths. Resumed 2026-10-08 with 64 pending paths and unchanged HEAD. Prior work was preserved; no commit, push, PR or configured-database write occurred. Temporary runtime packages, isolated databases, request captures and browser artifacts under `/tmp` disappeared across the environment restart. This interruption does not prove that a background browser journey finished. Its complete JavaScript/Boss acceptance remains unverified.

Skills applied: Caveman/Cavecrew investigation and builder delegation, caveman-explore, investigate-first, diagnosing-bugs, TDD, writing-plans, Laravel/testing practices, Unslop, caveman-review/code-review and verify-and-stop. Dedicated `reviewing-security` was unavailable; installed Laravel security guidance and independent security review were used. Chromium/Playwright were exercised. Current Chromium survives in the user's browser cache; the disposable Podman, MariaDB and Node22 runtimes do not.

## B. DOM failure root causes

All four reported failures originated in authored JavaScript expecting elements absent from `previewDocument`:

| Mission | Required initial DOM | Earlier observed error |
| --- | --- | --- |
| DOM_Node_Activation | `#status`, initially OFFLINE | Null element textContent assignment |
| Event_Listener_Rewire | `#emergencyBtn` | Null element addEventListener |
| Build_Nodes | Empty `#bands` list | Null appendChild target |
| Form_Guard | `#signup` form and `#callsign` input | Null element addEventListener |

The same investigation found missing setup for Query_All, Event_Bubble, Input_Events and Form_Data. Query_All silently selected no lamps. Module_Syntax promised a text preview for a two-file exercise but was previously executed as one classic script. Boss grading expects an HTML document with embedded styles/scripts, while the editor previously treated the complete document as CSS or JavaScript source.

## C. Curriculum-owned fixture changes

`database/seeders/CurriculumContentSeeder.php` now authors eight explicit `/* Preview HTML: … */` blocks directly in the existing persisted `broken_code` definitions. Module_Syntax has an explicit source-only marker. No table, dependency, grading rule, reference solution or second fixture authority was introduced.

`resources/js/preview.js::previewDocument(source, type, starter)` reads setup only from the original starter supplied separately by the application. `challenge.blade.php` serializes the authoritative starter with `Js::from`; `experiment.blade.php` uses its original initial source. Edited source cannot supply a replacement setup block. Module source is escaped and displayed as text.

The seeder explicitly applies the existing semantic material-field comparison when publishing Mission changes because `WithoutModelEvents` suppresses the model version hook. Changed material increments once; identical reruns do not increment. Historical Progress is preserved. Network/storage lesson copy now explains the sandbox restrictions instead of promising access to application APIs or native storage.

Regression: `CurriculumPreviewFixtureTest` covers persisted setup, hidden solutions/rules, publication/version stability, historical Progress and actual workspace starter propagation. `tests/preview.test.js` covers trusted setup selection, edited-source isolation and source-only modules.

## D. DOM preview acceptance

Current Node preview tests pass, and authored fixture/HTTP regression tests are included in the full Laravel suite. Complete real-application Chromium acceptance of all four corrected DOM cases was not captured before the environment interruption. The prepared browser harness exercises all eight authored DOM interactions through the real editor and sandboxed frame; it is not counted as passing merely because it exists.

Sandbox grants remain unchanged: JavaScript `allow-scripts` only, HTML/CSS empty sandbox. No same-origin, forms, popup, download or parent-navigation capability was added. RUN has no academic mutation path. Grader configuration and isolation files were unchanged by this continuation.

## E. JavaScript Student journey

Before interruption, actual UI submissions completed the 30 CSS prerequisite missions and CSS Boss. The first JavaScript mission completed after request normalization left a valid authored solution. A subsequent browser run exercised the second JavaScript mission's negative cases, correct submission, reload and replay. Those are partial journey facts, not proof of all 35 missions or final eligibility. The last prepared resume was running; its final evidence and disposable database cannot now be inspected.

`tests/codequest-javascript-browser.mjs` uses solutions exported read-only from seeded authoritative curriculum. It runs CSS prerequisites, JavaScript lessons/experiments, configured Knowledge Checks, challenges and Boss through normal authenticated UI. It does not mutate iframe DOM to fabricate passing fixtures or insert fabricated academic completions.

## F. JavaScript failure cases

The interrupted browser run reached syntax error, runtime exception, extra incorrect output, embedded malformed source, infinite-loop submission and long-running-timer submission. Before accepting the next mission, the harness checked no completion, visible feedback and bounded submission response. The complete post-restart rerun remains outstanding.

Two initial failures were test-fixture errors and were corrected without weakening application validation:

- A trailing NUL was trimmed by Laravel, leaving a valid program. The accepted academic completion was preserved. The negative test now embeds NUL inside source.
- Browser forms use CRLF; the initial API replay sent LF. Exact-source hashing correctly rejected the reused operation ID. Replay now uses captured browser request bytes.

**P2 limitation:** independent Chromium review observed an infinite preview script occupying its child frame. The parent remained responsive; ordinary lesson navigation recovered. Replacing srcdoc did not recover within 2.5 seconds. Preview execution itself is not bounded. Backend timeout proofs must not be presented as preview timeout proofs.

DOM exercises retain their existing server-owned structural grading; behavioral JavaScript missions retain container grading. Module text display and expected sandbox network/storage failures are not counted as successful runtime execution of those APIs.

## G. Boss Challenge journey

`resources/views/assessments/show.blade.php` now uses the HTML editor/document preview matching the existing Boss rubric. The frame still derives script permission from authoritative course type. Source restoration regressions now use actual document-form CSS/JavaScript examples and prove initial/edited source reaches preview unchanged. The grading rubric, eligibility, attempts, XP and first-pass logic were not changed.

The full JavaScript invalid-attempt → passing-attempt → course-completion → reload/replay journey remains unverified. The prepared harness contains these checks; no passing result is inferred from code inspection or earlier HTML/CSS Boss evidence.

## H. Authoritative persistence evidence

Before the runtime loss, disposable MariaDB target verification passed 118 tests/748 assertions, including journal lifecycle, fixture publication, academic concurrency, mission replay/mutation, Boss/sequence, Knowledge Check and XP ledger tests. A separate journal rehearsal passed 5 tests/53 assertions. These are historical continuation results; the databases/logs were temporary and are unavailable after restart.

Successful missions use Progress and XP transactions; the mission journal records cacheable failures and their replay identity. A successful mission is not falsely described as requiring a success journal row. Boss evidence lives in AssessmentAttempt snapshots and existing progression services. Full JavaScript/Boss database reconciliation remains outstanding.

## I. Replay and idempotency

The browser exercised exact-byte failed replay and completed-mission replay before interruption. Rapid requests exposed a transient same-session flash race: a flash message is not guaranteed to appear in both responses. The assertion now checks the persistent completed header, with database XP/Progress reconciliation still required. No application guard was removed.

Existing MariaDB worker-based concurrency tests passed before restart. The single-threaded disposable web server's rapid browser requests are not represented as an independent proof of simultaneous first-completion transactions.

## J. Browser results

Complete acceptance: **unverified**. Initial partial run stopped at the trailing-NUL expectation; subsequent partial runs stopped at replay-byte and transient-flash expectations. Correcting those tests does not retrospectively turn their runs into complete passing journeys. The final resume's result was lost/unconfirmed during the environment restart.

Current full Laravel verification includes its two required Chromium workspace tests. Those passing controls tests do not replace authenticated full-course, DOM interaction, Boss and database persistence acceptance.

## K. Regression results

Fresh verification after the 2026-10-08 restart:

| Check | Actual result |
| --- | --- |
| Full Laravel with required Chromium, outside socket-restricted sandbox | **1406 total: 1385 passed, 21 skipped, 0 failed, 8128 assertions, 53682ms** |
| Initial sandboxed full Laravel attempt | 1383 passed, 21 skipped, 2 local-server-binding failures; permission-corrected full rerun above passed |
| Node26 grader/frontend | **58 passed, 0 failed, 0 skipped** |
| PHPStan | **0 errors** |
| Pint dirty files | Passed |
| Vite production build | Passed,31 modules,1.25s; existing large-editor chunk advisory remains |
| Composer audit | 0 advisories |
| npm audit | 0 vulnerabilities |
| git diff --check | Passed |
| Strict container | **Unavailable; strict command fails because no Podman/Docker binary exists** |
| Node22/MariaDB current rerun | Unavailable; previous disposable runtime directories disappeared |
| Complete JS/Boss browser | Unverified |

Earlier continuation, before restart: full Laravel1385/21 skipped/8128 assertions; MariaDB118/748 assertions; journal rehearsal5/53; Node22/26 each58; strict rootless container24. Current runtime unavailability is not reported as a fresh acceptance pass.

Unslop inspected changed code/copy. Removed one extra blank line in the preview test. No authorization, transaction, validation, sandbox or concurrency safeguard was removed. No speculative runtime shim, fake fetch response or preview-only production branch was added.

## L. Configured journal deployment readiness

**CONFIGURED JOURNAL DEPLOYMENT READY: NO.** Existing migration safety was accepted earlier, but this continuation's complete configured rollout procedure has not been rehearsed after fixture changes/runtime loss. No configured write was made.

Pre-interruption read-only Boost metadata confirmed the journal table absent, existing migration repository and parent tables using InnoDB, and all four reported configured missions lacking the new setup blocks. Therefore journal DDL alone is insufficient: this release also needs controlled publication of the authored preview setup. Do not blindly rerun the whole curriculum seeder against Admin-edited real content; it upserts more fields than this release requires.

Required deployment acceptance:

1. Restore the disposable production-equivalent runtime and rehearse the existing exact journal migration plus reviewed fixture publication. The migration is `database/migrations/2026_10_07_020341_create_the404_mission_submission_results_table.php`. Verify unsigned parent identifiers, InnoDB, table absence, FK compatibility and replay uniqueness before deployment.
2. Take and restore-test an approved database backup. Preserve historical Progress, XP, attempts and Knowledge Check snapshots. Measure metadata-lock/maintenance time in the rehearsal; zero downtime has not been established.
3. Obtain explicit approval for configured DDL and separately enumerated curriculum data changes. Production safety rules prohibit running the broad migration tree. Review exact generated journal DDL; publish only reviewed starter/setup changes with identity/version/source-hash preconditions and one material-version increment.
4. Post-deployment verify journal columns/index/FKs, unchanged academic history, expected fixture content/version and duplicate replay behavior. Exercise an authorized real submission and the complete JavaScript/Boss journey.
5. Prefer forward fixes after journal entries exist. Dropping the journal loses replay history; it is not a harmless production rollback. Schema readiness is checked on each submission, so no schema-readiness cache needs clearing. Restart long-lived application workers as required by the deployed code; restore normal service only after health checks.

Safe disposable rehearsal command after restoring an explicitly approved `_test` connection:

```sh
CODEQUEST_ALLOW_DISPOSABLE_DATABASE=1 DB_CONNECTION=mariadb DB_HOST=127.0.0.1 DB_PORT=33309 DB_SOCKET='' DB_DATABASE=codequest_dom_rollout_test DB_USERNAME=root DB_PASSWORD='' DB_URL='' php artisan test --compact tests/Feature/JournalSchemaAcceptanceTest.php tests/Feature/CurriculumPreviewFixtureTest.php
```

This command is for a newly created disposable database, not the configured database. No configured migration/DDL command was executed or granted approval by this report.

## M. Remaining issues

- Release-critical: actual acceptance of four corrected DOM previews and complete JavaScript Student/Boss/persistence journey remains unverified after interruption.
- Release-critical: configured journal rollout and controlled curriculum publication remain unauthorized/unperformed; deployment readiness is not yet conclusively accepted.
- Environment blocker: disposable grader/container, MariaDB and Node22 runtime/evidence must be restored. A non-container evaluator is not an acceptable substitute.
- P2: infinite preview frame requires navigation recovery; execution is unbounded. Existing `/map` 404 remains non-blocking, framing refusal remains expected.

## N. Git state

`main`, HEAD `9a6cd6484d0767a8cb7f66d8a890afc2b977687f`; **64 pending paths**, none staged. Starting58 paths preserved. Six added pending paths are CurriculumContentSeeder.php, resources/js/preview.js, experiment.blade.php, tests/preview.test.js, CurriculumPreviewFixtureTest.php and codequest-javascript-browser.mjs. Existing report/challenge/Boss/source-restoration paths contain the continuation changes. No configured database credentials, dumps, runtime packages, browser artifacts or generated build files were added to Git. No commit/push.

## O. Independent review

Earlier independent standards/security review found no new P0/P1 in fixture trust, escaping, version handling or sandbox authority. It independently ran25 fixture/source-restoration tests/200 assertions and8 preview Node tests. It identified the preview-loop limitation and explicitly withheld full-course/Boss/persistence acceptance. Final post-restart independent review inspected the diff, reran25 fixture/source-restoration tests with200 assertions, confirmed the full-suite log and independently withheld acceptance. Builder assertions were not substituted for missing browser/database evidence.

Release acceptance remains withheld. Code changes and broad green regressions do not establish the remaining end-to-end boundaries.


| # | Independent acceptance question | Result |
| --- | --- | --- |
| 1 | All four corrected previews work in real application? | Implementation supports them; retained complete Chromium proof unavailable. |
| 2 | Fixtures curriculum-owned? | Yes: eight setups in authoritative seeded broken_code, no second runtime authority. |
| 3 | Real JS executes correctly? | Encoded script path preserved; complete current browser proof missing. |
| 4 | Sandbox preserved? | Yes: JavaScript allow-scripts only, no same-origin/navigation grants. |
| 5 | Preview changes academic state? | No mutation path found; srcdoc assignment remains separate from grading. |
| 6 | Invalid JS safe? | Authoritative grader unchanged; complete failure-case acceptance unverified. Preview execution itself is unbounded. |
| 7 | Valid JS completes once? | Existing locks/completed guards preserved; new full persistence reconciliation missing. |
| 8 | Complete JS course proven? | No. Harness implementation is not a completed journey. |
| 9 | Boss eligibility correct? | Authoritative prerequisite logic preserved; JS end-to-end proof missing. |
| 10 | JS Boss pass/course completion proven? | No completed current journey evidence. |
| 11 | Persistence correct? | Historical Progress preservation tested; full JS/Boss reconciliation unavailable. |
| 12 | Replay/idempotency accepted? | Existing protections preserved; complete newly exercised JS/Boss acceptance unverified. |
| 13 | Configured deployment procedurally ready? | Not conclusively verified; schema and controlled curriculum publication remain unapplied. |
| 14 | Grader isolation weakened? | No grader boundary changes from DOM work; current runtime unavailable. |
| 15 | New P0/P1 introduced? | None demonstrated by independent diff review/fresh tests; missing release evidence still blocks acceptance. |

Final `git status --short`:

```text
 M .ai/rules/services.md
 M .ai/rules/tests.md
 M .github/workflows/laravel.yml
 M CODEQUEST_FULL_SYSTEM_AUDIT_AND_RECOMMENDATIONS.md
 M app/Http/Controllers/MissionController.php
 M app/Services/AnnouncementService.php
 M app/Services/AssessmentService.php
 M app/Services/AttentionService.php
 M app/Services/CourseAnalyticsService.php
 M app/Services/DraftService.php
 M app/Services/MissionService.php
 M app/Services/StudentService.php
 M app/Services/TeacherDashboardService.php
 M app/Services/XpService.php
 M config/database.php
 M database/factories/CourseFactory.php
 M database/factories/MissionFactory.php
 M database/factories/SectionFactory.php
 M database/seeders/CurriculumContentSeeder.php
 M database/seeders/DatabaseSeeder.php
 M grader/src/server.js
 M grader/test/integration-container.sh
 M grader/test/server.test.js
 M package-lock.json
 M package.json
 M resources/css/app.css
 M resources/js/knowledge-check.js
 M resources/js/preview.js
 M resources/views/assessments/show.blade.php
 M resources/views/challenge.blade.php
 M resources/views/course-analytics.blade.php
 M resources/views/experiment.blade.php
 M resources/views/knowledge-check.blade.php
 M resources/views/layouts/app.blade.php
 M tests/Feature/AcademicConcurrencyTest.php
 M tests/Feature/AttentionTest.php
 M tests/Feature/ChallengeAnalyticsTest.php
 M tests/Feature/CourseAnalyticsTest.php
 M tests/Feature/CurriculumVersioningTest.php
 M tests/Feature/EditorSourceRestorationTest.php
 M tests/Feature/MissionTest.php
 M tests/Feature/RoleNavigationTest.php
 M tests/Feature/XpLedgerTest.php
 M tests/TestCase.php
 M tests/Unit/XpServiceTest.php
 M tests/preview.test.js
?? app/Support/DisposableDatabase.php
?? database/migrations/2026_10_07_020341_create_the404_mission_submission_results_table.php
?? database/seeders/DevelopmentDemoSeeder.php
?? tests/Feature/AnnouncementMutationIntegrityTest.php
?? tests/Feature/AssessmentReadinessParityTest.php
?? tests/Feature/CurriculumPreviewFixtureTest.php
?? tests/Feature/DatabaseClockTest.php
?? tests/Feature/DevelopmentDemoSeederTest.php
?? tests/Feature/FactoryCapacityTest.php
?? tests/Feature/JournalSchemaAcceptanceTest.php
?? tests/Feature/KnowledgeCheckMonitoringTest.php
?? tests/Feature/MissionAssistanceIntegrityTest.php
?? tests/Feature/MissionMutationIntegrityTest.php
?? tests/Feature/MissionSubmissionReplayTest.php
?? tests/Feature/ScopedAttentionTest.php
?? tests/Unit/DisposableDatabaseTest.php
?? tests/codequest-browser.mjs
?? tests/codequest-javascript-browser.mjs
```

**BLOCKED:** the disposable strict grader/container environment was lost during restart. **Needed:** restore the rootless runtime, runner image, isolated MariaDB and Node22 tooling; then rerun authored DOM acceptance, full JavaScript/Boss journeys, persistence/replay reconciliation and deployment rehearsal. No configured write is authorized. The safe next action is to restore that disposable acceptance environment, without replacing isolated grading with host execution.

REJECT


## Restored strict release acceptance — 2026-10-08

### A. Restored acceptance environment

Baseline: `main`, HEAD `9a6cd6484d0767a8cb7f66d8a890afc2b977687f`, 64 pending paths. All 64 remain present. No reset, stash, cleanup, commit, push or configured-database write occurred. Final pending count is 69, including two new restore scripts and three newly modified existing files.

Applied Caveman/Cavecrew, investigate-first, diagnosis, TDD, Laravel/testing conventions, Unslop, Chromium/Playwright, two-axis code-review and verify-and-stop. Separate investigators/builders/reviewers handled runtime, deployment rehearsal, preview import ordering, regression, Standards and Spec. Dedicated security-review skill unavailable; Laravel skill security guidance and independent boundary review supplied the equivalent. Root's current tool catalog exposes no Boost `record-rule`; the narrowly justified form-permission exception is documented here rather than pretending that tool ran.

New repository-controlled reconstruction entry points:

- `tests/acceptance-environment.sh`: private MariaDB bootstrap, migrations, curriculum and disposable demo seeders. Rejects traversal, foreign ownership, fixed-path symlinks and cached Laravel configuration; verifies connected server port and datadir before writes. Creates only `codequest_acceptance_test` and `codequest_acceptance_regression_test` in its private server.
- `grader/test/restore-runtime.sh`: existing native rootless Podman reuse, or checksum-verified pinned Arch-compatible x86_64 fallback. Private `/tmp/codequest-*` storage, no host installation or application dependency change. Rejects escaping paths/symlinks. Unsupported platforms fail explicitly.

Actual runtime: MariaDB on loopback port 33309 and `/tmp/codequest-acceptance-1000/database.sock`; application port 8768; rootless grader port 4318; private runner image built from repository Dockerfile. Official Node22 `v22.23.3`, host Node26 `v26.10.0`. Chromium and Playwright came from existing installed caches; no new browser dependency was added.

Reconstruction commands, from repository root, with password/token supplied externally:

```sh
export CODEQUEST_DEMO_PASSWORD='<local disposable password>'
export GRADER_TOKEN='<ephemeral disposable token>'
sh tests/acceptance-environment.sh
export CODEQUEST_RUNTIME_DIR="/tmp/codequest-rootless-$(id -u)"
sh grader/test/restore-runtime.sh
systemd-run --user --scope --quiet --property='Delegate=cpu memory pids' \
  env PATH="$CODEQUEST_RUNTIME_DIR/bin:$PATH" CODEQUEST_REQUIRE_SANDBOX_TESTS=1 \
  sh grader/test/integration-container.sh
systemd-run --user --scope --quiet --property='Delegate=cpu memory pids' \
  env PATH="$CODEQUEST_RUNTIME_DIR/bin:$PATH" GRADER_HOST=127.0.0.1 GRADER_PORT=4318 \
  GRADER_TOKEN="$GRADER_TOKEN" GRADER_RUNTIME=podman GRADER_EXEC_TIMEOUT_MS=10000 \
  node grader/src/server.js
```

Run grader in its own terminal. In another terminal:

```sh
. "/tmp/codequest-acceptance-$(id -u)/database.env"
export CODEQUEST_GRADER_TOKEN="$GRADER_TOKEN"
cd public
php -S 127.0.0.1:8768 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

Set installed executable/module paths externally through `CODEQUEST_CHROMIUM` and `CODEQUEST_PLAYWRIGHT_PATH`. Browser fixtures must be exported read-only from this seeded disposable database, not invented or installed through browser-only mutations. The CSS/JS export used by `tests/codequest-javascript-browser.mjs` is reproducible:

```sh
mariadb --no-defaults --socket="/tmp/codequest-acceptance-$(id -u)/database.sock" \
  --user=root --database=codequest_acceptance_test --batch --raw --skip-column-names \
  -e "SELECT JSON_OBJECT('id',m.id,'title',m.title,'solution',m.solution_code,'points',m.points,'course_id',c.id,'course',c.name,'type',c.type) FROM the404_missions m JOIN the404_courses c ON c.id=m.course_id LEFT JOIN the404_sections s ON s.id=m.section_id WHERE c.type IN ('css','js') ORDER BY c.order_num,s.order_num,m.order_num,m.id" \
  > /tmp/codequest-acceptance-curriculum.jsonl
```

The HTML fixture and browser environment can be reconstructed without browser-only data mutation:

```sh
mariadb --no-defaults --socket="/tmp/codequest-acceptance-$(id -u)/database.sock" \
  --user=root --database=codequest_acceptance_test --batch --raw --skip-column-names \
  -e "SELECT JSON_OBJECT('course',c.name,'missions',JSON_OBJECTAGG(m.title,m.solution_code)) FROM the404_missions m JOIN the404_courses c ON c.id=m.course_id WHERE c.type='html' GROUP BY c.id,c.name" \
  > /tmp/codequest-acceptance-html.json
export CODEQUEST_BROWSER_BASE_URL=http://127.0.0.1:8768
export CODEQUEST_BROWSER_DATABASE_SOCKET="/tmp/codequest-acceptance-$(id -u)/database.sock"
export CODEQUEST_BROWSER_DATABASE=codequest_acceptance_test
export CODEQUEST_BROWSER_HTML_FIXTURE=/tmp/codequest-acceptance-html.json
export CODEQUEST_BROWSER_JAVASCRIPT_FIXTURE=/tmp/codequest-acceptance-curriculum.jsonl
export CODEQUEST_BROWSER_ARTIFACTS=/tmp/codequest-acceptance-html-browser
node tests/codequest-browser.mjs
export CODEQUEST_BROWSER_ARTIFACTS=/tmp/codequest-acceptance-js-browser
node tests/codequest-javascript-browser.mjs
export CODEQUEST_BROWSER_ARTIFACTS=/tmp/codequest-acceptance-dom-browser
CODEQUEST_BROWSER_DOM_ONLY=1 node tests/codequest-javascript-browser.mjs
```

Browser environment: loopback application URL, external demo password, that fixture path, explicit private socket and `_test` database, installed Chromium/Playwright paths, and `/tmp` artifacts. Run `node tests/codequest-browser.mjs` for the existing HTML prerequisite journey with its HTML solution fixture; then `node tests/codequest-javascript-browser.mjs`. `CODEQUEST_BROWSER_DOM_ONLY=1` repeats the eight authoritative lessons without grading. Interrupted browser runs resumed against the same preserved history; no academic rows were fabricated to skip prerequisites.

### B. Eight DOM fixture results

Final post-build Chromium repeat: **77 assertions passed, zero failures**, all eight fixtures, zero page exceptions and zero valid-fixture console errors. Each preview left academic records identical.

| Curriculum fixture | Expected and actual behavior | Result |
| --- | --- | --- |
| DOM_Node_Activation | Authored status changes OFFLINE → SIGNAL ACTIVE | PASS |
| Event_Listener_Rewire | Real button click logs EMERGENCY PROTOCOL ACTIVATED | PASS |
| Query_All | All three authored lamps gain `on` | PASS |
| Build_Nodes | Authored list receives UHF node | PASS |
| Form_Guard | Native submit logs TOO SHORT for AB, ACCEPTED for CHEN | PASS |
| Event_Bubble | Clicking authored list item logs its actual UHF text | PASS |
| Input_Events | Input/change events report CHEN and saved | PASS |
| Form_Data | Native form event reads CHEN through FormData | PASS |

The eight setups remain curriculum-owned `broken_code` metadata from `CurriculumContentSeeder`; original starter, not editable submitted source, supplies their markup. Module_Syntax remains explicitly source-only because its exercise uses separate module files. Hidden rules and solutions are not exposed by student responses.

### C. JavaScript journey

A genuinely fresh database started with zero progress. Real HTML browser journey passed **275 assertions**, completing all 30 HTML missions and its Boss, unlocking CSS. The JavaScript harness completed all 30 CSS missions and CSS Boss, then all 35 JavaScript missions, lesson/experiment and configured Knowledge Checks, invalid/valid submissions, previews, reloads, XP/history pages and Teacher reporting.

This was an **interrupted and resumed journey**, not one uninterrupted green execution. Original run passed 481 checks before an empty-body visibility wait failed; next passed 223 before native form preview failed; bounded continuation passed 72 before the output import race hid an event target. Those defects were corrected without deleting history. Final remaining-mission/Boss run passed **183/183 checks, zero failures**, with zero page/server exceptions. Independent database reconciliation proves all 35 JavaScript completions. The separate final eight-fixture repeat proves current preview behavior after the complete patch.

### D. JavaScript failure cases

Real first JavaScript workspace, trusted curriculum solution modified to introduce each failure:

| Case | Response time | Authoritative outcome |
| --- | --- | --- |
| Syntax error | 3,873 ms | Failed behavioral result; no Progress/completion reward |
| Runtime exception | 3,723 ms | Failed behavioral result; no Progress/completion reward |
| Missing expected behavior / extra output | 5,125 ms | Hidden check failed; no completion |
| Embedded malformed NUL | 1,491 ms | Rejected; no completion |
| Infinite loop | 11,328 ms | Grading unavailable; no XP change or successful state |
| Long-running timer | 2,108 ms | Failed behavioral result; no completion |

Feedback was visible, execution bounded below the harness's 25-second limit, balances remained nonnegative. Wrong DOM source showed WRONG in preview and was rejected on submit. Syntax replay reused the failure; timeout retry remained fail-closed. These are submission timeouts. They do not claim that arbitrary infinite browser preview scripts are independently time-bounded.

### E. Boss Challenge journey

Boss sealed while required JavaScript missions remained, available after all 35 completed. Real editor initialized; document preview toggled the signal to ONLINE and reset ticks to 0 without granting a pass or changing academic state. Invalid submission persisted a failed 0% attempt, including after reload. Correct source passed at 100%, cleared course, persisted after reload. Replayed completed request left academic records unchanged. Deliberate practice created a distinct passing historical attempt and awarded no additional XP.

Exactly one assessment exists per course. JavaScript is the final authored course, so there is no next course to unlock; HTML→CSS and CSS→JavaScript unlocks were exercised. Boss evaluation uses the existing trusted structural rubric, not the mission container's behavioral JavaScript evaluator.

### F. Authoritative persistence evidence

Independent read-only reconciliation of the disposable database:

- Progress: **95 rows / 95 distinct missions**; HTML30, CSS30, JavaScript35.
- Mission rewards: **95 transactions, 8,020 XP**; no duplicate reward groups. Course mission totals: HTML2,440, CSS2,550, JS3,030.
- Boss rewards: **3 transactions ×100 XP**, one per assessment.
- Wrong penalties: **6 transactions /−60 XP**. Ledger: **104 rows, balance8,260**.
- Failure journal: **6 unique operation UUIDs**, five for mission61 and one for mission68. Successful submissions are recorded through Progress, XP and Activity; this journal intentionally caches failed results, not successful facts.
- Activity: **95 mission_completed**, **6 wrong_submission**, **10 Knowledge Check completion**. Two timeout infrastructure events did not fabricate journal outcomes or award XP.
- Knowledge Checks: **10 submitted**, with submitted timestamps.
- Boss attempts: **5 total**. JS retained original failed0% code, first pass100%, deliberate practice100%; the two passing attempts have distinct historical identity and the same correct source hash. One JS reward.
- Achievements: **3 distinct awards**.
- Frozen competency evidence: 30 mission snapshots have nonempty skill keys; other mission snapshots are legitimately empty under existing mapping. JavaScript Boss snapshots contain two frozen skill keys. There is no separate course-completion table: authoritative historical passing assessment determines completion.

Bootstrap rerun after acceptance preserved exactly95 Progress,104 ledger rows/balance8,260,6 journal rows and5 attempts.

### G. Replay / idempotency

UUID replay, rapid duplicate completed submission, syntax replay, retry after grader timeout, Boss pass replay, reload and deliberate practice were exercised. Successful completion/reward remained unique; failed attempts remained historical facts.

Browser rapid posts target an already completed mission and the acceptance PHP server is single-threaded. This is evidence of replay safety, not an independent simultaneous-first-completion race proof. Shared authenticated-user locking/constraints and the MariaDB concurrency regression provide that separate transaction evidence. No client identity, preview result or submitted score became academic authority.

### H. Regression results

| Verification | Current result |
| --- | --- |
| Full Laravel, actual Chromium fixtures required | **1,386 passed;21 skipped;8,304 assertions;0 failed**,66.747s |
| MariaDB selected integrity/progression/journal suites | **119 passed;920 assertions;0 failed**,13.803s |
| Journal/Admin publication rehearsal | **9 passed;285 assertions;0 failed** |
| Experiment targeted regression | **6 passed;59 assertions;0 failed** |
| Node22 v22.23.3 | **59 passed;0 failed;0 skipped** |
| Node26 v26.10.0, `--test-isolation=none` | **59 passed;0 failed;0 skipped** |
| Strict rootless container checks | **24 passed;0 failed**, final rerun |
| Final eight authored DOM previews | **77 passed;0 failed** |
| Final JS/Boss continuation | **183 passed;0 failed** |
| HTML prerequisite browser journey | **275 passed;0 failed** |
| PHPStan | Passed,0 errors |
| Pint, `--dirty --format agent` | Passed |
| Vite production build | Passed; existing large editor chunk warning remains |
| Composer audit | 0 advisories;0 abandoned packages |
| npm audit | 0 vulnerabilities |
| Shell syntax / Git diff check | Passed |

Initial sandbox runs could not bind fixture HTTP servers or reach audit registries; permitted reruns passed. Initial full PHP run also found four stale preview-helper mocks. Tests now import the actual helper and assert the resulting sandbox rather than weakening expected behavior. No tests were disabled. The full suite's21 existing skipped tests are not represented as passing.

Confirmed fixes this continuation:

| Files / symbol | Reproduced root cause and smallest fix | Regression evidence |
| --- | --- | --- |
| `preview.js::previewSandbox`, `editor.js`, challenge/experiment views | Chromium suppresses native submit events without allow-forms. Original authored form markup alone grants that permission; opaque origin retained, CSP `form-action 'none'` blocks navigation | New Node regression red→green; both native form fixtures; actual parent/storage/navigation probes and zero POST/logout requests |
| `app.js` lazy workspace/editor initialization | With2.5s workspace asset delay, Run dispatched before listener registration; late setup collapsed output. Editor now waits for workspace initialization | Independent same delayed Chromium probe changed hidden output to visible output |
| `codequest-javascript-browser.mjs::run` and probes | Empty body is valid; load wait was unbounded/racy; intentional blocked navigation could suppress load | Attached-body wait, registration before Run,15s deadline; blocked-form probe checks DOM/network directly; final journey and eight-fixture repeat |
| `acceptance-environment.sh` | Lexical temp prefix allowed path traversal and socket identity was insufficient | Direct-child/ownership/symlink guards; exact server datadir; traversal refusal; fresh bootstrap and state-preserving rerun |
| `restore-runtime.sh` | Lost private runtime could not be recreated from repository | Pinned/checksummed private bootstrap, path isolation;5 negative path tests; rootless24 checks |
| `CurriculumPreviewFixtureTest` / `ExperimentTest` | Publication/history and new preview API needed behavioral coverage | Admin HTTP publication/version/history/idempotency/role denial; rendered preview import and sandbox assertions |

Unslop removed the unused failure snapshot variable and inconsistent grader-URL override. Resumption validates passed checks, all six authoritative failure snapshots and completed curriculum identifiers. No authorization, CSP, sandbox, transaction, history or regression safeguard was removed. No further cleanup justified.

### I. Journal deployment readiness

**JOURNAL DEPLOYMENT READY: YES. CONFIGURED APPLICATION: NOT EXECUTED.**

Required targeted migration:
`database/migrations/2026_10_07_020341_create_the404_mission_submission_results_table.php`.

Read-only preflight confirmed configured journal table and migration record absent; parent IDs/engines compatible. Disposable fresh/upgrade/rollback/idempotency acceptance remains valid; this session's MariaDB rehearsal again passed. New table stores immutable failed-result replay data, unique `(user_id,submission_id)`, user/mission FKs, version/source hash/failure payload/timestamp. No academic-history backfill or rewrite.

Approval-stage procedure:

1. Verify exact target database and migration record, cache configuration, current release files, compatible parent columns/engines, backups and baseline academic counts/checksums. Freeze curriculum editing.
2. Take a protected backup using an external credential file and verify restoration into a disposable database before proceeding:

```sh
mariadb-dump --defaults-extra-file="$CQ_DB_CREDENTIALS_FILE" --single-transaction \
  --routines --triggers --events --databases "$CQ_DATABASE" > "$CQ_BACKUP_FILE"
```

3. Complete approved Admin metadata publication as below while authentication is available. Enter a short maintenance window and drain academic requests/workers. DDL metadata-lock duration is unmeasured; zero downtime is not claimed.
4. **Only after explicit configured-write approval**, run the single reviewed pending migration, never all legacy create-table migrations:

```sh
php artisan down --retry=60 --no-interaction
php artisan migrate --path=database/migrations/2026_10_07_020341_create_the404_mission_submission_results_table.php --force --no-interaction
```

5. Verify migration record, SHOW CREATE TABLE, FKs/unique constraint, unchanged historical checksums, failed-result retry, valid completion/XP and Boss eligibility. Restart long-lived PHP workers for deployed code; restart queues only if present. No schema-readiness cache invalidation is required because readiness checks the schema. Bring application up only after health acceptance.

```sh
php artisan up --no-interaction
```

Prefer forward fixes after journal traffic begins. Dropping the table destroys replay history. Rollback is acceptable only for an empty new table with explicit approval and the exact migration path; never broad batch rollback. None of these commands was run against the configured database.

### J. Curriculum publication readiness

**CURRICULUM PUBLICATION READY: YES, subject to approved target/payload preflight. CONFIGURED APPLICATION: NOT EXECUTED.**

The source seeder contains new preview metadata; configured records are not automatically updated by deploying source. Read-only inventory confirmed nine existing missions lack it: IDs68,69,77,86,87,88,90,94,95. Eight need the canonical Preview HTML prefix; Module_Syntax90 needs Preview source only. Use the existing authenticated Admin edit/update flow for course3:

- GET `/admin/courses/3/missions/{mission}/edit`;
- PUT `/admin/courses/3/missions/{mission}` with validated existing field values and the reviewed metadata-only change.

Extract only the canonical prefix, prepend it to each current stored starter, preserve local source/hints/rules/solutions/points and unrelated fields. Refresh version/hash immediately before update and hold an exclusive curriculum editing window: the current endpoint does not provide compare-and-swap protection for concurrent editor payloads. Conflicting existing metadata requires review, not overwrite. Do not run the whole curriculum seeder on the configured database.

The actual Admin HTTP rehearsal covered all nine prefix updates: exactly one material version increment/audit, repeated identical update no new version/audit, existing Progress byte-for-byte unchanged, and Teacher/Operator denial. New frozen historical facts were not rewritten. Final read-only Boost preflight also confirmed three missing description notices: #70 Fetch_Transmission_Protocol (version2), #79 Store_Local (version1), #89 Promise_Chain (version1). Thus the exact publication scope is **12 records**, nine starter changes plus three description changes. Insert only the canonical `Preview boundary:` paragraph from CurriculumContentSeeder.php lines984,1692,1915 before `Your task:` in each current description. Preserve all existing prose and unrelated fields; verify one material version increment/audit and unchanged historical state through the same authorized Admin flow. No broad seeder or configured update was performed.

After approved publication, verify stored prefixes/version/audit identity, unchanged history, all eight real previews, source-only module behavior and protected rules/solutions. Metadata publication and journal DDL both remain release requirements.

### K. Remaining issues

Release gates: configured journal migration and reviewed curriculum publication remain unapplied. No current code/runtime P0/P1 identified by independent review.

Existing nonblocking limitations remain explicit: arbitrary infinite preview code can stall its iframe; submission timeout is isolated and fail-closed. Module exercise is source-only. API/storage examples cannot access application authority from the opaque sandbox. Editor bundle warning remains. Browser retry checks do not replace genuine transaction concurrency tests. No broad redesign or speculative optimization was performed.

### L. Git state

Branch `main`; HEAD `9a6cd6484d0767a8cb7f66d8a890afc2b977687f`; **69 pending paths**. All64 original paths retained; unrelated baseline files unchanged. Changes this continuation are the two restore scripts, app/editor/preview wiring, challenge/experiment views, preview/Experiment/publication/browser tests and this append. Complete diff inspected; no credentials, environment files, DB dumps, logs, screenshots, runtime packages, vendor/node_modules or generated build output added to Git.

`git status --short`:

```text
 M .ai/rules/services.md
 M .ai/rules/tests.md
 M .github/workflows/laravel.yml
 M CODEQUEST_FULL_SYSTEM_AUDIT_AND_RECOMMENDATIONS.md
 M app/Http/Controllers/MissionController.php
 M app/Services/AnnouncementService.php
 M app/Services/AssessmentService.php
 M app/Services/AttentionService.php
 M app/Services/CourseAnalyticsService.php
 M app/Services/DraftService.php
 M app/Services/MissionService.php
 M app/Services/StudentService.php
 M app/Services/TeacherDashboardService.php
 M app/Services/XpService.php
 M config/database.php
 M database/factories/CourseFactory.php
 M database/factories/MissionFactory.php
 M database/factories/SectionFactory.php
 M database/seeders/CurriculumContentSeeder.php
 M database/seeders/DatabaseSeeder.php
 M grader/src/server.js
 M grader/test/integration-container.sh
 M grader/test/server.test.js
 M package-lock.json
 M package.json
 M resources/css/app.css
 M resources/js/app.js
 M resources/js/editor.js
 M resources/js/knowledge-check.js
 M resources/js/preview.js
 M resources/views/assessments/show.blade.php
 M resources/views/challenge.blade.php
 M resources/views/course-analytics.blade.php
 M resources/views/experiment.blade.php
 M resources/views/knowledge-check.blade.php
 M resources/views/layouts/app.blade.php
 M tests/Feature/AcademicConcurrencyTest.php
 M tests/Feature/AttentionTest.php
 M tests/Feature/ChallengeAnalyticsTest.php
 M tests/Feature/CourseAnalyticsTest.php
 M tests/Feature/CurriculumVersioningTest.php
 M tests/Feature/EditorSourceRestorationTest.php
 M tests/Feature/ExperimentTest.php
 M tests/Feature/MissionTest.php
 M tests/Feature/RoleNavigationTest.php
 M tests/Feature/XpLedgerTest.php
 M tests/TestCase.php
 M tests/Unit/XpServiceTest.php
 M tests/preview.test.js
?? app/Support/DisposableDatabase.php
?? database/migrations/2026_10_07_020341_create_the404_mission_submission_results_table.php
?? database/seeders/DevelopmentDemoSeeder.php
?? grader/test/restore-runtime.sh
?? tests/Feature/AnnouncementMutationIntegrityTest.php
?? tests/Feature/AssessmentReadinessParityTest.php
?? tests/Feature/CurriculumPreviewFixtureTest.php
?? tests/Feature/DatabaseClockTest.php
?? tests/Feature/DevelopmentDemoSeederTest.php
?? tests/Feature/FactoryCapacityTest.php
?? tests/Feature/JournalSchemaAcceptanceTest.php
?? tests/Feature/KnowledgeCheckMonitoringTest.php
?? tests/Feature/MissionAssistanceIntegrityTest.php
?? tests/Feature/MissionMutationIntegrityTest.php
?? tests/Feature/MissionSubmissionReplayTest.php
?? tests/Feature/ScopedAttentionTest.php
?? tests/Unit/DisposableDatabaseTest.php
?? tests/acceptance-environment.sh
?? tests/codequest-browser.mjs
?? tests/codequest-javascript-browser.mjs
```

### M. Independent review

Separate Standards and Spec/security reviewers distrusted builder conclusions and inspected actual diffs, authoritative paths, browser artifacts and read-only database state.

Standards: confirmed traversal fix, consistent grader environment, strengthened resumption checks, no redundant abstraction or removed safeguards. Native form exception satisfies the standing requirement for parent-access regression: seven actual DOM/cookie/storage/session/CSRF/top probes returned SecurityError; attempted application POST produced zero requests and identical academic state.

Spec: all14 required answers resolved. Eight current authored fixtures proven;35 JavaScript missions and Boss journey proven across disclosed resumptions; six failures safe; rewards unique; eligibility correct; course completion persists; terminal JS next-course N/A; replay safety proven with concurrency limits disclosed; grader isolation retained; preview non-authoritative; journal/publication procedures ready but unapplied. Independent delayed-asset probe verified the import-order fix. No remaining code/runtime P0/P1 identified.

CODE READY FOR CONFIGURED DEPLOYMENT

RELEASE BLOCKED ONLY BY JOURNAL/CURRICULUM APPLICATION

**REJECT**

## Configured deployment continuation — 2026-10-08

Deployment authorization remains subject to the user's hard gates. No configured-database writes were performed during this continuation.

### Checksum correction and recovered evidence

The previous claimed academic-history discrepancy was caused by the verification query, not proven historical mutation. The original preflight hashes each history row with `SHA2(JSON_ARRAY(columns in schema ordinal order),256)`, then hashes the newline-delimited ordered row hashes, including a final newline. The later comparison substituted `JSON_OBJECT`, which changes the serialized bytes for nonempty rows. Mission snapshots intentionally use `JSON_OBJECT`; the two procedures must remain distinct.

The original sanitized snapshot and procedures were recovered from recorded preflight tool evidence after `/tmp/codequest-configured-preflight` disappeared. Live read-only Boost queries using the original history procedure found **zero count/checksum differences across all eight protected history tables**. All twelve complete curriculum inventory records also match their original versions and content/material hashes.

The current 95-mission ordered digest is `2b452ef883345d01f2332d3a0e3f570b94907a66ca5251d2c0717d4012c8c181`, exactly matching the approved baseline. This reconciles all 12 targets and all 83 non-target missions. The twelve publication payloads were reconstructed from current stored content and repository-owned curriculum definitions without running the seeder. Every payload matches its original reviewed SHA-256 byte for byte. Recovery artifacts reside under `/tmp/codequest-deployment-recovery`; directories use mode 0700 and data files mode 0600. These are preflight artifacts, not a backup or proof that traffic is frozen.

### Live identity and remaining operational gates

Read-only identity: database `codequest`, MariaDB `13.0.2-MariaDB`, account `codequest@localhost`, through `/run/mysqld/mysqld.sock`. Only `2026_10_07_020341_create_the404_mission_submission_results_table` remains pending; no unexpected database-only migrations were found.

Host inspection identified a loopback HTTP listener on port 8123 belonging to `/home/vmc/Projects/CodeQuest-ui-redesign`, not this checkout. That service was left untouched. No verified control for holding all academic traffic and curriculum editing against the configured database, protected configured Admin session/credential source, or durable backup destination has been supplied. Those operational details were requested; the existing deployment authorization was not requested again. Native server and dump tools both report MariaDB 13.0.2, but no backup or isolated restoration has been attempted before the freeze gate.

### Independent review and status

A separate read-only reviewer confirmed the JSON_ARRAY/JSON_OBJECT root cause, equality of all eight history dictionaries and twelve inventory records, and independently recomputed the matching 95-row mission digest. The reviewer explicitly limited that conclusion to artifact reconciliation; freeze, backup, restore and deployment acceptance remain unproven.

Branch `main`; HEAD `9a6cd6484d0767a8cb7f66d8a890afc2b977687f`; all 69 pending paths preserved. No product code changed, no commit or push occurred, and `git diff --check` passed. Unslop review removed no safeguards or production code. Publication, migration, post-deployment tests and configured application smoke tests have not run. Execution must resume with a verified freeze, repeat read-only checks, protected backup and verified isolated restoration before any configured write.

Deployment verdict: **BLOCKED** pending the operational prerequisites above. The previously reported checksum blocker is resolved.
