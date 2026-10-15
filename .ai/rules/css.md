paths:
  - 'resources/css/**'
---

# Css

## CodeQuest visual world — Foundry Specimen & Type Case (chosen by user, seed b6691ef2)
Visual identity: Tailwind v4 CSS-first (no tailwind.config, no tailwind.config.*). Design system lives in `@theme` in `resources/css/app.css`. The direction contract is recorded at `.impeccable/surfaces/resources-views-layouts-app-blade-php.md` (all 8 blocks, verbatim).

Palette (paper under office light): ground `#F2F1EC`, sheet `#FFFFFF`, raised `#EAE9E3`, ink `#16181B` (three steps: fg/muted/subtle), one committed proof blue `#1E4FD8` for anything interactive, press red `#C6362A` reserved for faults. Light-mode only; there is NO dark variant and no `.light-mode` class swap.

Type: Archivo (system-ui sans fallback) for display/body via `--font-display`/`--font-body`/`--font-sans`; JetBrains Mono via `--font-code`/`--font-mono` for code + labels. BOTH self-hosted as variable woff2 in `public/fonts/` and loaded by `@font-face` in `app.css` — never a hosted font stylesheet. Targets: 4.5:1 minimum for body text on canvas/sheet/raised; large-type 3.0:1 minimum; every token pair must be verified against ALL grounds, not a cherry-picked subset (see contrast one-pass checker).

Materials/radii: brass hairlines are exactly 1px (`border-line`/`border-line-strong`); no radius above 2px (`--radius-*`); no glass, no gradient, no glow, no zero-offset colored halo. No CRT atmosphere anywhere — atmosphere is forbidden even where it was tolerated previously.

Structure: tokens are the real color authority; legacy names (`--color-phosphor`, `--color-cyan`, `--color-magenta`, `--color-amber`, `--color-phosphor-dim`, etc.) remain as ALIASES onto Foundry tokens until every reference migrates. All `rgba(51,255,0,...)` hard-codes were swept; none should reappear.

Attribution discipline: the sky is "Committed colour owns whole regions" — proof blue must own at least one region on a sheet rather than dotting every interactive element. "Sort code in the corner of every compartment" is the SIGNATURE. "Running time is a first-class number, right-aligned, tabular figures" is a built-in raise and `font-variant-numeric: tabular-nums` applies to tables/ledgers via `app.css:~96-101`.

No transition/ease above 200ms; `@keyframes` must pulse flat fills, never glow rings; `prefers-reduced-motion` cancels any animation.

## .table-stack responsive utility lives in @layer components
The `.table-stack` media block (@media max-width: 767.9px, hides thead, block-displays tr/td as cards, td::before renders data-label) is defined inside `@layer components` in resources/css/app.css, right after the `.terminal-input:focus` rule. It needs NO Tailwind config — it's plain CSS. Any new responsive-table behavior must extend this utility, not add per-blade duplicates. After editing app.css, re-run `npm run build` or the served manifest CSS won't change. Run `npm run build` before declaring a visual change complete (Vite manifest error otherwise).
