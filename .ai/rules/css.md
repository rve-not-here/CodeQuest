---
paths:
  - 'resources/css/**'
---

# Css

## CodeQuest retro terminal design system
Visual identity: Tailwind v4 CSS-first (no tailwind.config). Retro terminal palette in @theme (void #050505, phosphor #33ff00, amber, alert, cyan; ink #aabbaa). Light mode = add .light-mode class to <html> which overrides --color-* vars (deep forest green #4dff6e). Fonts bundled via laravel-vite-plugin bunny helper: Press Start 2P (--font-display), VT323 (--font-body/--font-sans), Courier Prime (--font-code). No glassmorphism, no large radii (max 2px terminal-crisp), effects subtle.

## .table-stack responsive utility lives in @layer components
The `.table-stack` media block (@media max-width: 767.9px, hides thead, block-displays tr/td as cards, td::before renders data-label) is defined inside `@layer components` in resources/css/app.css, right after the `.terminal-input:focus` rule. It needs NO Tailwind config — it's plain CSS. Any new responsive-table behavior must extend this utility, not add per-blade duplicates. After editing app.css, re-run `npm run build` or the served manifest CSS won't change.
