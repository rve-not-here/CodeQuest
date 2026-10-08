# CodeQuest Mission Atlas design specification

- Date: 2026-10-08
- Status: Approved and frozen for implementation planning after the product-owner's four amendments on 2026-10-08. This document does not authorize implementation.
- Branch: `redesign/astra-ui`
- Baseline: `f07236b213f6438189170bda5464a7a1c3258f50`
- Product: CodeQuest: An Integrated Gamified Learning and Assessment System

Primary visual reference: [approved Mission Atlas concept](/home/vmc/.codex/generated_images/01a11408-b934-71e0-ba52-d2757d8580e0/exec-094d413d-0273-454f-90a6-932c16eb690c.png).

Reference image absolute location: `/home/vmc/.codex/generated_images/01a11408-b934-71e0-ba52-d2757d8580e0/exec-094d413d-0273-454f-90a6-932c16eb690c.png`.

The generated image is optional visual reference material. The written specification alone is sufficient for implementation. Missing or unavailable generated-image files must never block implementation or become runtime or build dependencies.

This is the canonical design specification for the redesign. It supersedes conflicting visual direction in earlier prototypes and design documents, but never backend contracts, security requirements, or application data. The reference image establishes composition and visual character only. Its sample counts, time estimates, wording, course structure, and states are not requirements. Use actual application content and authoritative state. Do not reproduce image-generated gradients, invented curriculum, or decorative details that conflict with this document.

Design basis: Impeccable product context and critique; Huashu's reviewed direction selection; UI/UX Pro Max and UI Styling accessibility, typography, and responsive guidance; Design System token/state architecture; Brand identity consistency; Product Design Audit evidence distinctions; Product Design Ideate visual exploration; Superpowers specification-before-planning workflow; Unslop copy review; Caveman concise communication. Previously loaded guidance is reused. No new concept selection, implementation plan, or product implementation belongs to this specification task.

## 1. Purpose

Give CodeQuest a recognizable technical learning identity across Student, Teacher, and Admin experiences. A student identifies the next learning action, a teacher identifies relevant intervention evidence, and an administrator identifies management destinations and recorded operational activity.

The three dashboards share Mission Atlas typography, color, geometry, alignment, controls, and states. Their compositions serve different tasks. CodeQuest must remain recognizable when its logo is hidden through its restrained signal markers, ruled information groups, technical metadata, and explicit learning or operational context.

## 2. Design principles

- Put the role's primary task ahead of metrics. Every page has a clear heading and a dominant action when an action is available.
- Organize with alignment, spacing, typography, and rules before introducing a container.
- Show relationships with meaningful connectors. A connector must describe existing course organization or related evidence, not invent prerequisites.
- Keep instruction comfortable to read and coding space practical to use.
- Separate practice, pending submission, failure, completion, and assessment results visibly and semantically.
- Use the same control and state vocabulary across roles. Change density and composition to match the role.
- Preserve authoritative content and behavior. Missing data stays absent or receives an honest empty state.

## 3. Non-goals

No framework migration, new backend capability, database change, curriculum rewrite, new signup system, new grading workflow, or academic calculation is part of this redesign. Do not add predictive risk scores, fabricated mastery percentages, deadlines, completion estimates, alerts, or classroom-wide queue rankings.

No permanent desktop sidebar, neon effects, scanlines, glass, gradients, glow, decorative animation, oversized rounded panels, nested card systems, or metric-card dashboard grids. No new component library or dependency is required by this specification. Do not remove a working capability merely because it is secondary to the new composition.

## 4. Mission Atlas visual identity

Use a near-black technical canvas, warm off-white text, restrained acid-lime emphasis, and thin structural lines. The signature student structure is a course spine with aligned mission entries, a visibly current objective, and one distinct course-assessment endpoint. Teacher and Admin screens inherit the rules, alignment, typography, and state markers without inheriting a student map.

Retain System 404 through concise technical metadata and precise geometry. Keep the CodeQuest name. Do not repeat fictional system messages, terminal prompts, or error imagery across ordinary work. Preserve real course and mission titles; this specification does not authorize changes to stored curriculum names.

## 5. Shared application shell

Retain the Blade application shell and existing named routes. The visual shell is shared across authenticated roles: one top navigation band, page context, main content, and overlays when needed. Use a 56px minimum top bar; allow it to grow with text scaling. Standard content has a 1440px maximum width. Coding workspaces use available viewport width.

Keep a skip link and one main landmark. Maintain shrinkable flex/grid children so long content cannot widen the page. Role-specific content must not introduce a different font family, palette, button system, or rounded-container style. Public and error screens share the identity while using simpler navigation.

## 6. Navigation

- Student primary destinations: Dashboard, Learning Path, and Boss Challenges. A separate Learning record disclosure exposes existing progress, performance, timeline, XP ledger, reports, recommendations, achievements, and challenge-index destinations. Notifications remain directly reachable.
- Teacher primary destinations: the existing Teacher Dashboard/roster, My Classrooms, Needs Attention, and Reports. Course Analytics and Learning Activity remain reachable in a clearly labeled teaching-context disclosure.
- Admin primary destinations: Admin Console, Users, Classrooms, and Curriculum using the existing courses route. An Operations disclosure exposes existing announcements, audit, analytics, system report, and system status destinations.
- Account controls are separate from primary navigation. Preserve all existing account actions and logout behavior.
- When the full row no longer fits, replace it with a labeled navigation button opening the same destinations in source order. Do not hide mobile primary navigation inside the profile control.
- Indicate current location with text treatment and an underline or structural marker plus `aria-current`. No color-only location indication.
- Use ordinary navigation links and disclosure semantics, not ARIA application-menu roles for a simple list of links. Enter/Space opens disclosures; Tab traverses links; Escape closes and returns focus to the trigger. Outside click closes without stealing focus from the clicked control.
- Contextual back links return to the actual lesson, course, classroom, student, or management list. Preserve applicable filters and existing return parameters.

## 7. Typography

Use IBM Plex Sans for headings, navigation, prose, forms, tables, questions, and feedback. Use IBM Plex Mono only for code, identifiers, XP, compact state labels, and technical metadata. Use system sans/monospace fallbacks without blocking rendering. Load the intended fonts through the existing asset approach; verify actual rendered fonts rather than relying on a family declaration.

The implemented defense build must not depend on a runtime CDN or Internet connection for IBM Plex. Use the existing asset pipeline for locally available fonts. If IBM Plex is unavailable locally, the declared system fallbacks must remain fully usable and layout-safe. Do not introduce an external runtime font dependency merely to match the concept image. Browser acceptance must include offline/font-unavailable rendering.

| Role | Size / line height | Weight |
| --- | --- | --- |
| Signature course/objective heading | 40 / 48px desktop; 30 / 38px mobile | 600 |
| Page heading | 32 / 40px desktop; 28 / 36px mobile | 600 |
| Section heading | 24 / 32px | 600 |
| Subheading | 20 / 28px | 600 |
| Lesson prose | 18 / 30px | 400 |
| UI body and inputs | 16 / 26px | 400 |
| Table and supporting copy | 14 / 22px | 400–500 |
| Technical metadata | 12 / 18px | 500 |
| Editor and code examples | 14 / 22px | 400 |

Express sizes in scalable units. Lesson prose has a maximum measure of 68ch. Use normal body tracking and at most 0.04em tracking for short uppercase metadata. Wrap headings and labels instead of shrinking them. Long curriculum identifiers may break at safe boundaries. Do not apply monospace to a whole paragraph because its subject is technical.

## 8. Color tokens

| Semantic token | Value | Use |
| --- | --- | --- |
| Page canvas | `#111416` | Application background |
| Reading surface | `#171C1F` | Reading and task areas |
| Workspace surface | `#1B2023` | Editor surroundings and working regions |
| Elevated surface | `#242B2F` | Menus and dialogs |
| Primary text | `#F2F3EE` | Content and headings |
| Supporting text | `#AAB4B8` | Secondary content, locked explanations |
| Decorative rule | `#343E43` | Nonessential separators |
| Control boundary | `#78858A` | Boundaries needed to identify controls |
| Primary/current | `#C4F56A` | Primary action, current position, focus |
| On-primary | `#111416` | Text on acid-lime controls |
| Primary hover | `#D4FA92` | Primary control hover |
| Primary pressed | `#B1DD5F` | Primary control pressed |
| Completion/success | `#79C7A1` | Confirmed completion and successful actions |
| Warning/pending | `#F2BD65` | Attention and pending evaluation |
| Error | `#FF8F8F` | Validation and failed operations |
| Information | `#82D7E3` | Supporting informational emphasis |

Available items use primary text and hollow markers. Locked items use supporting text, a lock, and an explicit reason. Do not dim entire locked sections with opacity. Keep semantic aliases shared; components must not introduce their own near-equivalent colors.

Opaque-pair calculations against the lightest specified dark surface, `#242B2F`, give 12.88:1 for primary text, 6.79:1 for supporting text, and 3.78:1 for control boundaries. Final rendered states, syntax colors, hover colors, and composited backgrounds still require measurement.

## 9. Surface hierarchy

Use the page canvas for most content. Introduce a reading or working surface only to distinguish an actual work region. Elevated surfaces are reserved for overlays. A section heading, a rule, and spacing are sufficient for dashboard groups and ordinary records.

Do not nest elevated surfaces or apply a different background to every metric. Output rendered from student HTML remains authentic to that document; do not force a dark theme into student content. Frame the preview with Mission Atlas workspace chrome.

## 10. Border and geometry system

Structural sections and workspace boundaries have 0px radius. Controls have 2px radius. Menus and dialogs have at most 4px radius. Circular state symbols are allowed because shape conveys state; content containers and buttons do not become pills.

Use 1px rules and control borders. Use a 2px double-rule or paired-rule treatment to distinguish the course assessment. Focus uses a 3px outline with 2px offset. Decorative rules may be subtle; borders or symbols required to understand a control or state must meet non-text contrast requirements. Do not use shadows for ordinary content; overlays separate through their surface and border.

## 11. Spacing system

Use a 4px base with the scale 4, 8, 12, 16, 24, 32, 48, and 64px. Control contents use 8–16px spacing. Related rows use 12–16px vertical rhythm. Dashboard groups use 24–32px separation; reading sections use 32–48px.

Page gutters are 16px at narrow mobile widths, 24px at tablet widths, 32px on laptops, and 48px on large desktops within the content-width cap. Do not add empty space merely to make a sparse fixture look polished. Keep density readable with real long titles, validation messages, and populated tables.

## 12. State language

| State | Visible treatment | Meaning |
| --- | --- | --- |
| Complete | Checkmark, success color, explicit label | Existing authoritative completion |
| Current | Bracket/square marker, lime emphasis, explicit label | Existing server-selected current work |
| Available | Hollow marker and readable label | Existing authorization/availability allows the action |
| Locked | Lock, label, readable prerequisite reason | Existing server rule prevents the action |
| Draft saved | Quiet inline acknowledgment | Source saved; no academic completion implied |
| Preview updated | Neutral workspace status | Preview changed; no grade implied |
| Pending | Pending label and non-numeric activity indicator | Existing submission/evaluation is unresolved |
| Failed | Error or assessment-failure label and useful feedback | Current operation or attempt failed |
| Passed | Success label and recorded result | Authoritative assessment pass |

Academic states, UI selection, and persistence states are different concepts. Do not show an unchecked lesson as failed, a draft as progress, or an old course pass as revoked by a later failed practice attempt.

## 13. Focus/interaction states

Hover changes surface, text, or border without translating the control. Pressed controls use the defined pressed treatment. Selected navigation and tabs retain a structural marker. Keyboard focus remains visible during hover, selection, errors, and pending operations.

Use native disabled semantics when an action is unavailable and show its reason nearby. Do not disable access to readable prerequisite explanations. During submissions, expose pending status and prevent accidental duplicate activation while preserving existing server replay behavior.

Allow 120ms color feedback and 180ms disclosure transitions. Reduced-motion preference removes movement and nonessential animation. No pulsing mission markers, animated connector drawing, scanlines, celebratory overlays, or scroll-triggered content reveals.

## 14. Student Dashboard

Primary question: **What should I do next?**

The first content region is Current Objective. Show the actual course and section, mission title or course milestone, concise context, and one dominant Continue action. Keep the existing `resume`, `resumeHasDraft`, course, and progress decisions from `DashboardController`; do not select the next mission in JavaScript or derive it from the visual ordering.

Place a compact course-position structure beside the objective on wide screens and immediately after it on narrow screens. Connect related information through aligned rules. Use only supplied course progress, current position, and recorded evidence. The dashboard does not receive a complete mission graph; do not fabricate one or reproduce the full Learning Path map from partial data.

XP is compact metadata with access to its ledger. Recent learning activity is a short chronological record below the objective. Competency evidence and the existing recommendations destination remain secondary. Where recommendation data is not supplied, show the existing destination rather than inventing a personalized recommendation.

Preserve distinct existing states: no learnable courses, active mission, saved draft, outstanding Boss milestone, and all active courses completed. Completing missions alone must never produce a course-complete message. Preserve the current server-selected Continue destination, including lesson versus saved challenge behavior. Retire the generic metric strip and surrounding card grid without dropping their meaningful information.

## 15. Teacher Dashboard

Primary questions: **Who needs attention? Why? What evidence supports it?**

Use a teaching-context header with the existing My Classrooms destination and current permitted course/filter context. Do not imply a selected classroom where no classroom selection exists. Present the authoritative Needs Attention count and existing route as a prominent action within a ruled summary, not a student mission node.

Keep the roster as the principal dense evidence table. Preserve search, course and status filters, pagination, student links, course/progress evidence, assessment state, competency, recent activity, and attention indicators. Expose evidence already supplied for each row. Do not invent causes or rank learners from the current page of results.

Course assessment summaries use aligned rows with explicit bucket labels and their real counts. Recent activity remains secondary and retains its existing time window. All summaries and records must clearly belong to the teacher's authorized scope.

`/students` supplies `students`, `filterCourses`, `filters`, `dashboardMetrics`, `dashboardActivity`, and `dashboardSummary`. It does not supply a complete ranked attention queue. Keep the full Needs Attention experience on its existing route. A future embedded complete queue would require a separately reviewed scoped read-model change; it is excluded from this visual redesign.

## 16. Admin Dashboard

Primary question: **What requires management?**

Use ruled operational sections for Accounts, Classrooms, Curriculum, and System operations. Make existing management destinations readable links with compact supporting counts where those counts are supplied. Do not invent a classroom count or alert merely to fill a section.

Present existing assessment and learning totals as compact authoritative records, preserving their time windows and definitions. They are contextual information, not student achievements or controls for altering outcomes. Recent System Activity is a dense audited record with actor, recorded action, and time.

Use the same shell, type, colors, controls, and state markers as Student and Teacher. Do not give Admin a separate glowing terminal aesthetic or a student progression diagram. Preserve existing permissions, destinations, and empty audit-feed behavior.

## 17. Learning Path

Use a semantic course sequence with section groups and ordered mission entries. The visible course spine organizes content; it does not introduce a new dependency graph. Give the current mission the strongest local emphasis and one clear action. Completed work is compact but still accessible. Available and locked work remain distinguishable without opening every section.

Do not make identical panel accordions the primary composition. A disclosure may shorten completed history, but retain the current position, section heading, progress summary, and locked explanation outside the collapsed content. Preserve additional/unsectioned missions supported by the existing read model.

Show one Boss Challenge endpoint per course using assessment framing and authoritative eligibility. Keep mission completion and course completion distinct. Use existing backend availability even when visual order suggests a sequence. At mobile widths, maintain one vertical spine without horizontal panning or tiny map controls.

## 18. Lesson

Use a documentation reading column with the actual title, objective, instructional content, examples, expected output, and configured Knowledge Checks. Separate sections with headings and whitespace. Box code examples or meaningful warnings, not every paragraph.

Keep code selectable and readable, with horizontal scrolling contained inside code blocks. Preserve actual curriculum content and permitted sanitization behavior. Try It Yourself appears next to the relevant example. End with the existing next action, including a required Knowledge Check when the backend supplies that prerequisite. Keep contextual return navigation and the real mission/course position.

## 19. Try It Yourself

Present an editable example and its output in the shared workspace language. Label this practice clearly. Run updates the preview only; Reset restores the existing example according to current behavior. Preserve the existing return-to-lesson destination.

Use two working areas at desktop widths and accessible Code/Output modes at narrow widths. Switching modes must not recreate the editor or discard source, selection, or undo history. Do not add a completion control, grading claim, or academic mutation to this practice screen.

## 20. Knowledge Check

Give the question, choices, and existing submission action priority. Retain semantic fieldsets and legends, actual question numbering, instructions, answered count, and configured result/review content. Use aligned choices with comfortable hit areas rather than decorative option cards.

Preserve the current attempt, answer restoration, back navigation, retry policy, and submission granularity. Do not convert an existing whole-check submission into per-question grading. Question navigation may move focus within the existing form without creating a new attempt. Results distinguish recorded score, answer review, and the next permitted learning action.

Knowledge Check remains formative evidence. The Mission Atlas visual sequence must not imply that its score or completion determines Boss Challenge eligibility unless the existing authoritative backend rule explicitly says so. Required Coding Challenge completion and existing backend eligibility remain authoritative. Visual connectors never create academic prerequisites.

## 21. Coding Challenge

Desktop composition has Task, Editor, and Output regions below a compact context bar. At 1366px, target a 280px Task region, a flexible Editor, and a 300px Output region, with existing resize/collapse behavior. At larger widths, allow approximately 320px Task and 360px Output while keeping the Editor dominant. Resizing must preserve a usable editor; below the workspace breakpoint use modes instead of squeezing three panes.

Use 1100px as the initial design breakpoint for Task / Code / Output mode recomposition, not an immutable number. Browser acceptance may adjust the exact breakpoint for real content, zoom, localization or long text, and editor usability. Never squeeze the editor into an unusable width; recompose before usability degrades. All modes retain one mounted editor instance. Source, undo history, selection, draft state, and feedback survive pane changes. Inactive content is removed from focus traversal without destroying state. Document any final breakpoint adjustment with the viewport, content/state, observed usability issue, and browser evidence supporting the adjustment.

Run, Save draft, and Submit remain separate controls with distinct effects. Keep the primary submission action reachable without covering editor content. Show real requirements and existing feedback; do not expose hidden grading rules or solutions through redesigned task content. Preserve existing help availability and costs.

Preview is explicitly ungraded. Any stale-preview indication reflects source changes, not academic correctness. Submission errors and infrastructure failures preserve code and give the existing supported recovery action. Keep sandbox permissions and document-construction behavior unchanged. No interface redesign can grant student code additional application access.

## 22. Boss Challenge

Use the course-assessment double rule, a stronger title, and clear course context to distinguish Boss Challenge importance. Entry shows the actual prerequisites, authoritative readiness, assessment instructions, passing threshold, and existing attempt state. Do not invent time limits or attempt limits.

Once an attempt is open, use the same functional workspace composition as Coding Challenge. Preserve assessment-specific restrictions; do not add practice hints, solutions, draft capabilities, or reset behavior that the existing assessment does not provide. Retain existing consequential submission confirmation.

Keep recorded/submitted code read-only where required by current behavior. Pending evaluation, current attempt failure, previous pass, and course completion have separate explanations. Preserve existing resume-verification and retry flows rather than automatically resubmitting. A pass displays only authoritative course completion and next-course eligibility. A later practice failure must not visually erase an established pass.

## 23. Learning Performance

Use Learning Performance as a user-facing heading with Integrated Competency Analysis explained in supporting copy; preserve existing competency routes and model terminology. Organize actual Knowledge Check, Coding Challenge, and Boss evidence by the skill or course associations already supplied.

Show the evidence behind a state and the existing next-practice destination. Distinguish absent evidence from weak performance. Do not merge unlike scores, infer mastery from XP, invent a confidence percentage, or introduce a radar chart. Use ruled evidence rows and explanatory text before considering a chart.

## 24. Timeline

Present the Unified Learning Timeline as chronological, labeled records. Each entry identifies the existing event type, related work, recorded outcome, and timestamp. Use a restrained connecting line for chronology and group dates without inventing events.

Preserve existing event filtering, order, pagination, and links. Do not merge separate attempts into a fictitious single success or turn session bookkeeping into learning evidence. Long messages wrap; timestamps remain readable on mobile.

## 25. Recommendations

Show the existing prioritized recommendations as an action list with the supplied reason, relevant course/mission context, and supported destination. Emphasize the first applicable action without presenting all suggestions as equally weighted cards.

Retain existing review-queue semantics and permission checks. Do not infer a new recommendation from client-side metrics. If no recommendation is returned, explain that none is currently available and provide existing learning-path access without claiming the student has mastered everything.

## 26. Teacher operational screens

Classrooms, student monitoring, course analytics, Needs Attention, activity, and reports share the shell and operational table system. Show the current classroom, student, course, and date scope wherever the existing screen supplies them. Preserve the roster as the student picker and existing deep links back to it.

Monitoring distinguishes mission progress, Knowledge Check evidence, assessment history, and competency. Attention reasons link to supporting evidence where links already exist. Reporting keeps filters and export scope aligned, with dates and dataset names visible. Do not invent bulk intervention tools, teaching authority over curriculum, or new cross-classroom views.

## 27. Admin management screens

Use labeled filter toolbars, dense semantic tables, conventional forms, and clearly grouped actions. Keep user, classroom, course, section, mission, assessment, announcement, audit, and system screens within the same geometry and type system.

Preserve required fields, validation, version/concurrency controls, permissions, confirmations, and audit feedback. Put consequential actions away from routine navigation. Do not add a confirmation to every save or remove an existing protective confirmation. Display existing version/history information where supplied; do not invent publication controls or a new publishing workflow.

## 28. Login/public surfaces

Welcome introduces CodeQuest with a clear learning purpose and existing entry actions. Use a restrained illustrative progression treatment only when clearly non-personal; no fabricated user totals, outcomes, testimonials, or live system claims.

Login uses a narrow readable form, real username/password labels, existing validation feedback, and password-manager-compatible inputs. Preserve autocomplete, password paste, CSRF, and redirect behavior. No registration route/view was found in the inspected baseline; do not add a signup promise, registration page, or account-provisioning workflow.

## 29. Error pages

Use the shared canvas, sans reading text, compact technical status code, and a meaningful recovery link supported by the existing page. Preserve the standalone error layout's ability to render when the normal application shell or assets fail.

Explain forbidden access, missing pages, expired sessions, rate limits, server errors, and maintenance without exposing internal details. Do not present a server error as student failure or use fake terminal dumps as decoration. Recovery links must not silently repeat a mutation.

## 30. Empty states

Every empty state names what is absent, respects the current scope, and gives an existing useful action when available. Examples include no assigned courses, no matching students, no recorded learning evidence, and no administrative activity.

Filtered emptiness offers Reset filters. Unassigned learning does not claim completion. Missing competency evidence does not claim failure. Do not use zero-valued charts, decorative illustrations, or oversized cards to fill otherwise empty space.

## 31. Loading states

For existing asynchronous operations, retain the initiating label and show a concise pending description. Announce meaningful status changes once. Do not show fabricated numeric progress or grading success before a response.

Preserve visible source and prior evidence during a request. A failed request leaves a supported recovery action and does not clear work. Skeletons are limited to genuinely deferred content and reserve the actual layout; ordinary server-rendered navigation needs no artificial loading animation. Do not add polling, automatic retries, or background requests solely for visual effects.

## 32. Locked states

Show a lock symbol, the Locked label, and the actual prerequisite reason. Keep the title and reason readable. Do not provide an enabled mutation button for a denied action, and do not treat a disabled button as authorization enforcement.

Lock explanations are accessible without hover. Link to the prerequisite only when the existing data supplies a valid destination. A visual connector or course position cannot independently decide whether a mission or Boss Challenge is locked.

## 33. Completed states

Completion uses a checkmark, explicit label, and the recorded outcome. Provide the existing next action or review destination. Show awarded XP only when the authoritative response supplies it; do not animate speculative rewards.

Distinguish completed missions from a completed course and a passed assessment from a currently failed retry. Reopening completed work does not show a new completion event. Preview, draft save, and attempt creation never receive academic completion styling.

## 34. Responsive behavior

| Viewport | Required composition |
| --- | --- |
| 1920×1080 | 1440px maximum standard content, 48px available outer gutters, asymmetric objective/context composition; full-width three-region coding workspace |
| 1366×768 | 32px gutters, compact 56px shell, visible primary action, Task/Editor/Output workspace without wasting vertical space on stacked headings |
| 768px wide | 24px gutters, navigation disclosure as needed, vertical course structure, dashboard supporting groups below primary content; Task/Code/Output modes |
| 390px wide | 16px gutters, separate navigation/account controls, one course spine, wrapped titles, labeled operational records, 44px action targets; single active workspace mode |

Use 768×1024 and 390×844 as repeatable browser test sizes, plus short-height and landscape checks. At narrow widths, move secondary metadata below its related title rather than shrinking typography. Keep primary task first in both source and visual order. Do not use CSS ordering to create a conflicting keyboard order.

The page must not scroll horizontally. Code can scroll within its editor/example region. Teacher/Admin tables recompose into labeled records while retaining semantic headers and access to every field and action. Do not solve tables by clipping columns or hiding evidence. Sticky controls must reserve space and accommodate mobile browser chrome and the software keyboard.

## 35. Accessibility

Target [WCAG 2.2 AA](https://www.w3.org/TR/WCAG22/). Design compliance is an acceptance target, not a claim that unbuilt screens have passed.

- Source order: skip link, navigation, page heading/context, primary task, supporting evidence. Use native headings, lists, forms, and tables.
- Keyboard: every action works without a pointer. No keyboard traps. Disclosures and dialogs have defined Escape and focus-return behavior. Preserve browser and assistive-technology shortcuts.
- Focus: use the specified outline; sticky bars, overlays, and pane changes must not obscure the focused control. Do not remove focus styling for aesthetic reasons.
- Contrast: normal text at least 4.5:1; qualifying large text at least 3:1; required graphical/control indicators at least 3:1. Large text means at least 24 CSS px regular or approximately 18.67 CSS px bold. Measure syntax, states, and actual backgrounds.
- State: use text and symbol/structure beyond color. Decorative connectors and duplicate icons are hidden from assistive technology; state meaning remains in text.
- Forms: retain visible labels, required indications, autocomplete, field-associated errors, and a focused error summary when appropriate. Do not erase submitted input after validation failure.
- Menus: ordinary navigation uses disclosure/link semantics. Account disclosure remains keyboard operable. Do not assign menu roles without implementing the associated keyboard model.
- Dialogs: move focus into a labeled dialog, contain focus while modal, make the background inert, provide a visible safe exit where permitted, and return focus to the trigger. Preserve existing consequential-action rules.
- Live regions: ordinary static acknowledgments have no automatic alert/live semantics. Use one polite region for meaningful asynchronous status when needed. Use an alert for urgent newly presented errors, not every historical error or page-load record. Add atomic announcement only when the complete updated phrase is necessary.
- Tables: keep captions or accessible names, header associations, and row context. Responsive presentation must not remove header semantics. Sorting controls, where already available, expose their current state.
- Reflow: verify 200% text zoom and 400% browser zoom/reflow at an effective 320 CSS px width. No loss of content or controls. Preserve user text-spacing overrides.
- Motion: honor reduced motion and never make animation necessary to understand progress.
- Workspace: mode controls use the appropriate tab semantics at narrow widths, with arrow-key navigation and visible focus. Desktop pane controls expose expanded state. Existing resize handles retain keyboard adjustment and reset alternatives. CodeMirror retains an accessible way to leave the editor; pane changes preserve the editor instance.
- Behavior hooks: use explicit `data-*` attributes. ARIA roles, live-region attributes, and accessible labels must never be JavaScript interaction selectors. Updating an ARIA attribute to describe state is allowed; selecting the interaction target through that attribute is not.

## 36. Component primitives

Reuse existing Blade components and browser behavior before adding another abstraction. Define shared tokens first; repeated components consume them. Keep one visual contract for these repeated elements:

| Element | Contract |
| --- | --- |
| Button/link action | Primary, secondary, quiet, destructive; visible pending/disabled/focus states; no pill shape |
| Page header | Role/page context, one heading, concise explanation, existing actions |
| State indicator | Icon/shape plus text; optional technical metadata; no color-only meaning |
| Progress display | Real numerator/denominator and existing percent; accessible name and value |
| Mission entry | Actual title, supplied state, context, and authorized action within the course structure |
| Workspace region | Labeled Task, Editor, or Output area; preserved mounted content and pane controls |
| Code block | Selectable source, readable syntax, contained overflow |
| Form field | Visible label, input, help, associated error |
| Evidence table/record | Semantic relationships, scoped context, responsive labels, existing pagination |
| Feedback message | Tone, useful text, correct announcement semantics, explicit dismiss hook where supported |
| Disclosure/dialog | Native semantics where suitable, keyboard behavior, focus management |
| Empty/loading presentation | Honest state, preserved layout/context, supported next action |

Do not create wrapper components merely to add a border or spacing. Do not nest panel/card wrappers to achieve hierarchy.

## 37. Existing behavior/components to preserve

Preserve `layouts.app` and its named-route integration, authenticated role derivation, server-rendered view data, forms, CSRF fields, validation, and redirect targets. Existing file locations remain the implementation starting point, not a mandate to rewrite them.

Relevant UI files include `resources/views/dashboard.blade.php`, `students.blade.php`, `admin/dashboard.blade.php`, `learning-path.blade.php`, `lesson.blade.php`, `experiment.blade.php`, `knowledge-check.blade.php`, `challenge.blade.php`, and `assessments/show.blade.php`. Shared foundations include `resources/css/app.css`, `resources/css/student.css`, `resources/views/components/`, and `resources/js/workspace.js`.

Preserve CodeMirror reuse, source restoration, undo state, preview isolation, draft behavior, Knowledge Check attempt restoration, workspace keyboard controls, account-menu focus behavior, table labels, pagination, scoped filters, export parity, and existing error recovery. Reuse useful existing regression coverage rather than replacing behavioral tests with markup snapshots.

## 38. Existing visual patterns to retire

Retire equal-weight dashboard metric cards, nested panel composition, section accordions as the only path overview, all-monospace reading, large rounded toast containers, scanline/glow treatments, arbitrary role-specific palettes, decorative implementation labels, and account-menu-only mobile navigation.

Retire production reliance on a prototype-owned visual contract when the later approved implementation establishes canonical shared tokens. Preserve other prototype files and historical evidence. Do not perform unrelated cleanup or delete reference artifacts.

Retire ARIA-based dismissal discovery while preserving the interaction and correct announcements. Consolidate conflicting visual declarations only within the approved implementation scope; do not broadly reformat or refactor unrelated code.

## 39. Browser acceptance criteria

Acceptance requires fresh captures of actual implemented application screens using legitimate disposable fixtures, not generated concept images. Never use configured databases for fixture creation or smoke mutations without separate authorization. Record browser version, branch/commit, fixture state, viewport, screenshots, console exceptions, and test results.

- Capture Student, Teacher, and Admin dashboards at all four specified sizes. They share tokens, type, navigation treatment, controls, and state language while retaining different compositions.
- Check each dashboard without its logo: course/operational context, ruled structure, state markers, and typography still express the approved identity. No generic card grid remains.
- Student: verify current mission, saved-draft Continue target, no-course state, Boss-ready state, and course-complete state against backend output.
- Teacher: verify permitted roster, filters, pagination, assessment summaries, attention link, recent activity, and empty/filtered states. No partial roster is labeled a complete ranked queue.
- Admin: verify management links, authoritative counts, audit activity, permission-appropriate controls, and empty audit state.
- Learning screens: capture path states, long lesson content, practice, Knowledge Check input/results, challenge pending/failure/completion, and Boss locked/ready/pending/failed/passed states.
- Workspace: type unsaved code, change modes and resize/collapse regions, then confirm source, selection, undo history, draft state, and feedback survive. Verify preview alone changes no academic state.
- Keyboard: traverse navigation and account controls, close with Escape, verify focus return, operate forms and panes, and reach table actions without a mouse.
- Accessibility: measure rendered contrast; check reduced motion, text spacing, zoom/reflow, screen-reader names/order, live announcements, and responsive table semantics. Automated scans supplement manual checks.
- Layout: no page overflow, overlapping labels, hidden actions, clipped content, or obscured focused controls. Verify short laptop height and mobile software keyboard behavior.
- Errors: no unexplained page exceptions, duplicate interaction listeners, broken assets, or new script failures. Classify expected preview diagnostics rather than suppressing all console output.

No current browser acceptance is claimed by this document.

## 40. Regression-test expectations

Later implementation must run the narrow affected tests first and retain existing behavioral assertions. Existing coverage includes `DashboardTest`, `StudentDashboardNotificationsTest`, `TeacherDashboardTest`, `AdminDashboardTest`, `LearningPathTest`, `LearningPathRecommendationsTest`, `WorkspaceControlsTest`, and `WorkspaceBrowserTest` under `tests/Feature/`, plus existing Node/Chromium tooling.

Add focused coverage only for changed contracts: explicit dismissal hooks independent of ARIA, navigation keyboard behavior, role dashboard empty/state rendering, source-preserving workspace modes, and responsive evidence access. Do not duplicate existing tests or assert every styling class. Do not alter expected academic results to accommodate the redesign.

Run relevant authorization, teacher-scope, progression, and assessment integration coverage when shared view changes affect their rendered paths. Browser tests must load current production assets and use disposable data. Report actual passes, skips, failures, and unavailable checks.

Applicable completion gates include the Laravel suite, relevant Node tests, PHPStan, required formatter checks, production build, dependency audits, browser/accessibility acceptance, and `git diff --check`. This documentation-only task does not run or claim those implementation gates.

## 41. Protected backend/domain behavior

The redesign must not change authentication, authorization, RBAC, classroom scope, progression rules, scoring, XP, penalties, grading, assessment eligibility, Boss eligibility, competency calculations, attempts/history, journal behavior, curriculum, database schema, grader semantics, or configured database state.

Server-selected identity and authoritative outcomes remain authoritative. Preserve exactly-once rewards and existing replay/idempotency behavior. Draft is not Progress; Attempt is not Progress; Progress is not the XP ledger. Preserve one Boss Challenge per course, required-mission eligibility, pass-gated course completion, and existing next-course unlocking.

Teachers retain only their authorized academic scope. Admin management does not imply authority to falsify learning outcomes. Operators receive no additional academic authority or new role dashboard by implication. Preserve historical facts and existing transaction/error boundaries.

Student preview remains sandboxed and non-authoritative. Do not add same-origin access, navigation privileges, unrestricted execution, new server execution, or access to hidden grading fields. If a visual requirement cannot be met with existing data and behavior, stop that dependent change and explain the required backend expansion for separate review.

## 42. Risks

| Risk | Required handling |
| --- | --- |
| Legacy and prototype styles overlap | Establish one approved token contract during implementation; check all roles for cascade regressions |
| Reference image contains invented details | Use application titles, counts, permissions, and states; omit unsupported estimates |
| Dashboard data is smaller than the full learning model | Show supplied position/evidence and link to existing detailed screens; do not infer missing nodes or recommendations |
| Teacher dashboard lacks a complete ranked queue | Keep the existing Needs Attention route; any richer dashboard read model is a separate future dependency |
| Long titles, large courses, or sparse fixtures distort composition | Test populated, empty, long-content, additional-mission, and paginated states |
| Workspace panes consume short-height screens | Verify 1366×768, mobile keyboard, zoom, and persistent state during pane changes |
| Font declarations do not guarantee loaded fonts | Use local assets through the existing pipeline or fully usable system fallbacks; verify offline layout without runtime CDN or Internet dependencies |
| Mobile table restyling harms semantics | Test header associations, source order, labels, and every action with assistive technology |
| Theme controls or stored preferences conflict with the new palette | Do not silently remove existing functional preference behavior; resolve any incompatible existing theme requirement before changing it |
| Local concept image is not a portable repository asset | The image is optional; implement from this written contract alone when unavailable. Never require it for implementation, runtime, or builds |
| Earlier design documentation contradicts this specification | This specification governs the approved redesign; do not silently update unrelated documents in this task |

## 43. Definition of done

For specification approval: the authorized branch exists at the approved baseline, all 43 required sections are present, the four product-owner amendments are incorporated, the document has been self-reviewed for contradictions and invented domain behavior, and whitespace checks pass. The product owner has authorized a local checkpoint commit containing this specification only, followed by implementation planning. No product files, tests, or database state change as part of specification approval. No push or merge is authorized. Product-owner review of the implementation plan is the next gate.

For the later redesign: the approved specification is implemented across its agreed scope; all three role dashboards and the shell visibly share Mission Atlas; role-specific tasks and every protected behavior remain correct; responsive, keyboard, screen-reader, state, and browser acceptance meet sections 34–40; required regression gates have fresh evidence; and no unresolved release-critical regression remains. Visual similarity to the concept image alone does not establish completion.

Approval of this written specification permits implementation planning only. Implementation begins only after the subsequent plan is reviewed and authorized. No approval is inferred from this document's existence.
