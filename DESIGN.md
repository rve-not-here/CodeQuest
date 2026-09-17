---
name: CodeQuest System 404
description: A disciplined academic emergency terminal built from near-black surfaces and semantic phosphor signals.
colors:
  void: "#030604"
  surface: "#060c08"
  surface-alt: "#0a140d"
  panel: "#0b1610"
  input: "#08110b"
  header: "#040806"
  ink: "#e1ece4"
  static: "#91a198"
  phosphor: "#33ff00"
  phosphor-bright: "#75ff55"
  phosphor-dim: "#559260"
  amber: "#ffb000"
  alert: "#ff5c5c"
  cyan: "#49d8e8"
typography:
  display:
    fontFamily: "ui-monospace, 'Cascadia Code', 'SFMono-Regular', Consolas, 'Liberation Mono', monospace"
    fontSize: "clamp(1.75rem, 4vw, 3.15rem)"
    fontWeight: 700
    lineHeight: 1.18
    letterSpacing: "-0.045em"
  headline:
    fontFamily: "ui-monospace, 'Cascadia Code', 'SFMono-Regular', Consolas, 'Liberation Mono', monospace"
    fontSize: "1.5rem"
    fontWeight: 700
    lineHeight: 1.18
    letterSpacing: "-0.025em"
  title:
    fontFamily: "ui-monospace, 'Cascadia Code', 'SFMono-Regular', Consolas, 'Liberation Mono', monospace"
    fontSize: "1rem"
    fontWeight: 700
    lineHeight: 1.18
    letterSpacing: "0.02em"
  body:
    fontFamily: "ui-monospace, 'Cascadia Code', 'SFMono-Regular', Consolas, 'Liberation Mono', monospace"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.55
    letterSpacing: "normal"
  label:
    fontFamily: "ui-monospace, 'Cascadia Code', 'SFMono-Regular', Consolas, 'Liberation Mono', monospace"
    fontSize: "0.75rem"
    fontWeight: 700
    lineHeight: 1.1
    letterSpacing: "0.08em"
rounded:
  none: "0"
  sharp: "2px"
  full: "9999px"
spacing:
  xs: "0.5rem"
  sm: "0.75rem"
  md: "1rem"
  lg: "1.25rem"
  xl: "1.5rem"
  2xl: "2rem"
components:
  button-primary:
    backgroundColor: "{colors.phosphor}"
    textColor: "{colors.void}"
    typography: "{typography.label}"
    rounded: "{rounded.sharp}"
    padding: "0.65rem 1rem"
  button-primary-hover:
    backgroundColor: "{colors.phosphor-bright}"
    textColor: "{colors.void}"
    typography: "{typography.label}"
    rounded: "{rounded.sharp}"
    padding: "0.65rem 1rem"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.phosphor}"
    typography: "{typography.label}"
    rounded: "{rounded.sharp}"
    padding: "0.65rem 1rem"
  input-terminal:
    backgroundColor: "{colors.input}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.sharp}"
    padding: "0.65rem 0.75rem"
  panel:
    backgroundColor: "{colors.panel}"
    textColor: "{colors.ink}"
    rounded: "{rounded.sharp}"
    padding: "1rem"
  badge-phosphor:
    backgroundColor: "rgba(51, 255, 0, 0.1)"
    textColor: "{colors.phosphor}"
    typography: "{typography.label}"
    rounded: "{rounded.none}"
    padding: "0.25rem 0.5rem"
---

# Design System: CodeQuest System 404

## Overview

**Creative North Star: "The Academic Emergency Terminal"**

CodeQuest looks like a serious learning terminal running inside the System 404 fiction. Near-black opaque layers carry the interface. Phosphor green identifies primary action, successful progress, and active system identity. The atmosphere comes from faint scanlines, narrow glows, precise borders, and compact system labels, never from visual noise over reading or coding areas.

The system is operational first. It uses one monospace family for interface, prose, and code, then creates hierarchy through scale, weight, spacing, and restrained semantic color. The dashboard puts the next command first. Progression appears as an explicit signal rail. Lessons narrow and quiet the frame. Challenges give the editor the largest share of a three-pane workspace.

**Key Characteristics:**

- Opaque near-black layers with dark green undertones
- True phosphor green as the primary system signal
- Amber, red, and cyan reserved for distinct state meanings
- Sharp two-pixel forms and one-pixel structural borders
- System monospace throughout, with readable prose leading and clear weight changes
- Restrained CRT texture outside the reading and coding planes

## Colors

The palette is almost black until state or action calls for a signal. Color carries meaning, not decoration.

### Primary

- **True Phosphor:** Primary actions, successful or completed states, active navigation, progress fill, selection, and the System 404 identity.
- **Bright Phosphor:** Hover response for primary and ghost actions. It is a state color, not a second accent.
- **Dim Phosphor:** Rails, secondary terminal labels, quiet metadata, and inactive green structure.

### Secondary

- **Current-Signal Amber:** Current work, warnings, new transmissions, medium difficulty, and Boss Challenge emphasis.
- **Alert Red:** Errors, failed or destructive states, and hard difficulty.
- **Relay Cyan:** Information, available progression nodes, links, and keyboard focus.

### Neutral

- **Void Black:** The deepest canvas, scrollbar track, progress track, and inset terminal regions.
- **Terminal Surface:** The page ground beneath panels.
- **Raised Terminal Surface:** Secondary cells, support bands, and quiet grouped regions.
- **Panel Green-Black:** Standard containers and workspace panes.
- **Input Green-Black:** Editable field backgrounds.
- **Header Black:** Navigation and pane headers.
- **Primary Ink:** Main text and headings.
- **Static Gray-Green:** Supporting text, inactive navigation, timestamps, and secondary values.

### Named Rules

**The Semantic Signal Rule.** Green means primary or complete, amber means current or caution, red means error, and cyan means informational or available. Pair every state color with text, a symbol, or both.

**The Opaque Terminal Rule.** Panels remain dark and opaque. Do not blur the content behind them or simulate glass.

## Typography

**Display Font:** System monospace, with Cascadia Code, SFMono-Regular, Consolas, and Liberation Mono fallbacks  
**Body Font:** The same system monospace stack  
**Label/Mono Font:** The same system monospace stack

**Character:** One technical voice runs through navigation, teaching copy, state labels, and code. Hierarchy comes from measured changes in scale, weight, tracking, and line height instead of a decorative display face.

### Hierarchy

- **Display:** Bold and tightly tracked. Reserve it for the current command or an equally dominant operational heading.
- **Headline:** Bold and slightly condensed through negative tracking. Use it for page titles, with the desktop size stepping up at the medium breakpoint.
- **Title:** Bold. Use it for course names, node titles, pane titles, and compact section headings.
- **Body:** Regular weight with generous leading. Lesson prose opens further for sustained reading, while ordinary interface copy keeps the base rhythm.
- **Label:** Bold, uppercase, and letter-spaced. Use it for concise status, metadata, navigation, and control text, not paragraph copy.

### Named Rules

**The One Terminal Voice Rule.** Keep the system monospace stack everywhere. Build hierarchy with size, weight, case, and spacing.

**The Reading Plane Rule.** Long-form lesson copy uses relaxed leading and a narrow measure. Compact terminal labels stay short and functional.

## Layout

The application shell caps its top bar at 1536px. Standard page content sits in a centered 1180px container with fluid padding that grows from 1rem to 1.75rem. Student navigation becomes a sticky 15.5rem desktop rail at 1024px and above. Below that width it becomes an 88vw drawer capped at 20rem. The drawer traps focus, makes background regions inert, closes on Escape, and returns focus to its opener.

The spacing rhythm uses half-rem steps for tight relationships and 1rem to 2rem gaps for grouped regions. Dense tables become labeled stacked records below 768px. Page headers, action groups, status grids, and learning cards collapse to a single column when horizontal space no longer supports scanning.

The command-first dashboard gives one dominant operation band the first large block after the page header. Supporting XP, competency, achievement, activity, and transmission regions follow at lower contrast and smaller scale. This keeps the next action obvious without turning the page into an analytics wall.

Progression uses a centered vertical rail capped at 50rem. Every node carries a marker, written state, title, support text, and optional badge. Completed, current, available, and locked states remain distinguishable without color. The Boss Challenge is a separate terminal milestone at the end of the rail.

Lessons use a centered 54rem reading shell, relaxed prose, quiet panels, and a clear handoff to practice. Challenges remove the standard content cap and use three panes for brief, editor, and preview. The editor receives the largest share of the desktop grid. At 1100px and below, the panes stack with explicit borders and useful minimum heights.

## Elevation & Depth

Depth comes from tonal layering, one-pixel borders, and restrained dark shadows. Standard panels use a faint inset highlight and a low black shadow. Navigation and the mobile drawer use stronger structural shadows because they sit above the document. Green and amber glows are limited to active controls, the current progression marker, and a few system signals.

The page background carries a four-pixel scanline rhythm and one soft phosphor radial wash. Reading panels, editor panes, and preview panes stay visually still and opaque.

### Shadow Vocabulary

- **Panel Depth:** A faint top inset plus a soft 12px by 32px black shadow for ordinary containers.
- **Navigation Depth:** A downward black shadow with a trace of phosphor for the sticky top bar.
- **Drawer Depth:** A wider side shadow that separates the open drawer from its backdrop.
- **Current Signal:** A small amber ring and glow that pulses only on the current progression marker.

### Named Rules

**The Restrained CRT Rule.** Scanlines and glow belong to the outer atmosphere and active signals. They do not sit over lesson copy, code, or preview output.

**The Border Before Shadow Rule.** Use a crisp semantic border to define a surface. Add shadow only when the surface needs structural separation or active-state emphasis.

## Shapes

The form language is square and engineered. Buttons, inputs, panels, and focus treatments use a two-pixel radius. Workspace panes remove rounding so their shared borders read as one instrument. One-pixel strokes define most boundaries; two-pixel strokes mark active navigation or an emphasized edge.

Circles are limited to status dots and signal indicators. Progress markers, brand marks, avatars, badges, and Boss Challenge sigils stay square.

**The Two-Pixel Rule.** Interactive controls and standalone containers use the shared two-pixel corner. Do not introduce soft cards or pill-shaped controls.

## Components

### Buttons

Buttons are compact terminal commands with a minimum 44px target.

- **Shape:** Sharp corners, one-pixel phosphor border, and a minimum height of 2.75rem.
- **Primary:** Solid phosphor with void-black text, bold uppercase labeling, and a faint green glow.
- **Hover / Focus:** Hover brightens the phosphor and strengthens the glow over 120ms. Keyboard focus uses a three-pixel cyan outline with a three-pixel offset.
- **Ghost:** Transparent with phosphor text. Hover adds a low-opacity green fill.
- **Disabled:** Preserve the form but reduce opacity and use a not-allowed cursor.

### Chips

Badges are square status stamps, not pills.

- **Style:** One-pixel semantic border, ten-percent tonal fill, bold uppercase text, tight tracking, and compact padding.
- **State:** Phosphor, amber, red, cyan, and neutral variants use the same geometry. The label supplies the meaning that color reinforces.

### Cards / Containers

Panels are quiet terminal modules that support hierarchy rather than compete with it.

- **Corner Style:** Shared sharp corner.
- **Background:** Opaque panel green-black over the darker page surface.
- **Shadow Strategy:** Low panel depth at rest. Stronger glow belongs to active or current states.
- **Border:** One-pixel translucent phosphor.
- **Internal Padding:** 1rem by default, increasing to 1.25rem on small screens and above where the shared panel component permits it.

### Inputs / Fields

- **Style:** Full-width input green-black field, primary ink text, one-pixel low-phosphor border, sharp corner, and compact padding.
- **Focus:** Phosphor border and a three-pixel low-opacity phosphor ring. The global keyboard focus outline remains cyan.
- **Error / Disabled:** Use written error messaging with alert red. Disabled controls retain shape and reduce opacity.

### Navigation

Navigation is quiet until active. Top links use uppercase labels and a two-pixel phosphor underline for the current page. Sidebar links use numbered indices, a two-pixel left edge, and a low-opacity green field when active. Desktop students receive a sticky rail. Mobile and tablet users receive a modal drawer with a dark backdrop, trapped focus, Escape dismissal, and focus restoration.

### Status messages

Status messages use a bordered raised surface, a semantic dot, a title, and supporting copy. Success is phosphor, information is cyan, warning is amber, and error is red. The title and message remain readable in neutral ink, so state meaning never depends on the dot alone.

### Progress rail

The progression rail is the system's signature component. A vertical signal line connects square markers to full-width node cards. Every node includes a written state. Completed nodes use phosphor and a check, current nodes use amber and a dot, available nodes use cyan and an open circle, and locked nodes use neutral dashed borders and an x. Reduced-motion settings stop the current-state pulse.

### Progress bars

Progress bars use a void-black track, a low-phosphor border, a 0.625rem height, and a semantic fill. A visible label and percentage accompany the bar whenever context is not already explicit.

## Do's and Don'ts

### Do:

- **Do** keep the next action visually dominant and place supporting metrics after it.
- **Do** use phosphor for primary action, completion, progress, and active navigation.
- **Do** reserve amber for current work and warnings, red for errors, and cyan for information, availability, and focus.
- **Do** pair state color with plain text, a marker shape, or both.
- **Do** keep lesson reading surfaces calm and challenge workspaces editor-first.
- **Do** preserve the cyan focus outline, keyboard-safe drawer behavior, and reduced-motion override.
- **Do** use subtle scanlines and glow only where they do not compete with reading or coding.

### Don't:

- **Don't** turn CodeQuest into a generic SaaS dashboard or a freeCodeCamp clone.
- **Don't** use glassmorphism, translucent blur panels, or a purple-and-blue AI dashboard palette.
- **Don't** use pixel-art interface chrome or decorative pixel fonts.
- **Don't** add soft card rounding, pill controls, excessive gradients, or ornamental shadows.
- **Don't** animate large regions or add perpetual motion. Keep the current-node pulse restrained and honor reduced motion.
- **Don't** let CRT effects, glow, or texture enter the lesson reading plane, code editor, or preview output.
