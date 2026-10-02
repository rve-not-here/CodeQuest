# CodeQuest Full System Audit and Recommendations

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
