# Design

The visual world of CodeQuest. It defines what every screen is made of, what the one signature move is, and what the craft floor refuses. The direction contract lives at `.impeccable/surfaces/resources-views-layouts-app-blade-php.md`; this file is the engineering reference.

## Direction

**Foundry Specimen & Type Case** (seed `b6691ef2`). CodeQuest is a classified system: every mission, skill, record, and alert is a sort in a labelled compartment, and each screen is the specimen sheet that shows what the set can say. It refuses the dark terminal reading the fiction invited, in favour of the printed-works register the fiction sits inside: a foundry sheet, not a CRT.

## Palette

| Token | Value | Role |
|---|---|---|
| `--color-canvas` | `#F2F1EC` | page ground (warm paper) |
| `--color-surface` | `#FFFFFF` | sheet / card fill |
| `--color-raised` | `#EAE9E3` | recessed fill, table stripes |
| `--color-fg` | `#16181B` | ink — headings, body |
| `--color-fg-muted` | `#4A4D52` | secondary text |
| `--color-fg-subtle` | `#5F6267` | tertiary text, metadata |
| `--color-accent` | `#1E4FD8` | proof blue — anything interactive |
| `--color-danger` | `#B32F24` | press red — faults only |
| `--color-success` | `#14663A` | validation passed |
| `--color-warning` | `#8A5A00` | caution |

Light-mode only. There is no dark variant and no `.light-mode` class swap.

Contrast floor: 4.5:1 body, 3.0:1 large. Verify every token pair against every ground (`canvas`, `surface`, `raised`) — never a subset. Worst shipping pair is 4.74:1.

## Type

- **Display / body**: Archivo (`--font-display`, `--font-body`, `--font-sans`)
- **Code / labels**: JetBrains Mono (`--font-code`, `--font-mono`)

Both self-hosted as variable woff2 in `public/fonts/`, loaded by `@font-face` in `app.css`. Never a hosted font stylesheet.

Ramp (tokens in `@theme`): 11 / 13 / 16 / 22 / 28 / 36 / 46 px. Hierarchy is carried by the ramp, not by colour. Headings use the display register; the dashboard objective heading computes `clamp(30px, 3.3vw, 48px)`.

## Materials

- **Brass rules**: exactly 1px hairline borders (`border-line`, `border-line-strong`). No radius above 2px.
- **Proof marks**: a small dot or tick that carries state without colour alone.
- **No glass, no gradient, no glow, no zero-offset coloured halo, no CRT atmosphere.** The sheet is opaque; atmosphere is forbidden even where it was tolerated previously.

## Signature: the classified compartment

A hairline-bordered box whose corner carries a sort code that never repeats across the interface, so state and provenance are legible from the label alone.

- `.sort-code` utility (`student.css`) renders the box.
- `panel.blade.php` auto-derives a code from its title when none is passed.
- `page-header.blade.php` accepts a `code` prop.
- Every Dashboard, Learning Path, Lesson, and Challenge compartment carries one. Verified in the browser suite (`tests/workspace-browser.mjs`).

## Raises carried in

1. **Committed colour owns whole regions** — proof blue fills the primary action, the active nav underline, progress fills; it does not dot every interactive element.
2. **Running time is a first-class number** — right-aligned, tabular figures, never folded into prose.
3. **Proof beside the number** — every headline figure is one click from the record behind it.

## Craft floor (refusals)

- No kicker, eyebrow, or eyebrow-plus-title device on any card, page, panel, or header whose heading already opens the element.
- No Unicode glyphs or emoji standing in for an icon system — drawn SVG only (`workspace-icon.blade.php`).
- No system display face as the display voice of an own-world page.
- No dead-world vocabulary: `SHELL.ON`, `SIGNAL RESTORED`, `phosphor`, `CRT`, `scanline` do not appear in student surfaces.
- No transition/ease above 200ms; `@keyframes` pulse flat fills, never glow rings; `prefers-reduced-motion` cancels animation.

## Verification

| Gate | Command |
|---|---|
| Build | `npm run build` |
| Full suite | `php artisan test --compact` |
| Node | `node --test tests/*.test.js` |
| Browser | `CODEQUEST_REQUIRE_BROWSER_TESTS=1 CODEQUEST_CHROMIUM=... php artisan test tests/Feature/WorkspaceBrowserTest.php` |
| Format | `vendor/bin/pint --dirty --format agent` |
| Static | `vendor/bin/phpstan analyse --memory-limit=1G --no-progress` |

Captures live at `storage/app/private/approved-challenge-ui-review/`.
