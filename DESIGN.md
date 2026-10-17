# Design

The visual world of CodeQuest. It defines what every screen is made of, what the one signature move is, and what the craft floor refuses. The direction contract lives at `.impeccable/surfaces/resources-views-layouts-app-blade-php.md`; this file is the engineering reference.

## Direction

**Conventional SaaS**. CodeQuest is a clean, professional learning platform: white surfaces on a slate ground, indigo primary, Inter type, soft shadows, rounded corners. It refuses the retro-terminal aesthetic (CRT, glow, scanlines, uppercase mono labels, dashed "locked" styling) in favour of a familiar, accessible, modern SaaS register.

## Palette

| Token | Value | Role |
|---|---|---|
| `--color-bg` / `--color-canvas` | `#F8FAFC` | page ground (slate-50) |
| `--color-surface` | `#FFFFFF` | card / sheet |
| `--color-surface-alt` / `--color-raised` | `#F1F5F9` | recessed fill, table stripes |
| `--color-border` / `--color-line` | `#E2E8F0` | hairline |
| `--color-border-strong` / `--color-line-strong` | `#CBD5E1` | hover/focus border |
| `--color-fg` / `--color-ink` | `#0F172A` | ink — headings, body |
| `--color-fg-muted` | `#475569` | secondary text |
| `--color-fg-subtle` | `#64748B` | tertiary text, metadata |
| `--color-primary` / `--color-accent` | `#4F46E5` | indigo — anything interactive |
| `--color-primary-hover` | `#4338CA` | |
| `--color-primary-soft` | `#EEF2FF` | primary tint |
| `--color-success` | `#16A34A` | validation passed |
| `--color-warning` | `#D97706` | caution |
| `--color-danger` | `#DC2626` | faults only |

Light-mode only. There is no dark variant.

Contrast floor: 4.5:1 body, 3.0:1 large. Verify every token pair against every ground.

## Type

- **UI**: Inter 400/500/600/700 (self-hosted via Bunny), `--font-sans` / `--font-display` / `--font-body`.
- **Code / data**: JetBrains Mono (`--font-code` / `--font-mono`), kept for code blocks and numeric data only.

Scale: 12 / 14 / 16 / 18 / 20 / 24 / 30 px. Hierarchy is carried by the ramp and weight, not by colour or uppercase.

## Materials

- **Borders**: 1px solid `--color-border` (`#E2E8F0`). No radius above 12px.
- **Radius**: 8px cards, 6px controls, 999px pills.
- **Shadow**: `0 1px 2px rgb(15 23 42 / .05), 0 1px 3px rgb(15 23 42 / .10)` on cards; `0 4px 12px rgb(15 23 42 / .12)` on popovers/modals.
- **No glass, no gradient, no glow, no CRT atmosphere, no scanlines.** Opaque surfaces only.

## Signature: clean SaaS shell

Sticky white top bar with brand + primary links + account; persistent left sidebar (grouped sections, active state with left border + primary tint) for teacher and admin. Student uses top nav only.

## Craft floor (refusals)

- No kicker, eyebrow, or eyebrow-plus-title device on any card, page, panel, or header whose heading already opens the element.
- No Unicode glyphs or emoji standing in for an icon system — drawn SVG only (`workspace-icon.blade.php`).
- No system display face as the display voice of an own-world page.
- No dead-world vocabulary: `SHELL.ON`, `SIGNAL RESTORED`, `phosphor`, `CRT`, `scanline` do not appear in student surfaces.
- No transition/ease above 200ms; `prefers-reduced-motion` cancels animation.
- No uppercase mono labels on UI chrome; use normal-case sans.
- No dashed "locked" styling; use subtle gray + lock icon.

## Verification

| Gate | Command |
|---|---|
| Build | `npm run build` |
| Full suite | `php artisan test --compact` |
| Format | `vendor/bin/pint --dirty --format agent` |
| Static | `vendor/bin/phpstan analyse --memory-limit=1G --no-progress` |