# brand-spec.md — CodeQuest System 404 (extracted from repo, not invented)

Source of truth: `DESIGN.md` + `resources/css/app.css` + `PRODUCT.md` + `CONTEXT.md`.
No logo, photography, illustration, testimonial, or benchmark data exists in the repo (PRODUCT.md "Evidence on hand"). Do not fabricate any.

## Tokens (exact)
- void `#030604`, surface `#060c08`, surface-alt `#0a140d`, panel `#0b1610`, input `#08110b`, header `#040806`
- ink `#e1ece4`, static `#91a198`
- phosphor `#33ff00` (primary/action/complete), bright `#75ff55` (hover only), dim `#559260` (rails/meta)
- amber `#ffb000` (current/caution/Boss), alert `#ff5c5c` (error/hard), cyan `#49d8e8` (info/available/focus)
- Type: ONE monospace stack everywhere: `ui-monospace, 'Cascadia Code', 'SFMono-Regular', Consolas, 'Liberation Mono', monospace`. Hierarchy via size/weight/tracking, never a display face.
- Shape: 2px radius on controls/panels, 0 radius on workspace panes. 1px borders define surfaces; shadow only for structure. Circles only for status dots. Square markers/badges/sigils.
- Texture: 4px scanline rhythm + one soft phosphor radial wash on page ground only. Never over lesson copy, code, or preview. Opaque panels, no glassmorphism, no purple-blue AI gradient.
- Focus: 3px cyan outline + 3px offset everywhere. `prefers-reduced-motion` kills the current-node pulse.

## Semantic signal rule
Green = primary/complete, amber = current/caution, red = error, cyan = info/available. Every state pairs color + text/marker shape. Completed = phosphor + check, current = amber + dot + pulse, available = cyan + open circle, locked = neutral dashed + x. Boss Challenge = separate amber milestone.

## Language (must preserve)
Mission (internal) / Challenge (student-facing) / Section / Course / Learning Path / Assessment / Knowledge Check (formative, never completes anything) / Boss Challenge (only summative per course) / Progress / XP (spendable, server-authoritative, -10 wrong submit, hints -5/-10/-15, reveal -30, floor 0) / XP Transaction / Competency (HTML/CSS/JS per course, demonstrated is permanent) / System 404 fiction.
Avoid: Challenge as code term, quiz/exam/test, points for XP, skill score.

## Layout rules from repo
- Shell: top bar capped 1536px; content 1180px centered; student nav = sticky 15.5rem rail ≥1024px, else 88vw drawer (focus trap, inert bg, Esc, focus restore).
- Dashboard: one dominant command band first, then XP/competency/achievement/activity/transmissions at lower contrast. Never an analytics wall.
- Learning path: centered vertical rail max 50rem, node = marker + written state + title + support + badge.
- Lesson: centered 54rem reading shell, relaxed leading, quiet panels, clear handoff to practice.
- Challenge workspace: 3 panes (brief / editor / preview), editor largest; ≤1100px stack with borders and min-heights. Preview pane is white ground with sandboxed output (preview never proves completion).
