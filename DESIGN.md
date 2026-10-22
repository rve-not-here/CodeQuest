# Design

The visual world of CodeQuest. It defines what every screen is made of, what the one signature move is, and the craft floor refuses. This file is the engineering reference for the tokens and materials below; the tokens themselves live in `resources/css/app.css` (Tailwind v4, CSS-first — there is no `tailwind.config.*`).

## Direction

**Sector-map console.** CodeQuest is a learning console drawn like a technical dossier: a sheet of paper (or a terminal bay at night) carrying ruled panels, a single oxide-red accent, condensed uppercase navigation, and mono metadata. It borrows the System 404 world's vocabulary and geometry — status footers, a command band, a sheet column — without the CRT theatre: no glow, no scanlines, no blur, no glass.

Two lights, one system. The default ground is warm archival paper; the dark ground is terminal-black. Every token is defined once in the `:root` and `.dark` blocks and every component reads the alias, so a surface never hard-codes a colour.

## Palette

Light (default, warm archival paper):

| Token | Value | Role |
|---|---|---|
| `--cq-ground` | `#F4F2E9` | warm paper ground |
| `--cq-sheet` | `#FFFDF8` | sheet / surface |
| `--cq-raised` | `#EBE8DD` | recessed fill, table stripes |
| `--cq-ink` | `#1C2229` | ink — headings, body |
| `--cq-muted` | `#525A61` | secondary text |
| `--cq-subtle` | `#5E6970` | tertiary text, metadata |
| `--cq-line` / `--cq-edge` | `#D4D0C4` / `#C2BFB2` | hairline rules |
| `--cq-line-strong` | `#717A7B` | hover/focus rule |
| `--cq-accent` | `#B23A2E` | oxide red — anything interactive |
| `--cq-accent-strong` / `--cq-accent-pressed` | `#8F2C22` / `#75241C` | accent hover / pressed |
| `--cq-accent-soft` | `#F1E3DF` | accent tint |
| `--cq-success` | `#2C684B` | validation passed |
| `--cq-info` | `#4A478F` | informational |
| `--cq-warning` | `#845D12` | caution |
| `--cq-note` / `--cq-note-ink` | `#F1DF9B` / `#2B2A1E` | handwritten note block |
| `--cq-code-bg` | `#171B21` | code surface |

Dark (`.dark` on `<html>`): ground `#0F1318`, sheet `#181C22`, raised `#22272E`, ink `#E8ECE4`, muted `#B0B6AA`, subtle `#90988E`, line `#2B323A`, line-strong `#66717B`, accent `#E37B6E`, accent-strong `#EF968B`, accent-soft `#352321`, success `#74C7A0`, info `#A7A1EE`, warning `#DFB45E`, CTA `#B94C3E`.

The `--color-*` names (`--color-bg`, `--color-surface`, `--color-ink`, `--color-accent`, `--color-line`, …) are aliases onto the `--cq-*` values. Hand-written CSS reads the aliases; utilities inline the values.

Legacy phosphor aliases (`--color-phosphor`, `--color-amber`, `--color-cyan`, `--color-magenta`) exist for old rules and their `-glow` partners resolve to `transparent`: the accent colour is used, never a glow.

Contrast floor: 4.5:1 body, 3.0:1 large. Verify every token pair against every ground, in both lights.

## Type

Self-hosted through the Vite plugin's `bunny()` helper (`vite.config.js`), never a hosted stylesheet:

- **UI / body**: Barlow 400–700 (`--font-sans`, `--font-body`).
- **Display**: Barlow Condensed 500–700 (`--font-display`) — brand, primary navigation, panel headings. Uppercase with `0.06em`–`0.12em` tracking.
- **Code / data / metadata**: Fragment Mono 400 (`--font-mono`, `--font-code`) — code blocks, XP readouts, group labels, IDs, avatar initials, note text.

Scale: 11 / 12 / 14 / 16 / 18 / 20 / 24 / 30 px. Hierarchy is carried by the ramp, the condensed display face, and the weight — mono is for data, not for chrome. Data and metadata labels are sentence case at 11.5–13px; tracked uppercase is reserved for identity labels (entry line, course/register labels, status stamps). Numeric data uses tabular figures and right-aligns in register columns.

## Materials

- **Radius**: 2px. Every control, card, popover, and disclosure. No pills.
- **Shadow**: flat offset, never a soft blur — `3px 3px 0 var(--cq-shadow)` on panels, `4px 4px 0` on popovers.
- **Rules**: 1px hairlines in `--cq-line`; 2px rules for emphasis (active nav underline, sheet column edge).
- **No glass, no gradient, no blur, no glow, no scanlines.** Surfaces are opaque.

## Signature: the sheet column

Content sits on a dossier page rather than a card grid: the main column carries a 2px oxide left rule (`.sheet-column`), and the panels inside read as offset-shadowed cards laid on that sheet. The header is a command band — condensed uppercase primary navigation with an active 2px accent underline, a mono XP readout, popover secondary navigation with mono group labels, and a mono account chip. The terminal voice survives in exactly one place, the status footer (`CODEQUEST // SYSTEM 404 LEARNING NETWORK`, `STATUS: ONLINE`).

## Craft floor (refusals)

- No glow, gradient, glass, blur, or scanline atmosphere. Phosphor/amber/alert/cyan glow tokens are `transparent`; the accent carries the emphasis.
- No radius above 2px and no soft drop shadows. The offset shadow is the only depth.
- No dead-world vocabulary in student surfaces: `SHELL.ON`, `SIGNAL RESTORED`, `phosphor`, `CRT`, and `scanline` do not appear outside the status footer.
- No dashed "locked" styling: locked states use muted ink plus a lock icon.
- No Unicode glyphs or emoji standing in for an icon system — drawn SVG only (`x-icon`).
- The colour mode is applied before first paint (inline head script, account preference first, `localStorage` second) so dark never flashes.

## Verification

| Gate | Command |
|---|---|
| Build | `npm run build` |
| Full suite | `php artisan test --compact` |
| Format | `vendor/bin/pint --dirty --format agent` |
| Static | `composer run analyse` |
| Icons | `npm run icons:check` |
| Layout / browser | `CODEQUEST_REQUIRE_BROWSER_TESTS=1 CODEQUEST_CHROMIUM=<chromium> php artisan test --compact tests/Feature/WorkspaceBrowserTest.php` |
