---
paths:
  - resources/js/app.js
---

# Js

## The bench editor loads on approach, the preview loads first
The CodeMirror bundle is ~190KB gzipped, so a lesson page must not block on it: app.js only imports ./editor.js eagerly for the graded workspace ([data-workspace]). On the mission bench it waits for the editor host to come within 300px of the viewport (IntersectionObserver) or for a pointerdown on it, then imports once. The bench preview does not depend on CodeMirror — app.js imports ./preview.js on its own and dispatches 'cq:preview-ready', and the lesson's inline script renders the starter into #experiment-preview as soon as that event arrives, mounting the editor later on 'cq:codemirror-ready'. Keep the two decoupled: never make the render wait for the editor, and never import the editor eagerly on a reading page. The bench frame (a specimen taped to the pad) pulses once per run via the .is-refreshed class; keep that under 200ms and let the global prefers-reduced-motion rule cancel it.
