# Approved Coding Challenge Workspace Implementation Plan

> **For agentic workers:** Use superpowers:executing-plans to implement this plan task by task. The owner has approved the prototype and authorized applying and pushing the real UI to main.

**Goal:** Apply the approved focused workspace to the real Laravel Coding Challenge.

**Architecture:** Opt the Coding Challenge into a separate tabbed workspace controller. Keep the existing three-pane controller for Boss Challenge and other consumers. Retain the existing CodeMirror instance, preview construction and server form endpoints.

**Tech stack:** Installed Laravel 13, Blade, Tailwind v4 CSS tokens, Vite, CodeMirror 6, PHPUnit and the repository's existing Chromium harness.

**Spec:** `docs/design/coding-workspace-preview/README.md` and the owner's approved iterations in this conversation.

## Global constraints

- Preserve authentication, authorization, curriculum, progression, grading, XP, penalties, replay IDs, draft restoration and preview sandbox permissions.
- No controller, service, route, schema or configured database changes. Use in-memory SQLite and existing disposable browser fixtures.
- Preserve the approved Student, Teacher, Admin, Boss and Experiment compositions.
- Preserve the user's existing `package-lock.json` change without staging it.
- Use explicit data attributes for behavior; ARIA describes semantics and state.
- Work in the requested checkout. Publish only this focused implementation to main after verification, with no force push.

## Review focus

- Long/hostile lesson and code text stays escaped and scrolls locally without page overflow.
- Hidden panels must leave keyboard traversal while retaining the same editor, source, selection and undo history.
- Cancel/Escape from confirmation must neither Run nor submit; confirmed Run must not perform an academic mutation.
- Real draft restoration remains intact. Removing the main Save button must not erase the existing draft workflow.
- Shared code must not change Boss or Experiment pane behavior, submission confirmation or sandbox permissions.

## Files and interfaces

- `resources/js/tabbed-workspace.js`: `initializeTabbedWorkspace(root, workspace)` handles tabs, instruction resizing, reset and fullscreen, without owning editor content.
- `resources/js/workspace.js`: dispatches only `data-workspace-layout="tabs"` to the new controller; legacy behavior remains intact.
- `resources/views/challenge.blade.php`: real lesson/reference data, semantic tabs, real Hint/Submit forms, native confirmations, contextual feedback and existing editor initialization.
- `resources/views/components/workspace-icon.blade.php`: decorative local SVG icons with retained Lucide license notices.
- `resources/css/student.css`: scoped workspace geometry and 52px challenge navigation; other screens keep their shell dimensions.
- `tests/Feature/WorkspaceControlsTest.php`, `tests/Feature/MissionTest.php`, `tests/workspace.test.js`, `tests/workspace-browser.mjs`: changed semantic contracts and real browser verification. Add a focused Node test file if separating the tabbed controller is clearer.
- Design documentation records production adoption after verification.

## Task 1: Tabbed workspace controller

- [ ] Add failing Node coverage for initial Code selection, full-width Code/Preview exclusivity, desktop Instructions visibility, mobile Instructions selection, arrow/Home/End keyboard controls, reset and resize.
- [ ] Run `node --test tests/workspace.test.js tests/tabbed-workspace.test.js`; verify the new contract fails before implementation.
- [ ] Implement the opt-in controller and narrow dispatcher. Preserve legacy controller contracts and editor identity.
- [ ] Rerun the Node tests. Existing legacy workspace tests must remain green.

## Task 2: Real Challenge composition

- [ ] Update the focused feature test first: initial Preview hidden; lesson description/reference safely rendered; real draft restored unchanged; real Hint/Submit destinations, CSRF and submission ID preserved; no main Save or footer action bar.
- [ ] Run `WorkspaceControlsTest` and observe the intended failure.
- [ ] Implement the real view and scoped CSS. Use authoritative `description` and public `target_html`; keep authored instructions intact. Never copy the prototype fixture text into application views.
- [ ] Keep Hint and authorized revealed assistance outside the lesson reading pane. Keep the existing solution-reveal permission/cost flow.
- [ ] Use native Run/Submit confirmation with safe initial focus and focus return. Keep the preview builder and sandbox unchanged.
- [ ] Retain the real draft form and add a documented Ctrl/Command+S keyboard alternative within the workspace. It uses the existing POST endpoint and source payload.
- [ ] Preserve success overlay, failure/validation messages, source restoration and replay UUID. Show feedback only when supplied by the server.
- [ ] Rerun focused feature/source-restoration tests and production build.

## Task 3: Browser, regression and publication

- [ ] Extend the existing Chromium Challenge branch for the new semantics; keep Boss/Experiment checks intact. Check Code/Preview geometry, source/selection/undo, initial state, Run/Submit cancellation, draft shortcut payload, Hint isolation, fullscreen, keyboard and overflow.
- [ ] Verify 1920×1080, 1366×768, 768×1024, 390×844, 320px and both sides of the 1100px responsive breakpoint using rendered real Laravel views and built assets.
- [ ] Run the required Chromium feature suite using the installed browser and in-memory SQLite fixtures. Capture representative actual screens only under ignored private storage.
- [ ] Run the full Laravel suite, all frontend Node tests, PHPStan, dirty Pint, build and `git diff --check`. Classify failures before changing anything.
- [ ] Review the complete focused diff for UI/security regressions. Update design documentation to distinguish the historical prototype from production adoption.
- [ ] Commit only intended application, tests, icon notices and documentation. Fetch, verify safe main ancestry, push normally, and verify local/remote SHA equality.

## Execution record

- Baseline: 135 targeted Laravel tests passed / 621 assertions; configured database untouched.
- Ruling: render the existing mission description verbatim, including any authored task sentence, rather than deleting curriculum paragraphs to imitate the mockup.
- Ruling: remove the visible Save action while retaining real draft saving through Ctrl/Command+S; no new persistence service or automatic academic mutation.
- Ruling: retain the requested checkout and protect the unrelated lockfile change. No extra worktree or dependency installation is required.
