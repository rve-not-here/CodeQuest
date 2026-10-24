# Design

The visual world of CodeQuest. It defines what every screen is made of, what the one signature move is, and the craft floor refuses. This file is the engineering reference for the tokens and materials below; the tokens themselves live in `resources/css/app.css` (Tailwind v4, CSS-first — there is no `tailwind.config.*`).

## Direction

**The Terminal Lab.** CodeQuest is a learning console drawn as a working development bench at night: an ink-black ground under a faint computation grid, ruled panels, condensed uppercase navigation, mono metadata, and one signal green. The Engineer's Log's geometry — the command band, the sheet column, the numbered entry, the stamp vocabulary — carries over. The paper ground and oxide accent of that earlier world are retired as the primary mode and survive only in the daylight theme.

Dark is the product: a restrained terminal bay, not a CRT costume. Light is the same pad under office light. Every token is defined once in `:root` (light) and `.dark` (dark), and every component reads the alias, so a surface never hard-codes a colour. The inline head script applies the mode before first paint; a first-time visitor gets dark.

## Palette

Semantic roles, not decoration:

| Role | Token | Dark (default) | Light (daylight) |
|---|---|---|---|
| Ground | `--cq-ground` | `#0A0E0D` | `#F3F1E8` |
| Sheet / surface | `--cq-sheet` | `#101614` | `#FCFBF6` |
| Raised / input | `--cq-raised` / `--cq-input` | `#17201D` / `#0C1210` | `#E9E6DB` / `#FCFBF6` |
| Ink / muted / subtle | `--cq-ink` / `--cq-muted` / `--cq-subtle` | `#E8F0E9` / `#A6B3AA` / `#8A978F` | `#141A1C` / `#4A5457` / `#586367` |
| Rule | `--cq-line` / `--cq-line-strong` | `#223029` / `#566A5F` | `#D5D1C6` / `#6E7879` |
| Primary / action | `--cq-accent`, `--cq-cta` | `#33FF00` | `#1D6B33` |
| Action ink | `--cq-cta-ink` | `#04140A` | `#FFFFFF` |
| XP / reward | `--cq-warning` | `#FFB000` | `#7A4F00` |
| Failure / destructive | `--cq-danger` | `#FF5C5C` | `#B3342E` |
| Success / pass | `--cq-success` | `#33FF00` | `#1D6B33` |
| Informational | `--cq-info` | `#49D8E8` | `#0E6672` |
| Code surface | `--cq-code-bg` | `#060B09` | `#FBFBF6` |

Meaning is fixed: green owns primary actions, active progress and confirmed success; amber owns XP, rewards and attention; cyan owns secondary technical information; red owns errors and destructive actions. A status is never carried by colour alone — every stamp, badge and state also carries a word and an icon.

Contrast floor: 4.5:1 body, 3.0:1 large text and interactive edges. Measured pairs (dark): ink 16.7:1, muted 8.9:1, subtle 6.4:1, accent 14.3:1, action ink on green 13.9:1, amber 10.6:1, red 6.4:1, cyan 11.4:1, strong rule 3.1:1. Light pairs: ink 15.5:1, muted 6.9:1, subtle 5.5:1, accent 5.8:1, white on green 6.6:1, amber 6.3:1, red 5.4:1, cyan 5.9:1.

## Type

Self-hosted through the Vite plugin's `bunny()` helper (`vite.config.js`), never a hosted stylesheet:

- **UI / body**: Barlow 400–700 (`--font-sans`, `--font-body`).
- **Display**: Barlow Condensed 500–700 (`--font-display`) — brand, primary navigation, panel headings. Uppercase with `0.06em`–`0.12em` tracking.
- **Code / data / metadata**: Fragment Mono 400 (`--font-mono`, `--font-code`) — code blocks, XP readouts, group labels, IDs, avatar initials, note text.

Scale: 11 / 12 / 14 / 16 / 18 / 20 / 24 / 30 px. Hierarchy is carried by the ramp, the condensed display face, and weight. Mono is for data and technical identifiers, not for chrome. Data and metadata labels are sentence case at 11.5–13px; tracked uppercase is reserved for identity labels (entry line, course/register labels, status stamps). Numeric data uses tabular figures and right-aligns in register columns.

## Materials

- **Radius**: 2px. Every control, card, popover, and disclosure. No pills.
- **Shadow**: flat offset, never a soft blur — `3px 3px 0 var(--cq-shadow)` on panels, `4px 4px 0` on popovers. On the dark ground the shadow is black, so panels also carry a 1px edge.
- **Rules**: 1px hairlines in `--cq-line`; 2px rules for emphasis (active nav underline, sheet column edge).
- **Grid**: an 8px minor / 40px major computation grid on the body and command band at 2.6–4.8% ink in dark and 2.8–5% in light. It is the lab bench surface, never an overlay on text.
- **No glass, no gradient, no blur, no glow, no scanlines, no pixel art.** Surfaces are opaque.

## Signature: the sheet column

Content sits on a dossier sheet rather than a card grid: the main column carries a 2px phosphor left rule (`.sheet-column`), and the panels inside read as offset-shadowed cards laid on that sheet. The header is a command band — condensed uppercase primary navigation with an active 2px accent underline, an amber mono XP readout, popover secondary navigation with mono group labels, a two-option Light/Dark setter, and a mono account chip. The terminal voice survives in exactly one place, the status footer (`CODEQUEST // SYSTEM 404 LEARNING NETWORK`, `STATUS: ONLINE`).

## Craft floor (refusals)

- No glow, gradient, glass, blur, or scanline atmosphere. The accent carries the emphasis.
- No radius above 2px and no soft drop shadows. The offset shadow is the only depth.
- No Unicode glyphs, emoji, or pixel fonts standing in for an icon system — drawn SVG only (`x-icon`).
- No second filled primary button beside an existing primary action.
- No colour-only state: a stamp always pairs its hue with a label and an icon.
- No hard-coded palette hex in Blade; use the semantic tokens or the shared component classes.
- No ambiguous icon toggle for the colour mode: the control is two named options, and the option in force is the pressed one.
- The colour mode is applied before first paint (inline head script, newest of account and device) so dark never flashes; dark is the default for a new visitor.

## Verification

| Gate | Command |
|---|---|
| Build | `npm run build` |
| Full suite | `php artisan test --compact` |
| Format | `vendor/bin/pint --dirty --format agent` |
| Static | `composer run analyse` |
| Icons | `npm run icons:check` |
| Layout / browser | `CODEQUEST_REQUIRE_BROWSER_TESTS=1 CODEQUEST_CHROMIUM=<chromium> php artisan test --compact tests/Feature/ThemeBrowserTest.php tests/Feature/WorkspaceBrowserTest.php` |
