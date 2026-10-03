# CodeQuest visual prototype v3

The deliverable is the [working design lab](../../design-lab/codequest-redesign/index.html), not this note. Production application code and academic rules are unchanged.

From the repository root, launch with:

```bash
bash design-lab/codequest-redesign/launch.sh
```

Open [the selected Dashboard](http://127.0.0.1:4175/?concept=final&screen=dashboard), [Challenge](http://127.0.0.1:4175/?concept=final&screen=challenge), or [visual comparison gallery](http://127.0.0.1:4175/review.html). The bottom lab controls switch concepts, screens, and example feedback. No additional dependencies are required beyond the existing installation.

## Built and compared

| Concept | What worked | Why it was rejected or revised |
| --- | --- | --- |
| A: Learning workbench | Compact queue, technical alignment, simultaneous coding panes | Queue-first Dashboard felt like task management. Three panes squeezed the editor. Too much IDE vocabulary before learning. |
| B: Guided studio | Quiet reading, visible educational sequence | Dashboard was sparse. Task strip scrolled unnecessarily. Equal code/preview treatment wasted space. |
| C: Technical academy | Connected the next concept to a visible portfolio artifact | First continuation block was too tall. Example pushed requirements down. Phone artifact prolonged the page. |
| Selected: Build & learn | Open continuation, compact course journey, dominant editor | Second iteration removed the enclosing tinted block, reduced the artifact, moved task examples into disclosure, and increased editing space. Later passes compressed completed rows and repaired evidence bars. |

[Concept captures](../../design-lab/codequest-redesign/screenshots/concepts/) retain all three Dashboards and Challenges at four widths. [Second iteration](../../design-lab/codequest-redesign/screenshots/iteration-2/) preserves the intermediate selection.

## Actual current UI inspected

Real Laravel controllers and Blade pages ran through HTTP against a guarded disposable SQLite fixture, with fixture students authenticated only in the temporary local runtime. Production data and credentials were untouched. Dashboard, Learning Path, Challenges directory, Lesson, Knowledge Check, Challenge, Boss, Progress and Competency were captured at all four widths. These are representative fixture renderings, not an authenticated production-data audit.

[Current screenshots](../../design-lab/codequest-redesign/screenshots/current/) show repeated dashboard progress, recommendation boxes ahead of the course sequence, card-framed reading, and Boss briefing/metadata ahead of the editor. Mobile primary destinations were hidden. Some raw Markdown appears because fixture descriptions deliberately contained Markdown; this does not prove a production content-format defect.

## Major changes actually built

- Top navigation stays visible on phones. No permanent desktop sidebar or automatic breadcrumb chain.
- Dashboard has one continuation, an artifact showing the learning goal, a flat milestone sequence, and subordinate activity/evidence.
- Learning Path uses connected work rows. Completed rows are compact; current, available, locked and assessment states have distinct text and shapes.
- Learning-unit overview connects Lesson, optional Knowledge Check and Challenge without creating a new progression rule.
- Lesson pairs readable prose with code and its rendered meaning. XP is secondary.
- Knowledge Check uses native radios and local explanations. Phone submission sits in a compact bottom bar.
- Challenge/Boss now share a freeCodeCamp-informed three-pane workspace: instructions, full-height source, preview/feedback. Instructions default to 340px (280px on narrow laptops); preview uses 23vw, bounded at 260–360px; the editor takes the remaining width. A keyboard-accessible range adjusts instructions; collapse and focused-editor modes recover space. Below 960px, mounted Instructions/Code/Output tabs replace the desktop panes.
- Preview is explicit and ungraded; edits mark it stale. Feedback can return focus to the relevant anchor. Draft acknowledgment explicitly says in-memory demo.
- Boss removes practice assistance/save, confirms final submission, displays recorded code read-only, and retries blank. Unknown responses use Check status rather than automatic resubmission.
- Evidence separates practice completion, submitted responses and assessment pass. No invented mastery score, radar chart, or XP-as-competency label.

## Design system and reasoning

UI UX Pro Max was read and queried for all three directions, typography, analytics, responsive layout, Laravel, focus and recovery. [Search output](../../design-lab/codequest-redesign/skill-searches.txt) is retained. Its landing-page structures were rejected; its Inertia-specific guidance does not fit Blade. Its slate tool surfaces, readable sans/mono pairing, functional color and accessibility rules informed the built system. The existing comparative research informed mounted mobile panes, source-preserving recovery and assessment-history separation.

Canvas `#fafafd`, white operational surfaces, ink `#242538`, supporting `#62657b`, violet action/focus `#6044c5`, success `#216a4c`, warning `#825311`, failure `#b12f42`, locked `#707387`. Learning pages retain the dark code examples. Challenge/Boss use a white code canvas, navy navigation `#171c31`, gray pane chrome `#eeeef2`, blue action/focus `#174b87`, and 2px button corners. Light-editor syntax distinguishes comments, tags, attributes and strings with measured contrast. Sans titles/body, mono code/evidence; titles 26–32px, body 16px. Spacing follows 4/8/12/16/24/32/40/48. Controls use 6px radius; ordinary sections use alignment and separators. Shadows belong only to overlays or the illustrative artifact. No scanlines, fake terminal labels, neon, or nested dashboard cards.

## Visual and interaction verification

[Final screens](../../design-lab/codequest-redesign/screenshots/final/) cover 1440×900, 1024×768, 768px and 375px. [State captures and interaction results](../../design-lab/codequest-redesign/screenshots/states/) cover partial/success/error feedback and Boss eligibility/results. Narrow workspaces use Instructions/Code/Output modes with mounted editor state and reachable actions.

Executed: 21 interaction checks passed; no recorded script errors; no page-width overflow in the final 32 screen configurations; measured main button targets meet 44px. [17 opaque color pairs](../../design-lab/codequest-redesign/screenshots/contrast.json) meet text/control thresholds, including syntax on the active line. Screenshots were visually inspected during multiple iterations. Screen-reader, software-keyboard, full zoom and exhaustive composited-state checks remain open.

The isolated Vite build passed, with a large editor-bundle warning. Production implementation should lazy-load the editor, reuse existing Blade/workspace components, and retain server-provided source, capabilities, validation and outcomes. This lab uses fixed examples and in-memory interaction; reloading clears work and does not persist progress. Boss entry is an eligible 6/6 fixture, separate from the Dashboard's 3/6 snapshot. Rewrite the validated UI against existing services; do not ship the throwaway result fixtures or promote the prototype automatically.

## Workspace revision following visual feedback

The bottom output dock was rejected. The replacement adapts freeCodeCamp’s [desktop pane architecture](https://github.com/freeCodeCamp/freeCodeCamp/blob/main/client/src/templates/Challenges/classic/desktop-layout.tsx) and [mobile pane switching](https://github.com/freeCodeCamp/freeCodeCamp/blob/main/client/src/templates/Challenges/classic/mobile-layout.tsx), retaining CodeQuest’s explicit Run/Submit distinction and existing assessment constraints. UI UX Pro Max was queried again for developer/education design systems and keyboard/focus guidance. Its marketing layout recommendation was rejected as unsuitable for a coding task.

[Revised workspace captures](../../design-lab/codequest-redesign/screenshots/freecodecamp-workspace/) supersede the earlier Challenge/Boss captures. Both workspaces were rendered at 1440, 1024, 768 and 375px: eight configurations, no page-width overflow, no measured main button target below 44px, and no script exceptions. The 21 interaction checks were rerun successfully; [recovery and assessment captures](../../design-lab/codequest-redesign/screenshots/freecodecamp-workspace/states/) show the new layout. Build passed with the existing editor-bundle size warning. Production application code and business rules were not changed.


### Selected workspace refinement — Classic Learning Lab

User selected “go with classic learning lab”. Open `design-lab/codequest-redesign/design-demos/classic-learning-lab-v2.html`, or launch the existing lab and visit `/design-demos/`. The original concepts remain unchanged.

The refined workspace keeps the cream reading rail, charcoal editor and navy actions. At 1440px, its 300px instructions and 310px output leave 830px for editing. Both side panes can collapse; Run and Submit reopen output. At 1100px and below, Instructions / Code / Output tabs preserve source and remove hidden panes from keyboard navigation. Mobile controls stay visible without stacking three large panels. Boss uses the same layout with blank initial source, no practice hints or Save, the existing threshold, and final-submission confirmation. Prototype operations record no academic state.

UI UX Pro Max guidance applied: keyboard navigation, visible focus, 44px action targets, local feedback and no horizontal page scrolling. Huashu direction selection is recorded in `design-demos/direction-approved.md`. Screenshots and browser inspection results are under `design-demos/screenshots/classic-learning-lab-v2-*` and `classic-v2-*.json`. Production application code is unchanged.
