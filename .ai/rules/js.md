---
paths:
  - resources/js/app.js
---

# Js

## The bench editor loads on approach, the preview loads first
The CodeMirror bundle is ~190KB gzipped, so a lesson page must not block on it: app.js only imports ./editor.js eagerly for the graded workspace ([data-workspace]). On the mission bench it waits for the editor host to come within 300px of the viewport (IntersectionObserver) or for a pointerdown on it, then imports once. The bench preview does not depend on CodeMirror — app.js imports ./preview.js on its own and dispatches 'cq:preview-ready', and the lesson's inline script renders the starter into #experiment-preview as soon as that event arrives, mounting the editor later on 'cq:codemirror-ready'. Keep the two decoupled: never make the render wait for the editor, and never import the editor eagerly on a reading page. The bench frame (a specimen taped to the pad) pulses once per run via the .is-refreshed class; keep that under 200ms and let the global prefers-reduced-motion rule cancel it.

## The account theme write is retried once and never swallowed
The account is what carries the choice to every device. A failed POST /theme is retried once and then reported with console.warn instead of being swallowed. The device keeps the choice meanwhile: it stores cq-theme-at, and the head script applies whichever of device and account is newer.

## The theme click sets a mode, and the account write still happens
A [data-theme-set] button sets the mode it names (data-theme-set="light"|"dark"), never the opposite one. The device stores cq-theme plus cq-theme-at on every press, the account write is retried once and then reported with console.warn, and a press of the mode already in force still writes the account so the choice converges on every device.

## Dialogs are native and trigger-driven (2026-10-22)
`[data-dialog-open="id"]` opens `dialog[data-dialog]#id` with `showModal()`; the trigger click is prevented only after the dialog is found, so a submit button inside `#logout-form` remains a working no-JS logout. Backdrop click closes; cancel/backdrop/Escape return focus to the trigger or, when the account disclosure has closed, its summary. Do not add a second JS modal implementation — challenge confirmations keep their own `.cq-confirm` controller, shell confirmations use `.atlas-dialog`.
