# CodeQuest Mission Atlas implementation plan

> **For agentic workers:** REQUIRED SUB-SKILL: Default to superpowers:executing-plans for inline, task-by-task execution. Use superpowers:subagent-driven-development only for genuinely independent work where parallel agents cannot produce conflicting visual interpretations. Steps use checkbox syntax for tracking. Neither this document nor approval of the design specification authorizes product implementation.

**Goal:** Apply the approved Mission Atlas design across CodeQuest while preserving every authoritative learning and management behavior.

**Architecture:** Retain Laravel Blade, the existing shared components, CSS entry points, and JavaScript modules. Change presentation and interaction semantics within the existing view data contracts. Keep academic calculations, authorization, routes, persistence, and grading unchanged.

**Tech Stack:** Laravel 13, PHP 8.5, Blade, Tailwind v4 CSS-first tokens, Vite, existing JavaScript/CodeMirror, PHPUnit, Node tests, and repository Chromium/browser tooling. Confirm installed versions before relying on package APIs.

**Spec:** [Approved Mission Atlas specification](../specs/2026-10-08-codequest-mission-atlas-design.md), frozen in local checkpoint `9b52c289603400a9b173a9b9aae2f1d915dc1ada`.

## Global constraints

- Work on `redesign/astra-ui`. Preserve unrelated pending work. No reset, stash, cleanup, merge, push, or further commit without authorization. Only the specification and plan checkpoint commits are authorized; product implementation commits require separate approval.
- No controller, service, model, route, migration, seeder, curriculum, dependency, configured database, or grader changes. Stop if a visual requirement needs one.
- No configured-application browsing: authenticated GET requests can write reminders/session state. Browser acceptance requires a proven disposable environment. Do not provision, migrate, or seed one under this planning authorization.
- Canonical tokens remain in `resources/css/app.css`; student page compositions remain in `resources/css/student.css`. Preserve the existing Vite entry points. Do not create a parallel design system.
- Canvas `#111416`; primary text `#F2F3EE`; supporting text `#AAB4B8`; primary/current accent `#C4F56A`. Copy the remaining exact token values from specification sections 7–13.
- IBM Plex Sans for ordinary content; IBM Plex Mono only for code and technical metadata. Use local assets through the existing pipeline when available. Otherwise use the declared system fallbacks. No runtime font CDN or required generated image.
- Structural radius `0px`, controls `2px`, overlays at most `4px`. Thin rules; no gradients, glow, glass, permanent sidebar, nested panels, or floating metric-card grid.
- WCAG 2.2 AA is the acceptance target. Explicit `data-*` interaction hooks; ARIA describes semantics and state, never discovers interactive components.
- Knowledge Checks remain formative. Visual sequence and connectors never add prerequisites. Preserve server-selected Continue destinations and authoritative Boss eligibility.
- Workspace recomposition starts at `1100px`; adjust only with recorded browser evidence. One mounted editor must retain source, undo history, selection, draft state, and feedback.
- Keep current theme/account capabilities. If an existing theme conflicts with the approved palette, present that conflict for review rather than silently removing it.
- At each visual implementation phase, reuse or re-read the materially applicable approved design skills: Impeccable, Huashu Design, UI/UX Pro Max, UI Styling, Design System, Brand, Product Design Audit where relevant, and Unslop. Use Caveman for token efficiency. Record which guidance informed the phase; do not reopen Mission Atlas direction selection.
- Read the current applicable `.ai/rules/` files before each phase. Apply design guidance to composition, TDD to changed interaction contracts, and Unslop to changed copy. Do not reopen approved visual-direction selection.

## Review focus

1. Fonts unavailable offline: readable fallback, stable controls, and no obscured action. Phase 1 browser regression.
2. Long titles, long validation text, text scaling, and short windows: readable hierarchy without page overflow. Phases 3 and 10 browser regression.
3. Formative Knowledge Check results next to locked assessments: no implied eligibility or false completion. Phases 4 and 5 feature/browser regression.
4. Paginated Teacher data mistaken for a complete ranked queue: retain scope, filters, pagination, and honest labels. Phase 7 feature/browser regression.
5. Unsaved editor state during pane changes, resizing, failures, and repeated submission: preserve editing state and server authority. Phase 5 Node/Chromium regression.

## Execution and evidence contract

### Test commands

All commands below are for later authorized implementation, not evidence of tests run during planning. From the repository root, define this shell helper for the listed feature-test files:

```bash
cq_test() {
    APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL='' \
    SESSION_DRIVER=array CACHE_STORE=array php artisan test --compact "$@"
}
```

Before using it, verify `phpunit.xml` and `tests/TestCase.php` still enforce disposable isolation. Stop if cached configuration overrides it. Do not clear configured application caches to make tests run. The listed feature-test filenames are relative to `tests/Feature/`; pass their full paths, for example:

```bash
cq_test tests/Feature/ShellTest.php tests/Feature/RoleNavigationTest.php
node --test tests/workspace.test.js tests/preview.test.js
npm run build
CODEQUEST_REQUIRE_BROWSER_TESTS=1 cq_test tests/Feature/WorkspaceBrowserTest.php
```

`WorkspaceBrowserTest` needs Chromium and built assets; missing browser tooling is a blocked check, not a pass. Existing full-app scripts are `tests/codequest-browser.mjs` and `tests/codequest-javascript-browser.mjs`. Run them only with verified disposable application/session/cache/database connections and existing legitimate fixtures. Supply their required `CODEQUEST_BROWSER_*`, `CODEQUEST_DEMO_PASSWORD`, Chromium, and Playwright environment values securely; do not record passwords in the plan or artifacts. The JavaScript script also requires its authored fixture and isolated database socket/name. A localhost URL alone does not prove isolation.

Do not invoke `tests/acceptance-environment.sh` automatically: it provisions and seeds a database. If the disposable runtime is unavailable, report the missing prerequisite and obtain separate authorization for restoration. Never substitute the configured application.

### Common browser acceptance: V

Every phase uses **V**: `1920×1080`, `1366×768`, `768×1024`, `390×844`; desktop 200% zoom and 320 CSS-pixel effective width. Inspect actual content, long text, empty data, and keyboard operation. Use screenshots from the same legitimate fixture state before/after. Store artifacts outside tracked product files.

- Reading and tab order match semantic source order; one main landmark and a clear page heading.
- Navigation collapses before collision; visible primary action; no page-wide horizontal overflow. Wide semantic tables/code may scroll within labeled regions.
- Text contrast meets 4.5:1, large text 3:1; meaningful non-text controls/focus meet 3:1. Check actual composited colors, not token names.
- Visible focus is not clipped or hidden by sticky bars; controls provide 44px touch targets; state has text/icon meaning beyond color.
- Inspect reduced motion, form error association, disclosure Escape/outside click/focus return, and screen-reader status announcements where present.
- Build assets before Chromium verification. Capture browser console/page failures; distinguish application exceptions from expected sandbox diagnostics.

### Every phase stops

Run its pre-change tests, capture baseline screenshots, then change only its listed files. Add failing behavioral tests only for changed interaction/semantic contracts; CSS-only restyling needs visual evidence, not class-name assertions. Rerun the same tests after changes. Apply Unslop to the phase diff, then rerun affected checks and `git diff --check`.

Present changed paths, exact test totals, browser states/screenshots, accessibility results, and remaining discrepancies. **Stop for product-owner approval before the next phase.** A rejected composition is revised within its phase; do not build later screens on it. No phase completion is inferred from screenshots alone.

## Phase 1 — Design foundation and application shell

**Expected product files:** `resources/css/app.css`, `resources/css/student.css`, `resources/js/app.js`, `resources/views/layouts/app.blade.php`, and `resources/views/components/{student-header,navigation,page-header,panel,badge,progress-bar,status-message,student-toast}.blade.php`. Braces enumerate exact filenames, not permission to change every component.

**Before modification:** `ShellTest.php`, `RoleNavigationTest.php`, `AuthTest.php`, `DashboardTest.php`, `TeacherDashboardTest.php`, `AdminDashboardTest.php`; `node --test tests/workspace.test.js tests/preview.test.js tests/knowledge-check.test.js`.

**Interfaces/preservation:** Preserve Blade layout sections, component props/slots, named links, role visibility, account actions, logout forms, and notification access. Shared tokens/control styles become the contract consumed by all later phases. No new component framework or font dependency.

- [ ] Capture all three current role shells and public login at V, including open navigation/account disclosures.
- [ ] Extend `ShellTest.php`/`RoleNavigationTest.php` only for changed semantic structure and route visibility. Extend `tests/codequest-browser.mjs` for keyboard disclosures and ordinary/error message dismissal. Ordinary saved messages must not gain alert semantics to support dismissal.
- [ ] Replace canonical palette/type/geometry tokens in `app.css`. Preserve necessary legacy aliases until all consuming views migrate. Before removing production dependence on `docs/design/prototype/styles.css`, inventory every production-used selector/token it supplies and identify the consuming production views. Migrate only required structural rules into production stylesheets; never copy the entire prototype stylesheet. Retain required behavior, eliminate duplicate theme definitions, and leave prototype files untouched. Verify representative Student, Teacher, and Admin screens. Remove the import from `student.css` only when production no longer depends on it.
- [ ] Apply shared top navigation and explicit dismissal/disclosure hooks in `app.js`; preserve initialization behavior without duplicate listeners. Style existing components without changing their input contracts.
- [ ] Verify offline/font-blocked fallback at V, all role destinations, skip link, Escape, outside click, focus restoration, ordinary status and error announcements, dismissal, and repeated initialization/navigation.
- [ ] Rerun baseline tests/build; inspect other existing pages for global CSS damage. Stop for shell/token approval before changing dashboard composition.

**Viewport/a11y acceptance:** V; 56px minimum bar can grow, 1440px standard content maximum, mobile navigation separate from account controls. No permanent sidebar. Confirm text remains legible with local fonts absent.

## Phase 2 — Student Dashboard

**Expected product files:** `resources/views/dashboard.blade.php`, `resources/css/student.css`; shared components only if a demonstrated common defect needs correction and is reviewed separately.

**Before modification:** `DashboardTest.php`, `StudentDashboardNotificationsTest.php`, `StudentOverviewTest.php`, `PersonalizedLearningJourneyTest.php`.

**Interfaces/preservation:** Consume existing `course`, `courseProgress`, `totalXp`, `learnerStats`, `recentActivity`, `resume`, `resumeHasDraft`, and `competencySummary`. Preserve all server-selected Continue branches, draft return behavior, no-course state, and completed-course state. Dashboard data is not a full mission graph or a recommendation collection.

- [ ] Capture active mission, saved draft, new learner, no available course, and completed course states using existing disposable fixtures/tests.
- [ ] Compose Current Objective, course/section/mission context, primary Continue, compact XP/course position, and recent evidence as one ruled learning composition. Link to existing recommendation data rather than inventing an item.
- [ ] Extend `DashboardTest.php` only if link/semantic contracts change: preserve exact Continue destination for draft and normal resume, and truthful empty/completed states. Extend existing browser journey locators only when the approved accessible labels change.
- [ ] Run baseline tests and inspect V, long mission titles, keyboard order, primary-action visibility, and status meaning without color.
- [ ] Stop for Student Dashboard approval. Do not start the path until the owner accepts the defining composition.

**Viewport/a11y acceptance:** V; objective precedes supporting evidence in source order. On mobile, metadata follows the objective without displacing Continue. No fabricated graph, equal metric-card row, or hidden progress meaning.

## Phase 3 — Learning Path

**Expected product files:** `resources/views/learning-path.blade.php`, `resources/views/missions-index.blade.php`, `resources/css/student.css`.

**Before modification:** `LearningPathTest.php`, `LearningPathRecommendationsTest.php`, `MissionIndexUiTest.php`, `MissionIndexInputTest.php`, `CourseSequenceSecurityTest.php`, `UnsectionedMissionTest.php`.

**Interfaces/preservation:** Use the existing course/section/mission collections and backend states. Preserve mission URLs, ordering, filters, unsectioned mission handling, unlock reasons, and assessment destination. No client calculation of completion or availability.

- [ ] Capture complete/current/available/locked states, course boundaries, long titles, and unsectioned content.
- [ ] Build a connected, ordered course spine with section checkpoints and distinct assessment endpoint. Use semantic headings/lists and decorative connectors excluded from assistive output.
- [ ] Extend `LearningPathTest.php` and `MissionIndexUiTest.php` only for changed accessible state labels/links; preserve existing progression assertions. Add browser checks for long labels at zoom and equivalent state/action access after mobile recomposition.
- [ ] Run pre-change suites again; inspect V with current location visible, locked explanation readable, and no misleading prerequisite connector.
- [ ] Stop for Learning Path approval before migrating reading/workspace screens.

**Viewport/a11y acceptance:** V; wide layout uses alignment and deliberate whitespace; 768/390 use one ordered spine, not a scaled map. Check state labels beyond color and logical keyboard/source order.

## Phase 4 — Lesson / Try It Yourself / Knowledge Check

**Expected product files:** `resources/views/lesson.blade.php`, `resources/views/experiment.blade.php`, `resources/views/knowledge-check.blade.php`, `resources/css/student.css`; `resources/js/knowledge-check.js` only for an approved semantic/interaction change.

**Before modification:** `MissionTest.php`, `ExperimentTest.php`, `KnowledgeCheckTest.php`, `KnowledgeCheckMonitoringTest.php`, `EditorSourceRestorationTest.php`, `WorkspaceControlsTest.php`; `node --test tests/knowledge-check.test.js tests/preview.test.js tests/workspace.test.js`.

**Interfaces/preservation:** Keep authored lesson content, sanitized rendering, example/editor source, return destinations, attempt state, answer submission, feedback, and recovery. Practice and Knowledge Check results remain separate from challenge completion.

- [ ] Compose lessons as readable documentation: objective, content, examples, common mistakes where authored, Try action, and one next action. Do not write new curriculum.
- [ ] Simplify Knowledge Check to question position, question, native choices, submit, and feedback; retain back navigation and attempt restoration.
- [ ] Extend `KnowledgeCheckTest.php` and `tests/knowledge-check.test.js` only where changed controls/announcements need coverage. Assert formative wording and no new Boss eligibility implication. Preserve authoritative eligibility tests unchanged.
- [ ] Inspect lesson reading, Try editing, unanswered/incorrect/correct questions, validation errors, reload/back recovery, and existing finished attempt at V.
- [ ] Rerun tests/build and Chromium workspace checks. Stop for reading/formative-flow approval.

**Viewport/a11y acceptance:** V; lesson measure at most 68ch, body 18/30px, scrollable code without page overflow. Native labels and grouped choices; feedback announced once without focus theft. Try retains editor access on small screens pending Phase 5 workspace composition.

## Phase 5 — Coding Challenge / Boss Challenge

**Expected product files:** `resources/views/challenge.blade.php`, `resources/views/experiment.blade.php`, `resources/views/assessments/{index,show}.blade.php`, `resources/css/student.css`, `resources/js/workspace.js`, `resources/js/editor.js`. `resources/js/preview.js` is inspection/regression scope only; do not change sandbox construction to accomplish layout.

**Before modification:** `WorkspaceControlsTest.php`, `WorkspaceBrowserTest.php`, `EditorSourceRestorationTest.php`, `MissionBehavioralGradingTest.php`, `MissionSubmissionReplayTest.php`, `AssessmentTest.php`, `AssessmentReadinessParityTest.php`, `AssessmentRecoveryTest.php`, `AssessmentIntegrationTest.php`, `CurriculumPreviewFixtureTest.php`; `node --test tests/workspace.test.js tests/preview.test.js`.

**Interfaces/preservation:** Retain `initializeWorkspace(root)`, existing pane hooks, editor lifecycle, source restoration, draft protocol, request identifiers, submission endpoint/payload, grading feedback, sandbox restrictions, and authoritative eligibility/result state. Preserve one mounted CodeMirror instance across modes.

- [ ] Add failing state-preservation tests in `tests/workspace.test.js` and `tests/workspace-browser.mjs` before changing mode controls: enter text, select, edit, switch modes, resize, then undo and verify selection/source/draft/feedback remain intact.
- [ ] Compose desktop Task / Editor / Output and small-width Task / Code / Output modes. Use 1100px initially in both CSS and JavaScript. Record content width, zoom, long-text evidence, and final value if adjustment is necessary.
- [ ] Make run/draft/submit/status legible without reducing editor space. Provide keyboard pane selection and resize alternatives without trapping Tab in the workspace.
- [ ] Frame Boss readiness, attempt state, and result at course scale with typography and structural rules. Do not grant eligibility, grade locally, or conflate preview with success.
- [ ] Extend existing workspace/assessment browser coverage for locked/eligible Boss, invalid and valid submission, timeout, grader failure, saved draft, replay, and reload. Keep source/journal/XP assertions in existing full-app journeys; never weaken them for new labels.
- [ ] Run targeted tests/build/real Chromium; run authorized disposable JavaScript and Boss journeys. Verify eight authored DOM previews, no parent access, no preview-induced completion, and unchanged historical/replay behavior.
- [ ] Stop for workspace and Boss approval; missing disposable runtime or grader evidence blocks this gate.

**Viewport/a11y acceptance:** V plus either side of the final breakpoint. Editor remains useful at 1366×768 and browser zoom; mobile output/task changes preserve editing state. Verify labeled controls, active tab/pane semantics, focus visibility, announcement frequency, and feedback recovery.

## Phase 6 — Learning Performance / Timeline / Recommendations

**Expected product files:** `resources/views/{competency,timeline,recommendations,course-progress,section-progress,xp-ledger,achievements,notifications}.blade.php`, `resources/views/components/recommendation-cards.blade.php`, `resources/css/student.css`.

**Before modification:** `CompetencyTest.php`, `SkillCompetencyTest.php`, `TimelineTest.php`, `RecommendationsTest.php`, `CourseProgressTest.php`, `SectionProgressTest.php`, `XpLedgerTest.php`, `AchievementSystemTest.php`, `NotificationCenterTest.php`, `NotificationReadStateTest.php`.

**Interfaces/preservation:** Existing evidence, scores, recommendations, filters, pagination, time ordering, read-state actions, and reporting links remain authoritative. Preserve distinctions between Knowledge Check, challenge, and Boss evidence. No inferred mastery or progress totals.

- [ ] Replace metric-card emphasis with interpreted evidence rows, labeled progress, chronological records, and clear next actions from supplied data.
- [ ] Present XP transactions and achievements as learning records; do not create new rewards or derive progress from the ledger.
- [ ] Extend the corresponding existing feature test only when semantic structure/actions change. Assert evidence categories, filter persistence, correct target links, and honest insufficient-data states rather than styling classes.
- [ ] Inspect populated/empty/insufficient evidence, pagination, filters, long descriptions, notification read/unread/error states at V; rerun suites.
- [ ] Stop for learning-record approval.

**Viewport/a11y acceptance:** V; tabular evidence retains associated headings, chronological order survives reflow, charts have text equivalents, and recommendations remain actionable by keyboard.

## Phase 7 — Teacher Dashboard and operational screens

**Expected product files:** `resources/views/{students,student-progress,classrooms,classrooms-show,needs-attention,activity,course-analytics}.blade.php`, `resources/views/reports/{index,show}.blade.php`, `resources/css/app.css`.

**Before modification:** `TeacherDashboardTest.php`, `StudentOverviewTest.php`, `StudentProgressTest.php`, `ScopedAttentionTest.php`, `ClassroomMonitoringTest.php`, `ClassroomAuthorizationTest.php`, `TeacherAuthorizationTest.php`, `TeacherStudentReportTest.php`, `TeacherCourseReportTest.php`, `ReportPageTest.php`, `ReportFiltersTest.php`, `ReportExportTest.php`.

**Interfaces/preservation:** `/students` supplies paginated `students`, `filterCourses`, `filters`, `dashboardMetrics`, `dashboardActivity`, and `dashboardSummary`. Preserve scoped queries, roster ordering, pagination, selected filters, assessments, and Needs Attention route. No complete ranked attention queue can be inferred from the current page.

- [ ] First change only `students.blade.php` and its scoped CSS: class/course context, evidence summaries, honest roster, and Needs Attention destination. Extend `TeacherDashboardTest.php`/`StudentOverviewTest.php` for retained filters and non-misleading paginated labels when needed.
- [ ] Inspect teacher landing at V with multiple pages, active filters, no classroom, and actual attention evidence. **Stop for Teacher Dashboard approval before operational screens.**
- [ ] Apply accepted density/row structure to classroom, student detail, activity, analytics, and report views. Preserve export parameters; leave `resources/views/exports/pdf/report.blade.php` unchanged.
- [ ] Extend relevant existing feature tests only for changed UI contracts. Run authorization/scope/export tests unchanged; verify cross-role report presentation because report views are shared.
- [ ] Inspect attention reason/evidence, course/classroom context, empty filters, pagination, long student names, and report/export controls at V. Stop for Teacher operations approval.

**Viewport/a11y acceptance:** V; dense semantic tables on desktop, prioritized columns or labeled scroll regions on mobile. No hidden essential evidence, student-map decoration, unauthorized links, or color-only intervention states. Preserve table headers, filter labels, and keyboard reachability.

## Phase 8 — Admin Dashboard and management screens

**Expected product files:** `resources/views/admin/{dashboard,activity,analytics,system}.blade.php`; `resources/views/admin/announcements/{index,create,edit}.blade.php`; `resources/views/admin/classrooms/{index,create,edit}.blade.php`; `resources/views/admin/courses/{index,edit}.blade.php`; `resources/views/admin/sections/{index,edit}.blade.php`; `resources/views/admin/missions/{index,edit}.blade.php`; `resources/views/admin/assessments/{index,edit}.blade.php`; `resources/views/admin/users/{index,create,edit,show}.blade.php`; `resources/css/app.css`.

**Before modification:** `AdminDashboardTest.php`, `AdminAuthorizationTest.php`, `AdminUserTest.php`, `AdminRoleManagementTest.php`, `ClassroomManagementTest.php`, `AdminCourseManagementTest.php`, `AdminSectionManagementTest.php`, `AdminMissionManagementTest.php`, `AdminAssessmentManagementTest.php`, `AdminAnnouncementTest.php`, `AdminAuditTrailTest.php`, `AdminSystemTest.php`, `AdminSystemReportTest.php`, `AdminAnalyticsTest.php`.

**Interfaces/preservation:** Existing account/curriculum/assessment/learning counts and `recentSystemActivity` only. Preserve permissions, field names, methods, CSRF, old input, validation, confirmation, optimistic version fields, audited mutation routes, filters, and pagination. Do not submit curriculum changes for visual review.

- [ ] Redesign only the dashboard first: ruled operational sections, authoritative counts, recorded activity, and management destinations. No fake alerts or student rewards.
- [ ] Inspect V and run dashboard/authorization tests. **Stop for Admin Dashboard approval.**
- [ ] Restyle the user index and edit form as the representative table/form pair. Preserve field/error associations and consequence-specific confirmations. Inspect and approve this pair before propagating its treatment.
- [ ] Apply the accepted treatment to remaining listed screens in small view groups. Run each group's named tests before and after; change tests only for altered semantics, never expected authorization or validation outcomes.
- [ ] Inspect empty/populated tables, filters, long values, validation failures, disabled actions, confirmation cancel/confirm using isolated feature fixtures, and audit/system views. Stop for Admin management approval.

**Viewport/a11y acceptance:** V; semantic tables, labeled filters/inputs, linked error summaries, focus return from dialogs, no destructive default focus, and readable mobile actions. Admin remains operational and visually part of the same shell.

## Phase 9 — Public/login/error surfaces

**Expected product files:** `resources/views/welcome.blade.php`, `resources/views/auth/login.blade.php`, `resources/views/errors/{layout,403,404,419,429,500,503}.blade.php`, `resources/css/app.css`.

**Before modification:** `AuthTest.php`, `AccountStatusTest.php`, `ErrorHandlingTest.php`, `SecurityHeadersTest.php`, `ShellTest.php`.

**Interfaces/preservation:** Login fields, CSRF, remember-me, validation, throttling, safe redirects, status codes, and error recovery destinations remain unchanged. No registration implementation or invented recovery capability. Error layout must remain readable when Vite assets fail.

- [ ] Apply Mission Atlas type/rules/spacing with simpler public navigation and plain recovery copy.
- [ ] Keep independent fallback styling in the error layout so a missing asset bundle does not break error presentation.
- [ ] Extend `AuthTest.php`/`ErrorHandlingTest.php` only for changed accessible labels or recovery links; retain security assertions.
- [ ] Inspect login normal/error/throttled states, public welcome, every error page, no network/fonts, keyboard submission, password-manager autofill, and mobile keyboard at V. Rerun tests.
- [ ] Stop for public/error approval.

**Viewport/a11y acceptance:** V; readable forms without decoration competing with login, explicit labels and error associations, meaningful headings, safe focus order, and usable recovery without JavaScript.

## Phase 10 — Responsive, accessibility and full visual consistency pass

**Expected product files:** `resources/css/app.css`, `resources/css/student.css`, `resources/views/layouts/app.blade.php`, `resources/views/components/{student-header,navigation,page-header,panel,badge,progress-bar,status-message,student-toast}.blade.php`, `resources/js/app.js`, `resources/js/workspace.js`. Page-specific corrections are limited to exact files from approved Phases 2–9; list each reproduced defect/path before editing.

**Before modification:** `ShellTest.php`, `RoleNavigationTest.php`, `WorkspaceControlsTest.php`, `WorkspaceBrowserTest.php`, `DashboardTest.php`, `TeacherDashboardTest.php`, `AdminDashboardTest.php`; `node --test tests/*.test.js`.

**Interfaces/preservation:** No change to accepted composition or business contracts. Fix demonstrated reflow, contrast, focus, label, state, and interaction defects only. A substantial visual departure returns to its original approval gate.

- [ ] Compare all three dashboards and representative reading, workspace, table, form, public, and error views at V. Audit remaining prototype selectors and conflicting role token overrides; remove only proven unused rules.
- [ ] Check long content, 200%/400% zoom where applicable, 320px reflow, landscape/short heights, reduced motion, offline fonts, and keyboard-only flows. Measure contrast and inspect accessible names/headings/table associations.
- [ ] Add regression coverage for each confirmed interaction defect in the existing feature/Node/browser owner. Do not add snapshots of all CSS classes.
- [ ] Fix scoped defects; rerun affected tests. Record any final workspace breakpoint change with before/after screenshots, actual content widths, and editor-state evidence.
- [ ] Run Unslop on changed copy/code: remove dead helpers, debug output, repetitive comments, and generic labels. Preserve security checks, tests, and accessible interaction behavior. Stop for cross-product visual/a11y approval.

**Viewport/a11y acceptance:** Complete V for every listed page category and all roles. Record automated-tool availability and manual results separately; no claim of full WCAG conformance from an automated score alone.

## Phase 11 — Regression / browser acceptance

**Expected product changes:** None. Regression fixes return to the owning phase and repeat its approval gate. Test files may change only to close an identified contract gap, not to hide a failing assertion.

**Before acceptance:** Run the Phase 10 focused suite and verify build/runtime isolation. Preserve all existing authority, replay, grading, and scope checks.

- [ ] Run full SQLite regression and report current passed/skipped/failed/assertion counts:

```bash
cq_test
node --test tests/*.test.js grader/test/*.test.js
composer run analyse
vendor/bin/pint --dirty --format agent
npm run build
composer audit
npm audit
git diff --check
```

- [ ] Run Node tests on both installed supported Node 22 and Node 26 runtimes; record executable/version and separate totals. Missing runtimes are reported, not replaced with historical counts. Inspect any formatter changes before proceeding; do not reformat unrelated dirty files.
- [ ] Run real Chromium workspace acceptance after the build:

```bash
CODEQUEST_REQUIRE_BROWSER_TESTS=1 cq_test tests/Feature/WorkspaceBrowserTest.php
```

- [ ] In the separately authorized disposable runtime, run `node tests/codequest-browser.mjs` and `node tests/codequest-javascript-browser.mjs` with their verified fixture/environment inputs. Require full journeys, not DOM-only mode. Record Student login/resume/lesson/Knowledge Check/challenge/invalid/retry/valid/persistence/Boss/next-course behavior; Teacher scoped monitoring/reports; Admin authorized navigation/forms. Preserve exact authoritative journal/XP/progress/replay assertions.
- [ ] Run existing MariaDB concurrency/replay/assessment integration tests only against an already authorized disposable test database. Never pass configured credentials or override the test safety guard. If unavailable, leave database-specific acceptance blocked; do not reuse earlier counts. Existing strict grader checks remain unchanged; rerun their established isolated command if workspace changes expose a new execution-path concern.
- [ ] Review the complete product diff against the frozen specification and protected behavior list. Verify no dependency, backend, route, schema, seed, curriculum, credential, generated asset artifact, or configured-state change slipped in.
- [ ] Present the final evidence matrix: phase approvals, viewports, keyboard/screen-reader checks, exact command results, browser assertions, screenshots, warnings, and unresolved defects. Stop for final product-owner acceptance. No automatic commit, merge, push, or release.

**Viewport/a11y acceptance:** Repeat V for the three signature screens, Teacher/Admin landing pages, one operational table/form per role, login, and an error page. Verify all Phase 10 findings remain closed. A missing required browser state remains unverified even when PHPUnit passes.

## Plan review and completion criteria

Specification coverage: sections 1–13 and 36–38 belong to Phase 1; 14 to Phase 2; 17 to Phase 3; 18–20 to Phase 4; 21–22 to Phase 5; 23–25 to Phase 6; 15/26 to Phase 7; 16/27 to Phase 8; 28–29 to Phase 9. Sections 30–35 apply throughout and are reconciled in Phase 10. Sections 39–43 govern every gate and final Phase 11 acceptance.

Self-review: this plan uses existing view contracts, names actual repository tests, and adds no academic prerequisite or invented reporting capability. All four specification amendments have explicit verification owners. Shared CSS migration is the earliest cross-page risk, so Phase 1 checks all role shells before dashboard work. The workspace and dense management pages have separate review stops before propagation.

Implementation prerequisites still requiring evidence at execution: approved plan; current cleanly understood worktree; installed browser/tool versions; proven disposable runtime and legitimate fixtures; locally available fonts or tested fallbacks. These are gates, not permission to modify backend data or install dependencies.

The design is complete only when every phase has passed its tests/browser checks and received its approval. Product-owner approval of this plan authorizes only the explicitly agreed next phase; it does not waive later checkpoints.
