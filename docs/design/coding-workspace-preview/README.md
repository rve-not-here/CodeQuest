# Approved Coding Challenge workspace prototype

Approved by the product owner on 2026-10-15. This is a static, interactive design reference. Laravel product files and backend behavior are unchanged.

## Run locally

Use the checkout's existing frontend dependencies and Vite build:

```bash
npm run build
python3 docs/design/coding-workspace-preview/serve.py
```

Open `http://127.0.0.1:8809/`. Use `--port` to choose another local port. The server binds only to loopback and reads the Vite manifest, so asset hashes are not hard-coded. It requires no Laravel process, credentials, database, sessions or cache. It does not install dependencies.

## Approved composition

- Retain the Mission Atlas colors, typography and compact top navigation.
- Display lesson context and the example directly in Instructions.
- Share one full-width work area between Code and Preview tabs. Code opens first.
- Keep Instructions alongside the work area on desktop; add an Instructions tab at narrow widths.
- Keep one mounted CodeMirror instance across tabs, preserving source, selection and undo history.
- Support arrow keys, Home and End within the tab list.
- Keep Hint separate from the lesson text and submission consequences.
- Use compact desktop controls and 44px touch targets at mobile widths.
- Remove the footer toolbar and Save button from this prototype.
- Confirm Run and Submit separately. Run updates only the sandboxed preview.
- Hide empty submission feedback. Show the prototype confirmation feedback only after confirmation.

## Prototype boundaries

The sample lesson and example came from the inspected CodeQuest fixture. No new curriculum, eligibility or scoring rules are introduced. Navigation displays the existing application destination rather than pretending other screens have been implemented here.

Submit and Hint demonstrate interactions only. They cannot grade, spend or award XP, record a submission, persist a draft, or change academic progress. The HTML preview retains an empty iframe sandbox. Existing application draft behavior and backend actions remain unchanged; removing a prototype control does not remove an application endpoint.

Production adoption requires a separate implementation pass against the real Blade views and workspace integration, followed by application regression coverage. Prototype approval is not a full-system release verdict.

## Verification and attribution

The approved local prototype passed 141 Chromium browser checks, including seven viewport/breakpoint sizes, full-width tab geometry, source/selection/undo preservation, keyboard tabs, confirmations, fullscreen, reflow, the preview sandbox and absence of academic mutation requests. The publishable copy passed 145 checks through the manifest-based local server, including checks for all nine bundled icons. There were no page exceptions, failed asset requests or academic mutation requests. Screenshots, browser output and runtime files remain private and untracked.

Layout patterns were informed by [freeCodeCamp's challenge workspace](https://github.com/freeCodeCamp/freeCodeCamp/tree/main/client/src/templates/Challenges/classic). No freeCodeCamp code, assets or curriculum were imported.

The local SVG icons come from [Lucide](https://github.com/lucide-icons/lucide/tree/main/icons), downloaded on 2026-10-15. Copyright and ISC/MIT permission notices are retained in [icons/LICENSE](icons/LICENSE). They require no runtime CDN or icon package. CodeMirror and shared styles come from CodeQuest's existing build and dependencies.
