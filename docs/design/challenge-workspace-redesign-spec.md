# CodeQuest Challenge and Boss Challenge workspace redesign specification

Date: 2026-10-03. Inspected baseline: `a1a6f0cc61757784ec8ef8f5fe2cbc39bd876775`.

Status: implementation-ready design specification; no application implementation performed. Source inspection establishes the behavior below. Layout, contrast, focus, and device acceptance are requirements for later verification, not checks claimed to have passed.

Revision: UI/UX Pro Max applied on 2026-10-03, with a fresh inspection of the same baseline. This revision makes the visual system, action precedence, recovery examples, and implementation gates explicit. It supersedes speculative prototype directions for this scope. The deliverable is a specification; no application or prototype code is changed.

The primary design input is [comparative-ui-ux-research.md](comparative-ui-ux-research.md), particularly CQ01–CQ08/CQ11/CQ14 and R01–R07/R10. The product authority is [CODEQUEST_MASTER_SPEC_FINAL_UPDATED.md](../../CODEQUEST_MASTER_SPEC_FINAL_UPDATED.md), including Global Attempt/Preview/XP/Boss contracts, Challenge Workspace Layout, Screen I, and responsive/accessibility contracts. The accepted Boss domain must be preserved.

## 1. Current implementation audit

The design decision is one focused, readable programming workspace. The learner's code occupies the largest uninterrupted surface. Task and Output are supporting regions that can be opened without discarding work. Practice and assessment are modes of this workspace, with permissions and evidence supplied by their existing services.

### 1.1 Source inventory

| Concern | Inspected source and relevant location | Current behavior |
| --- | --- | --- |
| Practice workspace | [challenge.blade.php](../../resources/views/challenge.blade.php), lines 1–24, 80–184, 239–355 | Full-width workspace flag; compact header; Task/Code/Output; collapsible/resizable panes; sticky actions; inline CodeMirror initialization. |
| Boss workspace | [assessments/show.blade.php](../../resources/views/assessments/show.blade.php), lines 10–143, 146–209 | Constrained page with Briefing, entry/pending/editor state, vertically separate preview, details sidebar; its own editor/Run script. |
| Lesson transition | [lesson.blade.php](../../resources/views/lesson.blade.php), lines 64–87, 90–155; [MissionController](../../app/Http/Controllers/MissionController.php), `challenge`, `knowledgeCheckGate` | Lesson shows target/starter read-only. Required Knowledge Check must be submitted before workspace/draft/submit/help access; correctness is not an additional invented gate. Experiment remains separate. |
| Practice data/actions | MissionController, `viewData`, `submit`, `saveDraft`, `hint`, `reveal` | Supplies description, draft, paid help, completion, XP costs. POST/redirect actions; first failure only in flash. Completion includes practice percentage and next URL. |
| Practice authority | [MissionService](../../app/Services/MissionService.php), lines 42–165; [MissionGradingService](../../app/Services/MissionGradingService.php), lines 31–125 | Structural checks plus active hidden behavioral tests when present. Infrastructure unavailable means no completion or XP penalty. First completion records Progress/version/skill mapping, awards once, deletes draft. Completed submissions return early without regrading. |
| Draft/input | [DraftService](../../app/Services/DraftService.php); [SourceCodeInput](../../app/Support/SourceCodeInput.php) | One mutable draft per user/mission. Required string/byte limit for Save and Submit; default bound 65,536 bytes. Validation rejection removes code from old-input recovery. No autosave endpoint or Boss draft. |
| Boss data/actions | [AssessmentController](../../app/Http/Controllers/AssessmentController.php), `show`, `showData`, `submit`, `retry` | Own latest attempt; Begin/Submit/Retry only. `canEdit` currently includes available; `canBegin` currently excludes it. No detailed failed-requirement result supplied. |
| Boss authority | [AssessmentService](../../app/Services/AssessmentService.php), `isEligible`, `isCourseReached`, `isUnlocked`, `beginAttempt`, `submitAndEvaluateAttempt`, `evaluateAttempt`, `retryAttempt`, `hasPassed` | Requires reached active course, active Boss, at least one mission, and every course mission completed. Synchronous structural grading. Submitted recovery uses stored code. Terminal attempts immutable; retry after pass or fail creates a fresh blank started row. Historical pass controls course completion. |
| Preview | [preview.js](../../resources/js/preview.js); [ADR-0002](../adr/0002-sandboxed-iframe-preview.md) | Practice uses language adapter; Boss writes raw source to srcdoc. HTML/CSS sandbox empty; JS permits scripts only. Run never calls grader or awards academic state. JS console is bounded inside iframe. |
| Editor/loading | [editor.js](../../resources/js/editor.js), [app.js](../../resources/js/app.js), lines 1–17 | Lazy editor bundle exposes `window.CodeQuest.CodeMirror` and ready event. Failure replaces host, leaving adjacent action controls enabled. No common editor-readiness controller. |
| Pane/keyboard | [workspace.js](../../resources/js/workspace.js) | Practice only: 1100px narrow breakpoint; roving tab focus, arrows/Home/End, pointer and keyboard resizing; pane state remains in memory. No saved pane preferences or dirty-exit guard. |
| Shell/style/components | [layouts/app.blade.php](../../resources/views/layouts/app.blade.php), lines 231–232; [student-header](../../resources/views/components/student-header.blade.php); [student.css](../../resources/css/student.css), lines 1, 44–67, 353–393; [app.css](../../resources/css/app.css), workspace/completion rules; [student-toast](../../resources/views/components/student-toast.blade.php), [status-message](../../resources/views/components/status-message.blade.php) | Production imports prototype styling; multiple workspace rule sets. Practice body is height-constrained at every width. Mobile primary navigation lives in account disclosure. Toasts carry most practice feedback; Boss uses panels. |
| Route boundaries | [routes/web.php](../../routes/web.php), lines 69–94 | Student/auth boundaries; named write limiters. No Boss Save/Hint/Reveal/timer/poll endpoints. Limiter responses are admission errors, not assessment attempt caps. |
| Existing regression seams | [WorkspaceControlsTest](../../tests/Feature/WorkspaceControlsTest.php), [WorkspaceBrowserTest](../../tests/Feature/WorkspaceBrowserTest.php), [MissionTest](../../tests/Feature/MissionTest.php), [MissionBehavioralGradingTest](../../tests/Feature/MissionBehavioralGradingTest.php), [AssessmentRecoveryTest](../../tests/Feature/AssessmentRecoveryTest.php), [CurriculumVersioningTest](../../tests/Feature/CurriculumVersioningTest.php) | Tests can anchor layout controls, local preview, source preservation, server authority, rollback/recovery, and historical thresholds. Inspected here, not run. |

Installed Laravel was verified as 13.30.1. Existing frontend uses CodeMirror 6 and Tailwind v4. The specification requires no dependency replacement, schema migration, new assessment policy, or production database write.

### 1.2 Verified rules and limitations

Practice Save is manual. Failed submission retains any existing saved draft, but does **not** save the newly submitted source automatically. Its submitted source is flashed back for rendering; there is no ordinary Challenge attempt/source archive in the inspected flow. Progress proves completion, not possession of the student's exact successful source. Do not promise historical code review or a durable result history that this backend cannot supply.

Practice initial code is old input, otherwise revealed solution, otherwise draft/empty. Thus solution reveal can fail to load on the immediate redirect because old input wins, then replace draft initialization on a later GET because revealed solution wins. The proposed help presentation must remove this ambiguity without altering reveal entitlement or grading.

Boss no-attempt/available states require Begin. Started permits submission; submitted permits verification of the stored source; passed/failed permits fresh retry if still unlocked. The legacy/schema `evaluated` state has no normal public transition. No timer, deadline, attempt limit, product cooldown, Boss draft, hint, solution, partial-credit policy beyond existing numeric scoring, asynchronous queue, or behavioral grader is supported. These are excluded from the design.

Boss normal submission and evaluation share a transaction. A storage/evaluation exception can roll the attempt back to started and return HTTP 500. A dropped response therefore proves neither receipt nor failure. Preexisting submitted recovery retains stored source if evaluation fails. Both cases require state reconciliation before another consequential action.

### 1.3 Fresh audit corrections and scope boundaries

The supplied earlier audit described a 24/44/32 split and stacked mobile practice panels. Those are not the current practice implementation. `workspace.js` already switches panes at 1100px; `student.css` initially collapses Output and lets Code fill the remaining space. Preserve that work. The confirmed layout inconsistency is the Boss page, which lacks the workspace flag and shared pane controls.

Current student navigation already uses Dashboard, Learn, and Boss Challenges in `components/student-header.blade.php`. The older Missions/Assessments navigation criticism must not be treated as a still-open desktop rename. The remaining mobile concern is discovery through the account menu. This iteration preserves the application shell and changes only contextual workspace navigation.

The Boss template initializes source with `old('code', $code)` even for terminal review. That can present returned input instead of the stored submission. Read-only configuration alone does not correct evidence provenance. Submitted/terminal source must come exclusively from the owned attempt, with old input permitted only for a legally editable started state.

Practice workflow copy says to Run before Submit, although the backend imposes no preview prerequisite. Replace it with optional preview guidance. Neither interface may turn a local preview into a gate or grade. Boss description is repeated in header and Briefing; use the header for identity/context and Task for instructions.

Stale font/color notes in `.ai/rules/css.md` describe an older theme. The current student stylesheet imports IBM Plex Sans/JetBrains Mono and semantic tokens from `docs/design/prototype/styles.css`, while `app.css` retains legacy terminal definitions. The redesign must consolidate production ownership rather than restore old fonts or copy the new skill's default palette blindly.

## 2. Challenge versus Boss divergence analysis

Classification names are explicit; one divergence can have several causes. Accessibility/responsive findings marked “risk” need runtime verification. No client-forgery exploit is inferred from a presentation defect.

| ID | Divergence / verified gap | Classification | Decision and evidence |
| --- | --- | --- | --- |
| D01 | Practice uses full screen/panes; Boss stacks editor/preview in a capped page | Unnecessary UI inconsistency; responsive issue | Same shell and pane geometry. Boss label and capabilities distinguish assessment, not smaller code area. Both views and master Screen I support reuse. |
| D02 | Practice language adapter versus Boss raw srcdoc | Technical implementation inconsistency | Use `previewDocument` for both. Raw CSS/JS does not provide equivalent previews. |
| D03 | Practice Run changes pane; Boss Run does not; separate control geography | Unnecessary UI inconsistency; responsive issue | Same persistent Run/Submit positions and Output activation. Domain-specific action labels only. |
| D04 | Practice draft/help versus no Boss equivalents | Intentional domain difference | Practice Save/Hint/Reveal; omit these in Boss. No disabled pseudo-Save, fake autosave, or timer. |
| D05 | Practice repeated Submit can repair unfinished work; Boss terminal Submit closed | Intentional domain difference | Keep practice correction in current workspace. Boss Retry creates new blank attempt. Completed practice Submit also short-circuits: do not advertise fresh validation there. |
| D06 | Practice runtime behavioral tests possible; Boss structural score/threshold | Intentional domain difference; backend-authority boundary | Share feedback format, preserve evaluators. Do not add Boss hidden-test counts or change grading policy. |
| D07 | Paid help/wrong-practice penalty versus first Boss pass reward | Intentional domain difference | Costs and actual ledger effects come from services. No Boss failure penalty or repeated-pass reward. |
| D08 | Terminal Boss editor remains editable under “Review your code” | State-management issue; unnecessary UI inconsistency | Read-only submitted/terminal source, selectable/copyable and locally previewable. Services already forbid changes. |
| D09 | available attempt gets Submit despite started-only service guard | Technical implementation inconsistency; state-management issue | Map available to Begin through existing start route. Do not loosen submit guard. |
| D10 | Toast-first practice feedback versus Boss status cards; help points “below” into hidden Task | Unnecessary UI inconsistency; accessibility issue/risk; state-management issue | Persistent Output → Feedback; help lives in Task → Assistance. One announcement owner per operation. |
| D11 | Import failure leaves enabled controls; no loader recovery payload contract | Technical implementation inconsistency; accessibility issue/risk; state-management issue | Shared readiness gate and source-preserving textarea fallback. Buttons never post initially empty hidden fields accidentally. |
| D12 | Initial auto-preview in both; no stale revision/result indication | State-management issue | Explicit Run model, no automatic execution on edit/load. Keep earlier output labelled with its source revision. |
| D13 | Save/reveal/submit redirects lose pane/focus state; input rejection strips source | State-management issue | Progressive enhancement preserves source in memory and explicit confirmed-save baseline; fallback warns about limits. Keep oversized source out of flashed server input. |
| D14 | Generic workflow steps masquerade as requirements; lesson starter/target absent from workspace; solution precedence ambiguous | Unnecessary UI inconsistency; technical implementation inconsistency | Reuse permitted public task/starter/target content, label workflow separately. Paid reference shown separately; no silent replacement of work. Never derive public instructions from hidden validator config. |
| D15 | Historical Boss score compared with today's threshold | Technical implementation inconsistency; backend-authority issue in evidence presentation | Use `passing_score_snapshot` for the recorded attempt. Current threshold belongs to new attempts. Do not re-score history. |
| D16 | “Course progress” completion percent and “Next Challenge” actually linking to Lesson | Unnecessary UI inconsistency; backend-authority issue in evidence presentation | “Practice challenges complete” and accurately named destination. Course completion needs historical Boss pass. Continue returns to updated Path per master contract. |
| D17 | Practice unavailable flag and test counts lost before public controller result; Boss exceptions lack friendly handled state | Technical implementation inconsistency; state-management issue | Project safe outcome categories explicitly, not by parsing failure text. Boss rollback/unknown response is unresolved, not failed. See sections 6–8. |
| D18 | Practice tabs/splitters versus none in Boss; body locked to 100dvh; no verified software-keyboard behavior/name | Responsive issue; accessibility issue/risk | Shared semantic controls, adaptive height/scroll, explicit editor name and focus tests. Preserve working practice keyboard resizing. |
| D19 | Boss terminal initial source can prefer old input over recorded code | State-management issue; backend-authority issue in evidence presentation | Stored source alone owns submitted/terminal review. An editable-state rejection may restore returned input. `assessments/show.blade.php` initialCode and AssessmentController.showData supply the evidence. |
| D20 | Practice instructions imply required Run; Boss repeats description in two regions | Unnecessary UI inconsistency | Task owns authored instructions; header owns identity. Run is optional in both. No preview prerequisite exists in either submission service. |

## 3. Shared workspace architecture

### 3.1 Common structure and policy

Both pages extend `layouts.app` with the existing role and workspace option. Both render one workspace with Header, Task, Code, Output, and Toolbar. Output contains Preview, optional Console, and Feedback. Feedback is not a fourth permanent pane. Entry/locked/unavailable states use this same header/task vocabulary with a state panel instead of an enabled editor.

| Capability | Unfinished practice | Completed practice | Boss entry/available | Boss started | Boss submitted | Boss passed/failed |
| --- | --- | --- | --- | --- | --- | --- |
| Read public task | Yes | Yes | Yes if access permitted | Yes | Yes | Yes |
| Edit current source | Yes | Yes, local exploration | No | Yes | No | No |
| Run local preview | Yes | Yes | No | Yes | Yes, stored source | Yes, recorded source |
| Save server draft | Yes | Omitted, matching current UI | No | No | No | No |
| Hint/reference reveal | Paid entitlement only | No new purchases; read permitted prior help | No | No | No | No |
| Submit | Yes | Omitted; already completed | No | Yes | No replacement; Resume verification instead | No |
| Begin | Not applicable | Not applicable | Yes | No | No | No |
| Retry | Edit and submit again | No academic regrade offered | No | No | No | Yes, when unlocked, including after pass |

Access gates override all capabilities. Controls reflect server permissions; HTML hiding/disablement never replaces enforcement. Boss mode always says “Boss Challenge” and “Submit assessment.” Practice says “Challenge” and “Submit challenge.” Use “Run preview” for both.

### 3.2 Independent state dimensions

Do not implement one giant enum for every combination. Maintain:

- Access: permitted, prerequisite missing, sealed, denied, unavailable.
- Editor: absent, loading, ready, fallback ready, failed; editing or read-only policy.
- Work: unchanged versus locally changed; practice draft absent/saving/confirmed/failed, or Boss no-draft capability.
- Preview: never run, preparing, current, runtime-error, stale, unavailable.
- Request: idle, saving/help/submitting/reconciling, rejected/unknown.
- Academic: practice unfinished/completed; Boss none/available/started/submitted/passed/failed; historical course pass separately.

Precedence: access restriction → unknown request reconciliation → persisted Boss state → editor readiness → active mutation → academic next action → preview/draft status. A preview error cannot overwrite a passed verdict. A failed retry cannot overwrite historical course completion. A saved draft cannot imply submission.

### 3.3 Data contract for later implementation

Construct an allowlisted workspace array/presentation object in the existing controller/service boundary. Do not serialize Eloquent models wholesale or query from components.

| Group | Required fields and ownership |
| --- | --- |
| Context | kind, title, permitted course/section labels, language type, return URL/label, Path URL; server-derived |
| Task | public description/instructions, optional starter/target examples, difficulty if supplied; permitted revealed hints/reference only |
| Capabilities | read/edit/run/save/submit/begin/resume/retry/help flags and approved named action URLs; server-derived |
| Work | initial source, source origin, practice confirmed draft source/time if available, required maximum bytes; never claim Boss draft persistence |
| Assessment | own latest attempt ID as read-only correlation metadata, status, stored-source identity/digest, permitted score, historical passed boolean, attempt version, recorded passing threshold where available, new-attempt threshold, reward eligibility; no rule snapshot |
| Feedback | operation category, safe messages, authoritative verdict, permitted summary counts, confirmed actual XP effect/balance, supported next action |

Client revision counters identify which local source produced preview/requests. They are presentation bookkeeping, never evidence accepted for grading. Practice source/result history remains unavailable across unrelated future visits unless a separately approved persistence feature is implemented. No new database table is required for this workspace.

### 3.4 Research applied

Use freeCodeCamp's [responsive mounted layouts](https://github.com/freeCodeCamp/freeCodeCamp/blob/main/client/src/templates/Challenges/classic/mobile-layout.tsx), Judge0's [editor-first execution](https://github.com/judge0/ide/blob/master/js/ide.js), PrairieLearn's [task/history separation](https://github.com/PrairieLearn/PrairieLearn/blob/f6731bf1ca287ac4de6a6f15dbfe2f8851107b85/apps/prairielearn/src/components/QuestionContainer.tsx), Oppia's [state-specific actions](https://github.com/oppia/oppia/blob/23369a83784002c749a706608917b9ef914ebbca/core/templates/pages/exploration-player-page/current-lesson-player/layout-directives/progress-nav.component.html), and CodeCombat's [contextual repair feedback](https://github.com/codecombat/codecombat/blob/e26d50e8ea466e7158b4bae3bcbb3ef84436c40f/app/views/play/level/tome/ProblemAlertView.coffee). Independently implement these principles in Blade/CodeMirror. Do not import their stacks, assets, client scoring, game economy, or hover-only progress details.

### 3.5 How UI/UX Pro Max informs this specification

Applied skill: [UI/UX Pro Max](https://github.com/nextlevelbuilder/ui-ux-pro-max-skill/blob/09170eec67eefd46a7ae85de61b40c194020f997/.claude/skills/ui-ux-pro-max/SKILL.md), pinned to `09170eec67eefd46a7ae85de61b40c194020f997`. Repository popularity establishes the requested selection threshold, not design correctness. The comparative research and CodeQuest's domain remain primary.

The local search tool was executed for these concerns. Recommendations were checked against the actual interface before adoption; generated output was not persisted as a second design-system authority.

| Search / returned pattern | Adopted adaptation | Rejected or constrained recommendation |
| --- | --- | --- |
| `programming education workspace dark --design-system`, then `developer tool coding workspace --design-system` | Readable dark surfaces, restrained density, sans prose and mono code | Hero/testimonials and FAQ landing layouts are unrelated to solving code. Reject both page structures, decorative glow, and automatic palette replacement. |
| `developer IDE --domain product` | Quiet tool chrome and strong editor hierarchy, supporting the repository research | No bento grid or dashboard metrics around the editor; no new blue identity imposed by the catalog. |
| `error summary validation --domain ux` | Persistent, focusable error summary linked to the source, plus inline field error | A toast cannot be the only recovery path. Do not announce the same error from multiple regions. |
| `keyboard focus tabs --domain ux` | Visible focus and unobscured controls; explicit tab and editor behavior in section 10 | Generic focus advice does not replace the WAI tab contract or authorize shortcut conflicts. |
| `dragging movements --domain ux` | Keyboard resizing plus single-pointer preset buttons | Keyboard support alone does not satisfy the single-pointer alternative to dragging. |
| `responsive focus validation --stack laravel`, retried as `form validation --stack laravel` | Exact server field errors, source preservation within the mounted editor | First query returned no match. The retry returned old-input guidance, which cannot override SourceCodeInput's deliberate removal of rejected source. No oversized source flashing. |

Use the skill's search database as guidance, not as a new business specification. Its generated frontend defaults cannot change thresholds, source retention, academic evidence, submission rules, or backend ownership.

## 4. Desktop layout

```text
Existing compact student application header
Back to Lesson / Boss Challenges   Title + course/section   Mode / recorded state
Task toggle                       Code                    Output toggle
+------------------+--------------------------------------+---------------------+
| Task             | Code + source/draft status            | Preview Console     |
| Objective        |                                      | Feedback            |
| Public brief     | ONE CodeMirror instance              |                     |
| Requirements     |                                      | Active output mode  |
| Constraints      |                                      | / result summary    |
| Examples         |                                      |                     |
| Assistance [P]   |                                      |                     |
+------------------+--------------------------------------+---------------------+
Help [P]   Save draft [P]   confirmed state      Run preview   Submit [mode/state]
```

Use the full available content width and remaining height. No max-1180/1280 wrapper inside the workspace, full lesson article, course map, metric sidebar, or nested card grid. Thin pane separators, one pane heading, and one toolbar establish structure. The title has a full accessible name and an expand/read route for long visible text; truncation alone cannot hide task identity.

Default ≥1101 CSS px: Task open at 320px, Code fills the remainder, Output collapsed until opened or Run/Submit needs it. Task bounds 280–360px. Editor target is 60–70% or more while coding, minimum 560px in multi-pane mode. Open Output starts at 320px, bounds 280–480px. Separators are 8px visually with a larger hit area and keyboard controls. At 1440px, Task 320 + Output 280 + handles 16 leaves Code 824px; therefore three-pane view is permitted but not the default. Do not promise 60–70% editor width with both side panes open at every laptop width.

Three panes require measured width sufficient for their minima. At 1101–1199px, opening Output replaces the side Task pane; both cannot remain open if Code falls below 560px. At larger widths opening Output retains Task only if minima hold. Task/Output toggles remain available; Code cannot be collapsed in desktop split mode. Reset restores Task+Code, Output closed; never clears source/results. Resize preferences contain only geometry, keyed by workspace kind; clamp them on resize. Never persist learner source in geometry preferences.

Header starts around 56–64px plus the existing application header. Toolbar controls have 44px targets; pane headings 40–44px. Use flex/grid `min-height: 0` rather than subtracting guessed fixed header totals. At insufficient usable height or high zoom allow page scrolling with sticky, non-overlapping actions. A full-height screenshot is not grounds to trap content below a hidden body scrollbar.

Production tokens own surfaces, borders, text, focus, success/warning/failure/info. Prose/body/task text uses sans at roughly 15–16px/1.5–1.7; code uses existing mono around 14–16px with zoom support. Metadata may be smaller but essential feedback must remain readable. Use System 404 branding and restrained status accents; no scanlines/glow over CodeMirror, instructions, results, or controls. Refactor prototype imports and duplicate workspace rules as part of the later change.

### 4.1 Visual hierarchy and production token contract

Retain the current student palette as the initial implementation candidate, with explicit roles below. These values are design requirements for this workspace, not a claim that every existing rendered component already meets contrast. Move the semantic definitions into production CSS during implementation, with temporary aliases for other student surfaces. Do not add a parallel theme import or a new font/network dependency.

| Role / proposed production token | Value / use |
| --- | --- |
| Canvas / `--color-canvas` | `#0a0d0c`; application backdrop |
| Workspace / `--color-surface` | `#0f1412`; continuous work surface |
| Raised / `--color-raised` | `#151b18`; pane headers, toolbar and disclosures |
| Text / `--color-fg` | `#e6eee9`; title, instructions and essential feedback |
| Supporting text / `--color-fg-muted` | `#a3b0a8`; context and explanations |
| Quiet metadata / `--color-fg-subtle` | `#7a877f`; secondary metadata only, no opacity reduction |
| Decorative divider / `--color-line` | `#222b27`; optional grouping lines, never sole affordance |
| Control boundary / `--color-control-line` | `#62756b`; required field/control edges where an edge communicates operation |
| Progress/success/current / `--color-accent` | `#56db8e`; primary legal academic action, current pane and confirmed success |
| Filled accent text / `--color-accent-ink` | `#04140b`; dark text on accent button |
| Warning / `--color-warning` | `#e5b64a`; stale or unconfirmed work, service attention |
| Failure/destructive / `--color-danger` | `#f07a70`; input or assessed failure, destructive confirmation |
| Information / `--color-info` | `#62c8dc`; local-preview distinction and neutral instructions |
| Focus / `--color-focus` | Alias accent; 2px outline with 2px offset on controls, inset if clipping would hide it |

Calculated opaque token-pair ratios: foreground/surface 15.74:1, muted/surface 8.27:1, subtle/raised 4.66:1, accent-ink/accent 10.74:1, warning/surface 9.85:1, danger/surface 6.84:1, info/surface 9.58:1, control-line/raised 3.56:1, focus/raised 9.92:1. These calculations were performed for this revision. Actual opacity, overlays, syntax themes, adjacent colors, and focus clipping still require browser measurement.

Use IBM Plex Sans with system-sans fallback for title, Task, feedback, forms and action text. Use JetBrains Mono with system-mono fallback for code, numeric XP/score and concise metadata. No uppercase paragraphs. Title 20px/28px semibold; pane heading 16px/24px medium; instructions and result body 16px/26px; action text 14px/20px medium; metadata 12px/18px; editor 15px/24px initially, respecting user zoom. Keep the full accessible title when visual space is tight.

Spacing uses 4/8/12/16/24px increments. Pane content has 16px padding, related content 8px separation, independent Task sections 24px separation. Controls have at least 8px separation. Corners are 2px for small controls and at most 4px for confirmations. Workspace panes share flat boundaries without shadows or nested cards. A result is one summary plus details, not a metric wall.

Visual priority is code and objective first, Run/academic action next, then supporting status. Only the legal academic primary action uses a solid accent fill. Run uses an outlined secondary treatment; Help/Save are quiet controls. Do not use amber for assessment branding or difficulty decoration because it means attention. Boss mode has a text label, assessment state and recorded evidence, with the same colors and typography as practice.

At 1440px available workspace width, default Task 320px plus one 8px separator leaves Code 1112px. With Task 320px, Output 280px and two separators, Code is 824px. Use actual content width rather than screen width in these calculations. Below 1136px available width, both side panes at their minimum plus Code 560px cannot fit; switch to the two-region rule even if the viewport breakpoint suggests desktop. Do not hide overflow to conceal violated minima.

There is one source document today. Label its editor heading with the authoritative language, such as HTML source; do not invent clickable multi-file tabs, filenames, a terminal, a build step, or additional editable files. Add file tabs only if the domain later supplies multiple files.

## 5. Responsive and mobile layout

| Width / condition | Composition | Control behavior |
| --- | --- | --- |
| Wide desktop, sufficient minima | Task + Code; optional Output with bounded resizing | Footer help/save left; Run/Submit right. Result opens Output; Code remains visible. |
| Narrow laptop >1100px | Two visible regions at most if minima would be violated | Task and Output share the side region; no third squeezed pane. |
| Tablet/narrow ≤1100px | Task / Code / Output tabs, one active mounted pane | Default Code for editable work, Task for entry, Feedback inside Output for pending/terminal result. No split-pane drag targets. |
| Phone ≤640px | Same three tabs; Output internal Preview/Console/Feedback selector | Primary Run/Submit row stays reachable. Practice Help/Save secondary row, absent when unsupported; never leave empty Boss slots. |
| Short viewport/software keyboard | Active pane remains usable; compact metadata; page/active-panel scrolling allowed | Toolbar participates in usable viewport layout, not over the caret. If two sticky rows leave too little space, secondary Help moves to Task and toolbar becomes one row. |

Keep editor document, undo, selection, scroll, preview session, current feedback, and dirty state when switching tabs or crossing breakpoints. Hidden panes are excluded from focus navigation, not destroyed. Background result arrival cannot erase editor content or silently move focus from a different control.

On explicit Run, select Output → Preview and expose Console if relevant. On Submit result, select Output → Feedback. Task help success selects Task → Assistance and focuses the newly revealed heading. Provide Return to Code adjacent to feedback and help. Mobile pane tab counters may show “1 error” only when that count is actual and named, not a decoration.

Use one responsive DOM and navigation definition. Test 320, 390, 430, 768, 1024, 1100, 1280, and 1440 CSS px, landscape, zoom, and real software-keyboard behavior. Treat `100dvh` as an input to layout, not proof the virtual keyboard is handled. Prefer a flow layout/scroll fallback to adding fragile viewport-offset patches.

### 5.1 Narrow-screen composition

```text
Return link     Title / mode
Task            Code             Output
---------------------------------------
ONE active pane, with its own heading
Code: editor + draft/source status
Output: Preview | Feedback [Console if available]
        persistent result and Return to Code
---------------------------------------
Help [practice]  Save [practice]  status
Run preview                     Submit
```

At 320px, keep title/context above the three equal-width tabs. Return text may shorten to Lesson or Boss Challenges, with the accessible name retained. XP and threshold move into the relevant Task/Feedback section before action targets shrink. Button labels may wrap within their 44px minimum-height controls; never clip Submit assessment. At 641–1100px use a single toolbar row when it fits. At 640px and below use the two-row arrangement above for practice; Boss uses one row and a source-status line because Help/Save are absent.

For Feedback on a phone, place the result heading, current-source association and next action before detailed messages. Details scroll within the active panel. Return to Code restores the caret; Task links reveal instructions without resetting that caret. Do not place a completion overlay or assessment sidebar over the only editable pane. Use safe-area padding and scroll-padding matching actual sticky elements. If browser keyboard behavior prevents adequate usable height, allow document scrolling rather than forcing another fixed overlay.

## 6. Interaction model

### 6.1 Initialization and source

Render safe initial source in a labelled textarea before CodeMirror loads; that textarea is a usable fallback, not a hidden empty payload. Ready means the mounted editor **or** fallback has a synchronized source value and legal capabilities. Show “Loading editor” while enhancing. A loader failure says “Advanced editor unavailable. Your code is available in the basic editor.” Retry enhancement must not replace current fallback edits. If no usable source control exists, keep Run/Save/Submit/help disabled and offer recovery; never silently post an empty default.

Use one source-bearing native form with a single successful `code` control and action buttons targeting the existing routes through `form`/`formaction`. Begin/Retry can remain separate CSRF forms. CodeMirror updates that same control; fallback/no-JS editing submits it directly. Do not retain several initially empty hidden payloads as the no-JS path or submit duplicate `code` fields. Local Run requires JavaScript; native Save/Submit/help still use existing server routes when permitted.

Practice initial source priority: approved submitted/returned source from the current operation, otherwise saved draft, otherwise empty. The public starter is an optional explicit “Use starter” action, with replacement confirmation if code differs. Paid reference is displayed separately, never silently inserted on load. Preserve entitlement and optional copy-to-editor with explicit replacement confirmation. No starter/reference insertion awards completion or changes rules.

Boss started source is the server-supplied started source if present, otherwise empty. Retry opens blank exactly as today; do not preload the prior attempt. Submitted/terminal Boss source is always its stored source, never old input or client replacement. Legacy unknown state displays a diagnostic state and permitted Back action, not an editable assessment.

### 6.2 Run and edits

Run preview captures the current exact source and revision, prepares its language-specific iframe, then exposes Output → Preview. It never invokes Save or Submit. No automatic execution on load or keystroke; the empty preview explains “Run preview to see your output.” This replaces today's initial automatic preview intentionally, so both pages have a predictable explicit execution model.

Editing changes dirty state and marks existing preview stale without rerunning it. Saving does not run. Unsaved source can be previewed and submitted; a preliminary Save is not required. If source is empty, Run may show an empty document with “No output,” while required Save/Submit validation remains distinct. Runtime failure never charges XP.

### 6.3 Save and leaving

Practice Save captures source/revision, uses the existing draft service, and says Saved only after confirmation. The confirmed baseline is the acknowledged source. If the learner edited while saving, show “Earlier version saved; current changes unsaved,” without replacing newer code. On failure retain source and offer Retry save. A successful academic completion clears the draft according to the service; “No draft retained after completion” is distinct from a failed save.

No implicit autosave is promised. In-app dirty exit presents Stay / Save and leave / Leave without saving for unfinished practice; Save and leave navigates only after confirmed save. Empty/invalid source cannot be silently saved against the current required-input rule; explain the issue and offer Stay or deliberate Leave. Boss started has Stay / Leave without saving, with “This attempt has no server draft; edits will be lost on reload.” Leaving does not submit, finalize, abandon, or create an attempt.

Use a conditional browser exit warning as a fallback for reload/close. It is not a guarantee against device crash. Source stays in memory through enhanced requests and pane switching. Persistent browser source storage, downloadable backups, Boss drafts, and automatic Save-before-exit are outside this iteration unless separately specified. Geometry preferences are allowed; source storage is not implied.

### 6.4 Paid assistance

Practice toolbar has Help and Save; Help opens Task → Assistance. Show next hint cost, balance, remaining revealed/available hints, and solution cost from server data. New revealable hints must respect both authored hint count and the existing maximum of three. Already entitled hints/reference are readable without another debit. No purchases on completed practice or any Boss state.

Before a paid action, disclose the actual cost and confirm it; solution reveal also says it does not complete the Challenge. Preserve editor source through successful and rejected help requests. On insufficient XP keep code unchanged and place cost/balance explanation beside the help control. On success reveal/focus the content in Assistance and update confirmed server balance. Do not claim cost was zero unless the service confirms prior entitlement. Do not implement a new unlimited-help policy.

### 6.5 Submission, verification, and retry

Practice Submit captures current source without requiring a preview, synchronizes the payload, and marks “Checking submitted code.” Boss Submit assessment first provides a small inline review/confirmation: current source will become this attempt's recorded submission, score is decided by the server, and a later change needs a fresh retry. Confirmation does not start a timer or forbid leaving beyond existing rules. Begin and Retry are explicit POSTs, never GET side effects.

During a Submit/Begin/Retry/Resume/help mutation disable competing mutation controls. During academic Submit/Resume also disable Run and freeze source editing temporarily, retain selection, and keep read/copy/navigation usable. Do not abandon a POST through a client Cancel button that implies rollback. During Save alone editing and Run may continue; disable competing writes until it settles. Repeated clicks/shortcuts cannot create concurrent mutations in the same workspace.

Request validation rejection means the request was rejected, not academically failed. Display the real source error, keep the editor source, return editing permission, and focus its summary/editor link. Show byte count using UTF-8 bytes if advising a limit; never use character length as the server bound. Handle 429 with server Retry-After and a visible retry-until message, not a product attempt cooldown; 419/session expiry preserves local source and says no confirmed receipt; 403/eligibility change follows access/reconciliation behavior rather than retrying blindly.

On confirmed practice pass, render persistent Feedback containing completion, actual XP awarded/balance, and practice count. A compact completion panel offers Continue to the updated Learning Path and Review current code. Default is inline, not a mandatory modal; do not auto-launch the next Challenge. Server chooses permitted destination/anchor. If a secondary next-Lesson shortcut is offered, name it “Next lesson,” never “Next Challenge.”

On confirmed practice rejection, remain editable, show safe failures, retain the existing draft and current code, and expose Submit again. On completed practice, Run remains local exploration but Submit is omitted; existing completed submissions are not regraded. Do not label exploration as a new assessed pass.

Boss normal successful POST returns passed/failed synchronously. Render stored source read-only, authoritative score and recorded threshold, and legal Retry/Continue. “Submitted” is used only when the server actually reports a stored submitted attempt; it means “Submission recorded; verification required,” not “A worker is running.” Resume posts the stored source as required by the current input contract, but the service ignores replacement code. No automatic polling or replay.

After network timeout/dropped response/unexpected 500, say “We could not confirm the result,” preserve source, and offer Check status. Add an allowlisted representation to the existing authorized GET routes for enhanced reconciliation; retain HTML redirect behavior for ordinary forms. For Boss: submitted means Resume stored verification, terminal means Review the recorded result, and locked means no new mutation. Started is only the current snapshot: the original POST may still be running and commit later. It does not prove rollback or nonreceipt. Keep the unresolved outcome visible; any deliberate subsequent Submit must explain that an earlier request may still finish, and service guards remain the backstop. No automatic replay.

Correlate the captured operation with the server-returned own attempt ID and stored-source identity. These are read-only metadata, never accepted as a client target/verdict. If another tab has opened a newer retry, show “The current attempt has changed; refresh its status” rather than attach its verdict to earlier code. A code-only/latest-attempt POST cannot guarantee conditional targeting across tabs: preflight status checking is advisory, not a transaction lock. Do not claim that UI checks solve this backend limitation. Any stronger conditional-submission contract requires a separately reviewed backend precondition; this specification preserves existing target resolution.

For practice: completed means reconcile the fact of completion without re-award, not proof that the captured code caused it if another tab also worked on the task. Unfinished does **not** prove whether an unsuccessful request already charged XP. Show current server balance and an explicit Submit again choice, never auto-repeat the request or promise no prior charge.

Boss Retry is available only for latest passed/failed plus current unlock permission. It creates a new blank started row. It does not amend past score/code, revoke historical completion, or re-award first-pass XP. No unsupported retry cap or deadline copy. Keep the existing shared admission limiter; UI protection supplements it.

### 6.6 Primary-action resolution and concurrency

Resolve the toolbar from server capabilities in this order. Access denied or missing prerequisites offers Back/prerequisite navigation; unknown submission offers Check status; Boss submitted offers Resume verification; Boss entry offers Begin assessment; terminal results offer Continue or Retry; editable work offers Submit for its mode. Editor failure removes source-dependent actions unless the synchronized fallback works. Save and local Preview are supporting operations and never change the academic primary action.

The primary action is disabled, with an adjacent reason, during a conflicting mutation. Busy labels describe the operation: Saving draft, Checking submitted code, Beginning assessment, or Opening retry. The spinner supplements text. Do not replace every control with a spinner or remove the return link.

Every preview, save and submission captures its own immutable local source/revision. A completion handler may update only the matching operation's status. A delayed Save acknowledgement cannot clear dirty state for later edits. A late console event cannot affect a newer Run. A returned grading result is associated with the submitted snapshot even when the current editor differs. Source-origin text is explicit: Saved draft, Current edits, Recorded submission, or Current local exploration. These are frontend correlations, never client authority over the server's resolved assessment attempt.

Accessible form error summaries occupy the beginning of the active source form's feedback region. On narrow screens the error link first opens Code, then focuses its source control. Do not place a top-of-page error above hidden panels and assume it is discoverable.

## 7. Preview and output model

### 7.1 Output modes

| Mode | Content | Authority |
| --- | --- | --- |
| Preview | HTML document, CSS fixture rendering, or JS fixture/console environment for the exact captured source | Local non-authoritative rendering |
| Console | Bounded JS log/warn/error/runtime diagnostic text; only when instrumentation exists | Untrusted preview feedback, never grading evidence |
| Feedback | Server request rejection, grading unavailable, safe failed requirements, completed result, or stored Boss outcome | Academic meaning only from trusted server result |

Preview and Feedback remain independently available. Running after a failed submission must not erase its feedback. Editing after a result marks it “Result for previously submitted code”; it does not change the historical result. Output defaults to Feedback for stored Boss outcomes, Preview after explicit Run, and an empty-state explanation before any operation. If Console adds no information for a language, omit it rather than render an empty decorative tab.

### 7.2 Language adapter and security

Both views call the same production preview adapter. HTML renders the source; CSS installs source as stylesheet over the existing labelled sample fixture; JavaScript executes within its own sandboxed document with bounded console capture. Label the CSS fixture “Sample preview; assessment requirements may use different content.” Do not use solution code or target HTML automatically as a fixture without an authored/public contract. Preserve the current 100-line and 2000-character-per-entry console bounds, with visible truncation indication.

HTML/CSS iframe sandbox has no capabilities. JS has `allow-scripts` only. No same-origin, forms, top navigation, popups, downloads, or parent DOM access. Source embedding uses the existing safe encoding principles. CodeMirror syntax color is not a compiler. There is no build pipeline, HTML/CSS lint verdict, guaranteed runtime timeout, or Boss behavioral executor to advertise.

For host-visible JS diagnostics, a small instrumented message channel may project bounded log/error records from the active iframe. Validate source window, per-run identifier, message shape/length/count; opaque sandbox origin alone cannot identify the correct frame. Discard older-run messages and display text safely. Messages remain untrusted even if well-shaped and can never update verdict, XP, completion, or access. If line/column attribution is not trustworthy, show “Location unavailable,” without an invented editor marker. This adds no iframe privileges and is a presentation change, not a grading protocol.

### 7.3 Run lifecycle and stale output

| Event | Required behavior |
| --- | --- |
| Start Run | Capture source/revision, mark Preparing preview, disable duplicate Run, select Output → Preview. Preserve Feedback. |
| While preparing | Keep last preview visible with “Previous preview; updating.” First run shows a lightweight busy/empty state. Never show a blank panel as if output was deleted. |
| Frame load | Replace visible preview and identify its revision. Say “Preview loaded,” not “Challenge passed” or “All requirements satisfied.” Empty output is a valid preview state. |
| JS syntax/runtime/rejection diagnostic | Mark Preview error, display safe diagnostic/Console, keep rendered partial output where available. A labelled Previous preview disclosure retains the preceding run's source/console record, not a guarantee of frozen visual DOM or a silent re-execution. No academic state changes. |
| Frame preparation/load failure | Retain previous preview and safe explanation, re-enable Run, offer Try preview again. Any preparation timeout is a UI availability timeout, not a guarantee that an infinite program was terminated. |
| Edit after Run | Keep output but mark “Preview is from earlier code. Run preview to update.” Draft save does not clear this indicator. |
| Late async diagnostic | Attach to matching run only. It can change that preview's diagnostic state after load; never reinterpret “loaded” as guaranteed error-free execution. |
| Switch panes/resize | Keep active preview and code instance. Pause no academic lifecycle and submit nothing. |

Do not add a Stop control unless implementation can genuinely stop that preview environment. Browser iframe isolation is not the trusted grader's resource isolation. A failed/local preview does not block authoritative submission except for ordinary request/source readiness validation.

## 8. Feedback and recovery model

### 8.1 Placement and severity

| Situation | Severity and placement | Learner explanation / recovery |
| --- | --- | --- |
| Input missing/oversized/wrong type | Error at Code source status plus Output → Feedback summary | Exact rejected field/reason; Return to Code; no academic failed-attempt label |
| Preview syntax/runtime error | Error in Console/Preview status; optional trusted location action | What the browser reported; Go to line only with reliable metadata; edit and Run again |
| Safe failed requirement | Academic failed result in Feedback; anchor to a public requirement only if a safe ID exists | What requirement was not met, server message, repair action; no raw regex/answer/test input |
| Partially correct work | Informational evidence within the authoritative result, not a separate optimistic pass | Permitted counts or Boss score; final pass/fail remains authoritative; no invented percent for practice |
| Genuine practice failure | Error result, neutral supporting penalty text | Existing wrong-submission policy; actual debit/balance if server supplies it, clamped ledger amount rather than always claiming −10 |
| Grading unavailable | Warning/service error in Feedback | “Code was not graded; no XP changed” only when server explicitly confirms this category; retry later |
| Unknown/network/server response | Warning in Feedback and toolbar status | “Result not confirmed”; preserve source; Check status before deciding next mutation |
| Hint/reference purchase | Informational confirmation at Task → Assistance | Show entitled content, cost/balance confirmation, Return to Code; insufficient XP next to purchase |
| Draft saving/saved/failed | Quiet live status in Code header and toolbar | Confirmed persistence, retained unsaved source, Retry save; never a completion toast |
| Practice/Boss pass | Success summary in Feedback, completion panel inside it | Record supported accomplishment and actual reward; Continue/Review |
| Locked/prerequisite/permission | Neutral access state at entry/header; warning only if access changed mid-work | Server reason and allowed Path/Hub navigation; no leaked hidden task/source |

Blocking request/access problems take priority over local warnings; academic outcome remains independently visible. Use text plus icon and semantic color. Red means failure/destructive, amber attention/recovery, cyan information, phosphor confirmed success/current. Do not make an infrastructure incident look like a red academic fail. Toasts may acknowledge a secondary event but never be the only copy of task feedback.

### 8.2 Safe detail and missing data

Practice public task content is description, permitted target/starter examples, and already entitled assistance. Today's “Write / Run / Submit” list is workflow, not assessed requirements. There is no inspected public structured requirement collection. Render author-provided public prose; a RequirementList subcomponent is optional only when an approved public collection exists. Do not parse hidden rules into an upfront task checklist or show every check green based on the preview.

Practice `MissionGradingService` has safe failures/unavailable/test totals internally, but `MissionService` drops category/counts and the controller takes only the first message. Later implementation must propagate an explicit safe category and permitted summary to the presentation boundary, with all approved failure messages rather than guessing from prose. Do not expose raw tests/rules/transport payloads. Existing hidden-test count feedback can remain a summary without hidden cases. No additional practice numeric score is invented.

Boss persists score/verdict/source/threshold/version, but does not persist per-requirement feedback. First implementation shows those facts and public instructions for review. Do not reconstruct historical failures by rerunning stored source against today's rules or expose `grading_rule_snapshot`. Richer historical feedback requires a separately reviewed data/policy change; it is not a dependency of the shared workspace. Malformed Boss rule handling is an existing backend issue, not permission for this redesign to reclassify or rescore recorded attempts.

Assessment instructions are also read from the current authored assessment, not a persisted public-instruction snapshot. If recorded attempt version differs, label them “Current task instructions” and state that the recorded result belongs to its evaluated version. Do not present today's prose as the exact historical requirements. Submitted recovery continues to follow the existing service's evaluation behavior; the UI must not invent version-frozen grading at Begin.

Persistent means feedback stays in the active workspace until superseded and stored Boss results can be restored. It does not mean ordinary Challenge source/result history magically exists on a future visit. A saved draft is an editable backup; a recorded Boss submission is immutable evidence; those labels never interchange.

### 8.3 Concrete recovery and result compositions

| Scenario | What the learner sees | Next action and implementation boundary |
| --- | --- | --- |
| Practice returns one safe structural failure | Feedback heading Challenge not yet completed, the approved failure text, and Result for submitted code; source remains in Code | Return to Code, fix and Submit again. Do not invent a line, failed checklist row, or percentage from message text. |
| Practice returns permitted hidden-check totals | Confirmed passed/total summary beside the final verdict, followed by safe public failures | Show only approved aggregate counts. No hidden fixtures, output expectations, names or answer patterns. Passing some tests does not imply completion. |
| Boss fails with score 60 and recorded threshold 70 | This attempt failed; Score 60%; Passing threshold for this attempt 70%; read-only recorded source | Retry assessment opens blank code. Public instructions are review context, not a reconstructed explanation of each failed grading rule. |
| Boss passes at 80 with threshold 70 | This attempt passed; score/recorded threshold; Course completed when historical pass is confirmed | Continue to Path is primary. Retry remains a secondary permitted action. Do not render 80% as mastery or as course completion percentage. |
| Latest Boss retry fails after a prior pass | Latest attempt failed, separate Earlier course pass retained, no new XP award | Continue to Path remains primary, Retry secondary. Do not turn the course badge red or lock the next course on this result. |
| Source exceeds the actual configured byte limit | Submission rejected, exact server byte-limit text, a link to Code, and inline associated error | Editing remains available. No failed-attempt score, XP penalty claim, or truncated replacement of the learner's code. Local byte count is advice; server validation wins. |
| Submission response drops | Result not confirmed, preserved source, Check status and safe recovery guidance | Reconcile with an authorized read. Do not retry automatically or claim grading failed. Preserve the distinction between sent, recorded and evaluated. |
| Behavioral grader is explicitly unavailable | Code could not be graded, approved service explanation and no-XP-change statement only when confirmed | Retry later is explicit. Keep previous local output and feedback available without passing or failing the new source. |
| Hint purchase succeeds | New entitled hint inside Task → Assistance, confirmed cost/balance, Return to Code | Focus the new heading. The editor remains untouched. The same content is available without a repeat charge. |
| Solution purchase succeeds | Reference solution disclosure, This does not complete the Challenge, optional Copy to editor | Confirm replacement if current source differs. Never auto-load the reference into the student's work on redirect or future GET. |

For requirements with no safe public ID, group failures under Submission feedback and link to the Task instructions as a whole. A public requirement gets Pending, Met or Not met only when an authoritative result maps to that requirement explicitly. Do not infer these states from iframe rendering. For runtime diagnostics, expose Go to line only for reliable attribution to the captured source, and mark that diagnostic stale if the document has since changed.

## 9. Complete state matrix

P = practice; B = Boss; both = equivalent permitted coding states. “Legal actions” means the capability intersection in section 3, not a promise that unsupported actions appear. Disabled actions use native disabled state while temporarily busy; permanently unsupported actions are omitted. All rows include the shared Header; Task remains readable only when authorized. Editor/output policies are independent, so an unsaved draft plus stale preview plus an earlier failure can coexist.

### 9.1 Shared interaction states

| State / applies | Visible components | Primary action | Secondary actions | Disabled / omitted | Status text | Feedback location | Editor behavior | Preview/output behavior |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Initial, authorized both | Task, source control, toolbar, empty Output | Wait for usable source control | Back, read Task | Source-dependent actions until synchronized | Loading editor | Code header | Initial source retained; enhance textarea | Never run; no automatic execution |
| Loading editor, both | Same plus loading indicator | Use basic editor when synchronized | Retry enhancement, Back | Only actions lacking usable source; no empty hidden submission | Loading advanced editor | Code | Fallback editable or read-only per policy | Prior output retained if reinitializing |
| Editor failed, both | Basic editor/recovery and Task | Use basic editor; otherwise Reload after warning | Copy source, Retry editor | Source actions if no synchronized fallback | Advanced editor unavailable | Code alert, not academic result | Preserve fallback edits; no reset | Preview available only if adapter ready |
| Ready, P unfinished/B started | Task/Code/Output and legal toolbar | Submit challenge / Submit assessment | Run preview; P Save/Help; Back | Unsupported capabilities | Ready to edit | Code header | Editable | Empty or prior result |
| Editing, both editable | Same | Submit appropriate mode | Run; P Save/Help | No terminal/unsupported actions | Editing | Code | Undo/selection intact | No automatic update |
| Unsaved, P/B started | Dirty/source state | Submit; P may Save first | Run, Back with guard | B Save/Help omitted | P: Unsaved changes; B: Not saved; no server draft | Code + quiet toolbar | Editable | Any existing preview labelled stale if changed |
| Saving, P | Task/Code, Save busy state | Saving, not another request | Edit, Run, read/copy | Save/Submit/help until write settles | Saving draft | Code/toolbar live status | Editable; acknowledge captured revision only | Preview unchanged |
| Saved, P | Confirmed draft status | Submit challenge | Run, Help, Back | None beyond capability policy | Draft saved, or Earlier version saved; current changes unsaved | Code/toolbar | Newer edits never replaced | Saving does not refresh preview |
| Save failed, P | Error next to Save | Retry save | Edit, Run, Back with guard | Duplicate request until settled | Draft not saved | Code/toolbar + safe detail | Source retained | Existing output retained |
| Running/preparing, both canRun | Output → Preview, toolbar busy | Preparing preview | Read/copy, Return to Code | Duplicate Run; academic Submit until preparation settles | Preparing preview | Preview header/live status | Editable when academic request idle | Previous preview labelled updating; first run busy state |
| Run success/load, both canRun | Preview and available Console | Submit if editable; otherwise legal Continue/Retry | Run again, Return to Code | Terminal Submit; unsupported mutations | Preview loaded; not checked for completion | Preview header | Policy unchanged | Captured revision current only if no later edit; otherwise stale; no pass claim |
| Run error, both canRun | Preview diagnostic/Console | Return to Code if editable; review source if read-only | Run again; Submit remains legal if editable | No academic lock derived from runtime error | Preview error | Console/Preview | Editable or read-only by academic state | Partial output/error retained; previous-run record available |
| Empty output, both canRun | Output explanation | Run preview | Edit/read source, Submit if legal | None from emptiness alone | No output yet / Program produced no console output | Preview/Console | Policy unchanged | Empty ≠ failed; do not fake DOM/test results |
| Stale preview, both editable | Old output + revision indicator | Run preview | Submit current code; P Save | None from staleness alone | Preview is from earlier code | Preview header | Editable | Old output retained and explicitly stale |
| Request validation failure, both editable | Error summary + source field association | Return to Code / fix source | Run, P Save if valid, Back | Retry submission only until source/request valid | Code is required / actual byte-limit or other rejection | Feedback + Code status | Retain source; editable | Previous output/result retained |
| Partial evidence, where supplied | Authoritative Feedback summary | P: repair/Submit again; B: verdict's Retry/Continue | Read public requirements/source | No inferred pass/action from percentage | P: N of M hidden checks passed if supplied; B: scored X%, recorded verdict | Feedback | P editable; B terminal read-only | Preview independent; no invented passing rows |
| Submission pending, both | Feedback status + frozen source | Checking submitted code | Read/copy, guarded Back | Run and all competing mutations | Checking submitted code | Feedback/live status | Temporarily read-only, snapshot retained | Previous preview/feedback visible; not labelled queued |
| Mutation service/network error, both | Recovery summary | Check status after ambiguous submission; Retry save after confirmed save rejection | Copy source, read Task, guarded Back | Academic repeat until reconciliation; no auto-replay | Result not confirmed / actual safe error | Feedback or owning action | Retained; submission freeze released after legal state known | Previous output retained, never replaced by an infrastructure “fail” |
| Submission rejected by current state, both | Current server state + rejected operation explanation | Legal action for returned state | Back, copy permitted source | Rejected mutation | This operation is unavailable in the current state | Feedback | Server mode takes precedence | Prior preview retained if still permitted |
| Permission/session failure, both | Safe recovery state | Reauthenticate or Back where supported | Preserve/copy current local source | All academic mutations until authorized again | Session expired / Access unavailable | Feedback/access state | Local recovery only; no new protected data fetched | No new run after permission revocation; historical output not treated as access authorization |
| Locked/sealed, both | Authorized entry/path state with reason; no editor | View Learning Path / Boss hub | Existing permitted prerequisite link | Run/Save/Submit/Begin/Retry/help | Course or assessment unavailable | Entry/Path state | Absent; no hidden source/task leak | Absent |
| Prerequisites missing, both | Safe requirement explanation outside workspace | P: required check/earlier Path; B: unfinished course challenges/earlier Boss via Path | Back | All workspace mutations | Actual missing prerequisite | Lesson/Path/Hub | Absent; existing redirect/access behavior preserved | Absent |
| Timed assessment / expired time, B | None added | Not applicable | Existing non-timed actions | Timer/expiry/auto-submit omitted | No timer copy | Not applicable | Existing state policy | Existing state policy |

### 9.2 Academic and attempt overlays

| State / applies | Visible components | Primary action | Secondary actions | Disabled / omitted | Status text | Feedback location | Editor behavior | Preview/output behavior |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| Unfinished / failed submission, P | Shared workspace, safe failure and actual XP information | Submit again after repair | Run, Save, permitted Help | No unsupported attempt history | Challenge not yet completed | Feedback | Editable current source; existing saved draft retained | Old preview remains separate/stale as applicable |
| Completed, P | Completion summary + current workspace | Continue to Learning Path | Review current code, Run, read previously entitled help | Save/new Help/Submit omitted | Challenge complete; recorded on first pass | Feedback/inline completion | Editable local exploration; source is not an archived attempt | Run is local only; cannot reverse completion |
| No attempt / available, B | Public Task + assessment entry/status | Begin assessment | Back to Boss hub/Path | Editor/Run/Submit/Retry/Save/help | Ready to begin | Entry panel | Absent until existing start transition | Empty/absent |
| Started, B | Shared workspace + assessment/source status | Submit assessment | Run, read instructions, guarded exit | Save/help/retry omitted | Attempt in progress; edits are not saved to server | Code header + assessment status | Editable | Same language adapter as practice |
| Submitted, B | Stored source, recovery/status, Task | Resume verification | Run stored source, read/copy, Back | Edit/replacement Submit/Retry/Save/help | Submission recorded; verification required | Feedback | Read-only stored source | Optional explicit preview of stored source; no pending spinner implying worker |
| Passed, B | Recorded result, score/threshold/version, historical course pass | Continue to Path | Review stored code, Run, Retry if unlocked | Resubmit/Edit/Save/help | Boss Challenge passed; course completion recorded | Feedback/completion | Read-only | Explicit Run of recorded source only |
| Failed attempt, B without historical pass | Recorded score/threshold + instructions | Retry assessment if unlocked | Review source, Run, Back | Resubmit existing attempt/Edit/Save/help | Attempt failed; course not yet completed | Feedback | Read-only | Preview never changes score |
| Failed retry after historical pass, B | Latest failure and separate retained accomplishment | Continue to Path | Retry if unlocked, review, Run | Extra first-pass XP; historical revoke | Latest attempt failed; earlier course pass retained | Feedback + distinct course status | Read-only | Independent of retained completion |
| Retry available, B latest passed/failed | Terminal result + Retry control | Retry or Continue according to passed state | Review, Run, Back | Same-row Submit/Edit | Retry opens a new blank attempt | Feedback/toolbar | Read-only until POST creates fresh row | New attempt begins with no preview; history retained |
| Retry unavailable, B | Current supported state/reason | Begin / Submit / Resume / Back according to state | Read/copy if permitted | Retry; no attempt cap invented | Finish or verify the current attempt before retrying; or actual access reason | Feedback/entry | Existing mode | Existing mode |
| Legacy evaluated / unknown Boss status | Safe state/error explanation | Back to Boss hub | Copy already authorized source where safe | Begin/Edit/Submit/Retry/Resume until supported state resolved | Attempt state unavailable | Feedback/state panel | Read-only or absent | No auto-execution |

Partial evidence is an overlay, not a new stored state. A Boss score below 100 can still pass at its actual threshold. “Failed attempt” requires stored failed status; a 422, 429, 500, runtime preview error, or unavailable grader does not itself establish an academic attempt failure.

## 10. Accessibility and keyboard specification

### 10.1 Order, semantics, and shortcuts

Logical order: skip to workspace → existing application navigation → return link/title context → pane controls → visible Task links/help → first separator → Code editor/source status → second separator → visible Output selectors/content/Return to Code → toolbar secondary actions → Run → academic primary action. Narrow mode includes only the active pane in that sequence. Do not use positive tabindex or reorder meaningful controls visually away from DOM order. When a response removes the focused control, move focus to its stable status heading or legal replacement.

| Control/context | Keyboard contract |
| --- | --- |
| Buttons/links | Native Enter/Space for buttons; Enter for links. Label every icon. Disabled controls have a visible explanation nearby. |
| Narrow pane tabs and Output tabs | Labelled tablist; active tab has tabindex 0/aria-selected true, others −1; arrows wrap, Home/End; panels linked by IDs. Automatic activation is allowed because mounted content switches immediately. No handling of unrelated arrows outside the tablist. |
| Desktop pane controls | Native buttons with aria-expanded/controls, not tab roles when multiple panels are visible. Code remains visible. |
| Splitter | Named separator with value/min/max in meaningful width units, live constrained bounds; Left/Right moves 16 CSS px, Home/End reaches allowable bounds. In the pane-size disclosure provide Compact, Default and Wide native buttons as single-click/tap alternatives to dragging. Presets respect current minima and never modify code. Keyboard support alone is insufficient as the pointer alternative. When hidden, separator is not focusable. |
| Editor | Retain installed CodeMirror editing/search/undo bindings. Tab normally leaves the editor; do not add indentWithTab and trap navigation. Preserve its existing tab-focus mode escape behavior if configuration changes later. |
| Run/Submit/Save shortcuts | No new global accelerators in this iteration. Native button activation is the keyboard action. Specifically do not bind Ctrl/Cmd+Enter to Run/Submit: the installed CodeMirror default uses it to insert a blank line. Do not intercept Ctrl/Cmd+S/browser shortcuts globally or add unmodified letter shortcuts. |
| Confirmation/dirty-exit dialog | Named modal, initial focus on Stay/Cancel, trapped focus only while modal open, background inert, Escape cancels, focus returns to opener. Inline Boss final-review disclosure is not a modal and has no focus trap. |

The [WAI tabs pattern](https://www.w3.org/WAI/ARIA/apg/patterns/tabs/) supplies the tab contract. The shortcut/read-only distinctions were checked in installed CodeMirror declarations and command implementation; verify them again against installed versions during implementation. App-level events must not capture editor key events indiscriminately.

### 10.2 Focus and announcements

Run selects Preview and focuses its stable heading once at explicit activation. Load/runtime messages are announced without moving focus again. Return to Code restores prior editor selection and scroll. Submit selects Feedback and focuses its status heading; a returned result may refocus that heading only if focus is still within the pending operation. If the learner moved elsewhere, announce completion and expose “View result,” without stealing focus. Save never moves focus. Help explicitly opens Assistance and focuses the new hint/reference heading. Input rejection focuses a linked error summary; its source link reveals/focuses Code. Go to line is available only for reliable preview metadata, not guessed validator locations.

Use one small polite `role=status`/atomic announcement region for readiness, saved state, preview load, and submission progress/result summary. Repeated failures must still trigger an announcement. Use `role=alert` for a newly blocking input/access failure, without duplicating the same message in several live regions. Results and console rows are ordinary navigable text; do not announce every keystroke, every console line, or a whole test transcript. `aria-busy` belongs to the updating result/control region, not the entire document. These follow [status-message guidance](https://www.w3.org/WAI/WCAG22/Understanding/status-messages.html).

Editor content has a name such as “Code for Headings Challenge” and description of language, editing/read-only policy, and preview/assessment distinction. A read-only CodeMirror must set editing policy at state and DOM levels, preserve selection/copy, and provide an explicit focusable code region. Installed `EditorState.readOnly` and `EditorView.editable` control different things; disabling contenteditable alone is insufficient. Fallback textarea follows the same policy/name/error association. No-JS mode exposes usable source/forms and says local Preview requires JavaScript; lack of CodeMirror must not lose initial source.

### 10.3 Visual and touch criteria

Workspace action/tab/icon targets are at least 44×44 CSS px by product policy. Splitters may be visually 8px but have a non-overlapping practical hit area, keyboard control, and click/button resize alternatives where necessary. WCAG AA [target size](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html) is 24×24 CSS px or its defined exceptions; the 44px toolbar policy is deliberately larger.

Measure [text contrast](https://www.w3.org/WAI/WCAG22/Understanding/contrast-minimum.html): 4.5:1 normal text, 3:1 qualifying large text. Measure necessary control boundaries/status graphics/focus under [non-text contrast](https://www.w3.org/WAI/WCAG22/Understanding/non-text-contrast.html), normally 3:1 against adjacent colors. Disabled controls still need understandable explanations; syntax colors cannot be the only readable code representation. Status has icon/text, not color alone. Maintain visible focus and keep focused controls/caret clear of sticky bars; [focus-not-obscured](https://www.w3.org/WAI/WCAG22/Understanding/focus-not-obscured-minimum.html) defines the AA minimum, while this product aims for fully visible focused controls.

Support 200% text/zoom and effective 320px reflow; code may scroll within its editor, not widen the entire page. Respect reduced motion; no CRT overlays in work content. Test keyboard plus screen reader, including read-only source, empty Output, repeated errors, dialog cancellation, pane switching, and software keyboard. ARIA in source alone does not establish accessibility acceptance.

## 11. Progress and evidence terminology corrections

Audit applies to Lesson entry, workspace header, Feedback/completion, Boss hub, and the immediate Path/competency handoff. Do not introduce an unsupported strength of claim.

| Current/possible label | Actual evidence | Required wording / boundary |
| --- | --- | --- |
| Lesson “Completed” | MissionService finds a Progress row for its Challenge | “Challenge completed”; reading the lesson alone is not recorded completion. |
| Header “+N XP” | Today's mission.points is the potential first reward | “First completion: N XP” before completion; “Completed” afterward. Do not imply another Run/Submit earns it. Historical actual reward comes from Progress/ledger/outcome. |
| “Draft saved” | Confirmed DraftService write or fetched owned draft | Only acknowledged source is saved. Boss edits never get this label. |
| “Preview loaded / run success” | Frame load and captured diagnostics | “Preview loaded”; no pass/completion/competency claim. No guarantee of syntax correctness for HTML/CSS or later asynchronous JS behavior. |
| “Submitted” | Boss attempt.status=submitted and stored code/submitted_at | “Submission recorded; verification required.” A spinner, POST sent, or missing response is insufficient. Practice has no equivalent persistent submitted state. |
| “Attempted” | Existing owned Boss attempt/history; status further qualifies it | “Attempt started,” “Submitted,” or “Attempt result” as appropriate. Do not count a preview as an attempt or infer number from database ID. |
| “Validation passed / Challenge complete” | First authoritative practice pass plus recorded Progress | Supported. Specify server requirements satisfied, not universal code quality or mastery. Revisit can show completion even when successful source is unavailable. |
| Completion “Course progress X→Y%” | DashboardService completed mission count / total mission count | “Practice challenges complete: a/b,” optional percentage. If a/b is full and Boss unpassed, say “Practice complete; Boss Challenge still required.” |
| Completion “Next Challenge” linking mission.show | Link opens a Lesson | Use Continue to Learning Path as primary. Any secondary direct link says “Next lesson.” Current-section highlight comes from server progression. |
| Boss “Passed / Failed” | Immutable stored verdict and score | “This attempt passed/failed.” Numeric comparison uses passing_score_snapshot, not current threshold. |
| Boss “Need Y% to clear” in historical review | Snapshot exists for new evaluated attempts; legacy snapshot may be null | “Passing threshold for this attempt: Y%.” If unavailable, omit historical comparison; current threshold may be shown separately as current policy, never as historical fact. |
| Boss “Course cleared” | AssessmentService.hasPassed finds any owned historical pass | Prefer “Course completed.” Separate “Latest attempt failed” from “Earlier pass retained.” No reversion after retry. |
| Boss “Later retries are practice” | Fresh formal assessment rows, retained first pass and no repeat reward | “Retries create a new assessed attempt; your earlier course pass remains and no additional first-pass XP is awarded.” Do not disguise a graded retry as a local-only practice copy. |
| “XP earned” | Actual confirmed outcome/ledger entry, not displayed configuration | Show actual newly awarded amount; repeat pass says “No additional XP.” Practice genuine failure debit may be clamped below 10; infrastructure-unavailable category changes no XP. Boss has no failure debit. |
| “Achievement unlocked” | Newly awarded achievement from authoritative service | Show only returned new achievement; never derive from client counts. |
| “Competency evidence added” | Practice Progress stores mission version/skill keys; service combines mapped checks/completions | Prefer “Challenge completion recorded,” linking to learning record if permitted. Skill change cannot be asserted without reading CompetencyService's result. |
| Competency “Boss Challenge evidence” heading over skill percentages | CompetencyService.skills combines mapped Knowledge Check correctness and Challenge completion, excludes Boss in this formula | Correct to “Knowledge Check and Challenge evidence.” This small handoff correction does not authorize a wider competency redesign or formula change. |
| Course “Demonstrated” | CompetencyService course-level evidence conditions, including completion and Boss pass | Only use that service's supplied state and explain supporting evidence. A single run/pass/XP balance is insufficient. |
| “Mastered / mastery / expert / fully competent” | No mastery proof supplied by these flows | Omit. An assessed result under particular requirements is narrower than general mastery. |

The code sources for these distinctions are MissionService, AssessmentService, XpService, DashboardService.courseProgress, MissionController completion payload, and [CompetencyService](../../app/Services/CompetencyService.php), including the skill formula documentation around lines 407–422. Preserve historical mission/assessment versions and thresholds. Never expose protected grading snapshots to explain evidence.

## 12. Component architecture

Use anonymous Blade components under the existing `resources/views/components/` convention, nested as `workspace.*`. These are proposed paths, not created application files. Keep seven cohesive components; requirements, assessment metadata, status, and completion are sections/slots within them rather than ten one-line wrappers.

| Proposed component | Responsibility | Inputs/data | States | Shared? / current treatment |
| --- | --- | --- | --- | --- |
| `x-workspace.shell` | Full-width responsive structure, pane controls, stable regions and announcements | Allowlisted context/capabilities; pane slots; initial mode | Entry, editable, review, blocked; split/tab layout | Both. Extract practice data-workspace behavior; replace Boss capped shell. Keep layouts.app rather than x-app-layout. |
| `x-workspace.header` | Title, return link, mode, course/section, source/attempt and retained completion context | Context; difficulty if present; academic state; potential/actual reward where appropriate | Entry, active, submitted, terminal; long title | Both. Refactor challenge-context-bar and Boss header/details; no metadata sidebar. |
| `x-workspace.task` | Objective/public instructions/examples, workflow guidance, practice Assistance | Public task; permitted examples; entitled hints/reference; Help permissions/costs/balance | No authored brief, normal instructions, revealed help, insufficient XP | Both with practice-only Assistance. Refactor brief/Boss briefing; use existing status-message styling where appropriate. No hidden validator parsing. |
| `x-workspace.editor` | Named source control, CodeMirror host/fallback, source/draft/read-only explanation | Initial source/origin; language; max bytes; editability; confirmed draft baseline | Loading, ready, fallback, dirty, saving, saved, failed, read-only | Both. Replace duplicate inline initialization with shared JS. Export installed state/editability primitives only as needed. |
| `x-workspace.output` | Preview/optional Console/Feedback selectors, iframe, revision/empty/stale/error state | Language/sandbox policy; non-authoritative preview state; Feedback slot | Empty, preparing, loaded, stale, runtime error, unavailable | Both. Reuse preview.js; replace Boss raw srcdoc. Frame title includes task name. |
| `x-workspace.feedback` | Safe error/result/recovery summary, optional permitted counts, inline completion | Explicit outcome category; messages; recorded Boss score/threshold/version; confirmed XP; next actions | Rejection, unavailable, unknown, failed, partial evidence, passed, retained historical pass | Both, with policy-specific evidence. Replace toast-only failure and modal-only completion. Reuse status-message after avoiding duplicate live-region roles. |
| `x-workspace.toolbar` | Stable action geography and capability/request disablement | Approved action URLs/CSRF forms; capability flags; request/dirty state; Help/Save slot | Begin, editing, submitting, recovery, terminal, blocked | Both. Refactor practice toolbar/Boss controls. No unsupported Save/Help placeholders or duplicate submit controls. |

Existing student header remains the application shell; this task does not add a student sidebar. Existing toast component remains available for secondary notices, while core feedback lives in workspace.feedback. Modal behavior is needed only for genuine confirmation/dirty exit; share one accessible confirmation implementation if a reusable one already exists rather than copying the current completion trap.

JS ownership: extend/refactor `workspace.js` for pane/focus/geometry; add one production `challenge-workspace.js` controller for source readiness, snapshots, forms, operation state, and output. `editor.js` owns CodeMirror imports; `preview.js` owns language document generation/instrumentation. Remove both views' duplicate orchestration when equivalent behavior is covered. Scope DOM queries to the workspace root and destroy listeners/resources cleanly; one editor instance per page.

PHP ownership: keep academic services authoritative. Controller view-data composition supplies capabilities and a safe response projection; a small presenter in existing `app/Support/` may be extracted if shared mapping warrants it. Do not create a new academic service or aggregate that duplicates scoring/progression. Enhanced requests receive an allowlisted outcome/read-state representation from existing routes; ordinary HTML forms retain redirect/flash semantics. No frontend DTO includes solution before entitlement, grading rules/snapshots, hidden test inputs, arbitrary owner IDs, or a client-writable verdict.

CSS ownership: production student workspace component rules and `@theme` semantic tokens. Consolidate duplicated layout/action rules rather than layering another sheet. Token aliases may support existing pages during migration; removal of the prototype import needs build verification of other student pages. Reuse sans/code roles and existing semantic colors after contrast measurement.

## 13. Current → proposed implementation mapping

| Current implementation/file | Current problem | Proposed behavior | Proposed component/module | Expected impact |
| --- | --- | --- | --- | --- |
| [challenge view](../../resources/views/challenge.blade.php) | Markup, policies, inline scripts, toasts and completion all coupled | Render shared regions using explicit view data | workspace.shell/header/task/editor/output/feedback/toolbar | Template refactor; preserve routes/help/first completion |
| [Boss view](../../resources/views/assessments/show.blade.php) | Stacked/capped workspace, raw preview, editable terminal review | Same shell, language adapter, immutable source and assessment state | Same components; capabilities differ | Template refactor; no assessment policy change |
| [Boss initialCode](../../resources/views/assessments/show.blade.php) / AssessmentController.showData | old input can supersede stored source in recorded review | Editable started state may restore input; submitted/terminal state always uses owned stored code | workspace.editor + source-origin projection | Prevent misleading evidence presentation without changing records |
| [lesson](../../resources/views/lesson.blade.php) | Starter/target not carried into workspace; completion wording implies lesson state | Public examples available in Task, accurate Challenge completion; existing required check gate retained | workspace.task; lesson CTA/copy | Small data/copy change; no full lesson inside workspace |
| [MissionController](../../app/Http/Controllers/MissionController.php) | First failure only, ambiguous completion destination, solution initial-source precedence | Safe full failure/category projection; explicit draft/source/reference origins; Path completion action | Shared presenter/feedback data | Controller response/view composition; no grading reimplementation |
| [MissionService](../../app/Services/MissionService.php) / [MissionGradingService](../../app/Services/MissionGradingService.php) | Unavailable/count information drops at return boundary | Propagate safe operation category/counts without altering writes/verdict | Outcome projection | Small typed return-contract expansion; meaningful grading/XP regression tests |
| [AssessmentController](../../app/Http/Controllers/AssessmentController.php) | available offered Submit; code errors always “required”; historical threshold current; no safe reconciliation projection | Begin for available; exact input errors; snapshot threshold; authorized read/status data | Presenter + workspace.feedback/toolbar | Presentation/response corrections; keep source-only submit authority |
| [AssessmentService](../../app/Services/AssessmentService.php) | Accepted domain differs from practice | Consume existing states/retry/history/version | Boss capability projection | No scoring/lifecycle change required; no new polling/timer/draft |
| [SourceCodeInput](../../app/Support/SourceCodeInput.php) | Rejected source deliberately absent from old input | Retain code in mounted client control; display exact server errors | challenge-workspace.js/editor/feedback | Preserve existing admission behavior; no oversized flash/echo |
| [DraftService](../../app/Services/DraftService.php) | Save confirmation does not preserve pane/current later edits | Acknowledge captured draft revision; manual Save and leave | Editor/controller response | Existing persistence; no schema change or new autosave policy |
| [preview.js](../../resources/js/preview.js) | Boss bypasses adapter; diagnostics inside frame; no revision tracking | Shared adapter, labelled fixtures, bounded diagnostic projection and stale state | workspace.output + shared controller | Frontend refactor/extension; sandbox unchanged |
| [editor.js](../../resources/js/editor.js), [app.js](../../resources/js/app.js) | Lazy-load failure leaves actions usable; no read-only policy | Readiness/fallback and explicit editing mode | workspace.editor + controller | Consolidate initialization; preserve module-ready pattern |
| [workspace.js](../../resources/js/workspace.js) | Practice-only layout; percentage minima/pane state not preserved across return | Shared measured geometry/focus; constrained two/three-pane layout; geometry-only preferences | workspace.shell/controller | Extend existing accessible controls; no content remount |
| [student.css](../../resources/css/student.css), [app.css](../../resources/css/app.css), [prototype styles](prototype/styles.css) | Prototype production dependency, duplicate workspace geometry, fixed-height trap risk | One production workspace rule set and semantic tokens; adaptive height/scroll | All workspace components | CSS ownership change with built-asset regression of shared student pages |
| [status-message](../../resources/views/components/status-message.blade.php), [student-toast](../../resources/views/components/student-toast.blade.php) | Multiple announcement owners and dismissible core feedback | Persistent results, one operation announcement; toast only supplementary | workspace.feedback | Reuse/refactor styles and semantics, not duplicate notices |
| [DashboardService](../../app/Services/DashboardService.php), [LearningPathService](../../app/Services/LearningPathService.php), [ResumeService](../../app/Services/ResumeService.php) | Competing next-task traversal; practice percent labelled course progress | Accurate completion handoff; one deterministic server continuation projection | Existing learning read models/presenter | Focused continuation consistency work; no client unlocks |
| [competency view](../../resources/views/competency.blade.php) | Skill source heading calls KC/challenge formula Boss evidence | Correct evidence-source label | Existing competency view | Copy-only handoff correction; broader redesign deferred |
| [routes](../../routes/web.php) | Existing write/authorization contract must survive enhancement | Same named routes, CSRF, student middleware, limiters; optional safe response negotiation | Controllers/forms | No new business endpoint or weakened authorization |

## 14. Acceptance criteria

These are pass/fail requirements for the implementation phase. “Both” means compare equivalent editable or read-only modes, not invent practice capabilities in Boss. Use disposable test databases; never migrate/seed the protected system404 database for UI verification.

| ID | Testable criterion | Verification |
| --- | --- | --- |
| AC01 | Practice and started Boss render the same Header/Task/Code/Output/Toolbar geometry, action order, token treatment, and pane labels at each supported breakpoint; Boss mode is explicitly named | Render assertions + browser screenshot review using real built assets |
| AC02 | At 1440px default Task+Code keeps editor dominant; opening Output respects Task≥280, Code≥560, Output≥280 CSS px. At 1101–1199px no illegal three-pane layout is allowed. Reset changes geometry only | Browser element bounds and source/result assertions |
| AC03 | At 320/390/430/768/1024/1100px panes use tabs, not three stacked coding panels. No page-wide horizontal overflow; active pane and legal primary actions remain reachable | Browser bounds/scroll/focus tests; software-keyboard device pass |
| AC04 | Editing then switching panes, resizing, rotating and crossing breakpoint preserves exact source, undo, selection, scroll, dirty state, and matching preview/feedback | Browser interaction tests for both editable modes |
| AC05 | Run for identical HTML/CSS/JS source and authoritative type uses the same adapter in both modes; CSS styles the labelled fixture and JS console/errors are visible. Run makes no grading/write request and changes no academic records/XP | JS/browser tests + request observation + DB assertions |
| AC06 | No automatic execution on load/edit/save. Edit after Run marks output stale; Run updates captured revision. Empty/no-console-output never means failed academic work | Browser state transitions |
| AC07 | Delayed/failed preview preserves prior feedback/output as specified; stale run messages are ignored. Forged iframe messages cannot alter academic labels/XP/access; HTML/CSS sandbox empty, JS allow-scripts only | Preview tests + browser isolation regressions |
| AC08 | Simulated editor import failure leaves usable current source in fallback or disables all source-dependent actions. Recovery enhancement retains fallback edits. Submitted hidden payload always equals actual source | Browser loader-failure and request payload tests |
| AC09 | Practice Save says Saved only after acknowledgement; edits during Save remain unsaved and are not overwritten. Failed Save retains source. Pane/focus context survives enhancement | Browser delayed/error Save tests + draft DB check |
| AC10 | Dirty practice Back offers Stay/Save and leave/Leave; Save and leave requires confirmed persistence. Dirty Boss warns no server draft. Dialog Escape cancels and focus returns; no exit implicitly submits | Browser keyboard tests + network/DB assertions |
| AC11 | Paid hints respect authored count and backend max three; cost/balance are server-derived. Purchase failure preserves source; success opens Assistance. Reference never silently overwrites source. Completed/Boss modes offer no purchases | Feature/help ledger tests + browser focus/source assertions |
| AC12 | Empty/oversized/non-string source rejection shows the real error without an academic fail or XP effect. UTF-8 byte bounds match server. Rejected source is retained locally but not flashed into session contrary to SourceCodeInput | Admission feature tests + browser enhanced rejection |
| AC13 | Practice structural and permitted behavioral failures remain server-derived; all approved messages appear persistently in Feedback. Unavailable category says ungraded/no XP change, not failed work; client preview never fabricates test outcomes | MissionBehavioralGradingTest plus outcome/render coverage |
| AC14 | Practice first pass records Progress/reward once, deletes draft, displays actual reward and practice count, then Continue returns to updated Path. Already completed source is local exploration without a regrading Submit promise | Mission/service/journey regressions + browser completion |
| AC15 | Full ordinary Challenge completion with no Boss pass says practice complete and Boss still required; it does not say course complete/mastered | Feature/render fixture with all Progress and no passed Boss |
| AC16 | Boss no attempt/available offers Begin only; existing start moves available→started before Submit. Started has Run/Submit and no Save/Hint/Reveal/timer | Assessment feature/render tests for each state |
| AC17 | Boss submitted/passed/failed source is read-only for typing, paste, drop and programmatic UI replacement; selection/copy/explicit preview work. Recorded result never changes after local Run | Browser + service immutable-attempt tests |
| AC18 | Submitted Resume uses stored source, ignores replacement source and forged verdict/owner/attempt fields, and rewards at most once. UI has no automatic replay/polling | AssessmentRecoveryTest + request/browser checks |
| AC19 | A failed normal Boss submission transaction/ambiguous response cannot display Received/Saved/Failed without confirmed server state. A started GET during an in-flight POST remains unresolved; no automatic replay. Read-only attempt/source correlation prevents associating a newer tab's retry verdict with the older captured source; local source is preserved | Recovery rollback feature + browser delayed/dropped-response and two-tab tests |
| AC20 | Passed/failed Boss Retry creates a fresh blank started row; latest active/submitted states cannot retry. Previous pass, course reach and original XP remain after failed retry. No attempt cap or Boss failure penalty appears | AssessmentTest/IntegrationTest + retry/history render cases |
| AC21 | Changing current Boss threshold does not change an old result's displayed recorded comparison; attempt snapshot is used. Null snapshot is explicitly unavailable/omitted, not fabricated | CurriculumVersioningTest + legacy snapshot render test |
| AC22 | Sealed/inactive/unreached course, missing/inactive Boss, incomplete challenges and required check each yield the correct server reason/navigation without exposing protected task/source or enabling a write | Authorization/sequence/Knowledge Check gate feature tests |
| AC23 | 429 uses actual Retry-After, 419/500/dropped responses have recovery copy and preserve code; no error handler auto-replays a potentially charged/submitted request | Admission + browser mocked transport paths |
| AC24 | Keyboard alone reaches all legal actions; tab arrows/Home/End and splitter bounds work; hidden panes have no focusable controls. Ctrl/Cmd+Enter remains editor insertion, not Run/Submit | Browser keyboard tests with editor focus |
| AC25 | Run/Submit/help/error focus follows section 10; Save/late diagnostics do not steal focus. Repeated failure announces again, one owner announces each operation, and source/readonly controls have usable names | Browser focus assertions + screen-reader manual pass |
| AC26 | Controls meet 44px product target, necessary contrasts meet the stated measured ratios, statuses have text/icon, reduced motion suppresses decorative motion, and focus/caret remain visible with sticky controls/zoom/keyboard | Measurements + keyboard/device/assistive-technology review |
| AC27 | Student-safe payloads contain no rule/regex snapshots, answer keys, hidden cases, unentitled solution, foreign source, or client-authoritative result fields; forged fields affect no Progress/score/XP/unlock | Existing security suites + response allowlist tests |
| AC28 | No timer, queue, polling, Boss draft, richer historical test breakdown, copied retry source, or invented ordinary-attempt archive is implied by UI copy/components | State render and capability matrix tests |
| AC29 | Consolidated CSS/build renders Lesson/Path and other users of shared tokens without unintended regressions; no production import of prototype styles after migration | npm build + selected built-asset visual checks |
| AC30 | Existing ordinary Challenge draft/help/preview/validation/completion and Boss eligibility/start/score/retry/history/security regressions remain passing; formatter/static analysis applicable to later changes pass without hiding findings | Narrow suites first, then required project checks; report exact commands/results |
| AC31 | Workspace uses the production semantic tokens and typography roles in section 4.1. Both modes have identical non-domain styling. Essential text, controls and focus meet actual contrast measurements, including opacity and syntax-theme treatments | Built-asset token inspection and measured visual/contrast review |
| AC32 | A terminal/submitted Boss GET with unrelated flashed old code displays only the owned recorded source; no local preview or copy action misrepresents the returned input as submitted evidence | Feature fixture for old input plus browser source assertion |
| AC33 | Each resizable pane supports Compact/Default/Wide single-pointer controls as well as pointer dragging and keyboard resizing. Presets clamp at actual available-width minima and preserve source/results | Mouse/touch and keyboard browser checks |
| AC34 | At 320px long title, mode, and Submit assessment text remain available without clipped controls. No fictitious multi-file tabs, timer, terminal or build status appears | Long-content screenshots and component render fixtures |
| AC35 | Delayed Save, late preview diagnostic, prior practice failure and current dirty edits can coexist with correct independent statuses. No completion handler overwrites newer source or clears another operation's feedback | Browser controlled-delay and revision-mismatch scenarios |

Browser harness may use the existing WorkspaceBrowserTest setup and its production-asset/no-hot-file requirements. Test absence of side effects, not just label text. Inspect browser console for recent errors. Skipped/unavailable browser or screen-reader checks remain explicit incomplete acceptance, not a pass inferred from PHP tests. This documentation pass ran no implementation tests and makes no release-readiness claim.

## 15. Recommended implementation sequence

1. **Pin domain and response contracts.** Cover available→Begin, terminal editability, submitted recovery, historical threshold, practice unavailable category, byte rejection and ambiguous request states. Define the allowlisted presenter/outcome/read-state projection and snapshot rules. Do not change accepted scoring/retry/XP policies.
2. **Extract the shared shell and production styling.** Start from existing practice pane controls and toolbar. Apply it to Boss entry/active/recovery/review; preserve route names and all authorization gates. Establish measured width/height rules and semantic tokens; migrate prototype dependencies with selected student-page regression checks.
3. **Unify editor/source/preview orchestration.** One CodeMirror/fallback controller, shared adapter, explicit Run, bounded diagnostics, stale revisions, read-only evidence, and identical equivalent-mode behavior. Verify sandbox and payload correctness before feedback polish.
4. **Implement stateful actions and recovery.** Progressive enhancement of existing forms; confirmed manual Save, dirty-exit guard, mutation lock, exact input errors, safe GET reconciliation, and no automatic POST replay. Keep HTML/no-JS fallback honest about preview and recovery limits.
5. **Move feedback/help/completion beside the work.** Persistent Feedback and assistance, safe permitted summaries, recorded threshold/version, source-preserving reveal, accurate reward/progress labels, and Continue to updated Path. Keep rich Boss requirement history deferred unless separately authorized.
6. **Finish responsive/keyboard acceptance across all modes.** Exercise state matrix, minima, short viewport, software keyboard, focus/announcement, read-only copy, and no-JS fallback. Compare both pages using identical source/viewport fixtures. Close visual/contrast/manual assistive-technology checks rather than relying on happy-path screenshots.
7. **Verify the learning handoff and regression boundary.** Align completion/draft continuation with existing server learning projections, correct the competency-source heading, run relevant unit/feature/browser suites plus build/Pint/PHPStan for touched implementation, and document exact results. Preserve original untracked user files and never modify production data.

Ready-to-implement scope includes shared layout, presentation/read-response projection, editor/fallback/read-only behavior, preview consistency, stateful existing actions, safe feedback, and narrowly related terminology. Out of scope: new grading algorithms, mastery metrics, Boss draft/timers/attempt caps, new source archives/schema, assessment behavioral tests, extra sandbox privileges, framework replacement, and wholesale redesign of other dashboards. Any missing richer data uses the explicit fallback in this document rather than becoming an invented UI promise.

### 15.1 Delivery gates and review fixtures

The first implementation slice is both views plus the shared Header/Task/Code/Output/Toolbar structure, readiness gate, and immutable Boss review. Do not finish practice first and leave Boss for a later visual migration. The second slice unifies preview and persistent feedback. The third covers enhanced Save/help/Submit recovery and learning handoff. Production-token extraction must accompany the shell slice so a new sheet does not increase the existing CSS conflicts.

Before connecting mutations, review static state fixtures for practice ready/failure/completed and Boss entry/started/submitted/failed/passed/failed-retry. Use the same long title and identical HTML/CSS/JS source examples where states permit. Review at 1440×900, 1280×720, 1024×768, 768×1024, 390×844 and 320×568, then at 200% zoom and with a software keyboard. Fixture results must be labelled as examples; they do not validate backend grading.

Before acceptance, use the actual server routes and built assets. Verify the capability matrix, stored-source provenance, current versus recorded thresholds, no-JS forms, fallback editor, transport failures, two-tab uncertainty, help entitlement, and one-time XP. Existing tests are the starting point; additions should cover changed boundaries rather than snapshot every CSS class. Keep manual contrast/screen-reader/device items visibly open until exercised.

Documentation checks for this revision cover all 15 requested sections, referenced local paths, state/action coverage, and token-pair arithmetic. No application tests, live browser inspection, or production database changes were performed for this specification. Those checks belong to the later implementation and are not represented as passing here.
