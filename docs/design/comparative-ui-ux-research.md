# CodeQuest comparative UI/UX research and redesign direction

Research date: 2026-10-03. CodeQuest baseline: `a1a6f0cc61757784ec8ef8f5fe2cbc39bd876775`.

## Decision

The next design pass should address the learning interaction model before changing the visual treatment again. CodeQuest already has useful pieces: a prominent Continue Learning action, separate Lesson and Challenge modes, server-derived progression, mobile workspace tabs, a persistent action bar, and evidence-based competency services. Those pieces do not yet operate as one consistent product.

The largest weaknesses are inconsistent continuation targets, different coding experiences for ordinary Challenges and Boss Challenges, insufficient protection and recovery around unsaved work, and feedback that does not stay beside the task. The visual system also has two competing sources of tokens and component styling. More spacing and color patches would leave these problems intact.

Recommended direction: a learning-first student shell, one coding-workspace interaction contract with distinct practice and assessment policies, an evidence-oriented learning record, a classroom-scoped teacher console, and predictable admin directories. Borrow interaction principles from the repositories below, rather than their branding, curriculum, technology stacks, or grading models.

## Scope and evidence standard

This is comparative source research and a fresh inspection of CodeQuest's current implementation. It is not a browser inspection, usability study, contrast measurement, or accessibility certification. No application code, dependencies, database records, or configuration were changed. The deliverable is this document.

Evidence labels used below:

- **Confirmed in code:** a branch, component, markup, style, or service behavior is directly visible in the inspected source.
- **Design judgment:** an interpretation of that behavior against CodeQuest's tasks and approved specification. It is a recommendation, not a measured improvement in learner outcomes.
- **Browser validation needed:** layout, focus behavior, assistive-technology output, touch interaction, contrast, zoom, and software-keyboard behavior that source inspection cannot settle.

Repository source was studied through GitHub file retrieval and web browsing. The project analyses name the actual components inspected, including responsive layouts, operation states, navigation, and styling. Branch links can move; observed heads and retrieval limitations are recorded in the source inventory. No conclusion depends on stars, screenshots, or README marketing claims alone. Licenses establish the selection boundary; conceptual borrowing is the recommendation throughout.

The current CodeQuest source takes precedence over older audit descriptions. In particular, the earlier top-level Missions/Assessments vocabulary and vertically stacked Challenge panes are no longer accurate descriptions of the current implementation. Task / Code / Output tabs and a sticky action region already exist.

### CodeQuest constraints that references must respect

The [master specification](../../CODEQUEST_MASTER_SPEC_FINAL_UPDATED.md), especially the system-wide UX contract, canonical student experience, responsive-state preservation, and teacher/admin composition sections, remains the product authority. [CONTEXT.md](../../CONTEXT.md) establishes Mission as an internal record and Challenge as the student-facing term; implementation and the latest specification must be checked where older descriptions drift.

1. Course → Section → Lesson/Knowledge Check → Coding Challenge → Boss Challenge is CodeQuest's learning structure. Do not substitute a reference project's tracks, public exercise graph, or examination system.
2. Run previews code; Submit produces an authoritative academic outcome. Browser results must never award XP, mark completion, set scores, or unlock courses.
3. A Knowledge Check is formative. A Boss Challenge is summative and gates course progression. A required check can gate a Challenge without becoming a Boss Challenge.
4. Historical passes and evaluated attempts retain their meaning. A failed retry does not revoke an earlier course pass. Presentation must distinguish the latest attempt from retained achievement.
5. XP is an auditable resource, not competency. Missing evidence is not zero ability. Competency formulas remain owned by `CompetencyService`.
6. Teacher visibility follows authorized classroom/student/course intersections. A classroom selector may narrow that scope; it may never confer access. Teachers monitor rather than modify academic results.
7. Admins manage accounts, curriculum, classrooms, announcements, and lifecycle state. A redesign must not introduce arbitrary passing, XP edits, or hidden new capabilities.
8. Preserve Laravel/Blade, Tailwind v4, CodeMirror, existing services, route names, and the protected `system404` schema. None of the references justifies replacing the stack, adding a runtime dependency, or migrating production.
9. System 404 remains the identity layer. Use its motifs in branding, metadata, status, and completion moments; reading, coding, forms, and tables must remain clear.

## Comparative repository analyses

### 1. freeCodeCamp: curriculum orientation and a responsive coding workspace

[Repository](https://github.com/freeCodeCamp/freeCodeCamp) · [BSD-3-Clause license](https://github.com/freeCodeCamp/freeCodeCamp/blob/main/LICENSE.md).

**Problem and users.** A self-directed learner needs a route through a large programming curriculum, understandable coding feedback, and a way to continue after interruption. Its certification/curriculum structure differs from CodeQuest's classroom and course-gating model.

**Architecture and flows.** Curriculum stages contain certification/super-blocks, chapters/modules, blocks, and challenges. The [Map](https://github.com/freeCodeCamp/freeCodeCamp/blob/main/client/src/components/Map/index.tsx) groups current curricula and separates the archive. [Header](https://github.com/freeCodeCamp/freeCodeCamp/blob/main/client/src/components/Header/index.tsx) substitutes examination navigation during an exam. The [course introduction](https://github.com/freeCodeCamp/freeCodeCamp/blob/main/client/src/templates/Introduction/super-block-intro.tsx) expands the group associated with a breadcrumb, hash, current task, recent completion, or first group. This supports location and return, rather than making the learner rescan everything.

**What works.** [Challenge rows](https://github.com/freeCodeCamp/freeCodeCamp/blob/main/client/src/templates/Introduction/components/challenges.tsx) attach Start/Resume to the relevant incomplete project. [Map search](https://github.com/freeCodeCamp/freeCodeCamp/blob/main/client/src/templates/Introduction/components/super-block-search.tsx) includes a label, reset, counts, no-results text, and polite announcements. [Desktop layout](https://github.com/freeCodeCamp/freeCodeCamp/blob/main/client/src/templates/Challenges/classic/desktop-layout.tsx) uses resizable regions; [mobile layout](https://github.com/freeCodeCamp/freeCodeCamp/blob/main/client/src/templates/Challenges/classic/mobile-layout.tsx) changes to tabs while retaining mounted preview state. The useful principle is preserving task context while changing composition.

**Feedback and assessment.** [Execution feedback](https://github.com/freeCodeCamp/freeCodeCamp/blob/main/client/src/templates/Challenges/components/independent-lower-jaw.tsx) repeats announcements for repeated failures and makes the next successful action discoverable. [Quiz flow](https://github.com/freeCodeCamp/freeCodeCamp/blob/main/client/src/templates/Challenges/quiz/show.tsx) identifies unanswered questions, confirms finishing, and distinguishes editable from submitted answers. These are good interaction references; their frontend scoring must not become CodeQuest's academic authority.

**Friction and accessibility limits.** The introduction also appends a full curriculum map, increasing navigation competition. Dense mobile tabs warrant touch/zoom testing. [Test rows](https://github.com/freeCodeCamp/freeCodeCamp/blob/main/client/src/templates/Challenges/components/test-suite.tsx) derive hidden passed/failed text while running, so copying the spinner alone would reproduce an inconsistent spoken status. Numbered steps save space but convey less concept context than named lessons.

**Reuse and adaptation.** Adopt contextual resume, section expansion, preserved tab content, explicit operation announcements, and [semantic theme/font tokens](https://github.com/freeCodeCamp/freeCodeCamp/blob/main/client/src/components/layouts/variables.css). Keep CodeQuest's named Course/Section/Lesson hierarchy, fewer workspace modes, existing CodeMirror, and server-derived eligibility. Avoid detached preview windows, client completion heuristics, duplicated maps, and importing a certification examination model into a formative Knowledge Check.

### 2. Judge0 IDE: editor dominance and execution clarity

[Repository](https://github.com/judge0/ide) · [MIT license](https://github.com/judge0/ide/blob/master/LICENSE).

**Problem and users.** Developers and learners need to edit and execute programs in multiple languages. It is an execution tool, so courses, learner dashboards, progression, competency, and classroom monitoring are outside its purpose rather than missing features.

**Architecture and flows.** [The page](https://github.com/judge0/ide/blob/master/index.html) exposes language/configuration and Run around a docked editor/I/O workspace. [Layout and execution code](https://github.com/judge0/ide/blob/master/js/ide.js) gives source width 66 in the initial layout, read-only output, automatic editor sizing, and no minimap. Running activates Output, clears old output, displays loading, and then displays execution status and resource information. Ctrl/Cmd+Enter has platform-aware shortcut copy.

**What works.** The editor owns the available area, while output has a specific purpose and lifecycle. [Minimal configuration](https://github.com/judge0/ide/blob/master/js/configuration.js) hides advanced compiler/CLI and other overhead. [Theme handling](https://github.com/judge0/ide/blob/master/js/theme.js) updates both editor and shell. These patterns reduce the effort between understanding a task and seeing its result.

**Friction and accessibility limits.** The default configuration exposes options beginners may not need. Page markup includes clickable div actions and fields without explicit labels. No mobile tab composition was found in the inspected [small stylesheet](https://github.com/judge0/ide/blob/master/css/ide.css) or main layout; a resizable desktop dock is insufficient evidence of phone usability. The HTTP error modal exposes a raw response rather than a learner-oriented recovery action. These are source-supported concerns, not measured failures on every device.

**Reuse and adaptation.** Borrow editor-first sizing, minimal task-specific controls, visible execution states, output activation, and a discoverable shortcut. Use native labelled buttons and CodeQuest's keyboard-operable separators. Do not replace CodeMirror with Monaco/GoldenLayout, expose language configuration the curriculum does not need, copy wildcard messaging boundaries, or interpret browser execution status as a pass. CodeQuest Run is preview; academic Submit remains distinct. A result should identify which source revision produced it and say when code has changed since that run.

### 3. Moodle: course orientation and assessment navigation

[Repository](https://github.com/moodle/moodle) · [GPL-3.0 license](https://github.com/moodle/moodle/blob/b67620977802d28b91d1cf900558b60c9dfd8e9a/COPYING.txt).

**Problem and users.** Students, educators, and administrators need an institutional course environment containing many activity types. Its breadth explains navigation complexity that a smaller programming platform should not inherit.

**Architecture and flows.** The [Boost navbar](https://github.com/moodle/moodle/blob/b67620977802d28b91d1cf900558b60c9dfd8e9a/public/theme/boost/templates/navbar.mustache) separates primary navigation from user/context tools and names its mobile drawer control. [Course overview](https://github.com/moodle/moodle/blob/b67620977802d28b91d1cf900558b60c9dfd8e9a/public/blocks/myoverview/templates/main.mustache) composes search, grouping, sorting, and display partials. A course-local index locates the current activity independently of the global shell.

**What works.** [Activity entries](https://github.com/moodle/moodle/blob/b67620977802d28b91d1cf900558b60c9dfd8e9a/public/course/format/templates/local/courseindex/cm.mustache) express restrictions/current location, while [completion markup](https://github.com/moodle/moodle/blob/b67620977802d28b91d1cf900558b60c9dfd8e9a/public/course/format/templates/local/courseindex/cmcompletion.mustache) distinguishes complete, incomplete, failed, and no-completion states. [Course-index behavior](https://github.com/moodle/moodle/blob/b67620977802d28b91d1cf900558b60c9dfd8e9a/public/course/format/amd/src/local/courseindex/courseindex.js) expands the current section. Learners see where they are and why another item is unavailable.

**Assessment and recovery.** The [quiz renderer](https://github.com/moodle/moodle/blob/b67620977802d28b91d1cf900558b60c9dfd8e9a/public/mod/quiz/classes/output/renderer.php) includes current/flagged question states, a linked pre-submit summary, unanswered counts, and connection-loss/restoration messaging. [Final confirmation](https://github.com/moodle/moodle/blob/b67620977802d28b91d1cf900558b60c9dfd8e9a/public/mod/quiz/amd/src/submission_confirmation.js) addresses consequential finishing. [Empty course overview](https://github.com/moodle/moodle/blob/b67620977802d28b91d1cf900558b60c9dfd8e9a/public/blocks/myoverview/templates/zero-state.mustache) supplies explanation and contextual actions.

**Accessibility, cohesion, and friction.** [Drawers](https://github.com/moodle/moodle/blob/b67620977802d28b91d1cf900558b60c9dfd8e9a/public/lib/amd/src/drawer.js) update ARIA state and return focus to the opener. [Drawer styles](https://github.com/moodle/moodle/blob/b67620977802d28b91d1cf900558b60c9dfd8e9a/public/theme/boost/scss/moodle/drawer.scss) account for hidden content and fixed-header scrolling; [theme setup](https://github.com/moodle/moodle/blob/b67620977802d28b91d1cf900558b60c9dfd8e9a/public/theme/boost/scss/preset/default.scss) maps semantic tokens and font stacks. Conversely, [filter behavior](https://github.com/moodle/moodle/blob/b67620977802d28b91d1cf900558b60c9dfd8e9a/public/blocks/myoverview/amd/src/view_nav.js) clears search when grouping changes, breaking composability. Multiple catalog controls can overwhelm a short learning path.

**Reuse and adaptation.** Adopt explicit current/restricted/completed states, course-local orientation, focus-returning disclosures, composable persistent filters, and cause-specific recovery. Prefer semantic section lists and native links over an ARIA tree unless its complete keyboard contract is implemented. Avoid plugin/framework infrastructure, examination ceremony on every formative check, and Moodle's activity/grade definitions. Teacher/admin breadth informs disciplined scope and directories, not identical layouts for all CodeQuest roles.

### 4. PrairieLearn: explicit assessment and grading states

[Repository](https://github.com/PrairieLearn/PrairieLearn) · [mixed license terms](https://github.com/PrairieLearn/PrairieLearn/blob/f6731bf1ca287ac4de6a6f15dbfe2f8851107b85/LICENSE), including AGPL client-side terms and separately licensed enterprise portions. This is a community-source interaction study; no enterprise feature or source incorporation is proposed.

**Problem and users.** Students and instructors need reliable homework/exam delivery and explainable grading. Its course-instance → assessment → question variant → submission model differs from CodeQuest's lesson-first progression and XP economy.

**Architecture and flows.** [PageLayout](https://github.com/PrairieLearn/PrairieLearn/blob/f6731bf1ca287ac4de6a6f15dbfe2f8851107b85/apps/prairielearn/src/components/PageLayout.tsx) separates global, contextual, assessment, and optional side navigation. [Navbar](https://github.com/PrairieLearn/PrairieLearn/blob/f6731bf1ca287ac4de6a6f15dbfe2f8851107b85/apps/prairielearn/src/components/Navbar.tsx) gives students a small destination set and a skip link. [Assessment listings](https://github.com/PrairieLearn/PrairieLearn/blob/f6731bf1ca287ac4de6a6f15dbfe2f8851107b85/apps/prairielearn/src/pages/studentAssessments/studentAssessments.html.tsx) distinguish Not started, withheld score, future availability, inactive access, and closed work rather than treating absent grades as zero.

**What works.** The [question page](https://github.com/PrairieLearn/PrairieLearn/blob/f6731bf1ca287ac4de6a6f15dbfe2f8851107b85/apps/prairielearn/src/pages/studentInstanceQuestion/studentInstanceQuestion.html.ts) gives the task nine desktop columns and contextual score three. [QuestionContainer](https://github.com/PrairieLearn/PrairieLearn/blob/f6731bf1ca287ac4de6a6f15dbfe2f8851107b85/apps/prairielearn/src/components/QuestionContainer.tsx) separates task, authorized feedback, issues, and submission history, initially showing only recent submissions. Density follows task priority.

**States and assessments.** [QuestionScore](https://github.com/PrairieLearn/PrairieLearn/blob/f6731bf1ca287ac4de6a6f15dbfe2f8851107b85/apps/prairielearn/src/components/QuestionScore.tsx) distinguishes unanswered, saved, grading, and manual-grading pending. [Footer actions](https://github.com/PrairieLearn/PrairieLearn/blob/f6731bf1ca287ac4de6a6f15dbfe2f8851107b85/apps/prairielearn/src/pages/studentAssessmentInstance/components/ExamFooterContent.tsx) change labels according to the actual Save/Grade/Finish policy. [Assessment overview](https://github.com/PrairieLearn/PrairieLearn/blob/f6731bf1ca287ac4de6a6f15dbfe2f8851107b85/apps/prairielearn/src/pages/studentAssessmentInstance/studentAssessmentInstance.html.tsx) explains finalization and editing restrictions. State honesty is the main borrowing opportunity.

**Responsive behavior and friction.** [Split-pane CSS](https://github.com/PrairieLearn/PrairieLearn/blob/f6731bf1ca287ac4de6a6f15dbfe2f8851107b85/apps/prairielearn/assets/stylesheets/splitPane.css) uses flex/min-height constraints and natural mobile scrolling; [page layout](https://github.com/PrairieLearn/PrairieLearn/blob/f6731bf1ca287ac4de6a6f15dbfe2f8851107b85/apps/prairielearn/assets/stylesheets/pageLayout.css) includes mobile overlay and reduced-motion behavior. Do not copy [Scorebar](https://github.com/PrairieLearn/PrairieLearn/blob/f6731bf1ca287ac4de6a6f15dbfe2f8851107b85/apps/prairielearn/src/components/Scorebar.tsx) as an accessibility model: it lacks numeric progressbar ARIA. Many point columns, policy variants, and role overrides are warranted there but would burden CodeQuest.

**Reuse and adaptation.** Borrow received-versus-graded distinctions, immutable review, concise history disclosure, unavailable/withheld states, and task-dominant composition. Keep CodeQuest's synchronous or resumable verification behavior truthful; do not invent background queues, polling, timed exams, partial-credit formulas, impersonation controls, or a generic grade percentage masquerading as competency. Its stacked mobile question/sidebar layout is unsuitable as a replacement for CodeQuest's coding tabs.

### 5. Oppia: goal-oriented learning and answer transitions

[Repository](https://github.com/oppia/oppia) · [Apache-2.0 license](https://github.com/oppia/oppia/blob/23369a83784002c749a706608917b9ef914ebbca/LICENSE).

**Problem and users.** Learners need interactive, tutor-like explanations and practice; contributors author educational explorations. Topics contain stories, chapters, and explorations. Its conversational pedagogy is relevant to lessons and formative checks, but it is not a summative coding IDE.

**Architecture and flows.** The [learner dashboard](https://github.com/oppia/oppia/blob/23369a83784002c749a706608917b9ef914ebbca/core/templates/pages/learner-dashboard-page/learner-dashboard-page.component.html) composes Home, Goals, Progress, Certificates, and Suggestions. [Home](https://github.com/oppia/oppia/blob/23369a83784002c749a706608917b9ef914ebbca/core/templates/pages/learner-dashboard-page/home-tab.component.html) prioritizes continuation and distinguishes no resumable work from exhausted suggestions. [Progress](https://github.com/oppia/oppia/blob/23369a83784002c749a706608917b9ef914ebbca/core/templates/pages/learner-dashboard-page/progress-tab.component.html) separates incomplete/completed learning and provides an empty-state action.

**What works.** [Chapter maps](https://github.com/oppia/oppia/blob/23369a83784002c749a706608917b9ef914ebbca/core/templates/pages/story-viewer-page/story-viewer-page.component.html) distinguish current, completed, unavailable, and coming-soon content. [Lesson actions](https://github.com/oppia/oppia/blob/23369a83784002c749a706608917b9ef914ebbca/core/templates/pages/exploration-player-page/current-lesson-player/layout-directives/progress-nav.component.html) change among Submit, Continue, Revise, and other formative actions based on response state. [Conversation composition](https://github.com/oppia/oppia/blob/23369a83784002c749a706608917b9ef914ebbca/core/templates/pages/exploration-player-page/current-lesson-player/learner-experience/conversation-skin.component.html) constrains tutor reading width; [responsive styles](https://github.com/oppia/oppia/blob/23369a83784002c749a706608917b9ef914ebbca/core/templates/pages/exploration-player-page/current-lesson-player/learner-experience/conversation-skin.component.css) relocate supplemental interaction into the reading flow.

**Friction and accessibility limits.** Dashboard source retains old/new and separate mobile structures, with numerous [breakpoints](https://github.com/oppia/oppia/blob/23369a83784002c749a706608917b9ef914ebbca/core/templates/pages/learner-dashboard-page/learner-dashboard-page.component.css). Some filter/removal controls use click-only paragraphs/icons; next-lesson markup nests a button in a link. The [story loading failure branch](https://github.com/oppia/oppia/blob/23369a83784002c749a706608917b9ef914ebbca/core/templates/pages/story-viewer-page/story-viewer-page.component.ts) does not visibly balance loader cleanup, creating a potential stuck-loading path. These are specific source concerns, not a blanket accessibility verdict. Large illustrated chapter tracks reduce overview density.

**Reuse and adaptation.** Adopt a single next action per learning state, concept-first lessons, useful completed/empty states, and [help consequence disclosure](https://github.com/oppia/oppia/blob/23369a83784002c749a706608917b9ef914ebbca/core/templates/pages/exploration-player-page/current-lesson-player/modals/display-solution-interstitial-modal.component.html). Retain CodeQuest's server-owned help charges. Avoid parallel legacy/new shells, decorative path mazes, conversational skipping in Boss Challenges, and invented adaptive mastery. A responsive prose flow does not justify vertically stacking a coding workspace.

### 6. CodeCombat: coding feedback inside a gamified learning loop

[Repository](https://github.com/codecombat/codecombat) · [MIT source license](https://github.com/codecombat/codecombat/blob/e26d50e8ea466e7158b4bae3bcbb3ef84436c40f/LICENSE). [Level/content rights differ](https://github.com/codecombat/codecombat/blob/e26d50e8ea466e7158b4bae3bcbb3ef84436c40f/LICENSE-LEVELS.md); no levels, art, audio, or branding should be imported.

**Problem and users.** Students learn programming by controlling a simulated world; teachers manage classroom courses. Its multiple game/products and tournaments explain complexity that CodeQuest's HTML/CSS/JavaScript learning path does not need.

**Architecture and flows.** The [student course page](https://github.com/codecombat/codecombat/blob/e26d50e8ea466e7158b4bae3bcbb3ef84436c40f/app/templates/courses/courses-view.pug) groups course instances under classroom/language/teacher context, with Map and named Start/Continue actions. Locked/completed courses have different actions. The [course controller](https://github.com/codecombat/codecombat/blob/e26d50e8ea466e7158b4bae3bcbb3ef84436c40f/app/views/courses/CoursesView.js) manages these course/session states. Game feedback links editing to execution, goals, and continuation.

**What works.** [Run/Submit/Done markup](https://github.com/codecombat/codecombat/blob/e26d50e8ea466e7158b4bae3bcbb3ef84436c40f/app/templates/play/level/tome/cast-button-view.pug) and its [state controller](https://github.com/codecombat/codecombat/blob/e26d50e8ea466e7158b4bae3bcbb3ef84436c40f/app/views/play/level/tome/CastButtonView.coffee) distinguish running from finishing. [Problem alerts](https://github.com/codecombat/codecombat/blob/e26d50e8ea466e7158b4bae3bcbb3ef84436c40f/app/views/play/level/tome/ProblemAlertView.coffee) combine source errors, hints, line context, and a return to editing. A [screen-reader surface](https://github.com/codecombat/codecombat/blob/e26d50e8ea466e7158b4bae3bcbb3ef84436c40f/app/views/play/level/ScreenReaderSurfaceView.coffee) supplies named text alternatives and keyboard exploration for the visual world. The principle is meaningful textual output, not reliance on rendered imagery alone.

**Friction and metric clarity.** [Teacher progress dots](https://github.com/codecombat/codecombat/blob/e26d50e8ea466e7158b4bae3bcbb3ef84436c40f/app/views/teachers/classes/CourseProgressDotView.vue) place important counts in hover popovers on non-focusable divs and recompute statistics client-side. Tooltip completion and percentage calculations use different membership denominators; those can represent different measures, but their meaning needs explanation. Some disabled controls use CSS classes or attributes on links rather than native semantics. Animated/sounded failures and many product branches add cognitive and maintenance cost.

**Reuse and adaptation.** Borrow classroom context, named next tasks, distinct action stages, contextual repair feedback, textual result alternatives, and [typography/color roles](https://github.com/codecombat/codecombat/blob/e26d50e8ea466e7158b4bae3bcbb3ef84436c40f/app/styles/style-flat-variables.sass). Avoid client-derived academic results, hover-only evidence, noisy failure effects, game inventory/economy, and the [game-versus-editor split](https://github.com/codecombat/codecombat/blob/e26d50e8ea466e7158b4bae3bcbb3ef84436c40f/app/styles/play/play-level-view.sass). Its simulation is pedagogically central; CodeQuest needs an editor-dominant workspace and server-evaluated, separately explained competency.

### Supplementary comparison: Exercism's public website source

[Repository](https://github.com/exercism/website). This is **not counted among the six confirmed open-source references**: its [README](https://github.com/exercism/website/blob/main/README.md) describes an internal public-source repository and discourages reuse; no license grant was established in this pass. Study the interaction concepts, not its source/assets for incorporation.

**Problem, users, and architecture.** Individual learners practice through language tracks, exercises, test runs, submitted iterations, feedback, mentoring, and community solutions. That wider feedback ecosystem explains a richer sidebar than CodeQuest currently needs.

**Useful patterns.** [Editor.tsx](https://github.com/exercism/website/blob/main/app/javascript/components/Editor.tsx) separates draft, run, cancellation, submission, and focus states. Submission availability corresponds to the latest passing run and current files. [ExerciseList](https://github.com/exercism/website/blob/main/app/javascript/components/student/ExerciseList.tsx) preserves search/status in URL history. [FetchingBoundary](https://github.com/exercism/website/blob/main/app/javascript/components/FetchingBoundary.tsx) centralizes loading/error presentation. Conceptually, these support recoverable work and predictable filtered lists.

**Friction and boundaries.** [SplitPane](https://github.com/exercism/website/blob/main/app/javascript/components/common/SplitPane.tsx) clamps pointer resizing but lacks the keyboard separator semantics CodeQuest already has. [Status dots](https://github.com/exercism/website/blob/main/app/javascript/components/student/ExerciseStatusDot.tsx) depend on visual styling/tooltips without local accessible names. Filtered-zero results can produce an empty grid because the list checks unfiltered length. Avoid hover-only prerequisites, misleading disabled-button explanations, asynchronous focus stealing, and unnecessary mentoring/publishing tabs. Do not infer server trust from client test results in a request. CodeQuest needs the state separation, not Exercism's completion/publishing model or frontend verdict contract.

### Source inventory and limits

| Project | Observed ref | Frontend inspection scope |
| --- | --- | --- |
| freeCodeCamp | `41eebad084128e7d68ed1344f15ddfca0525a53c`, main | Map/header/navigation; introduction/current-group/search; desktop/mobile/action workspace; feedback/test rows; quiz; theme variables. Sixteen substantive frontend files. |
| Judge0 IDE | `4e0e7a4bfe3217e07f4a88967bc7d4837b8e38c0`, master | Page markup, layout/execution, configuration, theme, stylesheet, license. Five implementation files plus license. |
| Moodle | `b67620977802d28b91d1cf900558b60c9dfd8e9a`, main | Navbar; overview/search/preferences/empty/progress; course-index entries/completion/tree; drawers/styles/tokens; quiz renderer/finalization. Sixteen source reads. |
| PrairieLearn | `f6731bf1ca287ac4de6a6f15dbfe2f8851107b85`, master | Layout/navbar; assessment/gradebook/question pages; score/history/footer/state components; client lifecycle; responsive CSS. Seventeen reads, including a CSS re-export not used to infer package internals. |
| Oppia | `23369a83784002c749a706608917b9ef914ebbca`, develop | Dashboard/home/progress; story map/controller; practice; lesson navigation/conversation/continue/help; responsive CSS and license. Twelve substantive frontend files plus license. |
| CodeCombat | `e26d50e8ea466e7158b4bae3bcbb3ef84436c40f`, master | Course page/controller/styles; teacher progress; execution controls; problem alerts; screen-reader surface; workspace/type/color styles; licenses. Eleven substantive frontend files plus small mount template and licenses. |
| Exercism, supplementary | `6cb5042b500f3c7930e61bce3eec547555469c7d`, main | Editor/operation/focus hooks; split pane; exercise list/status chart/dots; fetching boundary. Ten frontend files, public-source/license limitation above. |

Moodle/PrairieLearn/Oppia/CodeCombat evidence links are commit-pinned. The practice references were read from branches before head observation and are not uniformly pinned; their branch links are intentional. Moodle's recursive tree response was truncated, so known files were retrieved directly. No missing candidate path was treated as proof that a feature does not exist. This is a selected-file architectural comparison, not an exhaustive audit of these repositories. None was run locally or inspected live.

## Current CodeQuest weaknesses

The following findings concern the current baseline, including the recent UI changes.

| ID | Current weakness and evidence | Classification | Practical consequence |
| --- | --- | --- | --- |
| CQ01 | Dashboard continuation uses `ResumeService::resolve()` → `DashboardService::nextMission()`, which selects the first unfinished mission in order. `LearningPathService::nextMission()` instead prefers a saved draft. [ResumeService](../../app/Services/ResumeService.php), [DashboardService](../../app/Services/DashboardService.php), [LearningPathService](../../app/Services/LearningPathService.php) | Confirmed in code | An earlier unfinished Challenge A and a later drafted Challenge B can produce different next-task suggestions. A learner cannot rely on a consistent answer to “what should I continue?” |
| CQ02 | Boss Run writes raw editor text to `iframe.srcdoc`; Challenge Run uses `previewDocument(source, type)`. [Boss view](../../resources/views/assessments/show.blade.php), [Challenge view](../../resources/views/challenge.blade.php), [preview adapter](../../resources/js/preview.js) | Confirmed in code | Raw CSS is not installed as a stylesheet, and raw JavaScript is not installed as executable script by the Boss preview path. The same learning language has different preview behavior across modes. |
| CQ03 | Boss terminal-attempt screens say “Review your code,” but their `EditorView` configuration does not set read-only/editability policy. Submit is correctly omitted. [Boss editor configuration](../../resources/views/assessments/show.blade.php) | Confirmed configuration gap; interaction needs browser verification | A review surface can appear editable while the recorded result remains immutable. Learners may mistake temporary editor changes for an amended attempt. This is a presentation problem, not evidence of a server mutation bypass. |
| CQ04 | Editor import failure replaces the host with an alert but does not disable the adjacent submission/help forms. Their hidden code fields start empty and are populated during editor initialization. [app.js](../../resources/js/app.js), [Challenge actions](../../resources/views/challenge.blade.php), [Boss actions](../../resources/views/assessments/show.blade.php) | Confirmed in code; consequences depend on the submitted request | An unavailable editor leaves enabled actions that look usable. Readiness, retry, fallback editing, and failure recovery lack one contract. |
| CQ05 | Challenge edits mark “Unsaved changes”; Save is a form POST and redirect. No navigation guard is present in the inspected editor scripts. Pane state is in-memory. Boss has a separate operation flow. [Challenge script](../../resources/views/challenge.blade.php), [workspace.js](../../resources/js/workspace.js), [MissionController](../../app/Http/Controllers/MissionController.php) | Confirmed in the inspected paths | Tab switching preserves the document, but page navigation/reload and action redirects do not preserve the same interaction state. Saving, returning from a hint purchase, and exiting need explicit continuity rules. |
| CQ06 | Challenge Output is a single preview iframe. A failed submission is reduced to `firstFailure()` and rendered in a dismissible toast; there is no persistent structured Feedback surface. After buying a hint, copy says “Assistance below,” although assistance now lives in Task and narrow mode initially selects Code. [Challenge view](../../resources/views/challenge.blade.php), [firstFailure](../../app/Http/Controllers/MissionController.php) | Confirmed in code | A learner must connect preview output, a detached failure message, and a hidden Task pane. Important task feedback can be dismissed or difficult to find. |
| CQ07 | Below `md`, primary destinations appear only inside the avatar/account disclosure. [student-header](../../resources/views/components/student-header.blade.php) | Confirmed composition; discoverability is a design judgment | Navigation to Learn and Boss Challenges requires opening a control labelled “Open account menu.” Account operations and learning navigation have different purposes. |
| CQ08 | Challenge completion percentage is labelled “Course progress” on the dashboard and map. Course completion separately requires a Boss pass. Competency repeats the Challenge completion bar; its skill section is labelled “Boss Challenge evidence,” while the inspected skill formula combines mapped Knowledge Check and Challenge evidence. [dashboard](../../resources/views/dashboard.blade.php), [learning-path](../../resources/views/learning-path.blade.php), [competency](../../resources/views/competency.blade.php), [CompetencyService](../../app/Services/CompetencyService.php) | Confirmed labels/formula; interpretation is a design judgment | “100%” can coexist with an unfinished course, and a skill percentage can be attributed to the wrong evidence source. The UI needs stage labels and evidence explanations, not new formulas. |
| CQ09 | The teacher roster's Attn cell is a literal dash for every row. Needs Attention exists separately, but that page still displays a SYSTEM-WIDE badge and has no per-student drill-down link in its inspected cards. [students](../../resources/views/students.blade.php), [needs-attention](../../resources/views/needs-attention.blade.php), [AttentionService](../../app/Services/AttentionService.php) | Confirmed in code | The monitoring table promises an attention signal it does not show. Scope wording contradicts the classroom authorization model, and attention detection is separated from the student detail action. |
| CQ10 | Admin Users has search and pagination; Courses has a count and table without equivalent search/filter controls. Its header exposes `ORDER_NUM`, and several directories duplicate a page title with a secondary directory title. [users directory](../../resources/views/admin/users/index.blade.php), [courses directory](../../resources/views/admin/courses/index.blade.php) | Confirmed examples; cross-directory consistency is a design judgment | Similar management tasks have different control positions and vocabulary. Added result counts do not by themselves establish a management design system. Do not add unsupported Create actions just for symmetry. |
| CQ11 | Production `student.css` imports the prototype stylesheet, which imports Tailwind and defines tokens/components. `app.css` defines another theme and global element styling; student/console rules then override it. Global table styling includes a literal green `!important` border. [student CSS](../../resources/css/student.css), [app CSS](../../resources/css/app.css), [prototype CSS](prototype/styles.css) | Confirmed in code | Source order, selector specificity, and hard-coded values carry design meaning that should live in tokens and components. A change to a prototype can affect production presentation. |
| CQ12 | The dashboard repeats course progress in its hero, summary, and course panel, and competency in its summary and separate panel. The Learning Path places recommendation rows before course content and contains multiple Continue actions and an overview per course. [dashboard](../../resources/views/dashboard.blade.php), [learning-path](../../resources/views/learning-path.blade.php) | Confirmed duplication; hierarchy is a design judgment | The main action is prominent, but supporting information repeats instead of adding distinct value. On long paths, orientation competes with recommendations and repeated navigation. |
| CQ13 | Knowledge Check already has Answered n/N and links to unanswered questions. On smaller screens the summary follows the entire question list; the native inputs do not use `required`, and the client summary does not prevent or review an incomplete submit. [Knowledge Check](../../resources/views/knowledge-check.blade.php), [summary script](../../resources/js/knowledge-check.js) | Confirmed in code; mobile friction needs browser validation | The desktop pattern is useful, but mobile answer navigation, review, and error focus are unfinished. Server validation remains necessary; client assistance must not become grading authority. |
| CQ14 | The Challenge constrains the body to `100dvh`, hides body overflow, and uses several header/action layers. Tabs and separators have keyboard support, but there is no demonstrated software-keyboard/zoom behavior or explicit editor accessible-name configuration in the inspected initialization. [student CSS](../../resources/css/student.css), [workspace.js](../../resources/js/workspace.js), [editor setup](../../resources/js/editor.js) | Confirmed constraints/omissions; failure is not established without browser tests | The editor may become too short when the keyboard or zoom reduces usable space. Screen-reader naming, focus visibility, and scrolling must be tested rather than inferred from the presence of ARIA attributes. |

### What should be preserved

Preserve the Dashboard's dominant Continue Learning action, the Lesson's constrained reading width, separation of teaching from coding, the existing native Knowledge Check radios/fieldsets, server-derived state and historical-pass behavior, responsive table labels, and keyboard-operable workspace separators/tabs. Research should improve their consistency and recovery behavior, rather than discard working foundations.

## Proposed adaptations

### R01: One workspace shell, distinct practice and assessment policies

- **Current problem:** CQ02–CQ04 and CQ14. Ordinary Challenge has the new pane system, but Boss still stacks its briefing, editor, and preview beside metadata. Preview adapters, readiness, and review editability differ.
- **Reference and why it handles this better:** freeCodeCamp's desktop/mobile layouts preserve a common task across compositions; PrairieLearn's question/container components separate task layout from grading policy. Judge0 gives editing the dominant region.
- **Adapt:** One workspace shell with compact context header, Task/Code/Output, collapsible/resizable desktop panes, and persistent actions. Start around a 300–360px Task pane and 60–70% editor share where width permits; collapse Output or Task before squeezing the editor. On narrow widths preserve the existing tabs and mounted editor. Render closed Boss source as immutable evidence.
- **Avoid:** One giant conditional template; fixed percentages that fail at intermediate widths; pretending practice and Boss have the same Save/Hint/Retry permissions; remounting the editor when tabs change.
- **Implementation:** Extract Blade shell/pane/action primitives from the current Challenge. Supply server-derived capabilities for practice, active assessment, pending verification, and terminal review. Centralize CodeMirror creation and `previewDocument` usage in production JS. Initialize controls disabled until editor readiness is known. A failed loader must expose recovery and the recoverable source; enable fallback submission only if a textarea is genuinely synchronized with the request payload. Preserve iframe sandbox/CSP and server verdict authority.

### R02: Persistent output and submission feedback

- **Current problem:** CQ06. Live preview, grader failure toast, hint location, and submission state are disconnected.
- **Reference and why it handles this better:** CodeCombat contextualizes errors beside editing; PrairieLearn separates submission/history/grade states; freeCodeCamp reannounces repeated failures and exposes the next action.
- **Adapt:** Output contains **Preview / Console / Feedback**. Feedback is an enduring server-result summary, with authorized test details when supplied. Label Preview as a local rendering result. Show attempt identity and source revision, and flag a result as belonging to earlier code after an edit. On failure offer repair/retry according to the backend outcome; on pass offer the server-derived next step.
- **Avoid:** A fake “Tests passed” panel populated from the iframe, exposing hidden evaluator rules, losing results when a toast closes, raw infrastructure errors, or animated/sounded failure effects.
- **Implementation:** Preserve existing bounded console capture and render text safely. Carry permitted result data through the existing controller/session or attempt read model into a feedback component; extend the trusted protocol only if necessary and separately reviewed. A hint response opens or links to Task → Assistance. Announce an operation summary through one live region. Focus the result heading after explicit submission, with a return-to-code control; background activity must not steal focus.

### R03: Recoverable work and honest operation states

- **Current problem:** CQ04–CQ05. Dirty state exists, but navigation protection, request failure recovery, and shared busy/readiness handling do not.
- **Reference and why it handles this better:** Exercism separates draft/run/submission state conceptually; Oppia changes actions with response state; PrairieLearn distinguishes saved work from received and graded work.
- **Adapt:** Independent readiness, draft, preview, and submission states. Use visible “Unsaved changes / Saving / Saved / Save failed” and “Submitting / Submission recorded / Verification unavailable / Result” only where those states exist. Preserve source through request failure; warn before leaving dirty work. Disable duplicate consequential actions during their active request.
- **Avoid:** Calling work Saved before server confirmation, invented autosave guarantees, optimistic academic passes, automatic re-submission, or a permanent spinner for a recoverable verification failure.
- **Implementation:** Begin with progressive enhancement of existing POST forms and stored drafts, not a SPA. Update dirty state against the last confirmed source, use a conditional exit guard, preserve pane/focus preferences on return, and keep requests retryable without losing code. Boss currently has different persistence: do not promise Boss autosave/drafts without backend support. Any local recovery snapshot needs user/task/version scoping and an explicit privacy/retention decision. Reuse existing “Resume verification”; add polling only if a real backend lifecycle supports it.

### R04: A consistent learning position and intelligible path

- **Current problem:** CQ01 and CQ12. Continue targets can differ, while repeated actions and recommendations compete with path orientation.
- **Reference and why it handles this better:** freeCodeCamp expands the relevant curriculum group; Moodle locates the current activity; Oppia makes current/locked/completed chapter states explicit.
- **Adapt:** One server-derived learning position: course, section, task type/title, eligible action, and lock reason. Dashboard, map, lesson footer, and completion feedback use the same projection. Expand the current section and preserve a return anchor. Keep other courses compact; place secondary recommendations after the main route. Add search/status filtering when path length warrants it.
- **Avoid:** Client unlock arithmetic, most-recent-completion heuristics that skip an earlier prerequisite, numbered tiles without concept names, decorative mazes, endless “continue” after all required work is done, or duplicated full maps.
- **Implementation:** Consolidate the competing traversal rules behind a service/read model and choose a deterministic draft priority consistent with prerequisites. Expose a shared progression-row view contract with type, title, state, reason, and permitted URL. Keep semantic nested lists/disclosures rather than adopting an ARIA tree unnecessarily. Handle required Knowledge Check and pending Boss as distinct next-task types; include a real completed-all-content state.

### R05: Discoverable mobile navigation and clear local context

- **Current problem:** CQ07. Primary mobile destinations are concealed inside an account disclosure.
- **Reference and why it handles this better:** Moodle names its navigation drawer independently of user tools; PrairieLearn keeps the student destination set small; freeCodeCamp changes context during examinations.
- **Adapt:** Dashboard / Learn / Boss Challenges remain primary. At narrow widths use visible compact destinations or an explicitly named Navigation control; Profile contains account and secondary Learning Record links. In a lesson/workspace, show course/section context and Back to Lesson without making every account page compete with coding.
- **Avoid:** A new large global sidebar, duplicate mobile/desktop destination definitions, ambiguous avatar navigation, or five equally weighted workspace tabs.
- **Implementation:** Render a common navigation definition with current-page state in the existing header. Use native disclosures or a focus-managed drawer, label its opener, return focus on close, and exclude hidden content from keyboard navigation. Keep XP a status/ledger link in the right-side account area. Browser-test discoverability and focus rather than assuming a menu icon solves it.

### R06: Explain learning progress and competency as different evidence

- **Current problem:** CQ08. Ordinary Challenge completion is labelled course progress; skill evidence is mislabelled as Boss evidence. Existing Why this result and Not assessed states are useful and should remain.
- **Reference and why it handles this better:** PrairieLearn distinguishes absence/pending/withheld from scores; Moodle distinguishes activity completion states. CodeCombat's ambiguous denominators demonstrate what to avoid.
- **Adapt:** Show **Practice: 8/8 Challenges complete; Boss Challenge: not passed; Course: in progress** when applicable. In the learning record, separate activity completion, course demonstration, and skill evidence. Every percentage has a named denominator/source; missing evidence remains Not assessed. Add supporting attempt/date/count and an eligible learning action.
- **Avoid:** Radar charts implying precision the data cannot support, XP as mastery, all-purpose progress rings, zero for no evidence, or a second frontend competency formula.
- **Implementation:** Keep `CompetencyService` authoritative. Correct the skill evidence heading to Knowledge Check and Challenge evidence. Render its actual mapped correctness/completion inputs through compact evidence rows/disclosures; retain Boss evidence for course demonstration separately. Use the existing accessible progress semantics and add text equivalents. Link only to existing authorized detail/task routes; if evidence provenance is unavailable, state the limitation rather than fabricate it.

### R07: Proportionate assessment navigation and review

- **Current problem:** CQ03 and CQ13. Knowledge Check navigation is useful on desktop but remote on mobile; incomplete submissions lack a client review path. Boss review does not clearly separate editable work from recorded evidence.
- **Reference and why it handles this better:** Moodle identifies unanswered questions at finalization; freeCodeCamp disables submitted quiz inputs; PrairieLearn explains irreversible operations and distinguishes verification from grading failure.
- **Adapt:** Keep native question fieldsets and Answered n/N. Provide a compact mobile answer navigator with numbered, labelled answered/unanswered links. Focus the first missing/invalid answer on validation. For consequential Boss finalization, summarize the source being submitted, editability, and existing retry/XP consequences. Terminal review shows submitted source, outcome, and legal next actions.
- **Avoid:** Exam ceremonies for every five-question formative check, forced multi-page wizards, timed/proctored rules not in the specification, frontend answer keys, or treating an infrastructure failure as an academic failure.
- **Implementation:** Enhance the existing Knowledge Check script and server error anchors; keep the server validating all answers. Review/confirmation should be proportional to actual consequences. Use server capability flags for Begin, Submit, Resume verification, Retry, Review, and Continue. An optional practice copy from recorded source must be explicitly separate from the original attempt and should only be added if a supported practice route exists.

### R08: Teacher monitoring with scope, attention, and drill-down

- **Current problem:** CQ09. A literal attention placeholder and SYSTEM-WIDE label weaken a classroom-scoped monitoring workflow.
- **Reference and why it handles this better:** CodeCombat places courses under classroom context; Moodle and PrairieLearn use structured scoped records. CodeCombat's hover-only progress detail is specifically unsuitable here.
- **Adapt:** Explicit authorized scope → concise summary → roster with meaningful attention reasons → student/course drill-down. Put essential status/counts in table cells, with a native detail link. Preserve selected filters on return. Empty states distinguish no assigned classroom, no enrolled students, and no attention items.
- **Avoid:** Student-style progression cards, decorative metric walls, hover-only evidence, frontend student aggregation, speculative risk scores, or filters that widen authorization.
- **Implementation:** Reuse `AttentionService` definitions and the existing classroom-access boundary in a batched server read model. Supply reason/evidence date and permitted detail URL to roster rows; no queries in Blade. Until this data is available, remove the empty column. Replace SYSTEM-WIDE with a truthful authorized scope label. Use existing responsive table conventions and preserve monitoring/export scope agreement.

### R09: Predictable admin directories

- **Current problem:** CQ10. Management pages vary in filters, headings, and action placement.
- **Reference and why it handles this better:** Moodle's overview decomposes search/group/sort/display; PrairieLearn's grouped records and disclosed history prioritize comparison. Both also show the cost of excess controls.
- **Adapt:** Shared title/context → supported primary action → search/filters → structured table/list → filtered count/pagination → consistent row actions. Use human-readable lifecycle labels and explicit destructive wording. Detail/form screens retain audit context.
- **Avoid:** Adding Create/Bulk actions the backend lacks, generic card grids for comparable records, repeated toolbars, implementation labels such as ORDER_NUM, and hiding destructive actions behind unlabeled icons.
- **Implementation:** Apply a directory shell to Users and Courses first, using existing controller capabilities and URL query filters. Paginate on the server; distinguish total from filtered results. Add filters only where service/query support exists and user tasks justify them. Reuse action/focus/status primitives across directories while keeping Admin density distinct from Student reading.

### R10: Production-owned tokens and a small component vocabulary

- **Current problem:** CQ11. Prototype imports, global styles, literal colors, and accumulating role overrides make styling ownership unclear.
- **Reference and why it handles this better:** freeCodeCamp separates prose/code typography and semantic colors; Moodle maps system tokens before component styling; CodeCombat separates body/headline identity roles. Oppia's parallel template paths demonstrate the maintenance risk to avoid.
- **Adapt:** Production `@theme` tokens for surfaces, text tiers, spacing, typography, focus, semantic states, and control dimensions. Shared button, badge, form field/error, disclosure, page header, directory, evidence row, and workspace primitives. Composition/density varies by role; status meanings remain shared.
- **Avoid:** Another override sheet, duplicating shells, green as every semantic meaning, monospaced body prose/tables, uppercase long labels, CRT overlays over reading/editor/forms, or copying another product's branding.
- **Implementation:** Move vetted tokens/components into production CSS and remove the runtime dependency on `docs/design/prototype/styles.css` in a focused later change. Audit global element selectors and `!important` rules against component ownership. Use sans for prose, titles, forms, and table content; mono for code, XP, metadata, and terminal feedback. Define success/current as phosphor, warning as amber, failure/destructive as red, information as cyan, plus text/icons. Keep clear focus styling independent of status. Validate actual contrasts rather than assuming named tokens are accessible.

### R11: A shared state system and a quieter dashboard

- **Current problem:** CQ12 plus state gaps in CQ04–CQ06. Repeated metrics compete with guidance, and different empty/error causes do not have one consistent treatment.
- **Reference and why it handles this better:** Oppia distinguishes no continuation from exhausted suggestions; Moodle gives empty states corrective actions; Exercism centralizes fetching presentation but demonstrates why filtered-empty needs its own branch.
- **Adapt:** Dashboard leads with one next step, course/section context, and one useful reason. Retain only secondary panels that add different information. Define named state variants: no content, no matches, no evidence, completed, prerequisite locked, administratively unavailable, permission denied, operation loading, and recoverable failure.
- **Avoid:** One generic “Nothing here,” artificial skeletons for already-rendered pages, celebratory clutter during reading, blaming learners for network/grader faults, or losing all feedback when notifications dismiss.
- **Implementation:** A contextual state component receives heading, explanation, severity, and a supported action. SSR pages render final empty/locked/error states directly; only real async work shows loading/busy states. Keep Lesson objective and concept first, with XP/help penalties in secondary metadata. Deduplicate Dashboard course/competency summaries, and place optional recommendations after the learning route. Test long copy and translations before locking dimensions.

## Validation required before implementation is called finished

Research and code inspection are insufficient for UI acceptance. A later implementation must provide evidence for these scenarios:

| Area | Acceptance scenario |
| --- | --- |
| Continuation | With an earlier unfinished Challenge and a later saved draft, Dashboard, map, and recommendations agree on the approved next task. A required Knowledge Check and a pending Boss still route through the correct learning mode. |
| Editor continuity | Change source, selection, scroll, pane selection, and preview; switch tabs, collapse panes, resize, rotate, save, return from a hint, and navigate back. The right draft and visible feedback survive as designed. |
| Readiness/recovery | Delay or fail editor loading, drop a save response, fail a submission request, and repeat a click. The UI reports the real state, preserves recoverable source, and never implies a pass or saved draft without server confirmation. |
| Assessment | Eligible/not eligible, start, active editing, submitting, stored-submission recovery, failed, passed, and failed retry after a historical pass each have the correct actions. Recorded-attempt review is visibly immutable. |
| Evidence | Complete all ordinary Challenges without passing the Boss. The UI says practice complete and Boss still outstanding. No evidence says Not assessed, not 0%. The skill explanation matches the authoritative service and report export. |
| Teacher scope | Teacher with no classroom, multiple classrooms, and overlapping enrollments sees explicit authorized scope, accurate attention reasons, and scoped drill-down. Changing a filter never widens authorization. |
| States | Distinguish no content, no filter matches, no activity yet, all content completed, prerequisites missing, administratively unavailable, permission denied, offline/timeout, and grader unavailable. Recovery actions match the cause. |
| Keyboard | Complete navigation, question answering, resize, pane switching, save, run, submit, help confirmation, result review, and exit without a pointer. Tabs use roving focus; editor navigation offers an escape; background saves do not steal focus. |
| Screen reader | Controls and editor have usable names. Form errors identify fields/questions. Repeated failures and operation results are announced without announcing every keystroke or dumping raw console output. |
| Responsive | Inspect 320/390/430, 768/1024, and 1280/1440 CSS-pixel layouts, 200% zoom and reflow at an effective 320-pixel width. Test long titles, translations, large rosters, empty states, and the phone software keyboard. A desktop coding/table exception is not permission for page-wide overflow. |
| Visual system | Measure text, status, focus, and control contrast in the implemented themes. Verify reduced motion. Check shared components in Student, Teacher, and Admin contexts before accepting screenshots of only the happy path. |

The [WAI tabs pattern](https://www.w3.org/WAI/ARIA/apg/patterns/tabs/) informs tab semantics and keyboard behavior. [WCAG focus-not-obscured guidance](https://www.w3.org/WAI/WCAG22/Understanding/focus-not-obscured-minimum.html) makes sticky bars and toasts part of focus testing. [WCAG reflow guidance](https://www.w3.org/WAI/WCAG22/Understanding/reflow.html) informs narrow-width and zoom checks. These are validation targets, not a claim that the inspected repositories or CodeQuest conform.

## Final redesign direction

### Most important problems

1. **The work/result contract is inconsistent.** Ordinary and Boss Challenges differ in layout, preview semantics, readiness, and review behavior. Fixing the raw CSS/JavaScript Boss preview and editor-failure handling is more urgent than visual polish.
2. **The learner's next action is unreliable.** Different resume algorithms and detached feedback make continuing and repairing work unnecessarily uncertain.
3. **Progress labels overstate or misattribute evidence.** Practice completion, course pass, and skill evidence need distinct names and denominators. Better visualization cannot compensate for misleading labels.
4. **Teacher/Admin workflows are only partly expressed.** Attention is separated from roster actions, scope copy is inaccurate, and admin directories lack a consistent interaction contract.
5. **Visual ownership is fragmented.** Prototype imports and production overrides encourage local fixes rather than reusable components. Keyboard/software-keyboard/zoom acceptance remains unverified.

### Reusable patterns worth adopting

| Principle | Useful reference | CodeQuest application |
| --- | --- | --- |
| Locate the current task, not just its course | freeCodeCamp, Moodle | Shared continuation projection, current-section expansion, return anchor |
| Preserve work while changing layout | freeCodeCamp; Exercism conceptually | One mounted editor and state-preserving panes across widths |
| Distinguish editing, saving, submission, and outcome | PrairieLearn, Oppia, CodeCombat | Honest operation states and immutable assessed review |
| Keep actionable feedback near the task | CodeCombat, freeCodeCamp | Persistent server Feedback beside Preview/Console |
| Show absence and restrictions as meaningful states | Moodle, PrairieLearn, Oppia | Not assessed, prerequisite explanation, completed-all-content, recoverable failure |
| Disclose detail without burying essential evidence | PrairieLearn; CodeCombat as a counterexample | Compact attempt/evidence rows, visible counts, keyboard-accessible detail |
| Separate global navigation from contextual learning | Moodle, PrairieLearn | Small student destination set, explicit mobile navigation, local course context |
| Share semantics while varying role density | freeCodeCamp tokens, Moodle components | Student learning, Teacher monitoring, Admin management |

### Patterns worth avoiding

Do not adopt an entire reference shell or stack. Specifically avoid client-owned grades/unlocks/competency, hover-only essential status, decorative progression mazes, numeric tiles without concept names, a full desktop IDE squeezed onto phones, frontend answer keys, excessive examination ceremony, parallel legacy/mobile component trees, raw error payloads, noisy failure animation, misleading percent rings, and extra game/community/advanced-compiler controls without a CodeQuest task. A polished reference can still have weak semantics, awkward mobile behavior, or license restrictions.

### Prioritized redesign plan

These priorities rank UI work; they are not a new security audit severity assessment.

| Order | Priority | Target | Deliverable and completion criterion |
| --- | --- | --- | --- |
| 1 | P0 | Challenge + Boss workspace and shared editor/preview controller | A single interaction contract, shared preview adapter, readiness/failure recovery, read-only assessed review, explicit Run versus Submit, persistent feedback. Verify CSS/JS previews and failure paths before accepting a mockup. |
| 2 | P0 | Continuation and evidence terminology | One deterministic server projection and accurate practice/Boss/course/skill labels. The same learner state produces the same next task everywhere; all percentage captions match their service input. |
| 3 | P1 | Production tokens and shared primitives | Replace prototype dependency with production ownership while implementing the workspace primitives; validate core typography/status/focus across roles. Do not postpone component ownership until after another page-by-page restyle. |
| 4 | P1 | Learning Path + mobile student header | Clear course/section/current-task orientation, explicit lock reasons, visible learning navigation, composable filters where useful. Dashboard remains the entry point with fewer duplicate summaries. |
| 5 | P1 | Knowledge Check + Boss entry/result/review | Mobile answer navigator, validation focus, proportionate finalization, truthful pending/retry/outcome states; keep formative and summative policy distinct. |
| 6 | P1 | Competency/Learning Record | Compact evidence groups, explicit denominators and missing-evidence states, authorized detail and next-practice links. No new mastery formula or XP substitution. |
| 7 | P1 | Teacher roster + Needs Attention | Accurate authorized scope, batched real attention reasons, contextual drill-down, persistent filters, and useful no-classroom/no-student/no-attention states. |
| 8 | P2 | Admin Users/Courses, then other directories | Predictable management shell, supported actions, human-readable status, filtered counts/pagination, and consistent row actions. |
| 9 | Required per phase | Browser and accessibility acceptance | Run the validation scenarios above against actual built assets; include loading/error/locked/completed cases in review. Do not defer all keyboard/mobile verification until the end. |

### First screens and components to redesign

Start with **ordinary Challenge and Boss Challenge together**, including their editor initialization, action bar, Output/Feedback, and immutable review modes. The current ordinary workspace is the starting point; the Boss should inherit its useful behavior. Next address **Learning Path and the mobile header**, supported by the shared continuation service. Then refine **Knowledge Check, Boss result, and competency evidence rows** as one learning-state sequence. Teacher roster/attention and Admin directory shells follow with their own compositions.

Before application edits, produce reviewable state-based layouts for: desktop coding, narrow coding with software keyboard, editor-load failure, dirty/failed-save work, failed submission, passed task, pending Boss verification, recorded-attempt review, locked next course, and no competency evidence. Each layout must name its primary action, permitted transitions, focus destination, and preserved data. A single happy-path dashboard screenshot is insufficient.

Implementation should remain incremental: reuse services and named routes; extract small Blade components; consolidate production tokens; add a shared JS workspace controller; keep forms progressively enhanced; test meaningful service inconsistencies and interaction transitions. No dependency replacement, production migration, wholesale visual imitation, or client academic authority is necessary to achieve this direction.
